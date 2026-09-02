<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    public const MIN_PASSWORD_LENGTH = 12;

    /** A shared address gets this multiple of the per-email attempt budget. */
    private const IP_ATTEMPT_MULTIPLIER = 6;

    private ?array $cachedUser = null;

    public function __construct(
        private PDO $pdo,
        private SessionStore $sessions,
        private RateLimiter $limiter
    ) {
    }

    /** @return int New user id. */
    public function register(string $email, string $password, string $displayName): int
    {
        $email = self::normaliseEmail($email);
        $displayName = trim($displayName);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('Enter a valid email address.');
        }
        if ($displayName === '' || mb_strlen($displayName) > 120) {
            throw new ValidationException('Enter a name of up to 120 characters.');
        }
        self::assertPasswordIsAcceptable($password, $email, $displayName);

        $existing = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $existing->execute([$email]);
        if ($existing->fetch() !== false) {
            throw new ValidationException('That email address is already registered.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $statement = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, display_name, role, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$email, self::hash($password), $displayName, 'user', 'active', $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Verifies credentials and starts a session.
     *
     * Failures are deliberately indistinguishable: an unknown email, a wrong
     * password and a disabled account all raise the same message, so the form
     * cannot be used to discover who holds an account.
     */
    public function login(string $email, string $password): array
    {
        $email = self::normaliseEmail($email);
        $ipKey = 'ip:' . Http::clientIp();
        $emailKey = 'email:' . $email;

        // The address is throttled at the configured threshold. A single IP is
        // allowed a larger budget, because an office, a school or any mobile
        // network puts many legitimate people behind one address.
        $checks = [
            $emailKey => $this->limiter->maxAttempts(),
            $ipKey => $this->limiter->maxAttempts() * self::IP_ATTEMPT_MULTIPLIER,
        ];
        foreach ($checks as $key => $max) {
            if ($this->limiter->isLocked($key, $max)) {
                $minutes = (int) ceil($this->limiter->secondsUntilRetry($key) / 60);
                throw new ValidationException(
                    'Too many sign-in attempts. Try again in ' . max(1, $minutes) . ' minute(s).'
                );
            }
        }

        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash, display_name, role, status FROM users WHERE email = ?'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();

        // Hash even when the address is unknown, so response time does not
        // reveal whether the account exists.
        $storedHash = is_array($user) ? (string) $user['password_hash'] : self::dummyHash();
        $passwordMatches = password_verify($password, $storedHash);

        if (!is_array($user) || !$passwordMatches || $user['status'] !== 'active') {
            $this->limiter->record($emailKey, false);
            $this->limiter->record($ipKey, false);
            throw new ValidationException('Those details did not match an active account.');
        }

        if (password_needs_rehash($storedHash, self::algorithm(), self::options())) {
            $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?')
                ->execute([self::hash($password), gmdate('Y-m-d H:i:s'), $user['id']]);
        }

        $this->limiter->clear($emailKey);
        $this->limiter->clear($ipKey);

        // A fresh token per sign-in, so a previously issued one is never reused.
        $this->sessions->start((int) $user['id']);
        $this->pdo->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d H:i:s'), $user['id']]);

        unset($user['password_hash']);
        return $this->cachedUser = $user;
    }

    /**
     * Signs in the account that was just created.
     *
     * This deliberately skips the sign-in throttle. Registration already
     * proved the credentials, so counting it as an attempt would let one
     * person's failed sign-ins block a new account on the same network.
     *
     * @return array The new user, without the password hash.
     */
    public function startSessionFor(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, display_name, role, status FROM users WHERE id = ?'
        );
        $statement->execute([$userId]);
        $user = $statement->fetch();
        if ($user === false || $user['status'] !== 'active') {
            throw new ValidationException('That account is not available.');
        }
        $this->sessions->start($userId);
        $this->pdo->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d H:i:s'), $userId]);
        return $this->cachedUser = $user;
    }

    public function logout(): void
    {
        $this->sessions->destroy();
        $this->cachedUser = null;
    }

    /** @return array|null The signed-in user, without the password hash. */
    public function user(): ?array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }
        $userId = $this->sessions->userId();
        if ($userId === null) {
            return null;
        }
        $statement = $this->pdo->prepare(
            'SELECT id, email, display_name, role, status, last_login_at, created_at FROM users WHERE id = ?'
        );
        $statement->execute([$userId]);
        $user = $statement->fetch();
        if ($user === false || $user['status'] !== 'active') {
            return null;
        }
        return $this->cachedUser = $user;
    }

    public function changePassword(int $userId, string $current, string $new): void
    {
        $statement = $this->pdo->prepare('SELECT email, display_name, password_hash FROM users WHERE id = ?');
        $statement->execute([$userId]);
        $user = $statement->fetch();
        if ($user === false || !password_verify($current, (string) $user['password_hash'])) {
            throw new ValidationException('Your current password is not correct.');
        }
        self::assertPasswordIsAcceptable($new, (string) $user['email'], (string) $user['display_name']);
        $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?')
            ->execute([self::hash($new), gmdate('Y-m-d H:i:s'), $userId]);
        // Revoke everything signed in with the old password.
        $this->sessions->destroyAllFor($userId);
        $this->cachedUser = null;
    }

    private static function assertPasswordIsAcceptable(string $password, string $email, string $displayName): void
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ValidationException(
                'Use a password of at least ' . self::MIN_PASSWORD_LENGTH . ' characters.'
            );
        }
        if (mb_strlen($password) > 4096) {
            throw new ValidationException('That password is too long.');
        }
        // Only compare against fragments long enough to be meaningful. A
        // one-letter local part or a two-letter name would otherwise match
        // most ordinary passphrases.
        $lower = mb_strtolower($password);
        $localPart = mb_strtolower(explode('@', $email)[0]);
        if (mb_strlen($localPart) >= 4 && str_contains($lower, $localPart)) {
            throw new ValidationException('Your password must not contain your email address.');
        }
        $name = mb_strtolower(trim($displayName));
        if (mb_strlen($name) >= 4 && str_contains($lower, $name)) {
            throw new ValidationException('Your password must not contain your name.');
        }
    }

    private static function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private static function algorithm(): string
    {
        // Argon2id where the host provides it, bcrypt otherwise. Both satisfy
        // the guidance in the README, and login upgrades stored hashes to
        // whichever one is active.
        return defined('PASSWORD_ARGON2ID') && in_array('argon2id', password_algos(), true)
            ? PASSWORD_ARGON2ID
            : PASSWORD_BCRYPT;
    }

    /** @return array<string,int> */
    private static function options(): array
    {
        return self::algorithm() === PASSWORD_BCRYPT
            ? ['cost' => 12]
            : ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
    }

    private static function hash(string $password): string
    {
        return password_hash($password, self::algorithm(), self::options());
    }

    private static function dummyHash(): string
    {
        static $hash = null;
        return $hash ??= self::hash('unmatchable-placeholder-' . bin2hex(random_bytes(8)));
    }
}
