<?php
declare(strict_types=1);

/**
 * Wires the application together. Every entry point includes this file first.
 */

namespace App;

use PDO;
use Throwable;

// This file is an include, never a page. Refuse to run if it is requested
// directly, in case the host does not honour the .htaccess rules.
if (realpath(__FILE__) === realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    http_response_code(404);
    exit;
}

define('APP_BOOTSTRAPPED', true);

require_once __DIR__ . '/src/Env.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = __DIR__ . '/src/' . substr($class, 4) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Prefer a .env kept one level above the site, where the web server cannot
// serve it at all. Fall back to the project root for hosts that give you no
// directory outside the document root.
$envFile = null;
foreach ([dirname(__DIR__, 2) . '/.env', dirname(__DIR__) . '/.env'] as $candidate) {
    if (is_readable($candidate)) {
        $envFile = $candidate;
        break;
    }
}
if ($envFile === null) {
    http_response_code(500);
    exit('Configuration missing. Copy .env.example to .env and fill it in.');
}
Env::load($envFile);

date_default_timezone_set('UTC');

$isProduction = Env::get('APP_ENV', 'production') === 'production';
ini_set('display_errors', $isProduction ? '0' : '1');
error_reporting(E_ALL);

Http::securityHeaders();

try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('The service is temporarily unavailable.');
}

$sessions = new SessionStore($pdo, Env::int('SESSION_LIFETIME_DAYS', 14) * 86400);
$limiter = new RateLimiter(
    $pdo,
    Env::int('LOGIN_MAX_ATTEMPTS', 5),
    Env::int('LOGIN_LOCKOUT_MINUTES', 15) * 60
);
$auth = new Auth($pdo, $sessions, $limiter);

// Occasional housekeeping, cheap enough to piggyback on normal traffic.
if (random_int(1, 100) === 1) {
    $sessions->purgeExpired();
    $limiter->purgeOld();
}

/** @return string HTML-escaped output. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
