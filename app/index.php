<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/bootstrap.php';

Http::redirect($auth->user() !== null ? 'studio.php' : 'login.php');
