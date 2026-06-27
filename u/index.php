<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';

$username = trim($_GET['username'] ?? '');
if (!$username) {
    http_response_code(404);
    echo 'User not found';
    exit;
}

$user = getUserByUsername($username);
if (!$user) {
    http_response_code(404);
    echo 'User not found';
    exit;
}

if ($user['is_banned']) {
    http_response_code(403);
    echo 'This page is not available';
    exit;
}

syncPremiumStatus($user['id']);
$user = getUserByUsername($username);
if (!$user['is_admin'] && isAccountExpired($user) && !isUserPremium($user)) {
    http_response_code(403);
    echo 'This page is not available';
    exit;
}

$links = getUserLinks($user['id']);

$platformIcons = [
    'instagram' => 'instagram',
    'twitter'   => 'x',        // Simple Icons uses "x" for the new brand
    'x'         => 'x',
    'youtube'   => 'youtube',
    'tiktok'    => 'tiktok',
    'twitch'    => 'twitch',
    'facebook'  => 'facebook',
    'linkedin'  => 'linkedin',
    'github'    => 'github',
    'discord'   => 'discord',
    'spotify'   => 'spotify',
    'patreon'   => 'patreon',
    'ko-fi'     => 'kofi',
    'substack'  => 'substack',
    'snapchat'  => 'snapchat',
    'pinterest' => 'pinterest',
    'threads'   => 'threads',
    'bereal'    => 'bereal',
    // fallbacks (no brand icon exists for these generic ones)
    'website'   => null,
    'link'      => null,
    'email'     => null,
    'shop'      => null,
    'merch'     => null,
    'podcast'   => null,
];

function getPlatformIcon(string $raw, array $map): string {
    $key  = strtolower(trim($raw));
    $slug = $map[$key] ?? null;

    if ($slug) {
        // Simple Icons CDN — white fill via CSS filter
        $url = "https://cdn.simpleicons.org/{$slug}/ffffff";
        return "<img src=\"{$url}\" width=\"20\" height=\"20\" alt=\"{$key}\" style=\"filter:brightness(0) invert(1);\">";
    }

    // Generic fallbacks as inline SVG
    return match($key) {
        'email'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M4 4h16v16H4z"/><polyline points="4,4 12,13 20,4"/></svg>',
        'website',
        'link'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        'podcast' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><circle cx="12" cy="11" r="3"/><path d="M6.6 18.4A9 9 0 1 1 17.4 18.4"/><line x1="12" y1="21" x2="12" y2="14"/></svg>',
        'shop',
        'merch'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        default   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    };
}

$displayName     = $user['display_name'] ?: $user['username'];

// Sanitize user theme colors — fall back to defaults if empty/invalid
function safeColor(string $val, string $default): string {
    $val = trim($val);
    return preg_match('/^#[0-9A-Fa-f]{3,6}$/', $val) ? $val : $default;
}
$bgColor         = safeColor($user['bg_color']          ?? '', '#0A0A0F');
$textColor       = safeColor($user['text_color']        ?? '', '#F8F4FF');
$buttonColor     = safeColor($user['button_color']      ?? '', '#7C3AED');
$buttonTextColor = safeColor($user['button_text_color'] ?? '', '#FFFFFF');

