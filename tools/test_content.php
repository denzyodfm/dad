<?php
declare(strict_types=1);

/**
 * Covers the content repository, uploads and the HTML sanitiser.
 *
 * Runs against in-memory SQLite using database/schema.sqlite.sql, the same
 * schema the development server uses.
 *
 * Run: php tools/test_content.php
 */

namespace App;

require_once __DIR__ . '/../app/src/Env.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = __DIR__ . '/../app/src/' . substr($class, 4) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use PDO;

// safe_html and card_heading live in bootstrap.php, which expects a request.
// Pull just those functions in rather than booting the whole application.
$bootstrap = file_get_contents(__DIR__ . '/../app/bootstrap.php');
foreach (['e', 'safe_html', 'card_heading'] as $fn) {
    if (preg_match('/\nfunction ' . $fn . '\(.*?\n}/s', $bootstrap, $m) === 1) {
        eval($m[0]);
    }
}

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  PASS  {$name}\n";
    } else {
        $failed++;
        echo "  FAIL  {$name}" . ($detail !== '' ? " -- {$detail}" : '') . "\n";
    }
}

function refused(callable $fn): string
{
    try {
        $fn();
        return '';
    } catch (ValidationException $e) {
        return $e->getMessage();
    }
}

function freshContent(): array
{
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sqlite.sql'));
    return [new Content($pdo), $pdo];
}

echo "Content types\n";
[$content, $pdo] = freshContent();
$projectType = $content->createType('Project', 'portfolio', 0);
$articleType = $content->createType('Article', 'writing', 1);
check('creates types', $projectType > 0 && $articleType > 0);
check('slugifies the name', $content->type($projectType)['slug'] === 'project');
check('lists both types', count($content->types()) === 2);
check('rejects a blank name', refused(fn() => $content->createType('  ', 'writing')) !== '');
check('rejects an unknown placement', refused(fn() => $content->createType('X', 'nowhere')) !== '');
$content->updateType($articleType, 'Essay', 'writing', 5);
check('updates a type', $content->type($articleType)['name'] === 'Essay');

echo "\nEntries\n";
$id = $content->createEntry([
    'type_id' => $projectType,
    'title' => 'Centralized Lending',
    'summary' => 'One platform across every branch.',
    'body' => '<p>Body text.</p>',
    'accent' => 'cobalt',
    'status' => 'published',
    'published_at' => '2026-09-01',
]);
check('creates an entry', $id > 0);
check('derives a slug', $content->entry($id)['slug'] === 'centralized-lending');
$second = $content->createEntry(['type_id' => $projectType, 'title' => 'Centralized Lending', 'status' => 'draft']);
check('makes duplicate slugs unique', $content->entry($second)['slug'] === 'centralized-lending-2');
check('rejects a blank title', refused(fn() => $content->createEntry(['type_id' => $projectType, 'title' => ' '])) !== '');
check('rejects a missing type', refused(fn() => $content->createEntry(['title' => 'No type'])) !== '');
check('rejects an unknown status',
    refused(fn() => $content->createEntry(['type_id' => $projectType, 'title' => 'X', 'status' => 'live'])) !== '');
check('rejects an unknown accent',
    refused(fn() => $content->createEntry(['type_id' => $projectType, 'title' => 'X', 'accent' => 'neon'])) !== '');
check('rejects an unknown media kind',
    refused(fn() => $content->createEntry(['type_id' => $projectType, 'title' => 'X', 'media_kind' => 'hologram'])) !== '');

$content->updateEntry($id, ['type_id' => $projectType, 'title' => 'Renamed', 'status' => 'published']);
check('updates an entry', $content->entry($id)['title'] === 'Renamed');
check('the slug stays put so links keep working', $content->entry($id)['slug'] === 'centralized-lending');

echo "\nUnknown columns cannot reach the SQL\n";
$before = $content->entry($id);
$content->updateEntry($id, [
    'type_id' => $projectType,
    'title' => 'Still Fine',
    'status' => 'published',
    'role = "admin" WHERE 1=1; --' => 'x',
    'id' => 999,
    'slug' => 'hijacked',
]);
$after = $content->entry($id);
check('the injected column is dropped', (int) $after['id'] === (int) $before['id']);
check('id cannot be overwritten', $content->entry(999) === null);
check('slug cannot be overwritten', $after['slug'] === 'centralized-lending');
check('the legitimate change still applied', $after['title'] === 'Still Fine');

