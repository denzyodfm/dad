<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

if ($auth->user() !== null) {
    Http::redirect('account.php');
}

$error = null;
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!Csrf::isValid()) {
        $error = 'That form expired. Please try again.';
    } else {
        try {
            $auth->login($email, (string) ($_POST['password'] ?? ''));
            Http::redirect('account.php');
        } catch (ValidationException $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Sign in';
$currentUser = null;
require __DIR__ . '/partials/head.php';
?>
      <div>
        <p class="eyebrow">Account</p>
        <h1>Sign in<br /><span>to continue.</span></h1>
      </div>

<?php if ($error !== null): ?>
      <p class="notice error"><?= e($error) ?></p>
<?php endif; ?>

      <form method="post" action="login.php" novalidate>
        <?= Csrf::field() ?>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>"
                 autocomplete="username" required autofocus />
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 autocomplete="current-password" required />
        </div>
        <button class="submit" type="submit">Sign in &#8599;</button>
      </form>

      <p class="auth-alt">No account yet? <a href="register.php">Create one</a>.</p>
<?php require __DIR__ . '/partials/foot.php';
