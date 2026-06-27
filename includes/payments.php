<?php
require_once __DIR__ . '/functions.php';

const FREE_LINK_LIMIT = 2;
const FREE_TRIAL_DAYS = 2;

function ensurePaymentSchema(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo = getDB();

    $pdo->exec("
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
        )
    ");

    $pdo->exec("
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
        )
    ");

    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_premium'")->fetch();
    if (!$cols) {
        $pdo->exec('ALTER TABLE users ADD COLUMN is_premium TINYINT(1) NOT NULL DEFAULT 0 AFTER is_banned');
    }

    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'premium_until'")->fetch();
    if (!$cols) {
        $pdo->exec('ALTER TABLE users ADD COLUMN premium_until DATETIME NULL DEFAULT NULL AFTER is_premium');
    }

    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'account_expires_at'")->fetch();
    if (!$cols) {
        $pdo->exec('ALTER TABLE users ADD COLUMN account_expires_at DATETIME NULL DEFAULT NULL AFTER premium_until');
    }

    // Backfill trial expiry for existing free users
    $pdo->exec('
        UPDATE users
        SET account_expires_at = DATE_ADD(created_at, INTERVAL ' . FREE_TRIAL_DAYS . ' DAY)
        WHERE account_expires_at IS NULL AND is_premium = 0 AND is_admin = 0
    ');

    // Paid users should never have an account expiry
    $pdo->exec('UPDATE users SET account_expires_at = NULL WHERE is_premium = 1');

    $exists = $pdo->query('SELECT id FROM payment_settings WHERE id = 1')->fetch();
    if (!$exists) {
        $pdo->exec("INSERT INTO payment_settings (id, instructions) VALUES (1, '1. Scan the GCash QR code or send payment to the number shown.\n2. Take a screenshot of your successful payment.\n3. Upload the screenshot below and wait for admin verification.')");
    }

    $done = true;
}

function getPaymentSettings(): array
{
    ensurePaymentSchema();
    $row = getDB()->query('SELECT * FROM payment_settings WHERE id = 1')->fetch();
    return $row ?: [];
}

function updatePaymentSettings(array $data): void
{
    ensurePaymentSchema();
    $stmt = getDB()->prepare('
        UPDATE payment_settings SET
            gcash_number = ?,
            gcash_name = ?,
            amount = ?,
            plan_label = ?,
            duration_days = ?,
            instructions = ?,
            gcash_qr = COALESCE(?, gcash_qr)
        WHERE id = 1
    ');
    $stmt->execute([
        $data['gcash_number'] ?? null,
        $data['gcash_name'] ?? null,
        $data['amount'] ?? 99.00,
        $data['plan_label'] ?? 'Premium (30 days)',
        $data['duration_days'] ?? 30,
        $data['instructions'] ?? null,
        $data['gcash_qr'] ?? null,
    ]);
}

function uploadGcashQr(array $file): ?string
{
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return null;
    }
    $filename = 'gcash_qr_' . time() . '.' . $ext;
    $dir = __DIR__ . '/../uploads/gcash/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return 'uploads/gcash/' . $filename;
    }
    return null;
}

function uploadPaymentProof(array $file, int $userId): ?string
{
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return null;
    }
    $filename = 'proof_' . $userId . '_' . time() . '.' . $ext;
    $dir = __DIR__ . '/../uploads/payments/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return 'uploads/payments/' . $filename;
    }
    return null;
}

