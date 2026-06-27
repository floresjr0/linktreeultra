<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle AJAX actions
if ($action) {
    switch ($action) {
        case 'ban':
            $id = (int)($_POST['id'] ?? 0);
            $banned = (int)($_POST['banned'] ?? 1);
            $stmt = getDB()->prepare('UPDATE users SET is_banned = ? WHERE id = ? AND is_admin = 0');
            $stmt->execute([$banned, $id]);
            jsonResponse(['success' => true]);

        case 'delete_user':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = getDB()->prepare('DELETE FROM users WHERE id = ? AND is_admin = 0');
            $stmt->execute([$id]);
            jsonResponse(['success' => true]);

        case 'send_message':
            $userId = (int)($_POST['user_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            if (!$userId || !$message) {
                jsonResponse(['success' => false, 'error' => 'Invalid data'], 400);
            }
            $stmt = getDB()->prepare('INSERT INTO admin_messages (user_id, message) VALUES (?, ?)');
            $stmt->execute([$userId, $message]);
            jsonResponse(['success' => true]);

        default:
            jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
    }
}

// Dashboard stats
$totalUsers = getDB()->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
$totalLinks = getDB()->query('SELECT COUNT(*) FROM social_links')->fetchColumn();
$bannedUsers = getDB()->query('SELECT COUNT(*) FROM users WHERE is_banned = 1')->fetchColumn();

$users = getDB()->query('
    SELECT u.*, COUNT(sl.id) as link_count 
    FROM users u 
    LEFT JOIN social_links sl ON u.id = sl.user_id 
    WHERE u.is_admin = 0 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
')->fetchAll();

$admin = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-page">
    <aside class="admin-sidebar">
        <a href="<?= BASE_URL ?>/" class="logo"><?= SITE_NAME ?></a>
        <span class="admin-badge">Admin Panel</span>
        <nav>
            <a href="<?= BASE_URL ?>/admin/" class="active">Users</a>
            <?php
            require_once __DIR__ . '/../includes/payments.php';
            $pendingPayments = countPendingPayments();
            ?>
            <a href="<?= BASE_URL ?>/admin/payments.php">
                Payments<?php if ($pendingPayments > 0): ?> (<?= $pendingPayments ?>)<?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/dashboard/">Dashboard</a>
            <a href="<?= BASE_URL ?>/logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Admin Dashboard</h1>
            <span>Logged in as <?= e($admin['username']) ?></span>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?= $totalUsers ?></span>
                <span class="stat-label">Total Users</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= $totalLinks ?></span>
                <span class="stat-label">Total Links</span>
            </div>
            <div class="stat-card stat-warning">
                <span class="stat-value"><?= $bannedUsers ?></span>
                <span class="stat-label">Banned Users</span>
            </div>
        </div>

        <section class="users-section">
            <h2>Manage Users</h2>
            <div class="users-table-wrap">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Links</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr data-id="<?= $u['id'] ?>" class="<?= $u['is_banned'] ? 'banned' : '' ?>">
                            <td>
                                <a href="<?= BASE_URL ?>/u/<?= e($u['username']) ?>" target="_blank">@<?= e($u['username']) ?></a>
                            </td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= (int)$u['link_count'] ?></td>
                            <td>
                                <?php if ($u['is_banned']): ?>
                                    <span class="badge badge-danger">Banned</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td class="actions-cell">
                                <button type="button" class="btn btn-sm btn-outline btn-message" data-id="<?= $u['id'] ?>" data-name="<?= e($u['username']) ?>">Message</button>
                                <?php if ($u['is_banned']): ?>
                                    <button type="button" class="btn btn-sm btn-success btn-unban" data-id="<?= $u['id'] ?>">Unban</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-warning btn-ban" data-id="<?= $u['id'] ?>">Ban</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-danger btn-delete-user" data-id="<?= $u['id'] ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

  <div id="message-modal" class="modal" hidden>
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <h3>Send Message to <span id="msg-user-name"></span></h3>
            <form id="message-form">
                <input type="hidden" name="user_id" id="msg-user-id">
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="4" required placeholder="Type your message..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="msg-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script>const BASE_URL = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
