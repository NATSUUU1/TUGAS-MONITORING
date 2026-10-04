CREATE DATABASE IF NOT EXISTS data_sensor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE data_sensor;

CREATE TABLE IF NOT EXISTS data_sensor (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    waktu TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    suhu FLOAT NOT NULL,
    kelembapan FLOAT NOT NULL,
    asap INT NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Akun akses halaman dashboard. Dikelola lewat menu Pengaturan.
-- Mendukung banyak akun. Setiap akun memiliki username unik.
CREATE TABLE IF NOT EXISTS dashboard_account (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data awal opsional untuk mencoba tampilan dashboard.
INSERT INTO data_sensor (suhu, kelembapan, asap) VALUES
    (28.5, 65.2, 120),
    (29.1, 63.8, 180),
    (30.0, 61.4, 245);
