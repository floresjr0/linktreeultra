<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
requireLogin();

$user = currentUser();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'update_profile':
        requireActiveAccount();
        $displayName = trim($_POST['display_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $avatarPath = $user['avatar'];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $newAvatar = uploadAvatar($_FILES['avatar'], $user['id']);
            if ($newAvatar) {
                $avatarPath = $newAvatar;
            }
        }

        $stmt = getDB()->prepare('UPDATE users SET display_name = ?, bio = ?, avatar = ? WHERE id = ?');
        $stmt->execute([$displayName, $bio, $avatarPath, $user['id']]);
        jsonResponse(['success' => true]);
        break;

    case 'update_theme':
        requireActiveAccount();
        $theme = $_POST['theme'] ?? 'default';
        $bgColor = $_POST['bg_color'] ?? '#1a1a2e';
        $textColor = $_POST['text_color'] ?? '#ffffff';
        $buttonColor = $_POST['button_color'] ?? '#e94560';
        $buttonTextColor = $_POST['button_text_color'] ?? '#ffffff';

        $stmt = getDB()->prepare('UPDATE users SET theme = ?, bg_color = ?, text_color = ?, button_color = ?, button_text_color = ? WHERE id = ?');
        $stmt->execute([$theme, $bgColor, $textColor, $buttonColor, $buttonTextColor, $user['id']]);
        jsonResponse(['success' => true]);
        break;

    case 'mark_messages_read':
        $stmt = getDB()->prepare('UPDATE admin_messages SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
        break;
}