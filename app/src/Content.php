<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Reads and writes content types, entries and their fact lists.
 *
 * Everything here uses plain portable SQL with PHP-side timestamps so it
 * behaves identically on MySQL and on the SQLite development database.
 */
final class Content
{
    public const PLACEMENTS = ['portfolio' => 'Project card', 'writing' => 'Writing'];
    public const ACCENTS = ['cobalt', 'ink', 'outline', 'outline-cobalt'];
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published'];

    /**
     * Columns a caller may write. Entry data is interpolated into the column
     * list of the INSERT and UPDATE, so anything not named here is dropped
     * rather than trusted.
     */
    private const WRITABLE = [
        'type_id', 'author_id', 'title', 'summary', 'body', 'category', 'status',
        'published_at', 'sort_order', 'accent', 'kicker', 'meta', 'card_heading', 'link_url',
        'link_label', 'cover_path', 'cover_alt', 'media_path', 'media_kind',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    // ---------------------------------------------------------------- types

    /** @return list<array> */
    public function types(): array
    {
        return $this->pdo->query(
            'SELECT * FROM content_types ORDER BY sort_order ASC, name ASC'
        )->fetchAll();
    }

    public function type(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM content_types WHERE id = ?');
        $statement->execute([$id]);
        return $this->orNull($statement->fetch());
    }

    public function typeBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM content_types WHERE slug = ?');
        $statement->execute([$slug]);
        return $this->orNull($statement->fetch());
    }

