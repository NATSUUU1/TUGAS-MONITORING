<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Kredensial khusus halaman Pengaturan. Hanya akun ini yang boleh
// membuka dan mengubah daftar akses login dashboard.
const SETTINGS_ADMIN_USERNAME = 'admin';
const SETTINGS_ADMIN_PASSWORD = '1175';

function settingsAdminIsAuthenticated(): bool
{
    return ($_SESSION['settings_admin_authenticated'] ?? false) === true;
}

function settingsAdminLogin(string $username, string $password): bool
{
    if (!hash_equals(SETTINGS_ADMIN_USERNAME, $username)
        || !hash_equals(SETTINGS_ADMIN_PASSWORD, $password)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['settings_admin_authenticated'] = true;
    $_SESSION['dashboard_csrf_token'] = bin2hex(random_bytes(32));

    return true;
}

function settingsAdminLogout(): void
{
    unset($_SESSION['settings_admin_authenticated']);
}
