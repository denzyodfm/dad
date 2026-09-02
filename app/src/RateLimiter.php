<?php
declare(strict_types=1);

namespace App;

use PDO;

/** Counts recent failed sign-ins per identifier (an email, or a client IP). */
final class RateLimiter
{
    public function __construct(
        private PDO $pdo,
        private int $maxAttempts,
        private int $windowSeconds
    ) {
    }

    /** @param int|null $max Overrides the configured threshold for this check. */
    public function isLocked(string $identifier, ?int $max = null): bool
    {
        return $this->failuresSince($identifier) >= ($max ?? $this->maxAttempts);
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function secondsUntilRetry(string $identifier): int
    {
        $statement = $this->pdo->prepare(
            'SELECT attempted_at FROM login_attempts
             WHERE identifier = ? AND successful = 0 AND attempted_at > ?
             ORDER BY attempted_at ASC LIMIT 1'
        );
        $statement->execute([$identifier, $this->windowStart()]);
        $row = $statement->fetch();
        if ($row === false) {
            return 0;
        }
        $retryAt = strtotime((string) $row['attempted_at']) + $this->windowSeconds;
        return max(0, $retryAt - time());
    }

    public function record(string $identifier, bool $successful): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO login_attempts (identifier, successful, attempted_at) VALUES (?, ?, ?)'
        );
        $statement->execute([$identifier, $successful ? 1 : 0, gmdate('Y-m-d H:i:s')]);
    }

    public function clear(string $identifier): void
    {
        $this->pdo->prepare('DELETE FROM login_attempts WHERE identifier = ?')->execute([$identifier]);
    }

    public function purgeOld(): int
    {
        $statement = $this->pdo->prepare('DELETE FROM login_attempts WHERE attempted_at <= ?');
        $statement->execute([gmdate('Y-m-d H:i:s', time() - ($this->windowSeconds * 4))]);
        return $statement->rowCount();
    }

    private function failuresSince(string $identifier): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM login_attempts
             WHERE identifier = ? AND successful = 0 AND attempted_at > ?'
        );
        $statement->execute([$identifier, $this->windowStart()]);
        return (int) ($statement->fetch()['total'] ?? 0);
    }

    private function windowStart(): string
    {
        return gmdate('Y-m-d H:i:s', time() - $this->windowSeconds);
    }
}
