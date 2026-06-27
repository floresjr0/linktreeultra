<?php
require_once __DIR__ . '/../config/database.php';

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getPlatformIcons(): array
{
    return [
        'instagram' => ['label' => 'Instagram', 'icon' => 'instagram'],
        'twitter' => ['label' => 'Twitter / X', 'icon' => 'twitter'],
        'facebook' => ['label' => 'Facebook', 'icon' => 'facebook'],
        'youtube' => ['label' => 'YouTube', 'icon' => 'youtube'],
        'tiktok' => ['label' => 'TikTok', 'icon' => 'tiktok'],
        'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin'],
        'github' => ['label' => 'GitHub', 'icon' => 'github'],
        'discord' => ['label' => 'Discord', 'icon' => 'discord'],
        'twitch' => ['label' => 'Twitch', 'icon' => 'twitch'],
        'spotify' => ['label' => 'Spotify', 'icon' => 'spotify'],
        'email' => ['label' => 'Email', 'icon' => 'email'],
        'website' => ['label' => 'Website', 'icon' => 'website'],
        'custom' => ['label' => 'Custom Link', 'icon' => 'link'],
    ];
}

function getThemes(): array
{
    return [
        'default' => ['bg' => '#1a1a2e', 'text' => '#ffffff', 'btn' => '#e94560', 'btn_text' => '#ffffff'],
        'ocean' => ['bg' => '#0f3460', 'text' => '#e8f4f8', 'btn' => '#16c7ff', 'btn_text' => '#0f3460'],
        'sunset' => ['bg' => '#2d1b69', 'text' => '#fff5e6', 'btn' => '#ff6b35', 'btn_text' => '#ffffff'],
        'forest' => ['bg' => '#1b4332', 'text' => '#d8f3dc', 'btn' => '#52b788', 'btn_text' => '#1b4332'],
        'minimal' => ['bg' => '#f8f9fa', 'text' => '#212529', 'btn' => '#212529', 'btn_text' => '#ffffff'],
        'neon' => ['bg' => '#0a0a0a', 'text' => '#00ff88', 'btn' => '#ff00ff', 'btn_text' => '#0a0a0a'],

        'midnight' => ['bg' => '#0D0F1A', 'text' => '#FFFFFF', 'btn' => '#7C3AED', 'btn_text' => '#FFFFFF'],
        'slate'    => ['bg' => '#1E2530', 'text' => '#E5E9F0', 'btn' => '#475569', 'btn_text' => '#FFFFFF'],
        'noir'     => ['bg' => '#0A0A0A', 'text' => '#F5F5F5', 'btn' => '#FFFFFF', 'btn_text' => '#0A0A0A'],
        'plum'     => ['bg' => '#241B2F', 'text' => '#F3E8FF', 'btn' => '#A855F7', 'btn_text' => '#FFFFFF'],
 
        // ── Light ────────────────────────────────────────────────────────
        'cream'    => ['bg' => '#F8F4EC', 'text' => '#2B2620', 'btn' => '#2B2620', 'btn_text' => '#F8F4EC'],
        'mono'     => ['bg' => '#FFFFFF', 'text' => '#111111', 'btn' => '#111111', 'btn_text' => '#FFFFFF'],
        'sky'      => ['bg' => '#EFF6FF', 'text' => '#1E3A5F', 'btn' => '#3B82F6', 'btn_text' => '#FFFFFF'],
        'mint'     => ['bg' => '#ECFDF5', 'text' => '#064E3B', 'btn' => '#10B981', 'btn_text' => '#FFFFFF'],
 
        // ── Martelinks brand (indigo / coral / mustard) ────────────────
        'indigo'   => ['bg' => '#0D0F1A', 'text' => '#FFFFFF', 'btn' => '#5B6BFF', 'btn_text' => '#FFFFFF'],
        'coral'    => ['bg' => '#1A0E0E', 'text' => '#FFF1ED', 'btn' => '#FF6B5B', 'btn_text' => '#FFFFFF'],
        'mustard'  => ['bg' => '#1C1708', 'text' => '#FFF8E1', 'btn' => '#E0A815', 'btn_text' => '#1C1708'],
 
        // ── Bold / colorful ─────────────────────────────────────────────
        'sunset'   => ['bg' => '#2D1B2E', 'text' => '#FFE8D6', 'btn' => '#F4845F', 'btn_text' => '#FFFFFF'],
        'rose'     => ['bg' => '#FFF0F3', 'text' => '#5E1A2E', 'btn' => '#E11D48', 'btn_text' => '#FFFFFF'],
        'amber'    => ['bg' => '#1F1606', 'text' => '#FFE9B8', 'btn' => '#F59E0B', 'btn_text' => '#1F1606'],
        'forest'   => ['bg' => '#0F1F16', 'text' => '#E3F5E9', 'btn' => '#22C55E', 'btn_text' => '#0F1F16'],
        'crimson'  => ['bg' => '#1A0A0C', 'text' => '#FCE4E6', 'btn' => '#DC2626', 'btn_text' => '#FFFFFF'],
    ];
}

function getUserByUsername(string $username): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getUserLinks(int $userId, bool $includeArchived = false): array
{
    $sql = 'SELECT * FROM social_links WHERE user_id = ?';
    if (!$includeArchived) {
        $sql .= ' AND is_archived = 0';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = getDB()->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUnreadMessages(int $userId): array
{
    $stmt = getDB()->prepare('SELECT * FROM admin_messages WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function uploadAvatar(array $file, int $userId): ?string
{
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $dir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return 'uploads/avatars/' . $filename;
    }
    return null;
}
