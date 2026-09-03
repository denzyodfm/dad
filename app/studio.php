<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

$currentUser = $auth->requireAdmin();
$content = new Content($pdo);

$entries = $content->entries();
$types = $content->types();

$byPlacement = ['portfolio' => [], 'writing' => []];
foreach ($entries as $entry) {
    $byPlacement[$entry['placement']][] = $entry;
}

$pageTitle = 'Content studio';
require __DIR__ . '/partials/studio-head.php';
?>
      <div class="studio-intro">
        <p class="eyebrow">Archive</p>
        <h1>Your content</h1>
        <a class="studio-new" href="entry-edit.php">+ New entry</a>
      </div>

      <?php if ($entries === []): ?>
      <p class="studio-empty">Nothing published yet. Start with a new entry.</p>
      <?php endif; ?>

      <?php foreach (Content::PLACEMENTS as $placement => $placementLabel): ?>
      <?php if ($byPlacement[$placement] === []) {
          continue;
      } ?>
      <section class="studio-group">
        <p class="eyebrow"><?= e($placementLabel) ?></p>
        <ul class="studio-list">
        <?php foreach ($byPlacement[$placement] as $entry): ?>
          <li>
            <a href="entry-edit.php?id=<?= (int) $entry['id'] ?>">
              <span class="studio-type"><?= e($entry['type_name']) ?></span>
              <strong><?= e($entry['title']) ?></strong>
              <span class="studio-meta">
                <?= e($entry['published_at'] ?: 'no date') ?>
                <?php if ($entry['status'] !== 'published'): ?>
                  <em class="studio-draft">Draft</em>
                <?php endif; ?>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>
      </section>
      <?php endforeach; ?>

      <section class="studio-group">
        <p class="eyebrow">Content types</p>
        <ul class="studio-list">
        <?php foreach ($types as $type): ?>
          <li>
            <a href="types.php#type-<?= (int) $type['id'] ?>">
              <span class="studio-type"><?= e(Content::PLACEMENTS[$type['placement']] ?? $type['placement']) ?></span>
              <strong><?= e($type['name']) ?></strong>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>
        <p class="auth-alt"><a href="types.php">Manage content types</a></p>
      </section>
<?php require __DIR__ . '/partials/studio-foot.php';
