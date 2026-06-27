<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
requireLogin();

$user = currentUser();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        requireActiveAccount();
        $links = getUserLinks($user['id'], true);
        jsonResponse(['success' => true, 'links' => $links]);
        break;

    case 'create':
        requireActiveAccount();
        $canAdd = canUserAddLink($user);
        if (!$canAdd['allowed']) {
            jsonResponse(['success' => false, 'error' => $canAdd['error']], 403);
            break;
        }
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $platform = $_POST['platform'] ?? 'custom';
        if (!$title || !$url) {
            jsonResponse(['success' => false, 'error' => 'Title and URL required'], 400);
            break;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            jsonResponse(['success' => false, 'error' => 'Invalid URL'], 400);
            break;
        }
        $platforms = getPlatformIcons();
        $icon = $platforms[$platform]['icon'] ?? 'link';
        $stmt = getDB()->prepare('SELECT MAX(sort_order) as max_order FROM social_links WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        $maxOrder = (int)($stmt->fetch()['max_order'] ?? 0);
        $stmt = getDB()->prepare('INSERT INTO social_links (user_id, title, url, platform, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $title, $url, $platform, $icon, $maxOrder + 1]);
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId()]);
        break;

    case 'update':
        requireActiveAccount();
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $platform = $_POST['platform'] ?? 'custom';
        if (!$id || !$title || !$url) {
            jsonResponse(['success' => false, 'error' => 'Invalid data'], 400);
            break;
        }
        $platforms = getPlatformIcons();
        $icon = $platforms[$platform]['icon'] ?? 'link';
        $stmt = getDB()->prepare('UPDATE social_links SET title = ?, url = ?, platform = ?, icon = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$title, $url, $platform, $icon, $id, $user['id']]);
        jsonResponse(['success' => true]);
        break;

    case 'delete':
        requireActiveAccount();
        $id = (int)($_POST['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM social_links WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['id']]);
        jsonResponse(['success' => true]);
        break;

    case 'archive':
        requireActiveAccount();
        $id = (int)($_POST['id'] ?? 0);
        $archived = (int)($_POST['archived'] ?? 1);
        if ($archived === 0) {
            $canAdd = canUserAddLink($user);
            if (!$canAdd['allowed']) {
                jsonResponse(['success' => false, 'error' => $canAdd['error']], 403);
                break;
            }
        }
        $stmt = getDB()->prepare('UPDATE social_links SET is_archived = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$archived, $id, $user['id']]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
        break;
}