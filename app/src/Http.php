<?php
declare(strict_types=1);

namespace App;

final class Http
{
    public static function isSecure(): bool
    {
        if (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') {
            return true;
        }
        // Behind a reverse proxy or shared-hosting load balancer.
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public static function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return is_string($ip) && $ip !== '' ? $ip : '0.0.0.0';
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    /** Headers appropriate for pages that render user-controlled data. */
    public static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
    }
}
