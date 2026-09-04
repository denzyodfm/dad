<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

$currentUser = $auth->requireAdmin();
$settingsStore = new SiteSettings($pdo);
$content = new Content($pdo);
$error = null;
$saved = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::isValid()) {
        $error = 'That form expired. Please try again.';
    } else {
        $settingsStore->update(array_map(static fn($v): string => is_string($v) ? $v : '', (array) ($_POST['settings'] ?? [])));
        $saved = true;
    }
}

$rows = $settingsStore->rows();
$systems = $content->entries(['placement' => 'portfolio']);
$pageTitle = 'Site settings';
require __DIR__ . '/partials/studio-head.php';
?>
  <div class="studio-intro">
    <p class="eyebrow">Settings</p>
    <h1>Site content</h1>
    <a class="studio-back" href="studio.php">&#8592; All content</a>
  </div>
  <p class="studio-empty">These fields drive the public profile, contact details, metrics and technology summary.</p>
<?php if ($saved): ?><p class="notice ok">Settings saved. <a href="../">View the site</a>.</p><?php endif; ?>
<?php if ($error !== null): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
  <form class="studio-form settings-form" method="post" action="settings.php">
    <?= Csrf::field() ?>
<?php foreach ($rows as $row): ?>
    <div class="field">
      <label for="setting-<?= e($row['setting_key']) ?>"><?= e($row['label']) ?></label>
<?php if ($row['input_type'] === 'textarea'): ?>
      <textarea id="setting-<?= e($row['setting_key']) ?>" name="settings[<?= e($row['setting_key']) ?>]" rows="4"><?= e($row['setting_value']) ?></textarea>
<?php else: ?>
      <input id="setting-<?= e($row['setting_key']) ?>" name="settings[<?= e($row['setting_key']) ?>]" type="<?= e($row['input_type']) ?>" value="<?= e($row['setting_value']) ?>" />
<?php endif; ?>
    </div>
<?php endforeach; ?>
    <div class="studio-actions"><button class="submit" type="submit">Save settings &#8599;</button></div>
  </form>

  <section class="studio-group settings-systems">
    <div class="studio-intro"><p class="eyebrow">Systems</p><h2>Portfolio management</h2><a class="studio-new" href="entry-edit.php">+ New system</a></div>
    <div class="table-scroll">
      <table class="management-table">
        <thead><tr><th>Order</th><th>System</th><th>Type</th><th>Status</th><th>Updated</th><th><span class="sr-only">Action</span></th></tr></thead>
        <tbody>
<?php foreach ($systems as $system): ?>
          <tr><td><?= (int) $system['sort_order'] ?></td><td><strong><?= e($system['title']) ?></strong><small><?= e($system['meta']) ?></small></td><td><?= e($system['type_name']) ?></td><td><?= e(ucfirst((string) $system['status'])) ?></td><td><?= e(gmdate('j M Y', strtotime((string) $system['updated_at']))) ?></td><td><a href="entry-edit.php?id=<?= (int) $system['id'] ?>">Edit all fields &#8599;</a></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php require __DIR__ . '/partials/studio-foot.php';
