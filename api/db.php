<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    /* Local dev convenience: create the database on first run. Shared hosts such as
       cPanel pre-create the database and deny CREATE, so a failure here is expected. */
    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET ' . DB_CHARSET . ' COLLATE ' . DB_CHARSET . '_unicode_ci');
    } catch (PDOException $e) {
        // No CREATE privilege — assume the database already exists.
    }
    $pdo->exec('USE `' . DB_NAME . '`');
} catch (PDOException $e) {
    wa_json(['ok' => false, 'message' => 'Database connection failed. Check api/config.php credentials.'], 500);
}

$pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT "pending",
    email_verified_at DATETIME NULL,
    phone_verified_at DATETIME NULL,
    google_id VARCHAR(190) NULL,
    facebook_id VARCHAR(190) NULL,
    created_at DATETIME NOT NULL,
    activated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$pdo->exec('CREATE TABLE IF NOT EXISTS otp_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    channel VARCHAR(20) NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at INT NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    KEY idx_otp_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$pdo->exec('CREATE TABLE IF NOT EXISTS activation_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at INT NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$pdo->exec('CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at INT NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$pdo->exec('CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    order_id VARCHAR(40) NULL,
    author VARCHAR(150) NOT NULL,
    rating TINYINT(1) NOT NULL,
    comment TEXT NOT NULL,
    images TEXT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_reviews_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at INT NOT NULL,
    KEY idx_attempts (identifier, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

function wa_now(): string { return date('Y-m-d H:i:s'); }
