<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

$currentUser = $auth->requireAdmin();
$content = new Content($pdo);

$error = null;
$saved = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::isValid()) {
        $error = 'That form expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'create') {
                $content->createType(
                    (string) ($_POST['name'] ?? ''),
                    (string) ($_POST['placement'] ?? 'writing'),
                    (int) ($_POST['sort_order'] ?? 0)
                );
                $saved = 'Content type added.';
            } elseif ($action === 'update') {
                $content->updateType(
                    (int) ($_POST['id'] ?? 0),
                    (string) ($_POST['name'] ?? ''),
                    (string) ($_POST['placement'] ?? 'writing'),
                    (int) ($_POST['sort_order'] ?? 0)
                );
                $saved = 'Content type updated.';
            } elseif ($action === 'delete') {
                $content->deleteType((int) ($_POST['id'] ?? 0));
                $saved = 'Content type deleted.';
            }
        } catch (ValidationException $e) {
            $error = $e->getMessage();
        }
    }
}

$types = $content->types();
$counts = [];
foreach ($types as $type) {
    $counts[(int) $type['id']] = count($content->entries(['type_id' => (int) $type['id']]));
}

$pageTitle = 'Content types';
require __DIR__ . '/partials/studio-head.php';
?>
      <div class="studio-intro">
        <p class="eyebrow">Settings</p>
        <h1>Content types</h1>
        <a class="studio-back" href="studio.php">&#8592; All content</a>
      </div>

<?php if ($saved !== null): ?>
      <p class="notice ok"><?= e($saved) ?></p>
<?php endif; ?>
<?php if ($error !== null): ?>
      <p class="notice error"><?= e($error) ?></p>
<?php endif; ?>
<?php if (isset($_GET['first'])): ?>
      <p class="notice ok">Add a content type before writing your first entry.</p>
<?php endif; ?>

      <p class="hint">A type's placement decides where its entries appear:
        <strong>Project card</strong> puts them on the home page,
        <strong>Writing</strong> puts them in the writing section.</p>

      <?php foreach ($types as $type): ?>
      <form class="studio-form type-row" id="type-<?= (int) $type['id'] ?>" method="post" action="types.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $type['id'] ?>" />
        <div class="row">
          <div class="field">
            <label for="name-<?= (int) $type['id'] ?>">Name</label>
            <input type="text" id="name-<?= (int) $type['id'] ?>" name="name" value="<?= e($type['name']) ?>" required />
          </div>
          <div class="field">
            <label for="placement-<?= (int) $type['id'] ?>">Placement</label>
            <select id="placement-<?= (int) $type['id'] ?>" name="placement">
            <?php foreach (Content::PLACEMENTS as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= $type['placement'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
            </select>
          </div>
          <div class="field narrow">
            <label for="order-<?= (int) $type['id'] ?>">Order</label>
            <input type="number" id="order-<?= (int) $type['id'] ?>" name="sort_order" value="<?= (int) $type['sort_order'] ?>" />
          </div>
        </div>
        <div class="studio-actions">
          <button class="submit" type="submit" name="action" value="update">Save</button>
          <button class="danger" type="submit" name="action" value="delete"
                  data-confirm="Delete the type &quot;<?= e($type['name']) ?>&quot;?">Delete</button>
          <span class="hint"><?= (int) $counts[(int) $type['id']] ?> entr<?= $counts[(int) $type['id']] === 1 ? 'y' : 'ies' ?></span>
        </div>
      </form>
      <?php endforeach; ?>

      <form class="studio-form panel-block" method="post" action="types.php">
        <?= Csrf::field() ?>
        <h2>Add a type</h2>
        <div class="row">
          <div class="field">
            <label for="new-name">Name</label>
            <input type="text" id="new-name" name="name" placeholder="e.g. Transliteration" required />
          </div>
          <div class="field">
            <label for="new-placement">Placement</label>
            <select id="new-placement" name="placement">
            <?php foreach (Content::PLACEMENTS as $value => $label): ?>
              <option value="<?= e($value) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
            </select>
          </div>
          <div class="field narrow">
            <label for="new-order">Order</label>
            <input type="number" id="new-order" name="sort_order" value="0" />
          </div>
        </div>
        <div class="studio-actions">
          <button class="submit" type="submit" name="action" value="create">Add type &#8599;</button>
        </div>
      </form>
<?php require __DIR__ . '/partials/studio-foot.php';