echo "\nFacts\n";
$content->replaceFacts($id, [
    ['label' => 'Outcome', 'value' => '30% fewer delays'],
    ['label' => '', 'value' => 'dropped'],
    ['label' => 'Scope', 'value' => 'Multi-branch'],
    ['label' => 'Blank', 'value' => '   '],
]);
$facts = $content->facts($id);
check('keeps only complete pairs', count($facts) === 2, (string) count($facts));
check('keeps the given order', $facts[0]['label'] === 'Outcome' && $facts[1]['label'] === 'Scope');
$content->replaceFacts($id, [['label' => 'Only', 'value' => 'One']]);
check('replacing wipes the previous set', count($content->facts($id)) === 1);

echo "\nPlacement decides where entries appear\n";
$article = $content->createEntry([
    'type_id' => $articleType, 'title' => 'On Method', 'status' => 'published', 'published_at' => '2026-09-02',
]);
$draft = $content->createEntry([
    'type_id' => $articleType, 'title' => 'Unfinished', 'status' => 'draft',
]);
$cards = $content->portfolioCards();
$writing = $content->writingEntries();
check('cards come from portfolio types only', array_reduce($cards, fn($c, $e) => $c && $e['placement'] === 'portfolio', true));
check('drafts stay off the home page', !in_array('Centralized Lending', array_column($cards, 'title'), true));
check('writing lists the published article', in_array('On Method', array_column($writing, 'title'), true));
check('writing hides the draft', !in_array('Unfinished', array_column($writing, 'title'), true));
check('a draft is not reachable by slug', $content->entryBySlug('unfinished') === null);
check('a draft is reachable when explicitly asked for', $content->entryBySlug('unfinished', false) !== null);

echo "\nDeleting\n";
check('a type with entries cannot be deleted', refused(fn() => $content->deleteType($articleType)) !== '');
$content->deleteEntry($article);
$content->deleteEntry($draft);
check('the type can be deleted once empty', refused(fn() => $content->deleteType($articleType)) === '');
$content->deleteEntry($id);
check('the entry is gone', $content->entry($id) === null);
check('its facts went with it',
    (int) $pdo->query("SELECT COUNT(*) FROM entry_facts WHERE entry_id = {$id}")->fetchColumn() === 0);

echo "\nCard headings\n";
check('falls back to the title', card_heading(['title' => 'Resort Booking', 'card_heading' => '']) === 'Resort Booking');
check('a bar becomes a line break',
    card_heading(['title' => 'x', 'card_heading' => 'Resort|Booking']) === 'Resort<br />Booking');
check('headings are escaped',
    card_heading(['title' => 'x', 'card_heading' => '<script>|ok']) === '&lt;script&gt;<br />ok');

echo "\nHTML sanitising\n";
check('script tags and their contents go',
    !str_contains(safe_html('<p>a</p><script>alert(1)</script>'), 'alert'));
check('style blocks go', !str_contains(safe_html('<style>b{}</style><p>a</p>'), 'b{}'));
check('event handlers go', !str_contains(safe_html('<p onclick="evil()">a</p>'), 'onclick'));
check('javascript URLs go', !str_contains(safe_html('<a href="javascript:bad()">l</a>'), 'javascript:'));
check('img is not allowed', !str_contains(safe_html('<img src=x onerror=alert(1)>'), '<img'));
check('normal formatting survives',
    safe_html('<p>Hello <strong>world</strong></p>') === '<p>Hello <strong>world</strong></p>');
check('real links survive',
    str_contains(safe_html('<a href="https://example.com">l</a>'), 'href="https://example.com"'));
check('empty input gives empty output', safe_html(null) === '' && safe_html('   ') === '');

echo "\nUploads\n";
$dir = sys_get_temp_dir() . '/portfolio-upload-test-' . bin2hex(random_bytes(4));
mkdir($dir, 0777, true);
$uploads = new Uploads($dir);

function fakeUpload(string $path, int $error = UPLOAD_ERR_OK): array
{
    return ['tmp_name' => $path, 'size' => is_file($path) ? filesize($path) : 0, 'error' => $error];
}

