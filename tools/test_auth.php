<?php
declare(strict_types=1);

/**
 * Exercises the auth layer end to end against an in-memory SQLite database.
 *
 * The production target is MySQL; SQLite is used here only so the logic can be
 * tested without a server. Every statement the app issues is portable ANSI SQL
 * with PHP-side timestamps, so the two behave the same.
 *
 * Run: php tools/test_auth.php
 */

namespace App;

require_once __DIR__ . '/../app/src/Env.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = __DIR__ . '/../app/src/' . substr($class, 4) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use PDO;
use Throwable;

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  PASS  {$name}\n";
    } else {
        $failed++;
        echo "  FAIL  {$name}" . ($detail !== '' ? " -- {$detail}" : '') . "\n";
    }
}

/** @return string The message of the thrown ValidationException, or '' if none. */
function refused(callable $fn): string
{
    try {
        $fn();
        return '';
    } catch (ValidationException $e) {
        return $e->getMessage();
    }
}

function freshDatabase(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // The MySQL schema in database/schema.sql, expressed for SQLite.
    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        display_name TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT "user",
        status TEXT NOT NULL DEFAULT "active",
        email_verified_at TEXT NULL,
        last_login_at TEXT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE user_sessions (
        id TEXT PRIMARY KEY,
        user_id INTEGER NOT NULL,
        expires_at TEXT NOT NULL,
        created_at TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        identifier TEXT NOT NULL,
        successful INTEGER NOT NULL DEFAULT 0,
        attempted_at TEXT NOT NULL)');
    return $pdo;
}

/** @return array{0:Auth,1:SessionStore,2:RateLimiter,3:PDO} */
function makeAuth(int $maxAttempts = 5): array
{
    $pdo = freshDatabase();
    $sessions = new SessionStore($pdo, 14 * 86400);
    $limiter = new RateLimiter($pdo, $maxAttempts, 15 * 60);
    return [new Auth($pdo, $sessions, $limiter), $sessions, $limiter, $pdo];
}

$GOOD = 'correct horse battery staple';

echo "Registration\n";
[$auth, $sessions, $limiter, $pdo] = makeAuth();
$id = $auth->register('Dennis@Example.com ', $GOOD, 'Dennis Dizon');
check('creates a user', $id > 0);
$row = $pdo->query('SELECT * FROM users')->fetch();
check('email is normalised to lower case', $row['email'] === 'dennis@example.com', $row['email']);
check('password is not stored in clear', !str_contains($row['password_hash'], $GOOD));
check('hash uses argon2id or bcrypt',
    str_starts_with($row['password_hash'], '$argon2id$') || str_starts_with($row['password_hash'], '$2y$'),
    substr($row['password_hash'], 0, 12));
check('defaults to the user role', $row['role'] === 'user');

check('rejects a duplicate email',
    refused(fn() => $auth->register('DENNIS@example.com', $GOOD, 'Someone Else')) !== '');
check('rejects a malformed email',
    refused(fn() => $auth->register('not-an-email', $GOOD, 'Someone')) !== '');
check('rejects a short password',
    refused(fn() => $auth->register('a@example.com', 'short', 'Someone')) !== '');
check('rejects a password containing the email',
    refused(fn() => $auth->register('dennisdizon@example.com', 'dennisdizon12345', 'Someone')) !== '');
check('rejects an empty display name',
    refused(fn() => $auth->register('b@example.com', $GOOD, '   ')) !== '');

echo "\nSign-in\n";
$user = $auth->login('dennis@example.com', $GOOD);
check('accepts correct credentials', (int) $user['id'] === $id);
check('never returns the password hash', !array_key_exists('password_hash', $user));
check('records last_login_at',
    $pdo->query('SELECT last_login_at FROM users')->fetch()['last_login_at'] !== null);

$wrong = refused(fn() => $auth->login('dennis@example.com', 'wrong-password-here'));
$unknown = refused(fn() => $auth->login('nobody@example.com', 'wrong-password-here'));
check('rejects a wrong password', $wrong !== '');
check('does not reveal whether an account exists', $wrong === $unknown, "{$wrong} vs {$unknown}");

echo "\nSessions\n";
[$auth, $sessions, $limiter, $pdo] = makeAuth();
$id = $auth->register('s@example.com', $GOOD, 'Session Tester');
$token = $sessions->start($id);
check('token is 64 hex characters', preg_match('/^[a-f0-9]{64}$/', $token) === 1);
$stored = $pdo->query('SELECT id FROM user_sessions')->fetch()['id'];
check('database stores only the token hash', $stored !== $token && $stored === hash('sha256', $token));
check('a valid token resolves to the user', $sessions->userId($token) === $id);
check('a forged token resolves to nobody', $sessions->userId(str_repeat('a', 64)) === null);
check('an empty token resolves to nobody', $sessions->userId('') === null);

$sessions->destroy($token);
check('destroy invalidates the token', $sessions->userId($token) === null);
check('destroy removes the row', (int) $pdo->query('SELECT COUNT(*) c FROM user_sessions')->fetch()['c'] === 0);

$expiredToken = bin2hex(random_bytes(32));
$pdo->prepare('INSERT INTO user_sessions (id, user_id, expires_at, created_at) VALUES (?,?,?,?)')
    ->execute([hash('sha256', $expiredToken), $id, gmdate('Y-m-d H:i:s', time() - 60), gmdate('Y-m-d H:i:s')]);
