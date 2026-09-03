<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

$currentUser = $auth->user();
if ($currentUser === null) {
    Http::redirect('login.php');
}

$error = null;
$success = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::isValid()) {
        $error = 'That form expired. Please try again.';
    } else {
        try {
            $auth->changePassword(
                (int) $currentUser['id'],
                (string) ($_POST['current_password'] ?? ''),
                (string) ($_POST['new_password'] ?? '')
            );
            // Every session was revoked, including this one, so sign back in.
            $success = 'Your password was changed. Please sign in again.';
            $currentUser = null;
        } catch (ValidationException $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Your account';
require __DIR__ . '/partials/head.php';
?>
      <div>
        <p class="eyebrow">Account</p>
        <h1>Your<br /><span>account.</span></h1>
      </div>

<?php if ($success !== null): ?>
      <p class="notice ok"><?= e($success) ?></p>
      <p class="auth-alt"><a href="login.php">Sign in again</a></p>
<?php else: ?>
<?php if ($error !== null): ?>
      <p class="notice error"><?= e($error) ?></p>
<?php endif; ?>

      <div class="account-facts">
        <div><span>Name</span><strong><?= e($currentUser['display_name']) ?></strong></div>
        <div><span>Email</span><strong><?= e($currentUser['email']) ?></strong></div>
        <div><span>Role</span><strong><?= e(ucfirst((string) $currentUser['role'])) ?></strong></div>
        <div><span>Last signed in</span><strong><?=
          e($currentUser['last_login_at'] !== null
            ? gmdate('j M Y, H:i', strtotime((string) $currentUser['last_login_at'])) . ' UTC'
            : 'This is your first visit') ?></strong></div>
      </div>

      <p class="auth-alt"><a href="studio.php">Open the content studio</a></p>

      <section class="panel">
        <h2>Change password</h2>
        <form method="post" action="account.php" novalidate>
          <?= Csrf::field() ?>
          <div class="field">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password"
                   autocomplete="current-password" required />
          </div>
          <div class="field">
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password"
                   autocomplete="new-password" required />
            <p class="hint">At least <?= Auth::MIN_PASSWORD_LENGTH ?> characters. Changing it signs
              you out everywhere, including here.</p>
          </div>
          <button class="submit" type="submit">Change password &#8599;</button>
        </form>
      </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/foot.php';
