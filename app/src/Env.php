<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

/** Minimal .env reader. Values stay in this process; nothing is exported. */
final class Env
{
    /** @var array<string,string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            throw new RuntimeException('Environment file not readable: ' . $path);
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            $quoted = strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0];
            self::$values[$key] = $quoted ? substr($value, 1, -1) : $value;
        }
    }

    public static function set(string $key, string $value): void
    {
        self::$values[$key] = $value;
    }

    public static function get(string $key, ?string $default = null): string
    {
        $value = self::$values[$key] ?? $default;
        if ($value === null) {
            throw new RuntimeException('Missing environment value: ' . $key);
        }
        return $value;
    }

    public static function int(string $key, int $default): int
    {
        $value = self::$values[$key] ?? null;
        return $value === null || !is_numeric($value) ? $default : (int) $value;
    }
}
