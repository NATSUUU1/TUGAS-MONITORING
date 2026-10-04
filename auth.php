<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Kredensial login tersimpan di database dan dikelola lewat halaman
// Pengaturan. Lihat config/credentials.php untuk fungsi terkait.

function dashboardIsAuthenticated(): bool
{
    return ($_SESSION['dashboard_authenticated'] ?? false) === true;
}

function dashboardCsrfToken(): string
{
    if (!isset($_SESSION['dashboard_csrf_token'])) {
        $_SESSION['dashboard_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['dashboard_csrf_token'];
}

function dashboardCsrfIsValid(string $token): bool
{
    return isset($_SESSION['dashboard_csrf_token'])
        && hash_equals($_SESSION['dashboard_csrf_token'], $token);
}

/**
 * Menyimpan pesan sekali-tampil (flash) untuk permintaan berikutnya.
 */
function dashboardFlash(string $type, string $message): void
{
    $_SESSION['dashboard_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Mengambil dan menghapus pesan flash yang tersimpan.
 */
function dashboardPullFlash(): ?array
{
    $flash = $_SESSION['dashboard_flash'] ?? null;
    unset($_SESSION['dashboard_flash']);

    return is_array($flash) ? $flash : null;
}

/**
 * Mengingat isian formulir agar tetap tampil setelah pengalihan halaman.
 */
function dashboardRememberInput(array $input): void
{
    $_SESSION['dashboard_old_input'] = $input;
}

/**
 * Mengambil dan menghapus isian formulir yang tersimpan.
 */
function dashboardPullInput(): array
{
    $input = $_SESSION['dashboard_old_input'] ?? [];
    unset($_SESSION['dashboard_old_input']);

    return is_array($input) ? $input : [];
}

function dashboardEndSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}
