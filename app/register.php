<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

/**
 * Self-registration is closed: this is a single-administrator site.
 *
 * Accounts are created from the command line with:
 *   php tools/create_admin.php <email> "<Name>"
 *
 * The page stays so that an old link explains itself rather than 404ing.
 */

if ($auth->user() !== null) {
    Http::redirect('account.php');
}

$pageTitle = 'Registration closed';
$currentUser = null;
require __DIR__ . '/partials/head.php';
?>
      <div>
        <p class="eyebrow">Account</p>
        <h1>Accounts are<br /><span>created by hand.</span></h1>
      </div>

      <p>This site has a single administrator, so there is no public sign-up.
        Accounts are created from the command line with
        <code>php tools/create_admin.php</code>.</p>

      <p class="auth-alt">Have an account? <a href="login.php">Sign in</a>.</p>
<?php require __DIR__ . '/partials/foot.php';
