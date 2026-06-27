<?php
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = getDB()->prepare('SELECT url FROM social_links WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);
    $link = $stmt->fetch();
    if ($link) {
        $stmt = getDB()->prepare('UPDATE social_links SET click_count = click_count + 1 WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: ' . $link['url']);
        exit;
    }
}
header('Location: ' . BASE_URL . '/');
exit;
