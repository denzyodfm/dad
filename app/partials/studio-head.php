<?php
declare(strict_types=1);

namespace App;

// Include-only: never reachable as a page.
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

if (!isset($pageTitle)) {
    $pageTitle = 'Content studio';
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
  <link rel="stylesheet" href="studio.css" />
  <script src="studio.js" defer></script>
</head>
<body class="studio">
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="studio-bar">
    <a class="brand" href="studio.php"><span class="brand-mark">DD</span>
      <span>Content Studio<small><?= e($currentUser['display_name'] ?? '') ?></small></span></a>
    <nav class="studio-nav">
      <a href="../">View site</a>
      <a href="account.php">Account</a>
      <form class="signout" method="post" action="logout.php">
        <?= Csrf::field() ?>
        <button type="submit">Sign out</button>
      </form>
    </nav>
  </header>
  <div class="studio-shell">
    <main id="main">
