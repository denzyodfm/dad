<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

$currentUser = $auth->requireAdmin();
$content = new Content($pdo);
$uploads = new Uploads(dirname(__DIR__) . '/output/uploads');

$types = $content->types();
if ($types === []) {
    Http::redirect('types.php?first=1');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$entry = $id > 0 ? $content->entry($id) : null;
if ($id > 0 && $entry === null) {
    http_response_code(404);
    exit('That entry does not exist.');
}

$error = null;
$isNew = $entry === null;

// The form redisplays what was typed, so failures never lose the work.
$form = [
    'type_id' => (int) ($entry['type_id'] ?? $types[0]['id']),
    'title' => (string) ($entry['title'] ?? ''),
    'card_heading' => (string) ($entry['card_heading'] ?? ''),
    'summary' => (string) ($entry['summary'] ?? ''),
    'category' => (string) ($entry['category'] ?? ''),
    'kicker' => (string) ($entry['kicker'] ?? ''),
    'meta' => (string) ($entry['meta'] ?? ''),
    'accent' => (string) ($entry['accent'] ?? 'cobalt'),
    'status' => (string) ($entry['status'] ?? 'draft'),
    'published_at' => (string) ($entry['published_at'] ?? gmdate('Y-m-d')),
    'sort_order' => (int) ($entry['sort_order'] ?? 0),
    'link_url' => (string) ($entry['link_url'] ?? ''),
    'link_label' => (string) ($entry['link_label'] ?? ''),
    'cover_alt' => (string) ($entry['cover_alt'] ?? ''),
    'body' => (string) ($entry['body'] ?? ''),
];
$facts = $entry['facts'] ?? [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (array_keys($form) as $field) {
        if (isset($_POST[$field])) {
            $form[$field] = is_string($_POST[$field]) ? trim($_POST[$field]) : $_POST[$field];
        }
    }
    $postedFacts = [];
    foreach ((array) ($_POST['fact_label'] ?? []) as $index => $label) {
        $postedFacts[] = [
            'label' => (string) $label,
            'value' => (string) (($_POST['fact_value'] ?? [])[$index] ?? ''),
        ];
    }
    $facts = array_map(
        static fn(array $f): array => ['label' => $f['label'], 'value' => $f['value']],
        $postedFacts
    );

    if (!Csrf::isValid()) {
        $error = 'That form expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'delete' && !$isNew) {
        $uploads->delete($entry['cover_path']);
        $uploads->delete($entry['media_path']);
        $content->deleteEntry((int) $entry['id']);
        Http::redirect('studio.php');
    } else {
        try {
            $data = [
                'type_id' => (int) $form['type_id'],
                'title' => $form['title'],
                'card_heading' => $form['card_heading'] !== '' ? $form['card_heading'] : null,
                'summary' => $form['summary'] !== '' ? $form['summary'] : null,
                'body' => $form['body'] !== '' ? $form['body'] : null,
                'category' => $form['category'] !== '' ? $form['category'] : null,
                'kicker' => $form['kicker'] !== '' ? $form['kicker'] : null,
                'meta' => $form['meta'] !== '' ? $form['meta'] : null,
                'accent' => $form['accent'],
                'status' => $form['status'],
                'published_at' => $form['published_at'] !== '' ? $form['published_at'] : null,
                'sort_order' => (int) $form['sort_order'],
                'link_url' => $form['link_url'] !== '' ? $form['link_url'] : null,
                'link_label' => $form['link_label'] !== '' ? $form['link_label'] : null,
                'cover_alt' => $form['cover_alt'] !== '' ? $form['cover_alt'] : null,
                'author_id' => (int) $currentUser['id'],
            ];

            $newCover = $uploads->storeImage($_FILES['cover'] ?? null);
            $newMedia = $uploads->storeMedia($_FILES['media'] ?? null);

            if ($newCover !== null) {
                $data['cover_path'] = $newCover;
            } elseif (!$isNew && ($_POST['remove_cover'] ?? '') === '1') {
                $data['cover_path'] = null;
                $data['cover_alt'] = null;
            }
            if ($newMedia !== null) {
                [$data['media_path'], $data['media_kind']] = $newMedia;
            } elseif (!$isNew && ($_POST['remove_media'] ?? '') === '1') {
                $data['media_path'] = null;
                $data['media_kind'] = null;
            }

            if ($isNew) {
                $entryId = $content->createEntry($data);
            } else {
                $entryId = (int) $entry['id'];
                $content->updateEntry($entryId, $data);
                // Replaced or removed files are deleted only once the row is saved.
                // Test with array_key_exists, not ??, which reads a deliberate
                // null as absent and would leave the old file orphaned on disk.
                $coverCleared = array_key_exists('cover_path', $data) && $data['cover_path'] === null;
                $mediaCleared = array_key_exists('media_path', $data) && $data['media_path'] === null;
                if ($newCover !== null || $coverCleared) {
                    $uploads->delete($entry['cover_path']);
                }
                if ($newMedia !== null || $mediaCleared) {
                    $uploads->delete($entry['media_path']);
                }
            }
            $content->replaceFacts($entryId, $facts);
            Http::redirect('entry-edit.php?id=' . $entryId . '&saved=1');
        } catch (ValidationException $e) {
            $error = $e->getMessage();
        }
    }
}

while (count($facts) < 4) {
    $facts[] = ['label' => '', 'value' => ''];
}

$selectedType = null;
foreach ($types as $type) {
    if ((int) $type['id'] === (int) $form['type_id']) {
        $selectedType = $type;
    }
}
$isCard = ($selectedType['placement'] ?? 'writing') === 'portfolio';

$pageTitle = $isNew ? 'Publish something new' : 'Edit entry';
require __DIR__ . '/partials/studio-head.php';
?>
      <div class="studio-intro">
        <p class="eyebrow"><?= $isNew ? 'New entry' : 'Editing' ?></p>
        <h1><?= $isNew ? 'Publish something new' : e($entry['title']) ?></h1>
        <a class="studio-back" href="studio.php">&#8592; All content</a>
      </div>

<?php if (isset($_GET['saved'])): ?>
      <p class="notice ok">Saved.<?php if (!$isNew): ?>
        <a href="../<?= $entry['placement'] === 'writing'
            ? 'entry.php?slug=' . urlencode((string) $entry['slug'])
            : '' ?>"><?= $entry['status'] === 'published' ? 'View it on the site' : 'Preview the draft' ?></a>.<?php endif; ?></p>
<?php endif; ?>
<?php if ($error !== null): ?>
      <p class="notice error"><?= e($error) ?></p>
<?php endif; ?>

      <form class="studio-form" method="post" enctype="multipart/form-data"
            action="entry-edit.php<?= $isNew ? '' : '?id=' . (int) $entry['id'] ?>">
        <?= Csrf::field() ?>

        <div class="row">
          <div class="field">
            <label for="type_id">Content type</label>
            <select id="type_id" name="type_id" required>
            <?php foreach ($types as $type): ?>
              <option value="<?= (int) $type['id'] ?>" <?= (int) $type['id'] === (int) $form['type_id'] ? 'selected' : '' ?>>
                <?= e($type['name']) ?> — <?= e(Content::PLACEMENTS[$type['placement']] ?? '') ?>
              </option>
            <?php endforeach; ?>
            </select>
            <p class="hint">Save to switch the form between card fields and writing fields.</p>
          </div>
          <div class="field">
            <label for="published_at">Publication date</label>
            <input type="date" id="published_at" name="published_at" value="<?= e($form['published_at']) ?>" />
          </div>
        </div>

        <div class="field">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" value="<?= e($form['title']) ?>"
                 maxlength="200" placeholder="Give this entry a clear title" required />
        </div>

        <div class="field">
          <label for="summary">Short introduction</label>
          <textarea id="summary" name="summary" rows="3"
                    placeholder="A concise summary shown on content cards"><?= e($form['summary']) ?></textarea>
        </div>

        <div class="row">
          <div class="field">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" value="<?= e($form['category']) ?>"
                   placeholder="e.g. Methodology" />
          </div>
          <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
            <?php foreach (Content::STATUSES as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= $form['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
            </select>
          </div>
        </div>

        <fieldset class="panel-block<?= $isCard ? '' : ' is-muted' ?>">
          <legend>Project card<?= $isCard ? '' : ' (unused by this type)' ?></legend>
          <div class="row">
            <div class="field">
              <label for="card_heading">Card heading</label>
              <input type="text" id="card_heading" name="card_heading" value="<?= e($form['card_heading']) ?>"
                     placeholder="Leave blank to use the title" />
              <p class="hint">A vertical bar forces a line break: <code>Resort|Booking</code></p>
            </div>
            <div class="field">
              <label for="accent">Card colour</label>
              <select id="accent" name="accent">
              <?php foreach (Content::ACCENTS as $accent): ?>
                <option value="<?= e($accent) ?>" <?= $form['accent'] === $accent ? 'selected' : '' ?>><?= e($accent) ?></option>
              <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="field">
              <label for="kicker">Small label</label>
              <input type="text" id="kicker" name="kicker" value="<?= e($form['kicker']) ?>"
                     placeholder="e.g. Multi-branch operations" />
            </div>
            <div class="field">
              <label for="meta">Corner label</label>
              <input type="text" id="meta" name="meta" value="<?= e($form['meta']) ?>"
                     placeholder="e.g. FileMaker · API" />
            </div>
          </div>
          <div class="row">
            <div class="field">
              <label for="sort_order">Order</label>
              <input type="number" id="sort_order" name="sort_order" value="<?= (int) $form['sort_order'] ?>" />
            </div>
            <div class="field">
              <label for="link_label">Link label</label>
              <input type="text" id="link_label" name="link_label" value="<?= e($form['link_label']) ?>"
                     placeholder="e.g. View repository" />
            </div>
          </div>
          <div class="field">
            <label for="link_url">Link address</label>
            <input type="url" id="link_url" name="link_url" value="<?= e($form['link_url']) ?>"
                   placeholder="https://github.com/..." />
          </div>
        </fieldset>

        <div class="panel-block media-block">
          <div class="media-preview">
            <?php if (!empty($entry['cover_path'])): ?>
              <img src="../output/uploads/<?= e($entry['cover_path']) ?>" alt="" />
            <?php else: ?>
              <span>Picture preview</span>
            <?php endif; ?>
          </div>
          <div class="media-fields">
            <div class="field">
              <label for="cover">Cover picture</label>
              <input type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp,image/gif" />
              <p class="hint">JPG, PNG, WebP or GIF · up to 8 MB</p>
              <?php if (!empty($entry['cover_path'])): ?>
              <label class="checkline"><input type="checkbox" name="remove_cover" value="1" /> Remove the current picture</label>
              <?php endif; ?>
            </div>
            <div class="field">
              <label for="cover_alt">Picture description</label>
              <input type="text" id="cover_alt" name="cover_alt" value="<?= e($form['cover_alt']) ?>"
                     placeholder="Describe the picture for visitors using screen readers" />
            </div>
          </div>
        </div>

        <div class="panel-block">
          <div class="field">
            <label for="media">Audio or video recording</label>
            <input type="file" id="media" name="media"
                   accept="audio/*,video/mp4,video/webm,video/ogg,video/quicktime" />
            <p class="hint">MP3, WAV, OGG, M4A, MP4, WebM or MOV · up to 60 MB</p>
            <?php if (!empty($entry['media_path'])): ?>
              <?= media_player(['media_path' => $entry['media_path'], 'media_kind' => $entry['media_kind'], 'title' => $entry['title']]) ?>
              <label class="checkline"><input type="checkbox" name="remove_media" value="1" /> Remove the current recording</label>
            <?php endif; ?>
          </div>
        </div>

        <fieldset class="panel-block">
          <legend>Detail facts</legend>
          <p class="hint">Label and value pairs shown in the entry's panel. Blank rows are ignored.</p>
          <?php foreach ($facts as $fact): ?>
          <div class="row">
            <div class="field">
              <input type="text" name="fact_label[]" value="<?= e($fact['label']) ?>" placeholder="Label" />
            </div>
            <div class="field">
              <input type="text" name="fact_value[]" value="<?= e($fact['value']) ?>" placeholder="Value" />
            </div>
          </div>
          <?php endforeach; ?>
        </fieldset>

        <div class="field">
          <label for="body">Content</label>
          <p class="hint">Simple HTML is supported: &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;blockquote&gt;, &lt;a&gt;</p>
          <textarea id="body" name="body" rows="14" class="code"
                    placeholder="&lt;p&gt;Begin your text here...&lt;/p&gt;"><?= e($form['body']) ?></textarea>
        </div>

        <div class="studio-actions">
          <button class="submit" type="submit">Save entry &#8599;</button>
          <?php if (!$isNew): ?>
          <button class="danger" type="submit" name="action" value="delete"
                  data-confirm="Delete this entry and its uploads permanently?">Delete</button>
          <?php endif; ?>
        </div>
      </form>
<?php require __DIR__ . '/partials/studio-foot.php';
