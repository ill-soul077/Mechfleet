<?php
// includes/auth.php
// Minimal session-based auth for manager-only pages.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_login(string $username): void {
    $_SESSION['manager_user'] = $username;
}

function auth_logout(): void {
    unset($_SESSION['manager_user']);
}

function auth_is_logged_in(): bool {
    return isset($_SESSION['manager_user']);
}

function auth_require_login(): void {
    if (!auth_is_logged_in()) {
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/public/sql_demos.php'));
        exit;
    }
}
