<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

// Sign-out changes state, so it is POST-only and CSRF-checked; a GET link
// could otherwise be triggered from anywhere.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !Csrf::isValid()) {
    Http::redirect('account.php');
}

$auth->logout();
Http::redirect('login.php');
