<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/app/bootstrap.php';

$content = new Content($pdo);
$entries = $content->writingEntries();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Writing by Dennis Dizon on building business systems, FileMaker delivery and IT infrastructure." />
  <meta name="theme-color" content="#f2f0e9" />
  <title>Writing — Dennis Dizon</title>
  <meta property="og:type" content="website" />
  <meta property="og:title" content="Writing — Dennis Dizon" />
  <meta property="og:description" content="Writing by Dennis Dizon on building business systems, FileMaker delivery and IT infrastructure." />
  <meta property="og:image" content="output/og-image.png" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Writing — Dennis Dizon" />
  <meta name="twitter:image" content="output/og-image.png" />
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
      <p class="role">Web &amp; FileMaker Developer / IT Specialist</p>
      <div class="header-actions"><a href="./">Back to work &#8599;</a></div>
    </header>

    <main id="main" class="reading">
      <div class="reading-head">
        <p class="eyebrow">Archive</p>
        <h1>Writing</h1>
      </div>

      <?php if ($entries === []): ?>
      <p class="reading-empty">Nothing published here yet.</p>
      <?php else: ?>
      <ul class="entry-list">
      <?php foreach ($entries as $entry): ?>
        <li>
          <a href="entry.php?slug=<?= e(rawurlencode((string) $entry['slug'])) ?>">
            <span class="entry-kind"><?= e($entry['type_name']) ?><?php if (!empty($entry['category'])): ?> · <?= e($entry['category']) ?><?php endif; ?></span>
            <strong><?= e($entry['title']) ?></strong>
            <?php if (!empty($entry['summary'])): ?><span class="entry-summary"><?= e($entry['summary']) ?></span><?php endif; ?>
            <?php if (!empty($entry['published_at'])): ?><time datetime="<?= e($entry['published_at']) ?>"><?= e(gmdate('j F Y', strtotime((string) $entry['published_at']))) ?></time><?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </main>

    <footer><span>&copy; 2026 Dennis Dizon</span><a href="tel:+639095994462"><b>Phone</b> +63 909 599 4462</a><a href="mailto:denzyodfm@gmail.com"><b>Email</b> denzyodfm@gmail.com</a></footer>
  </div>
</body>
</html>
