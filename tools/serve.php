<?php
declare(strict_types=1);

/**
 * One-command development server for the whole site.
 *
 * Serves the static portfolio and runs the PHP account pages on the same
 * port, so links between them work exactly as they would in production.
 *
 *   php tools/serve.php            # http://127.0.0.1:8000
 *   php tools/serve.php 8080       # a different port
 *   php tools/serve.php --reset    # start from an empty database
 *
 * Development uses SQLite so there is nothing to install. Production is
 * MySQL; see the Backend section of README.md.
 */

$root = dirname(__DIR__);
$devDir = $root . '/.dev';
$database = $devDir . '/portfolio.sqlite';
$envFile = $root . '/.env';

$port = 8000;
$reset = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--reset') {
        $reset = true;
    } elseif (ctype_digit($argument)) {
        $port = (int) $argument;
    } else {
        fwrite(STDERR, "Unknown argument: {$argument}\n");
        exit(1);
    }
}

if (!is_dir($devDir) && !mkdir($devDir, 0777, true) && !is_dir($devDir)) {
    fwrite(STDERR, "Could not create {$devDir}\n");
    exit(1);
}

if ($reset && is_file($database)) {
    unlink($database);
    echo "Removed the existing development database.\n";
}

$isNew = !is_file($database);
try {
    $pdo = new PDO('sqlite:' . $database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec(file_get_contents($root . '/database/schema.sqlite.sql'));
} catch (Throwable $e) {
    fwrite(STDERR, 'Could not prepare the development database: ' . $e->getMessage() . "\n");
    exit(1);
}
echo ($isNew ? 'Created' : 'Using') . ' development database: .dev/portfolio.sqlite' . "\n";

// A development .env, written only if there is not one already, so a real
// configuration is never overwritten.
if (!is_file($envFile)) {
    file_put_contents($envFile, <<<ENV
        # Local development only. Written by tools/serve.php; git-ignored.
        # Production uses MySQL -- see .env.example and README.md.
        APP_ENV=development
        DB_DSN=sqlite:{$database}
        SESSION_LIFETIME_DAYS=14
        LOGIN_MAX_ATTEMPTS=5
        LOGIN_LOCKOUT_MINUTES=15
        ENV);
    echo "Wrote a development .env (git-ignored).\n";
} else {
    echo "Using the existing .env.\n";
}

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

echo "\n";
echo "  Portfolio   http://127.0.0.1:{$port}/\n";
echo "  Account     http://127.0.0.1:{$port}/app/\n";
echo '  Accounts    ' . ($userCount === 0
    ? "none yet - run: php tools/create_admin.php you@example.com \"Your Name\"\n"
    : "{$userCount} registered\n");
echo "\n  Ctrl+C to stop.\n\n";

$command = sprintf(
    '%s -S 127.0.0.1:%d -t %s %s',
    escapeshellarg(PHP_BINARY),
    $port,
    escapeshellarg($root),
    escapeshellarg($root . '/tools/router.php')
);

$exitCode = 0;
passthru($command, $exitCode);
exit($exitCode);
