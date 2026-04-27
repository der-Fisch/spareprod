<?php

declare(strict_types=1);

function find_user_by_id(mysqli $conn, string $id): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, username, email, password, role, created_at FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    return $user;
}

function find_user_by_username(mysqli $conn, string $username): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, username, email, password, role, created_at FROM users WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    return $user;
}

function attempt_login(mysqli $conn, string $username, string $password): bool
{
    $user = find_user_by_username($conn, $username);

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    $_SESSION['user_id'] = $user['id'];

    return true;
}

function current_user(mysqli $conn): ?array
{
    static $user = null;
    static $resolved = false;

    if ($resolved) {
        return $user;
    }

    $resolved = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $user = find_user_by_id($conn, $_SESSION['user_id']);

    if (!$user) {
        unset($_SESSION['user_id']);
    }

    return $user;
}

function require_login(mysqli $conn): array
{
    $user = current_user($conn);

    if (!$user) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }

    return $user;
}

function require_role(mysqli $conn, string $role): array
{
    $user = require_login($conn);

    if ($user['role'] !== $role) {
        set_flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect('dashboard.php');
    }

    return $user;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
