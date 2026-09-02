<?php
declare(strict_types=1);

namespace App;

// Include-only: never reachable as a page.
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

if (!isset($pageTitle)) {
    $pageTitle = 'Account';
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="theme-color" content="#f2f0e9" />
  <title><?= e($pageTitle) ?> — Dennis Dizon</title>
  <link rel="icon" href="../favicon.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="../fonts/fonts.css" />
  <link rel="stylesheet" href="../styles.css" />
  <link rel="stylesheet" href="auth.css" />
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="shell">
    <header class="site-header">
      <a class="brand" href="../"><span class="brand-mark">DD</span><span>Dennis Dizon</span></a>
      <p class="role">FileMaker Developer / IT Specialist</p>
      <div class="header-actions">
<?php if (isset($currentUser) && $currentUser !== null): ?>
        <span><?= e($currentUser['display_name']) ?></span>
        <form class="signout" method="post" action="logout.php">
          <?= Csrf::field() ?>
          <button type="submit">Sign out</button>
        </form>
<?php else: ?>
        <a href="login.php">Sign in</a>
<?php endif; ?>
      </div>
    </header>
    <main id="main" class="auth">
