<?php
declare(strict_types=1);

namespace App;

use PDO;

final class SiteSettings
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array> */
    public function rows(): array
    {
        return $this->pdo->query('SELECT * FROM site_settings ORDER BY sort_order, setting_key')->fetchAll();
    }

    /** @return array<string,string> */
    public function values(): array
    {
        $values = [];
        foreach ($this->rows() as $row) {
            $values[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        return $values;
    }

    /** @param array<string,string> $values */
    public function update(array $values): void
    {
        $allowed = array_column($this->rows(), 'setting_key');
        $statement = $this->pdo->prepare('UPDATE site_settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?');
        $this->pdo->beginTransaction();
        try {
            foreach ($values as $key => $value) {
                if (!in_array($key, $allowed, true)) continue;
                $statement->execute([trim($value), gmdate('Y-m-d H:i:s'), $key]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
