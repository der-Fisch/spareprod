<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function current_path(): string
{
    return basename($_SERVER['PHP_SELF']);
}

function nav_active(array $paths): string
{
    return in_array(current_path(), $paths, true) ? 'is-active' : '';
}

function format_rupiah(int|float|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function generate_id(string $prefix): string
{
    return strtoupper($prefix) . date('YmdHis') . random_int(10, 99);
}

function first_letter(string $value): string
{
    return strtoupper(substr($value, 0, 1));
}
