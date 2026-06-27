-- MarteLinks Database Setup
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS martelinks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE martelinks;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    theme VARCHAR(50) DEFAULT 'default',
    bg_color VARCHAR(20) DEFAULT '#1a1a2e',
    text_color VARCHAR(20) DEFAULT '#ffffff',
    button_color VARCHAR(20) DEFAULT '#e94560',
    button_text_color VARCHAR(20) DEFAULT '#ffffff',
    is_admin TINYINT(1) DEFAULT 0,
    is_banned TINYINT(1) DEFAULT 0,
    is_premium TINYINT(1) DEFAULT 0,
    premium_until DATETIME DEFAULT NULL,
    account_expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    platform VARCHAR(50) DEFAULT 'custom',
    icon VARCHAR(50) DEFAULT 'link',
    sort_order INT DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    click_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_archived)
);

CREATE TABLE admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default admin and demo users are created by install.php

-- GCash manual payment (see includes/payments.php for runtime migration)
-- Tables: payment_settings, payment_submissions
