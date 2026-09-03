<?php
declare(strict_types=1);

namespace App;

/**
 * Sitemap for the published pages.
 *
 * Search engines need absolute URLs, so this builds them from the request.
 * Set SITE_ORIGIN in .env once the site has its real domain, otherwise the
 * host the request arrived on is used.
 */

require_once __DIR__ . '/app/bootstrap.php';

$origin = Env::get('SITE_ORIGIN', '');
if ($origin === '') {
    $scheme = Http::isSecure() ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1');
    $origin = $scheme . '://' . $host;
}
$origin = rtrim($origin, '/');

$content = new Content($pdo);
$writing = $content->writingEntries();

$urls = [
    ['loc' => $origin . '/', 'priority' => '1.0', 'changefreq' => 'monthly'],
];
if ($writing !== []) {
    $urls[] = ['loc' => $origin . '/writing.php', 'priority' => '0.8', 'changefreq' => 'weekly'];
}
foreach ($writing as $entry) {
    $urls[] = [
        'loc' => $origin . '/entry.php?slug=' . rawurlencode((string) $entry['slug']),
        'lastmod' => substr((string) $entry['updated_at'], 0, 10),
        'priority' => '0.6',
        'changefreq' => 'yearly',
    ];
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
  <url>
    <loc><?= e($url['loc']) ?></loc>
<?php if (isset($url['lastmod'])): ?>
    <lastmod><?= e($url['lastmod']) ?></lastmod>
<?php endif; ?>
    <changefreq><?= e($url['changefreq']) ?></changefreq>
    <priority><?= e($url['priority']) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