function getUserPaymentSubmissions(int $userId): array
{
    ensurePaymentSchema();
    $stmt = getDB()->prepare('SELECT * FROM payment_submissions WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUserPendingPayment(int $userId): ?array
{
    ensurePaymentSchema();
    $stmt = getDB()->prepare("SELECT * FROM payment_submissions WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getPaymentSubmissions(string $status = 'all'): array
{
    ensurePaymentSchema();
    $sql = '
        SELECT ps.*, u.username, u.email, u.display_name
        FROM payment_submissions ps
        JOIN users u ON u.id = ps.user_id
    ';
    if ($status !== 'all') {
        $sql .= ' WHERE ps.status = ?';
    }
    $sql .= ' ORDER BY FIELD(ps.status, \'pending\', \'approved\', \'rejected\'), ps.created_at DESC';

    $stmt = getDB()->prepare($sql);
    if ($status !== 'all') {
        $stmt->execute([$status]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function countPendingPayments(): int
{
    ensurePaymentSchema();
    return (int) getDB()->query("SELECT COUNT(*) FROM payment_submissions WHERE status = 'pending'")->fetchColumn();
}

function countUserActiveLinks(int $userId): int
{
    ensurePaymentSchema();
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM social_links WHERE user_id = ? AND is_archived = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function isAccountExpired(array $user): bool
{
    ensurePaymentSchema();
    if (!empty($user['is_admin'])) {
        return false;
    }
    if (isUserPremium($user)) {
        return false;
    }
    if (empty($user['account_expires_at'])) {
        return false;
    }
    return strtotime($user['account_expires_at']) <= time();
}

function setFreeTrialExpiry(int $userId): void
{
    ensurePaymentSchema();
    $expires = date('Y-m-d H:i:s', strtotime('+' . FREE_TRIAL_DAYS . ' days'));
    getDB()->prepare('
        UPDATE users SET account_expires_at = ?
        WHERE id = ? AND is_premium = 0 AND is_admin = 0
    ')->execute([$expires, $userId]);
}

function activatePremium(int $userId): void
{
    ensurePaymentSchema();
    getDB()->prepare('
        UPDATE users SET is_premium = 1, premium_until = NULL, account_expires_at = NULL
        WHERE id = ?
    ')->execute([$userId]);
}

function canUserAddLink(array $user): array
{
    if (!empty($user['is_admin']) || isUserPremium($user)) {
        return ['allowed' => true, 'remaining' => null];
    }
    if (isAccountExpired($user)) {
        return [
            'allowed' => false,
            'error' => 'Your free trial has expired. Upgrade to Premium to add links.',
        ];
    }
    $active = countUserActiveLinks((int) $user['id']);
    if ($active >= FREE_LINK_LIMIT) {
        return [
            'allowed' => false,
            'error' => 'Free plan allows up to ' . FREE_LINK_LIMIT . ' links. Upgrade to Premium for unlimited links.',
            'remaining' => 0,
        ];
    }
    return ['allowed' => true, 'remaining' => FREE_LINK_LIMIT - $active];
}

function getUserPlanInfo(array $user): array
{
    $premium = isUserPremium($user);
    $activeLinks = countUserActiveLinks((int) $user['id']);
    $canAdd = canUserAddLink($user);

    return [
        'is_premium' => $premium,
        'is_admin' => !empty($user['is_admin']),
        'link_limit' => ($premium || !empty($user['is_admin'])) ? null : FREE_LINK_LIMIT,
        'active_links' => $activeLinks,
        'can_add_link' => $canAdd['allowed'],
        'remaining_links' => $canAdd['remaining'] ?? null,
        'account_expires_at' => ($premium || !empty($user['is_admin'])) ? null : ($user['account_expires_at'] ?? null),
        'is_expired' => isAccountExpired($user),
        'trial_days' => FREE_TRIAL_DAYS,
    ];
}

function isUserPremium(array $user): bool
{
    ensurePaymentSchema();
    if (!empty($user['is_premium']) && !empty($user['premium_until'])) {
        return strtotime($user['premium_until']) > time();
    }
    if (!empty($user['is_premium']) && empty($user['premium_until'])) {
        return true;
    }
    return false;
}

function syncPremiumStatus(int $userId): void
{
    ensurePaymentSchema();
    $stmt = getDB()->prepare('SELECT is_premium, premium_until, is_admin FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || !$user['is_premium'] || !empty($user['is_admin'])) {
        return;
    }
    // Legacy time-limited premium: downgrade when period ends
    if (!empty($user['premium_until']) && strtotime($user['premium_until']) <= time()) {
        getDB()->prepare('UPDATE users SET is_premium = 0, premium_until = NULL WHERE id = ?')->execute([$userId]);
    }
}

function submitPayment(int $userId, string $referenceNumber, array $proofFile): array
{
    ensurePaymentSchema();
    syncPremiumStatus($userId);

    if (getUserPendingPayment($userId)) {
        return ['success' => false, 'error' => 'You already have a payment pending verification. Please wait for admin approval.'];
    }

    $settings = getPaymentSettings();
    if (empty($settings['gcash_qr']) && empty($settings['gcash_number'])) {
        return ['success' => false, 'error' => 'Payment is not configured yet. Please contact the administrator.'];
    }

    $proofPath = uploadPaymentProof($proofFile, $userId);
    if (!$proofPath) {
        return ['success' => false, 'error' => 'Invalid proof image. Upload a JPG, PNG, GIF, or WebP screenshot.'];
    }

    $stmt = getDB()->prepare('
        INSERT INTO payment_submissions (user_id, reference_number, amount, proof_image, status)
        VALUES (?, ?, ?, ?, \'pending\')
    ');
    $stmt->execute([
        $userId,
        $referenceNumber ?: null,
        $settings['amount'],
        $proofPath,
    ]);

    return ['success' => true, 'id' => getDB()->lastInsertId()];
}

function reviewPayment(int $submissionId, int $adminId, string $decision, ?string $adminNote = null, ?int $durationDays = null): array
{
    ensurePaymentSchema();
    $stmt = getDB()->prepare('SELECT * FROM payment_submissions WHERE id = ?');
    $stmt->execute([$submissionId]);
    $submission = $stmt->fetch();

    if (!$submission) {
        return ['success' => false, 'error' => 'Submission not found.'];
    }
    if ($submission['status'] !== 'pending') {
        return ['success' => false, 'error' => 'This submission was already reviewed.'];
    }

    if ($decision === 'approve') {
        activatePremium($submission['user_id']);

        getDB()->prepare("
            UPDATE payment_submissions
            SET status = 'approved', admin_note = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ")->execute([$adminNote, $adminId, $submissionId]);

        $msg = 'Your GCash payment has been verified. You now have unlimited links and your account will not expire.';
        if ($adminNote) {
            $msg = 'Payment approved: ' . $adminNote . ' — Unlimited links unlocked.';
        }
        getDB()->prepare('INSERT INTO admin_messages (user_id, message) VALUES (?, ?)')
            ->execute([$submission['user_id'], $msg]);

        return ['success' => true];
    }

    if ($decision === 'reject') {
        if (!$adminNote) {
            return ['success' => false, 'error' => 'Please provide a reason for rejection.'];
        }

        getDB()->prepare("
            UPDATE payment_submissions
            SET status = 'rejected', admin_note = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ")->execute([$adminNote, $adminId, $submissionId]);

        getDB()->prepare('INSERT INTO admin_messages (user_id, message) VALUES (?, ?)')
            ->execute([$submission['user_id'], 'Payment rejected: ' . $adminNote]);

        return ['success' => true];
    }

    return ['success' => false, 'error' => 'Invalid decision.'];
}

function paymentStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => ucfirst($status),
    };
}