$png = $dir . '/source.png';
imagepng(imagecreatetruecolor(20, 20), $png);
$stored = $uploads->storeImage(fakeUpload($png));
check('stores a real PNG', $stored !== null && is_file($dir . '/' . $stored));
check('renames it to something we chose',
    (bool) preg_match('/^[a-f0-9]{32}\.png$/', (string) $stored), (string) $stored);

$disguised = $dir . '/shell.php.jpg';
file_put_contents($disguised, "<?php echo 'pwned'; ?>");
check('refuses a script wearing an image extension',
    refused(fn() => $uploads->storeImage(fakeUpload($disguised))) !== '');

$textFile = $dir . '/notes.txt';
file_put_contents($textFile, 'just text');
check('refuses a text file as media', refused(fn() => $uploads->storeMedia(fakeUpload($textFile))) !== '');

check('an empty field is not an upload', $uploads->storeImage(fakeUpload('', UPLOAD_ERR_NO_FILE)) === null);
check('a null field is not an upload', $uploads->storeImage(null) === null);
check('reports a partial upload', refused(fn() => $uploads->storeImage(fakeUpload($png, UPLOAD_ERR_PARTIAL))) !== '');

$uploads->delete($stored);
check('deletes what it stored', !is_file($dir . '/' . $stored));
$outside = $dir . '/keepme.txt';
file_put_contents($outside, 'keep');
$uploads->delete('../keepme.txt');
$uploads->delete('keepme.txt');
check('will not delete a name it did not generate', is_file($outside));

array_map('unlink', glob($dir . '/*') ?: []);
rmdir($dir);

echo "\nThe two schema files agree\n";
/**
 * The MySQL schema is what production runs; the SQLite one backs development
 * and these tests. They are maintained by hand, so compare them here rather
 * than discovering a drift on a deploy.
 *
 * @return array<string,list<string>> table => column names
 */
function tablesOf(string $sql): array
{
    $tables = [];
    preg_match_all(
        '/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?\s*\((.*?)\n\)/is',
        $sql,
        $matches,
        PREG_SET_ORDER
    );
    foreach ($matches as $match) {
        $columns = [];
        foreach (explode("\n", $match[2]) as $line) {
            $line = trim($line);
            // Skip blanks, comments and table-level clauses.
            if ($line === '' || str_starts_with($line, '--')) {
                continue;
            }
            if (preg_match('/^(PRIMARY|UNIQUE|KEY|CONSTRAINT|FOREIGN|INDEX)\b/i', $line) === 1) {
                continue;
            }
            if (preg_match('/^`?(\w+)`?\s+\S/', $line, $column) === 1) {
                $columns[] = strtolower($column[1]);
            }
        }
        sort($columns);
        $tables[strtolower($match[1])] = $columns;
    }
    return $tables;
}

$mysql = tablesOf(file_get_contents(__DIR__ . '/../database/schema.sql'));
$sqlite = tablesOf(file_get_contents(__DIR__ . '/../database/schema.sqlite.sql'));

check('both files were parsed', $mysql !== [] && $sqlite !== [],
    count($mysql) . ' vs ' . count($sqlite) . ' tables');
$missingTables = array_diff(array_keys($mysql), array_keys($sqlite));
$extraTables = array_diff(array_keys($sqlite), array_keys($mysql));
check('the same tables exist in both', $missingTables === [] && $extraTables === [],
    'only in MySQL: ' . implode(', ', $missingTables) . '; only in SQLite: ' . implode(', ', $extraTables));

foreach ($mysql as $table => $columns) {
    if (!isset($sqlite[$table])) {
        continue;
    }
    $onlyMysql = array_diff($columns, $sqlite[$table]);
    $onlySqlite = array_diff($sqlite[$table], $columns);
    check("{$table} has the same columns", $onlyMysql === [] && $onlySqlite === [],
        'only in MySQL: ' . implode(', ', $onlyMysql) . '; only in SQLite: ' . implode(', ', $onlySqlite));
}

echo "\n" . str_repeat('-', 52) . "\n";
echo ($failed === 0 ? 'ALL PASS' : 'FAILURES') . ": {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
