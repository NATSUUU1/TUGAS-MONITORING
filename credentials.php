<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

const DASHBOARD_DEFAULT_USERNAME = 'user';
const DASHBOARD_DEFAULT_PASSWORD = '123';
const DASHBOARD_USERNAME_MIN_LENGTH = 3;
const DASHBOARD_USERNAME_MAX_LENGTH = 60;
const DASHBOARD_PASSWORD_MIN_LENGTH = 6;
const DASHBOARD_PASSWORD_MAX_LENGTH = 72;

/**
 * Memastikan tabel akun dashboard tersedia dan terisi satu baris default.
 */
function dashboardEnsureAccountStorage(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS dashboard_account (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(60) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Migrasi otomatis: tambah kolom created_at jika belum ada (skema lama).
    $columns = $pdo->query("SHOW COLUMNS FROM dashboard_account LIKE 'created_at'")->fetchAll();
    if (count($columns) === 0) {
        $pdo->exec(
            'ALTER TABLE dashboard_account
             ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER password_hash'
        );
    }

    // Migrasi otomatis: pastikan id bertipe INT UNSIGNED AUTO_INCREMENT.
    $idCol = $pdo->query("SHOW COLUMNS FROM dashboard_account LIKE 'id'")->fetch();
    if ($idCol && stripos((string) $idCol['Type'], 'int') !== false && stripos((string) $idCol['Type'], 'tinyint') !== false) {
        $pdo->exec('ALTER TABLE dashboard_account MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    // Migrasi otomatis: pastikan UNIQUE KEY pada username ada.
    $indexes = $pdo->query("SHOW INDEX FROM dashboard_account WHERE Key_name = 'uq_username'")->fetchAll();
    if (count($indexes) === 0) {
        try {
            $pdo->exec('ALTER TABLE dashboard_account ADD UNIQUE KEY uq_username (username)');
        } catch (PDOException $e) {
            // Abaikan jika sudah ada unique key dengan nama lain
        }
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM dashboard_account')->fetchColumn();
    if ($total === 0) {
        $statement = $pdo->prepare(
            'INSERT INTO dashboard_account (username, password_hash) VALUES (:username, :password_hash)'
        );
        $statement->execute([
            'username' => DASHBOARD_DEFAULT_USERNAME,
            'password_hash' => password_hash(DASHBOARD_DEFAULT_PASSWORD, PASSWORD_DEFAULT),
        ]);
    }
}

/**
 * Mengambil akun dashboard pertama (untuk kompatibilitas).
 */
function dashboardAccount(PDO $pdo): ?array
{
    dashboardEnsureAccountStorage($pdo);

    $row = $pdo->query(
        'SELECT id, username, password_hash FROM dashboard_account ORDER BY id ASC LIMIT 1'
    )->fetch();

    return $row === false ? null : $row;
}

/**
 * Mengambil semua akun dashboard yang terdaftar.
 */
function dashboardAllAccounts(PDO $pdo): array
{
    dashboardEnsureAccountStorage($pdo);

    return $pdo->query(
        'SELECT id, username, created_at, updated_at FROM dashboard_account ORDER BY id ASC'
    )->fetchAll();
}

/**
 * Mengambil satu akun berdasarkan ID.
 */
function dashboardAccountById(PDO $pdo, int $id): ?array
{
    dashboardEnsureAccountStorage($pdo);

    $stmt = $pdo->prepare('SELECT id, username, password_hash, created_at, updated_at FROM dashboard_account WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Mengambil akun berdasarkan username.
 */
function dashboardAccountByUsername(PDO $pdo, string $username): ?array
{
    dashboardEnsureAccountStorage($pdo);

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM dashboard_account WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Memverifikasi kombinasi username dan password terhadap akun tersimpan.
 */
function dashboardVerifyCredentials(PDO $pdo, string $username, string $password): bool
{
    $account = dashboardAccountByUsername($pdo, $username);
    if ($account === null) {
        return false;
    }

    return password_verify($password, (string) $account['password_hash']);
}

/**
 * Membuat akun baru. Mengembalikan true jika berhasil, false jika username sudah dipakai.
 */
function dashboardCreateAccount(PDO $pdo, string $username, string $password): bool
{
    dashboardEnsureAccountStorage($pdo);

    // Cek apakah username sudah terpakai
    $existing = dashboardAccountByUsername($pdo, $username);
    if ($existing !== null) {
        return false;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO dashboard_account (username, password_hash) VALUES (:username, :password_hash)'
    );
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return true;
}

/**
 * Menghapus akun berdasarkan ID. Mengembalikan true jika berhasil.
 */
function dashboardDeleteAccount(PDO $pdo, int $id): bool
{
    dashboardEnsureAccountStorage($pdo);

    // Jangan hapus jika hanya sisa 1 akun
    $total = (int) $pdo->query('SELECT COUNT(*) FROM dashboard_account')->fetchColumn();
    if ($total <= 1) {
        return false;
    }

    $stmt = $pdo->prepare('DELETE FROM dashboard_account WHERE id = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}

/**
 * Menghitung total akun.
 */
function dashboardCountAccounts(PDO $pdo): int
{
    dashboardEnsureAccountStorage($pdo);
    return (int) $pdo->query('SELECT COUNT(*) FROM dashboard_account')->fetchColumn();
}

/**
 * Menyimpan (mengubah) username dan password akun dashboard.
 */
function dashboardUpdateCredentials(PDO $pdo, string $username, string $password, int $id = 0): void
{
    dashboardEnsureAccountStorage($pdo);

    if ($id === 0) {
        $account = dashboardAccount($pdo);
        $id = (int) ($account['id'] ?? 0);
    }

    $statement = $pdo->prepare(
        'UPDATE dashboard_account SET username = :username, password_hash = :password_hash WHERE id = :id'
    );
    $statement->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => $id,
    ]);
}

/**
 * Mengubah hanya username akun tanpa menyentuh password.
 */
function dashboardUpdateUsername(PDO $pdo, string $username, int $id = 0): void
{
    dashboardEnsureAccountStorage($pdo);

    if ($id === 0) {
        $account = dashboardAccount($pdo);
        $id = (int) ($account['id'] ?? 0);
    }

    $statement = $pdo->prepare(
        'UPDATE dashboard_account SET username = :username WHERE id = :id'
    );
    $statement->execute([
        'username' => $username,
        'id' => $id,
    ]);
}

/**
 * Memvalidasi isian formulir akun dan mengembalikan daftar pesan kesalahan.
 * Password baru bersifat opsional: kosongkan kedua kolom password untuk
 * mempertahankan password yang sedang berlaku.
 */
function dashboardAccountInputErrors(
    string $username,
    string $password,
    string $confirmation
): array {
    $errors = [];

    $usernameLength = mb_strlen($username, 'UTF-8');
    if ($usernameLength < DASHBOARD_USERNAME_MIN_LENGTH || $usernameLength > DASHBOARD_USERNAME_MAX_LENGTH) {
        $errors[] = 'Username harus ' . DASHBOARD_USERNAME_MIN_LENGTH
            . '–' . DASHBOARD_USERNAME_MAX_LENGTH . ' karakter.';
    } elseif (preg_match('/^[A-Za-z0-9._@-]+$/', $username) !== 1) {
        $errors[] = 'Username hanya boleh berisi huruf, angka, titik, garis bawah, tanda hubung, dan simbol @.';
    }

    $wantsNewPassword = $password !== '' || $confirmation !== '';
    if ($wantsNewPassword) {
        $passwordLength = strlen($password);
        if ($passwordLength < DASHBOARD_PASSWORD_MIN_LENGTH || $passwordLength > DASHBOARD_PASSWORD_MAX_LENGTH) {
            $errors[] = 'Password baru harus ' . DASHBOARD_PASSWORD_MIN_LENGTH
                . '–' . DASHBOARD_PASSWORD_MAX_LENGTH . ' karakter.';
        } elseif ($password !== $confirmation) {
            $errors[] = 'Konfirmasi password baru tidak cocok dengan password baru.';
        }
    }

    return $errors;
}

/**
 * Memvalidasi isian formulir penambahan akun baru.
 */
function dashboardNewAccountInputErrors(string $username, string $password, string $confirmation): array
{
    $errors = [];

    $usernameLength = mb_strlen($username, 'UTF-8');
    if ($usernameLength < DASHBOARD_USERNAME_MIN_LENGTH || $usernameLength > DASHBOARD_USERNAME_MAX_LENGTH) {
        $errors[] = 'Username harus ' . DASHBOARD_USERNAME_MIN_LENGTH
            . '–' . DASHBOARD_USERNAME_MAX_LENGTH . ' karakter.';
    } elseif (preg_match('/^[A-Za-z0-9._@-]+$/', $username) !== 1) {
        $errors[] = 'Username hanya boleh berisi huruf, angka, titik, garis bawah, tanda hubung, dan simbol @.';
    }

    $passwordLength = strlen($password);
    if ($passwordLength < DASHBOARD_PASSWORD_MIN_LENGTH || $passwordLength > DASHBOARD_PASSWORD_MAX_LENGTH) {
        $errors[] = 'Password harus ' . DASHBOARD_PASSWORD_MIN_LENGTH
            . '–' . DASHBOARD_PASSWORD_MAX_LENGTH . ' karakter.';
    } elseif ($password !== $confirmation) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    return $errors;
}
