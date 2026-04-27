<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

logout_user();
session_start();
set_flash('success', 'Anda sudah logout.');
redirect('login.php');