    public function createType(string $name, string $placement, int $sortOrder = 0): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new ValidationException('Give the content type a name.');
        }
        if (!array_key_exists($placement, self::PLACEMENTS)) {
            throw new ValidationException('Choose where entries of this type appear.');
        }
        $now = gmdate('Y-m-d H:i:s');
        $this->pdo->prepare(
            'INSERT INTO content_types (slug, name, placement, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$this->uniqueSlug($name, 'content_types'), $name, $placement, $sortOrder, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateType(int $id, string $name, string $placement, int $sortOrder): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new ValidationException('Give the content type a name.');
        }
        if (!array_key_exists($placement, self::PLACEMENTS)) {
            throw new ValidationException('Choose where entries of this type appear.');
        }
        $this->pdo->prepare(
            'UPDATE content_types SET name = ?, placement = ?, sort_order = ?, updated_at = ? WHERE id = ?'
        )->execute([$name, $placement, $sortOrder, gmdate('Y-m-d H:i:s'), $id]);
    }

    public function deleteType(int $id): void
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM entries WHERE type_id = ?');
        $statement->execute([$id]);
        if ((int) $statement->fetchColumn() > 0) {
            throw new ValidationException(
                'That type still has entries. Move or delete them first.'
            );
        }
        $this->pdo->prepare('DELETE FROM content_types WHERE id = ?')->execute([$id]);
    }

    // -------------------------------------------------------------- entries

    /**
     * @param array{placement?:string,status?:string,type_id?:int} $filters
     * @return list<array>
     */
    public function entries(array $filters = []): array
    {
        $sql = 'SELECT e.*, t.name AS type_name, t.slug AS type_slug, t.placement
                FROM entries e JOIN content_types t ON t.id = e.type_id';
        $where = [];
        $bind = [];
        if (isset($filters['placement'])) {
            $where[] = 't.placement = ?';
            $bind[] = $filters['placement'];
        }
        if (isset($filters['status'])) {
            $where[] = 'e.status = ?';
            $bind[] = $filters['status'];
        }
        if (isset($filters['type_id'])) {
            $where[] = 'e.type_id = ?';
            $bind[] = $filters['type_id'];
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.sort_order ASC, e.published_at DESC, e.id DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bind);
        return $statement->fetchAll();
    }

    /** Published entries of types placed on the home page, with their facts. */
    public function portfolioCards(): array
    {
        return $this->withFacts($this->entries(['placement' => 'portfolio', 'status' => 'published']));
    }

    /** Published entries of types placed in the writing section. */
    public function writingEntries(): array
    {
        return $this->entries(['placement' => 'writing', 'status' => 'published']);
    }

    public function entry(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT e.*, t.name AS type_name, t.slug AS type_slug, t.placement
             FROM entries e JOIN content_types t ON t.id = e.type_id WHERE e.id = ?'
        );
        $statement->execute([$id]);
        $entry = $this->orNull($statement->fetch());
        if ($entry !== null) {
            $entry['facts'] = $this->facts($id);
        }
        return $entry;
    }

    public function entryBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        $sql = 'SELECT e.*, t.name AS type_name, t.slug AS type_slug, t.placement
                FROM entries e JOIN content_types t ON t.id = e.type_id WHERE e.slug = ?';
        if ($publishedOnly) {
            $sql .= " AND e.status = 'published'";
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$slug]);
        $entry = $this->orNull($statement->fetch());
        if ($entry !== null) {
            $entry['facts'] = $this->facts((int) $entry['id']);
        }
        return $entry;
    }

    /** @param array<string,mixed> $data */
    public function createEntry(array $data): int
    {
        $data = $this->validate($data);
        $now = gmdate('Y-m-d H:i:s');
        $columns = array_keys($data);
        $this->pdo->prepare(sprintf(
            'INSERT INTO entries (%s, slug, created_at, updated_at) VALUES (%s, ?, ?, ?)',
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        ))->execute([...array_values($data), $this->uniqueSlug((string) $data['title'], 'entries'), $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function updateEntry(int $id, array $data): void
    {
        $data = $this->validate($data);
        $assignments = implode(', ', array_map(static fn(string $c): string => "{$c} = ?", array_keys($data)));
        $this->pdo->prepare("UPDATE entries SET {$assignments}, updated_at = ? WHERE id = ?")
            ->execute([...array_values($data), gmdate('Y-m-d H:i:s'), $id]);
    }

    public function deleteEntry(int $id): void
    {
        // entry_facts cascades on MySQL; SQLite needs foreign keys switched on,
        // so clear them explicitly and stay correct on both.
        $this->pdo->prepare('DELETE FROM entry_facts WHERE entry_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM entries WHERE id = ?')->execute([$id]);
    }

    // ---------------------------------------------------------------- facts

    /** @return list<array> */
    public function facts(int $entryId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM entry_facts WHERE entry_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute([$entryId]);
        return $statement->fetchAll();
    }

    /**
     * Replaces an entry's facts wholesale; blank rows from the form are dropped.
     *
     * @param list<array{label:string,value:string}> $facts
     */
    public function replaceFacts(int $entryId, array $facts): void
    {
        $this->pdo->prepare('DELETE FROM entry_facts WHERE entry_id = ?')->execute([$entryId]);
        $statement = $this->pdo->prepare(
            'INSERT INTO entry_facts (entry_id, label, value, sort_order) VALUES (?, ?, ?, ?)'
        );
        $order = 0;
        foreach ($facts as $fact) {
            $label = trim((string) ($fact['label'] ?? ''));
            $value = trim((string) ($fact['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $statement->execute([$entryId, $label, $value, $order++]);
        }
    }

    // -------------------------------------------------------------- helpers

    /** @param list<array> $entries */
    private function withFacts(array $entries): array
    {
        foreach ($entries as $index => $entry) {
            $entries[$index]['facts'] = $this->facts((int) $entry['id']);
        }
        return $entries;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function validate(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new ValidationException('Give this entry a title.');
        }
        if (mb_strlen($title) > 200) {
            throw new ValidationException('Titles are limited to 200 characters.');
        }
        if ((int) ($data['type_id'] ?? 0) <= 0) {
            throw new ValidationException('Choose a content type.');
        }
        $status = (string) ($data['status'] ?? 'draft');
        if (!array_key_exists($status, self::STATUSES)) {
            throw new ValidationException('Choose a valid status.');
        }
        if (isset($data['accent']) && !in_array($data['accent'], self::ACCENTS, true)) {
            throw new ValidationException('Choose a valid card colour.');
        }
        if (isset($data['media_kind']) && $data['media_kind'] !== null
            && !in_array($data['media_kind'], ['audio', 'video'], true)) {
            throw new ValidationException('Attachments must be audio or video.');
        }
        $data['title'] = $title;

        // Keep only known columns, in a fixed order, so the generated column
        // list can never carry anything a caller invented.
        $clean = [];
        foreach (self::WRITABLE as $column) {
            if (array_key_exists($column, $data)) {
                $clean[$column] = $data[$column];
            }
        }
        if ($clean === []) {
            throw new ValidationException('Nothing to save.');
        }
        return $clean;
    }

    /** Slugifies and appends a counter until the value is free. */
    private function uniqueSlug(string $source, string $table): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $source) ?? '', '-'));
        if ($base === '') {
            $base = 'entry';
        }
        $base = mb_substr($base, 0, 140);
        $slug = $base;
        $suffix = 2;
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ?");
        while (true) {
            $statement->execute([$slug]);
            if ((int) $statement->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $suffix++;
        }
    }

    private function orNull(mixed $row): ?array
    {
        return $row === false ? null : $row;
    }
}
