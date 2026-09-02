<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Server-side sessions kept in user_sessions.
 *
 * The cookie carries a random token; the table stores only its SHA-256 hash,
 * so a leaked database still cannot be replayed as a valid session.
 */
final class SessionStore
{
    public const COOKIE = 'sid';

    public function __construct(private PDO $pdo, private int $lifetimeSeconds)
    {
    }

    /** Issues a new session and sets the cookie. Returns the raw token. */
    public function start(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = gmdate('Y-m-d H:i:s', time() + $this->lifetimeSeconds);
        $statement = $this->pdo->prepare(
            'INSERT INTO user_sessions (id, user_id, expires_at, created_at) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$this->fingerprint($token), $userId, $expires, gmdate('Y-m-d H:i:s')]);
        $this->writeCookie($token, time() + $this->lifetimeSeconds);
        return $token;
    }

    /** Returns the signed-in user id, or null when there is no valid session. */
    public function userId(?string $token = null): ?int
    {
        $token ??= $_COOKIE[self::COOKIE] ?? null;
        if (!is_string($token) || $token === '') {
            return null;
        }
        $statement = $this->pdo->prepare(
            'SELECT user_id, expires_at FROM user_sessions WHERE id = ?'
        );
        $statement->execute([$this->fingerprint($token)]);
        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }
        if (strtotime((string) $row['expires_at']) <= time()) {
            $this->deleteByToken($token);
            return null;
        }
        return (int) $row['user_id'];
    }

    public function destroy(?string $token = null): void
    {
        $token ??= $_COOKIE[self::COOKIE] ?? null;
        if (is_string($token) && $token !== '') {
            $this->deleteByToken($token);
        }
        $this->writeCookie('', time() - 3600);
    }

    /** Drops every session for a user, e.g. after a password change. */
    public function destroyAllFor(int $userId): void
    {
        $this->pdo->prepare('DELETE FROM user_sessions WHERE user_id = ?')->execute([$userId]);
    }

    public function purgeExpired(): int
    {
        $statement = $this->pdo->prepare('DELETE FROM user_sessions WHERE expires_at <= ?');
        $statement->execute([gmdate('Y-m-d H:i:s')]);
        return $statement->rowCount();
    }

    private function deleteByToken(string $token): void
    {
        $this->pdo->prepare('DELETE FROM user_sessions WHERE id = ?')->execute([$this->fingerprint($token)]);
    }

    private function fingerprint(string $token): string
    {
        return hash('sha256', $token);
    }

    private function writeCookie(string $value, int $expires): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => Http::isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