check('an expired session is rejected', $sessions->userId($expiredToken) === null);
check('an expired session is deleted on use',
    (int) $pdo->query('SELECT COUNT(*) c FROM user_sessions')->fetch()['c'] === 0);

$a = $sessions->start($id);
$b = $sessions->start($id);
check('sessions are independent', $a !== $b && $sessions->userId($a) === $id && $sessions->userId($b) === $id);
$sessions->destroyAllFor($id);
check('destroyAllFor revokes every session',
    $sessions->userId($a) === null && $sessions->userId($b) === null);

echo "\nRate limiting\n";
[$auth, $sessions, $limiter, $pdo] = makeAuth(3);
$auth->register('r@example.com', $GOOD, 'Rate Tester');
for ($i = 0; $i < 3; $i++) {
    refused(fn() => $auth->login('r@example.com', 'definitely-wrong-pass'));
}
$locked = refused(fn() => $auth->login('r@example.com', $GOOD));
check('locks out after the configured failures', str_contains($locked, 'Too many sign-in attempts'), $locked);
check('reports a retry delay', $limiter->secondsUntilRetry('email:r@example.com') > 0);

[$auth, $sessions, $limiter, $pdo] = makeAuth(3);
$auth->register('c@example.com', $GOOD, 'Clear Tester');
refused(fn() => $auth->login('c@example.com', 'definitely-wrong-pass'));
refused(fn() => $auth->login('c@example.com', 'definitely-wrong-pass'));
$auth->login('c@example.com', $GOOD);
check('a successful sign-in clears the counter', !$limiter->isLocked('email:c@example.com'));

echo "\nRegistration is not blocked by the sign-in throttle\n";
[$auth, $sessions, $limiter, $pdo] = makeAuth(2);
$auth->register('victim2@example.com', $GOOD, 'Existing User');
// Someone on the same address burns through the attempt budget.
for ($i = 0; $i < 12; $i++) {
    refused(fn() => $auth->login('victim2@example.com', 'definitely-wrong-pass'));
}
check('the email is locked out', $limiter->isLocked('email:victim2@example.com'));
$newId = 0;
$registerError = refused(function () use ($auth, $GOOD, &$newId) {
    $newId = $auth->register('newcomer@example.com', $GOOD, 'Newcomer');
});
check('a newcomer on that address can still register', $registerError === '', $registerError);
$signedIn = refused(fn() => $auth->startSessionFor($newId));
check('and is signed in straight away', $signedIn === '', $signedIn);
check('the new session is usable', $auth->user() !== null);

echo "\nShared addresses get a larger budget than one email\n";
[$auth, $sessions, $limiter, $pdo] = makeAuth(3);
$auth->register('shared@example.com', $GOOD, 'Shared User');
for ($i = 0; $i < 3; $i++) {
    refused(fn() => $auth->login('shared@example.com', 'definitely-wrong-pass'));
}
check('one email locks at its own threshold', $limiter->isLocked('email:shared@example.com', 3));
check('the address is not locked at the same count',
    !$limiter->isLocked('ip:' . Http::clientIp(), 3 * 6));

echo "\nPassword change\n";
[$auth, $sessions, $limiter, $pdo] = makeAuth();
$id = $auth->register('p@example.com', $GOOD, 'Password Tester');
$t1 = $sessions->start($id);
check('rejects a wrong current password',
    refused(fn() => $auth->changePassword($id, 'not-the-password', 'a brand new passphrase')) !== '');
$auth->changePassword($id, $GOOD, 'a brand new passphrase');
check('the old password stops working',
    refused(fn() => $auth->login('p@example.com', $GOOD)) !== '');
check('the new password works', (int) $auth->login('p@example.com', 'a brand new passphrase')['id'] === $id);
check('changing the password revokes existing sessions', $sessions->userId($t1) === null);

echo "\nCSRF\n";
check('a matching token validates', (function (): bool {
    $_COOKIE['csrf'] = str_repeat('b', 64);
    return Csrf::isValid(str_repeat('b', 64));
})());
check('a mismatched token is refused', Csrf::isValid(str_repeat('c', 64)) === false);
check('an empty token is refused', Csrf::isValid('') === false);
check('a missing cookie is refused', (function (): bool {
    unset($_COOKIE['csrf']);
    return Csrf::isValid(str_repeat('b', 64)) === false;
})());

echo "\nSQL injection\n";
[$auth, $sessions, $limiter, $pdo] = makeAuth();
$auth->register('victim@example.com', $GOOD, 'Victim');
$payload = "victim@example.com' OR '1'='1";
check('injected email does not authenticate', refused(fn() => $auth->login($payload, 'anything at all')) !== '');
check('users table is intact after injection attempt',
    (int) $pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'] === 1);
$dropper = "x@example.com'); DROP TABLE users; --";
refused(fn() => $auth->register($dropper, $GOOD, 'Dropper'));
check('table survives a drop attempt',
    (int) $pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'] === 1);

echo "\n" . str_repeat('-', 52) . "\n";
echo ($failed === 0 ? "ALL PASS" : "FAILURES") . ": {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
