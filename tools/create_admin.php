<?php
declare(strict_types=1);

/**
 * Creates an administrator, or promotes and re-passwords an existing account.
 *
 *   php tools/create_admin.php you@example.com "Dennis Dizon"
 *   php tools/create_admin.php you@example.com "Dennis Dizon" "a long passphrase"
 *
 * With no password given, a strong one is generated and printed once.
 * Uses the same .env the site uses, so it writes to whichever database is
 * configured -- the SQLite dev database, or production MySQL.
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

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$email = $argv[1] ?? '';
$name = $argv[2] ?? '';
$password = $argv[3] ?? null;

if ($email === '' || $name === '') {
    fwrite(STDERR, "Usage: php tools/create_admin.php <email> \"<Name>\" [password]\n");
    exit(1);
}

$envFile = null;
foreach ([dirname($root) . '/.env', $root . '/.env'] as $candidate) {
    if (is_readable($candidate)) {
        $envFile = $candidate;
        break;
    }
}
if ($envFile === null) {
    fwrite(STDERR, "No .env found. Run php tools/serve.php once, or copy .env.example.\n");
    exit(1);
}
Env::load($envFile);
date_default_timezone_set('UTC');

/** Readable and strong: five words plus digits beats a short symbol soup. */
function generatePassword(): string
{
    $words = ['harbour', 'lantern', 'meridian', 'compass', 'thicket', 'quarry', 'bramble',
              'cinder', 'furrow', 'gantry', 'kestrel', 'marrow', 'plinth', 'saffron',
              'tundra', 'vellum', 'wicket', 'zephyr', 'basalt', 'cobalt'];
    $picked = [];
    for ($i = 0; $i < 5; $i++) {
        $picked[] = $words[random_int(0, count($words) - 1)];
    }
    return implode('-', $picked) . '-' . random_int(10, 99);
}

$generated = $password === null;
$password ??= generatePassword();

try {
    $pdo = Database::connect();
    $sessions = new SessionStore($pdo, 86400);
    $limiter = new RateLimiter($pdo, 5, 900);
    $auth = new Auth($pdo, $sessions, $limiter);

    [$id, $created] = $auth->upsertAdmin($email, $password, $name);
    if ($created) {
        echo "Created administrator: {$email}\n";
    } else {
        echo "Updated existing account to administrator: {$email}\n";
        echo "Existing sessions for that account were signed out.\n";
    }
} catch (ValidationException $e) {
    fwrite(STDERR, 'Refused: ' . $e->getMessage() . "\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($generated) {
    echo "\n  Password: {$password}\n";
    echo "\nStore it now; it is not shown again and is not recoverable.\n";
}
echo "\nSign in at /app/login.php\n";
