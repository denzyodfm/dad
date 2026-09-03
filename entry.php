<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/app/bootstrap.php';

$content = new Content($pdo);
$slug = (string) ($_GET['slug'] ?? '');
$entry = $slug === '' ? null : $content->entryBySlug($slug);

if ($entry === null) {
    http_response_code(404);
    $notFound = true;
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php if (!isset($notFound)): ?>
  <meta name="description" content="<?= e((string) ($entry['summary'] ?? $entry['title'])) ?>" />
<?php endif; ?>
  <meta name="theme-color" content="#f2f0e9" />
  <title><?= isset($notFound) ? 'Not found' : e($entry['title']) ?> — Dennis Dizon</title>
  <link rel="icon" href="favicon.svg" type="image/svg+xml" />
  <link rel="preload" href="fonts/manrope-400-700-latin.woff2" as="font" type="font/woff2" crossorigin />
  <link rel="stylesheet" href="fonts/fonts.css" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="shell">
    <header class="site-header">
      <a class="brand" href="./"><span class="brand-mark">DD</span><span>Dennis Dizon</span></a>
      <p class="role">FileMaker Developer / IT Specialist</p>
      <div class="header-actions"><a href="writing.php">All writing &#8599;</a></div>
    </header>

<?php if (isset($notFound)): ?>
    <main id="main" class="reading">
      <div class="reading-head">
        <p class="eyebrow">Error 404</p>
        <h1>That entry<br /><span>is not here.</span></h1>
      </div>
      <p class="reading-empty"><a href="writing.php">Back to the writing archive</a></p>
    </main>
<?php else: ?>
    <main id="main" class="reading">
      <article>
        <div class="reading-head">
          <p class="eyebrow"><?= e($entry['type_name']) ?><?php if (!empty($entry['category'])): ?> · <?= e($entry['category']) ?><?php endif; ?></p>
          <h1><?= e($entry['title']) ?></h1>
          <?php if (!empty($entry['summary'])): ?><p class="reading-summary"><?= e($entry['summary']) ?></p><?php endif; ?>
          <?php if (!empty($entry['published_at'])): ?>
          <p class="reading-date"><time datetime="<?= e($entry['published_at']) ?>"><?= e(gmdate('j F Y', strtotime((string) $entry['published_at']))) ?></time></p>
          <?php endif; ?>
        </div>

        <?php if (!empty($entry['cover_path'])): ?>
        <img class="reading-cover" src="output/uploads/<?= e($entry['cover_path']) ?>"
             alt="<?= e((string) $entry['cover_alt']) ?>" loading="lazy" />
        <?php endif; ?>

        <?php if (!empty($entry['media_path'])): ?>
        <?= media_player($entry) ?>
        <?php endif; ?>

        <?php if ($entry['facts'] !== []): ?>
        <div class="facts">
        <?php foreach ($entry['facts'] as $fact): ?>
          <div><span><?= e($fact['label']) ?></span><strong><?= e($fact['value']) ?></strong></div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="reading-body"><?= safe_html($entry['body']) ?></div>

        <?php if (!empty($entry['link_url'])): ?>
        <a class="dialog-action" href="<?= e($entry['link_url']) ?>" target="_blank" rel="noreferrer"><?= e($entry['link_label'] ?: 'Open link') ?> &#8599;</a>
        <?php endif; ?>
      </article>
    </main>
<?php endif; ?>

    <footer><span>&copy; 2026 Dennis Dizon</span><a href="tel:+639095994462"><b>Phone</b> +63 909 599 4462</a><a href="mailto:denzyodfm@gmail.com"><b>Email</b> denzyodfm@gmail.com</a></footer>
  </div>
</body>
</html>