// Derive a readable border from buttonColor (25% opacity via rgba is CSS-only,
// so we just output the hex and handle opacity in CSS)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($displayName) ?> — <?= SITE_NAME ?></title>
    <meta name="description" content="<?= e($user['bio'] ?? 'Check out my links!') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* ─── Design Tokens ───────────────────────────────────────── */
        :root {
            /* Static palette */
            --obsidian:     #0A0A0F;
            --obsidian-2:   #12121A;
            --obsidian-3:   #1A1A26;
            --violet:       #7C3AED;
            --violet-soft:  #9D5CF6;
            --rose:         #F43F8A;
            --amber:        #F59E0B;
            --pearl:        #F8F4FF;
            --lavender:     #C4B5FD;
            --lavender-dim: #8B7FBF;
            --glass-bg:     rgba(255,255,255,0.04);
            --glass-border: rgba(196,181,253,0.14);
            --glass-hover:  rgba(255,255,255,0.08);

            /* ── User theme colors (injected from PHP) ── */
            --user-bg:       <?= $bgColor ?>;
            --user-text:     <?= $textColor ?>;
            --user-btn:      <?= $buttonColor ?>;
            --user-btn-text: <?= $buttonTextColor ?>;

            --font-display: 'Syne', sans-serif;
            --font-body:    'Inter', sans-serif;
            --radius-pill:  9999px;
            --radius-card:  20px;
            --transition:   0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-smooth: 0.3s ease;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        /* ─── Body: uses user bg color ───────────────────────────── */
        body {
            background-color: var(--user-bg);
            color: var(--user-text);
            font-family: var(--font-body);
            font-weight: 400;
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Aurora Orb ──────────────────────────────────────────── */
        .aurora-stage {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .aurora-orb {
            position: absolute;
            top: -20vh;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 900px;
            border-radius: 50%;
            background: conic-gradient(
                from 0deg,
                var(--user-btn) 0deg,
                #F43F8A 90deg,
                #F59E0B 160deg,
                var(--user-btn) 220deg,
                #F43F8A 290deg,
                var(--user-btn) 360deg
            );
            filter: blur(130px);
            opacity: 0.22;
            animation: aurora-spin 18s linear infinite;
        }
        .aurora-orb-2 {
            position: absolute;
            top: -10vh;
            left: 50%;
            transform: translateX(-50%);
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--user-btn) 0%, #F43F8A 50%, transparent 75%);
            filter: blur(80px);
            opacity: 0.18;
            animation: aurora-pulse 6s ease-in-out infinite alternate;
        }
        @keyframes aurora-spin {
            to { transform: translateX(-50%) rotate(360deg); }
        }
        @keyframes aurora-pulse {
            from { opacity: 0.12; transform: translateX(-50%) scale(0.92); }
            to   { opacity: 0.24; transform: translateX(-50%) scale(1.08); }
        }

        /* ─── Page Layout ─────────────────────────────────────────── */
        .profile-page {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 64px 20px 80px;
            min-height: 100vh;
        }
        .profile-container {
            width: 100%;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ─── Avatar ──────────────────────────────────────────────── */
        .avatar-wrap {
            position: relative;
            margin-bottom: 28px;
        }
        .avatar-glow {
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            background: conic-gradient(
                from 0deg,
                var(--user-btn),
                var(--rose),
                var(--amber),
                var(--user-btn)
            );
            animation: aurora-spin 5s linear infinite;
            filter: blur(4px);
            opacity: 0.9;
        }
        .avatar-glow-mask {
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: var(--user-bg);
            z-index: 1;
        }
        .profile-avatar {
            position: relative;
            z-index: 2;
            width: 108px;
            height: 108px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        .profile-avatar-placeholder {
            position: relative;
            z-index: 2;
            width: 108px;
            height: 108px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--user-btn), var(--rose));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--user-btn-text);
        }

        /* ─── Header Text ─────────────────────────────────────────── */
        .profile-header {
            text-align: center;
            margin-bottom: 36px;
        }
        .profile-name {
            font-family: var(--font-display);
            font-size: clamp(1.75rem, 5vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            color: var(--user-text);
            margin-bottom: 12px;
        }
        .profile-username-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--user-text);
            opacity: 0.6;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 5px 14px;
            border-radius: var(--radius-pill);
            margin-bottom: 16px;
        }
        .profile-username-tag::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--user-btn);
            box-shadow: 0 0 6px var(--user-btn);
            animation: dot-pulse 2s ease-in-out infinite;
        }
        @keyframes dot-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .profile-bio {
            font-size: 0.95rem;
            font-weight: 300;
            color: var(--user-text);
            opacity: 0.65;
            max-width: 380px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* ─── Link Buttons: use user button color ─────────────────── */
        .profile-links {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 48px;
        }
        .profile-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 24px;
            border-radius: var(--radius-card);
            /* Use user button color with transparency for glass effect */
            background: color-mix(in srgb, var(--user-btn) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--user-btn) 30%, transparent);
            text-decoration: none;
            color: var(--user-text);
            font-size: 0.95rem;
            font-weight: 500;
            transition:
                background var(--transition-smooth),
                border-color var(--transition-smooth),
                transform var(--transition),
                box-shadow var(--transition-smooth);
            overflow: hidden;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        /* Shimmer sweep */
        .profile-link::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                105deg,
                transparent 40%,
                color-mix(in srgb, var(--user-btn) 15%, transparent) 60%,
                transparent 80%
            );
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }
        .profile-link:hover::before { transform: translateX(100%); }

        .profile-link:hover {
            background: color-mix(in srgb, var(--user-btn) 22%, transparent);
            border-color: color-mix(in srgb, var(--user-btn) 55%, transparent);
            transform: translateY(-3px) scale(1.008);
            box-shadow:
                0 12px 40px color-mix(in srgb, var(--user-btn) 25%, transparent),
                0 0 0 1px color-mix(in srgb, var(--user-btn) 15%, transparent);
        }
        .profile-link:active { transform: translateY(-1px) scale(1.002); }

        /* Left accent bar */
        .profile-link::after {
            content: '';
            position: absolute;
            left: 0; top: 20%; height: 60%; width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--user-btn);
            opacity: 0;
            transition: opacity var(--transition-smooth);
        }
        .profile-link:hover::after { opacity: 1; }

        .link-platform-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
            width: 36px; height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--user-btn) 18%, transparent);
            border-radius: 10px;
            border: 1px solid color-mix(in srgb, var(--user-btn) 25%, transparent);
        }
        .link-title { flex: 1; }
        .link-arrow {
            flex-shrink: 0;
            width: 20px; height: 20px;
            color: var(--user-text);
            opacity: 0;
            transform: translateX(-6px);
            transition: opacity var(--transition-smooth), transform var(--transition-smooth);
        }
        .profile-link:hover .link-arrow { opacity: 0.7; transform: translateX(0); }

        .profile-empty {
            text-align: center;
            color: var(--user-text);
            opacity: 0.45;
            font-size: 0.9rem;
            padding: 40px 0;
        }

        /* ─── Divider ─────────────────────────────────────────────── */
        .profile-divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(
                to right,
                transparent,
                color-mix(in srgb, var(--user-btn) 30%, transparent) 30%,
                color-mix(in srgb, var(--user-btn) 30%, transparent) 70%,
                transparent
            );
            margin-bottom: 32px;
        }

        /* ─── Footer ──────────────────────────────────────────────── */
        .profile-footer { text-align: center; }
        .profile-footer a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-display);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--user-text);
            opacity: 0.55;
            padding: 10px 22px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-pill);
            background: rgba(255,255,255,0.04);
            transition: all var(--transition-smooth);
        }
        .profile-footer a:hover {
            opacity: 1;
            border-color: color-mix(in srgb, var(--user-btn) 45%, transparent);
            background: color-mix(in srgb, var(--user-btn) 10%, transparent);
            box-shadow: 0 0 24px color-mix(in srgb, var(--user-btn) 20%, transparent);
        }
        .footer-logo-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--user-btn);
            flex-shrink: 0;
        }

        /* ─── Animations ──────────────────────────────────────────── */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .profile-header { animation: fade-up 0.6s ease both; }
        .profile-links  { animation: fade-up 0.6s 0.15s ease both; }
        .profile-footer { animation: fade-up 0.6s 0.3s ease both; }
        .profile-link:nth-child(1) { animation: fade-up 0.5s 0.18s ease both; }
        .profile-link:nth-child(2) { animation: fade-up 0.5s 0.23s ease both; }
        .profile-link:nth-child(3) { animation: fade-up 0.5s 0.28s ease both; }
        .profile-link:nth-child(4) { animation: fade-up 0.5s 0.33s ease both; }
        .profile-link:nth-child(5) { animation: fade-up 0.5s 0.38s ease both; }
        .profile-link:nth-child(6) { animation: fade-up 0.5s 0.43s ease both; }
        .profile-link:nth-child(n+7) { animation: fade-up 0.5s 0.48s ease both; }

        @media (prefers-reduced-motion: reduce) {
            .aurora-orb, .aurora-orb-2 { animation: none; }
            .profile-link::before { display: none; }
            * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
        @media (max-width: 480px) {
            .profile-page { padding: 48px 16px 64px; }
            .profile-link { padding: 16px 18px; gap: 12px; }
            .aurora-orb { width: 600px; height: 600px; }
        }
    </style>
</head>
<body>

    <div class="aurora-stage" aria-hidden="true">
        <div class="aurora-orb"></div>
        <div class="aurora-orb-2"></div>
    </div>

    <main class="profile-page">
        <div class="profile-container">

            <div class="avatar-wrap" role="img" aria-label="<?= e($displayName) ?>'s profile picture">
                <div class="avatar-glow"></div>
                <div class="avatar-glow-mask"></div>
                <?php if ($user['avatar']): ?>
                    <img src="<?= BASE_URL ?>/<?= e($user['avatar']) ?>"
                         class="profile-avatar"
                         alt="<?= e($displayName) ?>">
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-placeholder" aria-hidden="true">
                        <?= strtoupper(substr($displayName, 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <header class="profile-header">
                <span class="profile-username-tag">@<?= e($user['username']) ?></span>
                <h1 class="profile-name"><?= e($displayName) ?></h1>
                <?php if ($user['bio']): ?>
                    <p class="profile-bio"><?= e($user['bio']) ?></p>
                <?php endif; ?>
            </header>

            <nav class="profile-links" aria-label="<?= e($displayName) ?>'s links">
                <?php if (empty($links)): ?>
                    <p class="profile-empty">No links yet — check back soon.</p>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                    <?php $icon = getPlatformIcon($link['icon'] ?? '', $platformIcons); ?>
                    <a href="<?= BASE_URL ?>/api/click.php?id=<?= (int)$link['id'] ?>"
                       class="profile-link"
                       target="_blank"
                       rel="noopener noreferrer">
<span class="link-platform-icon" aria-hidden="true"><?= getPlatformIcon($link['icon'] ?? '', $platformIcons) ?></span>                        <span class="link-title"><?= e($link['title']) ?></span>
                        <svg class="link-arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12M10 4l6 6-6 6" stroke="currentColor" stroke-width="1.5"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>

            <div class="profile-divider" aria-hidden="true"></div>

            <footer class="profile-footer">
                <a href="<?= BASE_URL ?>/">
                    <span class="footer-logo-dot" aria-hidden="true"></span>
                    Create your own <?= SITE_NAME ?>
                </a>
            </footer>

        </div>
    </main>

</body>
</html>