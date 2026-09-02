<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        // DB_DSN lets the test harness point the same code at SQLite.
        $dsn = Env::get('DB_DSN', '');
        $user = null;
        $password = null;
        if ($dsn === '') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Env::get('DB_HOST'),
                Env::get('DB_PORT', '3306'),
                Env::get('DB_NAME')
            );
            $user = Env::get('DB_USER');
            $password = Env::get('DB_PASSWORD');
        }
        self::$pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return self::$pdo;
    }

    public static function set(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
