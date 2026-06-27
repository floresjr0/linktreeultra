-- Payment tables migration (also applied automatically by includes/payments.php)

CREATE TABLE IF NOT EXISTS payment_settings (
    id INT PRIMARY KEY DEFAULT 1,
    gcash_qr VARCHAR(255) DEFAULT NULL,
    gcash_number VARCHAR(50) DEFAULT NULL,
    gcash_name VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 99.00,
    plan_label VARCHAR(100) NOT NULL DEFAULT 'Premium (30 days)',
    duration_days INT NOT NULL DEFAULT 30,
    instructions TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reference_number VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    proof_image VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
);

INSERT IGNORE INTO payment_settings (id, instructions) VALUES (1,
'1. Scan the GCash QR code or send payment to the number shown.
2. Take a screenshot of your successful payment.
3. Upload the screenshot below and wait for admin verification.');
