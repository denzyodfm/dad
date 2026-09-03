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

/**
 * Renders an entry body, which is stored as HTML written in the studio.
 *
 * Only the administrator can write it, and the page's CSP already refuses
 * inline scripts, but the body is still reduced to a small tag allowlist with
 * event handlers and javascript: URLs stripped. Defence in depth costs
 * nothing here and keeps a mistake in the editor from breaking the page.
 */
function safe_html(?string $html): string
{
    $html = (string) $html;
    if (trim($html) === '') {
        return '';
    }
    // Drop script and style blocks with their contents. strip_tags alone would
    // remove the tags but leave the code sitting in the page as visible text.
    $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $html) ?? $html;
    $html = preg_replace('#</?(script|style)\b[^>]*>#i', '', $html) ?? $html;

    $allowed = '<p><br><b><strong><i><em><ul><ol><li><blockquote><h3><h4><code><pre><a>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/\s(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2/i', '', $html) ?? $html;
    return $html;
}

/**
 * The heading shown on a project card.
 *
 * Falls back to the title. A vertical bar forces a line break, which is how
 * the cards keep their two-line rhythm without putting markup in the title.
 */
function card_heading(array $entry): string
{
    $heading = trim((string) ($entry['card_heading'] ?? ''));
    if ($heading === '') {
        $heading = (string) $entry['title'];
    }
    return implode('<br />', array_map(e(...), explode('|', $heading)));
}

/** An <audio> or <video> element for an entry's attachment. */
function media_player(array $entry): string
{
    $src = 'output/uploads/' . rawurlencode((string) $entry['media_path']);
    $label = e((string) ($entry['title'] ?? 'Attachment'));
    if (($entry['media_kind'] ?? '') === 'video') {
        return '<video class="entry-media" controls preload="metadata" src="' . e($src)
            . '" title="' . $label . '"></video>';
    }
    return '<audio class="entry-media" controls preload="metadata" src="' . e($src)
        . '" title="' . $label . '"></audio>';
}
