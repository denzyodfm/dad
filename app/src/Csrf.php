<?php
declare(strict_types=1);

namespace App;

/**
 * Double-submit CSRF tokens.
 *
 * The token lives in an HttpOnly cookie and is echoed into each form by the
 * server. A cross-origin page can neither read the cookie nor set the field,
 * and SameSite=Lax already blocks the cross-site POST itself.
 */
final class Csrf
{
    private const COOKIE = 'csrf';
    private const FIELD = '_token';

    public static function token(): string
    {
        $existing = $_COOKIE[self::COOKIE] ?? null;
        if (is_string($existing) && preg_match('/^[a-f0-9]{64}$/', $existing) === 1) {
            return $existing;
        }
        $token = bin2hex(random_bytes(32));
        $_COOKIE[self::COOKIE] = $token;
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            setcookie(self::COOKIE, $token, [
                'expires' => 0,
                'path' => '/',
                'secure' => Http::isSecure(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return $token;
    }

    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            self::FIELD,
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
        );
    }

    public static function isValid(?string $submitted = null): bool
    {
        $submitted ??= $_POST[self::FIELD] ?? '';
        $cookie = $_COOKIE[self::COOKIE] ?? '';
        if (!is_string($submitted) || $submitted === '' || !is_string($cookie) || $cookie === '') {
            return false;
        }
        return hash_equals($cookie, $submitted);
    }
}
