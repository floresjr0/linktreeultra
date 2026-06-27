<?php
/**
 * One-time setup script. Visit: http://localhost/linktreeultra/install.php
 * Delete this file after successful installation.
 */
require_once __DIR__ . '/config/database.php';

$step = $_GET['step'] ?? 'run';
$messages = [];

try {
  // Create DB if not exists
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE ' . DB_NAME);

    $sql = file_get_contents(__DIR__ . '/setup.sql');
    // Remove CREATE DATABASE and USE statements as we already handled them
    $sql = preg_replace('/CREATE DATABASE.*?;/s', '', $sql);
    $sql = preg_replace('/USE martelinks;/', '', $sql);
    $pdo->exec($sql);

    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $demoHash = password_hash('demo123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    if (!$stmt->fetch()) {
        $pdo->prepare('INSERT INTO users (username, email, password, display_name, is_admin) VALUES (?, ?, ?, ?, 1)')
            ->execute(['admin', 'admin@martelinks.com', $adminHash, 'Administrator']);
        $messages[] = 'Admin user created (admin / admin123)';
    }

    $stmt->execute(['demo']);
    if (!$stmt->fetch()) {
        $pdo->prepare('INSERT INTO users (username, email, password, display_name, bio, theme) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute(['demo', 'demo@martelinks.com', $demoHash, 'Demo User', 'Welcome to my links page!', 'default']);
        $demoId = $pdo->lastInsertId();
        $links = [
            ['Instagram', 'https://instagram.com', 'instagram', 'instagram', 1],
            ['Twitter / X', 'https://twitter.com', 'twitter', 'twitter', 2],
            ['YouTube', 'https://youtube.com', 'youtube', 'youtube', 3],
        ];
        foreach ($links as $l) {
            $pdo->prepare('INSERT INTO social_links (user_id, title, url, platform, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$demoId, $l[0], $l[1], $l[2], $l[3], $l[4]]);
        }
        $messages[] = 'Demo user created (demo / demo123)';
    }

    $messages[] = 'Database setup complete!';
    $success = true;

    require_once __DIR__ . '/includes/payments.php';
    ensurePaymentSchema();
    $messages[] = 'Payment tables ready (GCash manual verification).';
} catch (Exception $e) {
    $messages[] = 'Error: ' . $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install - MarteLinks</title>
    <style>
        body { font-family: system-ui; max-width: 600px; margin: 3rem auto; padding: 2rem; background: #1a1a2e; color: #fff; }
        .ok { color: #52b788; } .err { color: #e94560; }
        a { color: #e94560; }
    </style>
</head>
<body>
    <h1>MarteLinks Install</h1>
    <?php foreach ($messages as $m): ?>
        <p class="<?= $success ? 'ok' : 'err' ?>"><?= htmlspecialchars($m) ?></p>
    <?php endforeach; ?>
    <?php if ($success): ?>
        <p><a href="<?= BASE_URL ?>/">Go to homepage</a> | <a href="<?= BASE_URL ?>/login.php">Login</a></p>
        <p><small>Delete install.php after setup for security.</small></p>
    <?php endif; ?>
</body>
</html>
