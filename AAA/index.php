<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user($conn);

if ($user) {
    redirect('dashboard.php');
}

redirect('login.php');
