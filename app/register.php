<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

if ($auth->user() !== null) {
    Http::redirect('account.php');
}

$error = null;
$email = '';
$displayName = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    if (!Csrf::isValid()) {
        $error = 'That form expired. Please try again.';
    } else {
        try {
            $userId = $auth->register($email, (string) ($_POST['password'] ?? ''), $displayName);
            $auth->startSessionFor($userId);
            Http::redirect('account.php');
        } catch (ValidationException $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Create an account';
$currentUser = null;
require __DIR__ . '/partials/head.php';
?>
      <div>
        <p class="eyebrow">Account</p>
        <h1>Create<br /><span>an account.</span></h1>
      </div>

<?php if ($error !== null): ?>
      <p class="notice error"><?= e($error) ?></p>
<?php endif; ?>

      <form method="post" action="register.php" novalidate>
        <?= Csrf::field() ?>
        <div class="field">
          <label for="display_name">Name</label>
          <input type="text" id="display_name" name="display_name" value="<?= e($displayName) ?>"
                 maxlength="120" autocomplete="name" required autofocus />
        </div>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>"
                 autocomplete="username" required />
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 autocomplete="new-password" required />
          <p class="hint">At least <?= Auth::MIN_PASSWORD_LENGTH ?> characters. A short phrase you
            will remember beats a short string of symbols.</p>
        </div>
        <button class="submit" type="submit">Create account &#8599;</button>
      </form>

      <p class="auth-alt">Already registered? <a href="login.php">Sign in</a>.</p>
<?php require __DIR__ . '/partials/foot.php';
