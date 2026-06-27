<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
requireLogin();

$user = currentUser();
syncPremiumStatus($user['id']);
$user = currentUser();
$links = getUserLinks($user['id'], true);
$messages = getUnreadMessages($user['id']);
$platforms = getPlatformIcons();
$themes = getThemes();
$paymentSettings = getPaymentSettings();
$paymentSubmissions = getUserPaymentSubmissions($user['id']);
$pendingPayment = getUserPendingPayment($user['id']);
$isPremium = isUserPremium($user);
$planInfo = getUserPlanInfo($user);
$canAddLink = $planInfo['can_add_link'];
$isExpired = $planInfo['is_expired'];

/**
 * Tiny inline icon set (replaces emoji). All icons are 24x24 viewBox,
 * stroke = currentColor, so they inherit color from CSS automatically.
 */
function icon($name, $size = 18) {
    $paths = [
        'link'     => '<path d="M9 17H7a5 5 0 0 1 0-10h2"/><path d="M15 7h2a5 5 0 0 1 0 10h-2"/><line x1="8" y1="12" x2="16" y2="12"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'palette'  => '<circle cx="13.5" cy="6.5" r="0.5"/><circle cx="17.5" cy="10.5" r="0.5"/><circle cx="8.5" cy="7.5" r="0.5"/><circle cx="6.5" cy="12.5" r="0.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C22.011 6.011 17.519 2 12 2z"/>',
        'eye'      => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'gear'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'megaphone'=> '<path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 0 1-5.8-1.2"/>',
        'plus'     => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'pencil'   => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'archive'  => '<polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>',
        'restore'  => '<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>',
        'trash'    => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>',
        'grip'     => '<circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/>',
        'click'    => '<path d="M9 11l4.5 11 1.8-5.7 5.7-1.8L9 11z"/><path d="M3 3l3.5 3.5"/><path d="M3 9.5V3h6.5"/>',
        'box'      => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'x'        => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'arrow-up-right' => '<line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>',
        'wallet'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h.01"/><path d="M2 10h20"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'check'    => '<polyline points="20 6 9 17 4 12"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'upload'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'menu'     => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
    ];
    if (!isset($paths[$name])) return '';
    return '<svg class="icn" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths[$name] . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dashboard — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
    /* ─── Reset & Base ─────────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --bg:         #0D0F1A;
        --surface:    rgba(255,255,255,0.055);
        --surface-hover: rgba(255,255,255,0.09);
        --border:     rgba(255,255,255,0.08);
        --border-strong: rgba(124,58,237,0.5);
        --accent:     #7C3AED;
        --accent-glow:#9F67FF;
        --accent-soft:#C4B5FD;
        --text:       #FFFFFF;
        --text-2:     #A5B4C8;
        --text-3:     #5B6B82;
        --danger:     #F87171;
        --success:    #34D399;
        --warning:    #FCD34D;
        --sidebar-w:  72px;
        --sidebar-w-open: 240px;
        --radius:     14px;
        --radius-sm:  8px;
        --transition: 0.22s cubic-bezier(.4,0,.2,1);
        --bottom-nav-h: 64px;
        --safe-bottom: env(safe-area-inset-bottom, 0px);
    }

    html, body {
        height: 100%;
        background: var(--bg);
        color: var(--text);
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        line-height: 1.5;
        overflow-x: hidden;
        -webkit-tap-highlight-color: transparent;
    }

    .icn { display: block; flex-shrink: 0; }

    /* ─── Background Ambience ───────────────────────────────────────────────── */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background:
            radial-gradient(ellipse 60% 50% at 10% 0%, rgba(124,58,237,0.18) 0%, transparent 70%),
            radial-gradient(ellipse 40% 40% at 90% 100%, rgba(59,130,246,0.1) 0%, transparent 70%);
        pointer-events: none;
        z-index: 0;
    }

    /* ─── Layout Shell ──────────────────────────────────────────────────────── */
    .shell {
        display: flex;
        min-height: 100vh;
        position: relative;
        z-index: 1;
    }

    /* ─── Sidebar (desktop only) ────────────────────────────────────────────── */
    .sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: var(--sidebar-w);
        background: rgba(13,15,26,0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        padding: 20px 10px;
        z-index: 100;
        transition: width var(--transition);
        overflow: hidden;
    }
    .sidebar:hover {
        width: var(--sidebar-w-open);
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px 6px 24px;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
    }
    .logo-mark {
        width: 36px;
        height: 36px;
        min-width: 36px;
        background: linear-gradient(135deg, var(--accent), #3B82F6);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Syne', sans-serif;
        font-weight: 800;
        font-size: 16px;
        color: #fff;
        box-shadow: 0 0 20px rgba(124,58,237,0.4);
    }
    .logo-text {
        font-family: 'Syne', sans-serif;
        font-weight: 700;
        font-size: 17px;
        color: var(--text);
        letter-spacing: -0.3px;
    }

    .nav-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px 10px;
        border-radius: var(--radius-sm);
        text-decoration: none;
        color: var(--text-2);
        font-weight: 500;
        white-space: nowrap;
        transition: all var(--transition);
        position: relative;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        min-height: 44px;
    }
    .nav-item:hover {
        background: var(--surface-hover);
        color: var(--text);
    }
    .nav-item.active {
        background: rgba(124,58,237,0.2);
        color: var(--accent-soft);
    }
    .nav-item.active .nav-icon {
        color: var(--accent-glow);
    }
    .nav-item.active::before {
        content: '';
        position: absolute;
        left: 0; top: 20%; bottom: 20%;
        width: 3px;
        background: var(--accent-glow);
        border-radius: 0 2px 2px 0;
        box-shadow: 0 0 8px var(--accent-glow);
    }
    .nav-icon {
        min-width: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--text-2);
    }
    .nav-label {
        opacity: 0;
        transition: opacity var(--transition);
        flex: 1;
    }
    .sidebar:hover .nav-label { opacity: 1; }
    .sidebar:hover .logo-text { opacity: 1; }
    .logo-text { opacity: 0; transition: opacity var(--transition); }

    .nav-divider {
        height: 1px;
        background: var(--border);
        margin: 10px 6px;
    }

    /* ─── Mobile Top Bar ────────────────────────────────────────────────────── */
    .mobile-topbar {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 56px;
        background: rgba(13,15,26,0.92);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border);
        z-index: 200;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
    }
    .mobile-topbar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .mobile-topbar-logo .logo-mark {
        width: 30px; height: 30px; min-width: 30px;
        font-size: 13px;
    }
    .mobile-topbar-logo .logo-text {
        opacity: 1;
        font-size: 15px;
    }
    .mobile-topbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .mobile-view-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: var(--accent-soft);
        text-decoration: none;
        padding: 6px 10px;
        border-radius: 20px;
        background: rgba(124,58,237,0.1);
        border: 1px solid rgba(124,58,237,0.2);
        min-height: 36px;
    }
    .mobile-user-dot {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), #3B82F6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        color: #fff;
        flex-shrink: 0;
    }

    /* ─── Mobile Bottom Nav ─────────────────────────────────────────────────── */
    .mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: calc(var(--bottom-nav-h) + var(--safe-bottom));
        padding-bottom: var(--safe-bottom);
        background: rgba(13,15,26,0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 1px solid var(--border);
        z-index: 200;
        align-items: stretch;
    }
    .mobile-bottom-nav-inner {
        display: flex;
        flex: 1;
        align-items: stretch;
    }
    .mob-nav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        border: none;
        background: none;
        color: var(--text-3);
        font-size: 10px;
        font-weight: 500;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: color var(--transition);
        padding: 8px 4px;
        text-decoration: none;
        min-height: 100%;
        position: relative;
    }
    .mob-nav-item.active {
        color: var(--accent-soft);
    }
    .mob-nav-item.active::after {
        content: '';
        position: absolute;
        top: 0; left: 20%; right: 20%;
        height: 2px;
        background: var(--accent-glow);
        border-radius: 0 0 2px 2px;
        box-shadow: 0 0 8px var(--accent-glow);
    }
    .mob-nav-item:active { opacity: 0.7; }
    .mob-nav-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ─── Main Content ──────────────────────────────────────────────────────── */
    .main {
        margin-left: var(--sidebar-w);
        flex: 1;
        padding: 32px 40px;
        min-height: 100vh;
        transition: margin-left var(--transition);
        /* FIX: prevent horizontal overflow */
        min-width: 0;
        overflow-x: hidden;
    }

    /* ─── Page Header ───────────────────────────────────────────────────────── */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        gap: 16px;
    }
    .page-header-left h1 {
        font-family: 'Syne', sans-serif;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }
    .page-header-left p {
        color: var(--text-3);
        font-size: 13px;
        margin-top: 2px;
    }
    .page-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }
    .user-pill {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 40px;
        padding: 6px 14px 6px 8px;
        font-size: 13px;
        color: var(--text-2);
    }
    .user-avatar {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), #3B82F6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
        color: #fff;
    }

    /* ─── Alerts ────────────────────────────────────────────────────────────── */
    .alerts {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 24px;
    }
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        border: 1px solid rgba(252,211,77,0.3);
        background: rgba(252,211,77,0.08);
        color: var(--warning);
        font-size: 13px;
        line-height: 1.5;
    }
    .alert .icn { flex-shrink: 0; margin-top: 1px; }

    /* ─── Tab Navigation ────────────────────────────────────────────────────── */
    .tab-bar {
        display: flex;
        gap: 4px;
        margin-bottom: 28px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 4px;
        width: fit-content;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .tab-bar::-webkit-scrollbar { display: none; }
    .tab-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        border: none;
        background: none;
        border-radius: 6px;
        color: var(--text-3);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all var(--transition);
        font-family: 'Inter', sans-serif;
        white-space: nowrap;
        min-height: 40px;
    }
    .tab-btn:hover { color: var(--text-2); background: var(--surface-hover); }
    .tab-btn.active {
        background: var(--accent);
        color: #fff;
        box-shadow: 0 0 16px rgba(124,58,237,0.4);
    }

    /* ─── Tab Panels ────────────────────────────────────────────────────────── */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ─── Section Header ────────────────────────────────────────────────────── */
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        gap: 12px;
        flex-wrap: wrap;
        /* FIX: prevent overflow */
        width: 100%;
        min-width: 0;
    }
    .section-header h2 {
        font-family: 'Syne', sans-serif;
        font-size: 18px;
        font-weight: 700;
        /* FIX: allow h2 to shrink so button doesn't get pushed out */
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* FIX: ensure the button in section-header never gets clipped */
    .section-header .btn {
        flex-shrink: 0;
    }

    /* ─── Buttons ───────────────────────────────────────────────────────────── */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        border: none;
        cursor: pointer;
        transition: all var(--transition);
        text-decoration: none;
        white-space: nowrap;
        min-height: 40px;
        touch-action: manipulation;
    }
    .btn-primary {
        background: var(--accent);
        color: #fff;
        box-shadow: 0 0 20px rgba(124,58,237,0.35);
    }
    .btn-primary:hover, .btn-primary:active {
        background: var(--accent-glow);
        box-shadow: 0 0 28px rgba(124,58,237,0.55);
        transform: translateY(-1px);
    }
    .btn-outline {
        background: transparent;
        border: 1px solid var(--border-strong);
        color: var(--accent-soft);
    }
    .btn-outline:hover { background: rgba(124,58,237,0.1); }
    .btn-ghost {
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text-2);
    }
    .btn-ghost:hover { background: var(--surface-hover); color: var(--text); }
    .btn-danger {
        background: rgba(248,113,113,0.15);
        border: 1px solid rgba(248,113,113,0.3);
        color: var(--danger);
    }
    .btn-danger:hover { background: rgba(248,113,113,0.25); }

    /* ─── Link Cards ────────────────────────────────────────────────────────── */
    .links-grid {
        display: flex;
        flex-direction: column;
        gap: 10px;
        /* FIX: contain children properly */
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .link-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        transition: all var(--transition);
        position: relative;
        /* FIX: these two prevent the card from overflowing its container */
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .link-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(124,58,237,0.06), transparent 60%);
        opacity: 0;
        transition: opacity var(--transition);
        pointer-events: none;
    }
    .link-card:hover {
        border-color: var(--border-strong);
        background: var(--surface-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 0 0 1px rgba(124,58,237,0.15);
    }
    .link-card:hover::before { opacity: 1; }
    .link-card.archived { opacity: 0.5; }
    .link-card.archived:hover { opacity: 0.7; }

    .link-icon-wrap {
        width: 40px; height: 40px;
        min-width: 40px;
        border-radius: 10px;
        background: rgba(124,58,237,0.15);
        border: 1px solid rgba(124,58,237,0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-soft);
        flex-shrink: 0;
    }
    .link-info {
        flex: 1;
        min-width: 0;
        overflow: hidden; /* FIX: clip long URLs */
    }
    .link-title {
        font-weight: 600;
        font-size: 14px;
        color: var(--text);
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .link-url {
        font-size: 12px;
        color: var(--text-3);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .link-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .clicks-badge {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: var(--text-3);
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border);
        padding: 4px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }
    .badge-archived {
        font-size: 11px;
        padding: 3px 9px;
        border-radius: 20px;
        background: rgba(91,107,130,0.2);
        color: var(--text-3);
        border: 1px solid var(--border);
        white-space: nowrap;
    }
    .link-actions {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        /* FIX: always push actions to the far right */
        margin-left: auto;
    }
    .btn-icon {
        width: 36px; height: 36px;
        border: none;
        background: transparent;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all var(--transition);
        color: var(--text-3);
        position: relative;
        z-index: 2;
        touch-action: manipulation;
        flex-shrink: 0; /* FIX: prevent icon buttons from squishing */
    }
    .btn-icon:hover, .btn-icon:active { background: var(--surface-hover); color: var(--text); }
    .btn-icon.btn-delete:hover, .btn-icon.btn-delete:active { background: rgba(248,113,113,0.15); color: var(--danger); }
    .btn-icon.btn-restore:hover, .btn-icon.btn-restore:active { background: rgba(52,211,153,0.15); color: var(--success); }

    /* Drag handle */
    .drag-handle {
        color: var(--text-3);
        cursor: grab;
        display: flex;
        align-items: center;
        padding: 0 2px;
        user-select: none;
        touch-action: none;
        flex-shrink: 0; /* FIX: don't let handle shrink */
    }
    .drag-handle:active { cursor: grabbing; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 48px 20px;
        color: var(--text-3);
    }
    .empty-state .icn {
        margin: 0 auto 16px;
        opacity: 0.4;
        width: 40px;
        height: 40px;
    }
    .empty-state h3 {
        font-family: 'Syne', sans-serif;
        font-size: 18px;
        color: var(--text-2);
        margin-bottom: 8px;
    }
    .empty-state p {
        font-size: 13px;
        max-width: 280px;
        margin: 0 auto 20px;
    }

    /* ─── Settings Forms ────────────────────────────────────────────────────── */
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        max-width: 780px;
    }
    .settings-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 24px;
    }
    .settings-card.full { grid-column: 1 / -1; }
    .settings-card h3 {
        font-family: 'Syne', sans-serif;
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 18px;
        color: var(--text);
    }

    .form-group { margin-bottom: 16px; }
    .form-group:last-child { margin-bottom: 0; }
    label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 7px;
    }
    input[type="text"],
    input[type="url"],
    input[type="email"],
    textarea,
    select {
        width: 100%;
        background: rgba(0,0,0,0.3);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        color: var(--text);
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        padding: 10px 14px;
        transition: border-color var(--transition), box-shadow var(--transition);
        outline: none;
        -webkit-appearance: none;
        appearance: none;
        min-height: 44px;
    }
    input[type="text"]:focus,
    input[type="url"]:focus,
    input[type="email"]:focus,
    textarea:focus,
    select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
    }
    textarea { resize: vertical; min-height: 90px; }
    select option { background: #1a1d2e; }

    input[type="file"] {
        width: 100%;
        font-size: 13px;
        color: var(--text-2);
        font-family: 'Inter', sans-serif;
        padding: 8px 0;
    }
    input[type="file"]::file-selector-button {
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text-2);
        border-radius: 6px;
        padding: 8px 12px;
        margin-right: 12px;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        transition: all var(--transition);
        min-height: 36px;
    }
    input[type="file"]::file-selector-button:hover {
        background: var(--surface-hover);
        color: var(--text);
    }

    .avatar-preview-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 12px;
    }
    .avatar-preview {
        width: 56px; height: 56px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-strong);
    }
    .avatar-initials {
        width: 56px; height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), #3B82F6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 20px;
        flex-shrink: 0;
    }

    /* ─── Color Inputs ──────────────────────────────────────────────────────── */
    .color-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .color-input-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(0,0,0,0.3);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 8px 12px;
        transition: border-color var(--transition);
        min-height: 44px;
    }
    .color-input-wrap:focus-within { border-color: var(--accent); }
    input[type="color"] {
        -webkit-appearance: none;
        appearance: none;
        width: 28px; height: 28px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        background: none;
        padding: 0;
        flex-shrink: 0;
    }
    input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
    input[type="color"]::-webkit-color-swatch { border: none; border-radius: 5px; }
    .color-hex {
        font-size: 12px;
        font-family: 'Inter', monospace;
        flex: 1;
        border: none;
        background: none;
        outline: none;
        color: var(--text-2);
        min-height: unset;
        padding: 0;
        min-height: 0 !important;
    }

    /* ─── Theme Presets ─────────────────────────────────────────────────────── */
    .presets-row {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
        margin-bottom: 8px;
        max-height: 280px;
        overflow-y: auto;
        padding-right: 4px;
        -webkit-overflow-scrolling: touch;
    }
    .preset-btn {
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition);
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        gap: 9px;
        text-align: left;
        background: var(--surface);
        color: var(--text-2);
        min-height: 44px;
        touch-action: manipulation;
    }
    .preset-btn:hover, .preset-btn.active {
        border-color: var(--accent);
        box-shadow: 0 0 12px rgba(124,58,237,0.3);
        color: var(--text);
    }
    .preset-dot {
        width: 16px; height: 16px;
        border-radius: 50%;
        flex-shrink: 0;
        border: 2px solid rgba(255,255,255,0.25);
    }

    /* ─── Theme Preview ─────────────────────────────────────────────────────── */
    .theme-preview-box {
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
        padding: 28px 20px;
        text-align: center;
        margin: 20px 0;
        transition: all 0.3s ease;
    }
    .preview-avatar {
        width: 60px; height: 60px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        margin: 0 auto 12px;
        border: 2px solid rgba(255,255,255,0.3);
    }
    .preview-name { font-weight: 600; font-size: 16px; margin-bottom: 14px; }
    .preview-link-btn {
        display: block;
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        margin: 8px auto;
        max-width: 200px;
        transition: all 0.2s;
    }

    /* ─── Modal ─────────────────────────────────────────────────────────────── */
    .modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal[hidden] { display: none; }
    .modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .modal-box {
        position: relative;
        z-index: 1;
        background: #141626;
        border: 1px solid var(--border-strong);
        border-radius: var(--radius);
        padding: 24px;
        width: 100%;
        max-width: 440px;
        max-height: 90vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        box-shadow: 0 24px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(124,58,237,0.2);
        animation: modal-in 0.2s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes modal-in {
        from { opacity: 0; transform: scale(0.93) translateY(12px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
    }
    .modal-header h3 {
        font-family: 'Syne', sans-serif;
        font-size: 18px;
        font-weight: 700;
    }
    .btn-close {
        width: 36px; height: 36px;
        border: 1px solid var(--border);
        background: var(--surface);
        border-radius: 6px;
        color: var(--text-3);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--transition);
        flex-shrink: 0;
        touch-action: manipulation;
    }
    .btn-close:hover { background: var(--surface-hover); color: var(--text); }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }

    /* ─── View Page Link ────────────────────────────────────────────────────── */
    .view-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--accent-soft);
        text-decoration: none;
        padding: 4px 10px;
        border-radius: 20px;
        background: rgba(124,58,237,0.1);
        border: 1px solid rgba(124,58,237,0.2);
        transition: all var(--transition);
        min-height: 36px;
    }
    .view-link:hover { background: rgba(124,58,237,0.2); box-shadow: 0 0 12px rgba(124,58,237,0.25); }

    /* ─── Stats Strip ───────────────────────────────────────────────────────── */
    .stats-strip {
        display: flex;
        gap: 12px;
        margin-bottom: 28px;
    }
    .stat-card {
        flex: 1;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }
    .stat-icon {
        width: 38px; height: 38px;
        min-width: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-icon.violet { background: rgba(124,58,237,0.15); color: var(--accent-soft); }
    .stat-icon.blue   { background: rgba(59,130,246,0.15); color: #93C5FD; }
    .stat-icon.green  { background: rgba(52,211,153,0.15); color: var(--success); }
    .stat-num {
        font-family: 'Syne', sans-serif;
        font-size: 20px;
        font-weight: 800;
        color: var(--text);
        line-height: 1;
        margin-bottom: 2px;
    }
    .stat-label { font-size: 11px; color: var(--text-3); }

    /* ─── Premium / Payment ─────────────────────────────────────────────────── */
    .premium-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(52,211,153,0.15);
        color: var(--success);
        border: 1px solid rgba(52,211,153,0.3);
        white-space: nowrap;
    }
    .premium-badge.inactive {
        background: rgba(91,107,130,0.15);
        color: var(--text-3);
        border-color: var(--border);
    }
    .payment-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .payment-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 24px;
    }
    .payment-card h3 {
        font-family: 'Syne', sans-serif;
        font-size: 16px;
        margin-bottom: 16px;
    }
    .gcash-qr-wrap {
        text-align: center;
        padding: 16px;
        background: rgba(255,255,255,0.03);
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        margin-bottom: 16px;
    }
    .gcash-qr {
        max-width: 220px;
        width: 100%;
        border-radius: 10px;
        margin-bottom: 12px;
    }
    .gcash-info { font-size: 13px; color: var(--text-2); line-height: 1.7; }
    .gcash-info strong { color: var(--text); }
    .payment-instructions {
        white-space: pre-line;
        font-size: 13px;
        color: var(--text-2);
        line-height: 1.7;
        margin-bottom: 16px;
    }
    .payment-history { margin-top: 20px; }
    .payment-history-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .payment-history-item:last-child { border-bottom: none; }
    .status-pending { color: var(--warning); }
    .status-approved { color: var(--success); }
    .status-rejected { color: var(--danger); }
    .payment-alert {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        margin-bottom: 16px;
        font-size: 13px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        line-height: 1.5;
    }
    .payment-alert .icn { flex-shrink: 0; margin-top: 1px; }
    .payment-alert.info {
        background: rgba(124,58,237,0.12);
        border: 1px solid rgba(124,58,237,0.25);
        color: var(--accent-soft);
    }
    .payment-alert.warning {
        background: rgba(252,211,77,0.1);
        border: 1px solid rgba(252,211,77,0.25);
        color: var(--warning);
    }
    .btn-download {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
    }
    .plan-banner {
        padding: 14px 18px;
        border-radius: var(--radius-sm);
        margin-bottom: 20px;
        font-size: 13px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        line-height: 1.6;
    }
    .plan-banner .icn { flex-shrink: 0; margin-top: 2px; }
    .plan-banner.free {
        background: rgba(124,58,237,0.12);
        border: 1px solid rgba(124,58,237,0.25);
        color: var(--accent-soft);
    }
    .plan-banner.expired {
        background: rgba(248,113,113,0.12);
        border: 1px solid rgba(248,113,113,0.3);
        color: #fca5a5;
    }
    .plan-banner.premium {
        background: rgba(52,211,153,0.12);
        border: 1px solid rgba(52,211,153,0.25);
        color: var(--success);
    }
    .plan-banner a { color: inherit; font-weight: 600; }
    .btn-disabled,
    .btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* ─── Desktop: hide mobile elements ────────────────────────────────────── */
    @media (min-width: 769px) {
        .mobile-topbar,
        .mobile-bottom-nav { display: none !important; }
    }

    /* ─── Mobile Responsive ─────────────────────────────────────────────────── */
    @media (max-width: 768px) {

        /* Hide desktop sidebar */
        .sidebar { display: none !important; }

        /* Show mobile chrome */
        .mobile-topbar { display: flex; }
        .mobile-bottom-nav { display: flex; }

        /* Main shifts for top/bottom bars */
        .main {
            margin-left: 0;
            padding: 16px;
            padding-top: calc(56px + 16px);
            padding-bottom: calc(var(--bottom-nav-h) + var(--safe-bottom) + 20px);
            min-height: 100vh;
            /* FIX: critical — prevents any child from creating horizontal scroll */
            overflow-x: hidden;
            width: 100%;
        }

        /* Page header stacks */
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 12px;
        }
        .page-header-left h1 { font-size: 22px; }
        .page-header-right { width: 100%; }
        .user-pill { width: 100%; }
        /* Hide desktop view-link since it's in mobile topbar */
        .page-header-right .view-link { display: none; }

        /* Tab bar full width, scrollable */
        .tab-bar {
            width: 100%;
            border-radius: var(--radius-sm);
        }
        .tab-btn {
            padding: 8px 14px;
            font-size: 12px;
            flex: 1;
            justify-content: center;
        }
        .tab-btn .icn { display: none; }

        /* Stats: 3-column compact on mobile */
        .stats-strip {
            gap: 8px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 12px 8px;
            gap: 6px;
        }
        .stat-icon {
            width: 32px; height: 32px; min-width: 32px;
            border-radius: 8px;
        }
        .stat-num { font-size: 18px; }
        .stat-label { font-size: 10px; }

        /* Section header: FIX - proper layout on mobile */
        .section-header {
            flex-wrap: nowrap;       /* keep on one row */
            align-items: center;
            gap: 10px;
        }
        .section-header h2 {
            font-size: 16px;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .section-header .btn {
            flex-shrink: 0;
            padding: 8px 14px;      /* slightly tighter on mobile */
            font-size: 12px;
        }

        /* Link grid: FIX - must not overflow */
        .links-grid {
            width: 100%;
            overflow: hidden;
        }

        /* Link cards: tighter and fully contained */
        .link-card {
            padding: 12px;
            gap: 10px;
            width: 100%;
            /* FIX: box-sizing already set globally but be explicit */
            box-sizing: border-box;
        }
        .link-icon-wrap {
            width: 36px; height: 36px;
            min-width: 36px;
            border-radius: 8px;
        }
        .link-info {
            /* FIX: must be able to shrink below its content size */
            flex: 1 1 0;
            min-width: 0;
            overflow: hidden;
        }
        .link-title { font-size: 13px; }
        .link-url   { font-size: 11px; }

        /* Hide click count on mobile to save space */
        .link-meta { display: none; }

        /* Action buttons: tight but touchable */
        .link-actions {
            gap: 0;
            flex-shrink: 0;
        }
        .btn-icon {
            width: 34px;
            height: 34px;
        }

        /* Settings grid → single column */
        .settings-grid {
            grid-template-columns: 1fr;
            max-width: 100%;
        }
        .settings-card.full { grid-column: 1; }
        .settings-card { padding: 18px 16px; }

        /* Color grid stays 2 cols but smaller */
        .color-row { grid-template-columns: 1fr 1fr; gap: 10px; }

        /* Payment layout → single column */
        .payment-layout { grid-template-columns: 1fr; }
        .payment-card { padding: 18px 16px; }

        /* Theme presets: 2 cols on mobile */
        .presets-row {
            grid-template-columns: 1fr 1fr;
            max-height: none;
        }

        /* Modal: slide up from bottom on mobile */
        .modal { align-items: flex-end; padding: 0; }
        .modal-box {
            border-radius: var(--radius) var(--radius) 0 0;
            max-width: 100%;
            max-height: 85vh;
            padding: 20px 16px;
            padding-bottom: calc(16px + var(--safe-bottom));
            animation: modal-slide-up 0.25s cubic-bezier(.34,1.25,.64,1);
        }
        @keyframes modal-slide-up {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .modal-footer { flex-direction: column-reverse; }
        .modal-footer .btn { width: 100%; justify-content: center; }

        /* Plan banner */
        .plan-banner { padding: 12px 14px; font-size: 12px; }

        /* Alerts */
        .alert { font-size: 12px; padding: 10px 14px; }

        /* Drag handle — slightly bigger touch target */
        .drag-handle { padding: 4px; }
    }

    /* Extra-small phones */
    @media (max-width: 380px) {
        .main { padding-left: 12px; padding-right: 12px; }
        .link-card { gap: 8px; padding: 10px; }
        .link-icon-wrap { width: 32px; height: 32px; min-width: 32px; }
        .link-meta { display: none; }
        .stat-label { display: none; }
        .tab-btn { padding: 8px 10px; }
        .settings-card { padding: 14px 12px; }
        .btn-icon { width: 30px; height: 30px; }
        .section-header .btn { padding: 7px 10px; font-size: 11px; }
        /* FIX: hide the drag handle on tiny screens to recover space */
        .drag-handle { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { transition: none !important; animation: none !important; }
    }
    </style>
</head>
<body>
<div class="shell">

    <!-- ── Mobile Top Bar ───────────────────────────────────────────────────── -->
    <div class="mobile-topbar">
        <a href="<?= BASE_URL ?>/" class="mobile-topbar-logo">
            <div class="logo-mark"><?= strtoupper(substr(SITE_NAME, 0, 1)) ?></div>
            <span class="logo-text"><?= SITE_NAME ?></span>
        </a>
        <div class="mobile-topbar-right">
            <a href="<?= BASE_URL ?>/u/<?= e($user['username']) ?>" target="_blank" class="mobile-view-btn">
                <?= icon('eye', 14) ?> View
            </a>
            <div class="mobile-user-dot"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
        </div>
    </div>

    <!-- ── Sidebar (desktop) ────────────────────────────────────────────────── -->
    <aside class="sidebar">
        <a href="<?= BASE_URL ?>/" class="sidebar-logo">
            <div class="logo-mark"><?= strtoupper(substr(SITE_NAME, 0, 1)) ?></div>
            <span class="logo-text"><?= SITE_NAME ?></span>
        </a>

        <nav class="nav-section">
            <button type="button" class="nav-item active" data-tab="links">
                <span class="nav-icon"><?= icon('link', 18) ?></span>
                <span class="nav-label">My Links</span>
            </button>
            <button type="button" class="nav-item" data-tab="profile">
                <span class="nav-icon"><?= icon('user', 18) ?></span>
                <span class="nav-label">Profile</span>
            </button>
            <button type="button" class="nav-item" data-tab="theme">
                <span class="nav-icon"><?= icon('palette', 18) ?></span>
                <span class="nav-label">Theme</span>
            </button>
            <button type="button" class="nav-item" data-tab="premium">
                <span class="nav-icon"><?= icon('wallet', 18) ?></span>
                <span class="nav-label">Premium</span>
            </button>

            <div class="nav-divider"></div>

            <a href="<?= BASE_URL ?>/u/<?= e($user['username']) ?>" target="_blank" class="nav-item">
                <span class="nav-icon"><?= icon('eye', 18) ?></span>
                <span class="nav-label">View Page</span>
            </a>

            <?php if ($user['is_admin']): ?>
            <a href="<?= BASE_URL ?>/admin/" class="nav-item">
                <span class="nav-icon"><?= icon('gear', 18) ?></span>
                <span class="nav-label">Admin</span>
            </a>
            <?php endif; ?>
        </nav>

        <div>
            <a href="<?= BASE_URL ?>/logout.php" class="nav-item">
                <span class="nav-icon"><?= icon('logout', 18) ?></span>
                <span class="nav-label">Log out</span>
            </a>
        </div>
    </aside>

    <!-- ── Main ─────────────────────────────────────────────────────────────── -->
    <main class="main">

        <!-- Header -->
        <div class="page-header">
            <div class="page-header-left">
                <h1>Dashboard</h1>
                <p>Manage your links and page appearance</p>
            </div>
            <div class="page-header-right">
                <a href="<?= BASE_URL ?>/u/<?= e($user['username']) ?>" target="_blank" class="view-link">
                    <?= icon('arrow-up-right', 13) ?> View my page
                </a>
                <div class="user-pill">
                    <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                    @<?= e($user['username']) ?>
                    <?php if ($isPremium): ?>
                        <span class="premium-badge"><?= icon('check', 12) ?> Premium</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Admin messages -->
        <?php if (!empty($messages)): ?>
        <div class="alerts">
            <?php foreach ($messages as $msg): ?>
            <div class="alert">
                <?= icon('megaphone', 16) ?>
                <span><?= e($msg['message']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($isPremium): ?>
        <div class="plan-banner premium">
            <?= icon('check', 16) ?>
            <span><strong>Premium active</strong> — Unlimited links and your account never expires.</span>
        </div>
        <?php elseif ($isExpired): ?>
        <div class="plan-banner expired">
            <?= icon('clock', 16) ?>
            <span><strong>Free trial expired.</strong> Your public page is hidden. Go to the <a href="#" data-tab="premium" class="plan-tab-link">Premium</a> tab, pay via GCash, and upload your proof to restore full access.</span>
        </div>
        <?php else: ?>
        <div class="plan-banner free">
            <?= icon('clock', 16) ?>
            <span>
                <strong>Free plan:</strong> up to <?= FREE_LINK_LIMIT ?> links.
                Account expires <?= $planInfo['account_expires_at'] ? date('M j, Y g:i A', strtotime($planInfo['account_expires_at'])) : 'in ' . FREE_TRIAL_DAYS . ' days' ?>.
                <?php if (!$canAddLink && !$isExpired): ?> Link limit reached — <?php endif; ?>
                <a href="#" data-tab="premium" class="plan-tab-link">Upgrade to Premium</a> for unlimited links and no expiration.
            </span>
        </div>
        <?php endif; ?>

        <!-- Tab bar -->
        <div class="tab-bar">
            <button type="button" class="tab-btn active" data-tab="links"><?= icon('link', 14) ?> My Links</button>
            <button type="button" class="tab-btn" data-tab="profile"><?= icon('user', 14) ?> Profile</button>
            <button type="button" class="tab-btn" data-tab="theme"><?= icon('palette', 14) ?> Theme</button>
            <button type="button" class="tab-btn" data-tab="premium"><?= icon('wallet', 14) ?> Premium</button>
        </div>

        <!-- ── Links Panel ──────────────────────────────────────────────────── -->
        <div id="tab-links" class="tab-panel active">

            <!-- Stats -->
            <div class="stats-strip">
                <div class="stat-card">
                    <div class="stat-icon violet"><?= icon('link', 18) ?></div>
                    <div>
                        <div class="stat-num"><?= count(array_filter($links, fn($l) => !$l['is_archived'])) ?></div>
                        <div class="stat-label">Active links</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><?= icon('click', 18) ?></div>
                    <div>
                        <div class="stat-num"><?= array_sum(array_column($links, 'click_count')) ?></div>
                        <div class="stat-label">Total clicks</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><?= icon('box', 18) ?></div>
                    <div>
                        <div class="stat-num"><?= count(array_filter($links, fn($l) => $l['is_archived'])) ?></div>
                        <div class="stat-label">Archived</div>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <h2>Social Links<?php if (!$isPremium && !$planInfo['is_admin']): ?> <small style="font-weight:400;color:var(--text-3);font-size:13px;">(<?= $planInfo['active_links'] ?>/<?= FREE_LINK_LIMIT ?>)</small><?php endif; ?></h2>
                <?php if ($canAddLink && !$isExpired): ?>
                <button type="button" class="btn btn-primary" id="btn-add-link">
                    <?= icon('plus', 14) ?> Add Link
                </button>
                <?php else: ?>
                <button type="button" class="btn btn-primary btn-disabled" disabled title="<?= $isExpired ? 'Account expired' : 'Link limit reached' ?>">
                    <?= icon('plus', 14) ?> Add Link
                </button>
                <?php endif; ?>
            </div>

            <div id="links-list" class="links-grid">
                <?php if (empty($links)): ?>
                <div class="empty-state">
                    <?= icon('link', 40) ?>
                    <h3>No links yet</h3>
                    <p>Add your social profiles, websites, or any URL you want to share.</p>
                    <?php if ($canAddLink && !$isExpired): ?>
                    <button type="button" class="btn btn-primary" id="btn-add-link-empty"><?= icon('plus', 14) ?> Add your first link</button>
                    <?php elseif ($isExpired): ?>
                    <p style="margin-top:12px;color:var(--danger);">Trial expired — upgrade via the Premium tab.</p>
                    <?php else: ?>
                    <p style="margin-top:12px;color:var(--text-3);">Free limit reached (<?= FREE_LINK_LIMIT ?> links). Upgrade to Premium for more.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                    <div class="link-card <?= $link['is_archived'] ? 'archived' : '' ?>"
                         data-id="<?= $link['id'] ?>"
                         data-title="<?= e($link['title']) ?>"
                         data-url="<?= e($link['url']) ?>"
                         data-platform="<?= e($link['platform']) ?>">
                        <span class="drag-handle" title="Drag to reorder"><?= icon('grip', 16) ?></span>
                        <div class="link-icon-wrap">
                            <?= icon('link', 18) ?>
                        </div>
                        <div class="link-info">
                            <div class="link-title"><?= e($link['title']) ?></div>
                            <div class="link-url"><?= e($link['url']) ?></div>
                        </div>
                        <div class="link-meta">
                            <?php if ($link['is_archived']): ?>
                                <span class="badge-archived">Archived</span>
                            <?php endif; ?>
                            <div class="clicks-badge">
                                <?= icon('click', 12) ?>
                                <?= (int)$link['click_count'] ?>
                            </div>
                        </div>
                        <div class="link-actions">
                            <button type="button" class="btn-icon btn-edit" data-id="<?= $link['id'] ?>" title="Edit"><?= icon('pencil', 15) ?></button>
                            <?php if ($link['is_archived']): ?>
                                <button type="button" class="btn-icon btn-restore" data-id="<?= $link['id'] ?>" title="Restore"><?= icon('restore', 15) ?></button>
                            <?php else: ?>
                                <button type="button" class="btn-icon btn-archive" data-id="<?= $link['id'] ?>" title="Archive"><?= icon('archive', 15) ?></button>
                            <?php endif; ?>
                            <button type="button" class="btn-icon btn-delete" data-id="<?= $link['id'] ?>" title="Delete"><?= icon('trash', 15) ?></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Profile Panel ────────────────────────────────────────────────── -->
        <div id="tab-profile" class="tab-panel">
            <form id="profile-form">
                <div class="settings-grid">
                    <div class="settings-card">
                        <h3>Basic Info</h3>
                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" name="display_name" value="<?= e($user['display_name'] ?? '') ?>" maxlength="100" placeholder="Your full name">
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" rows="4" maxlength="500" placeholder="Tell people a bit about yourself…"><?= e($user['bio'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>

                    <div class="settings-card">
                        <h3>Profile Photo</h3>
                        <div class="avatar-preview-wrap">
                            <?php if ($user['avatar']): ?>
                                <img src="<?= BASE_URL ?>/<?= e($user['avatar']) ?>" class="avatar-preview" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-initials"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:600;font-size:14px;margin-bottom:4px;"><?= e($user['display_name'] ?? $user['username']) ?></div>
                                <div style="font-size:12px;color:var(--text-3);">@<?= e($user['username']) ?></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Upload Photo</label>
                            <input type="file" name="avatar" accept="image/*">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ── Theme Panel ───────────────────────────────────────────────────── -->
        <div id="tab-theme" class="tab-panel">
            <form id="theme-form">
                <input type="hidden" name="theme" value="<?= e($user['theme']) ?>">

                <div class="settings-grid">
                    <div class="settings-card full">
                        <h3>Theme Presets</h3>
                        <div class="presets-row">
                            <?php foreach ($themes as $key => $theme): ?>
                            <button type="button" class="preset-btn" data-theme="<?= $key ?>">
                                <span class="preset-dot" style="background:<?= $theme['btn'] ?>"></span>
                                <?= ucfirst($key) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="settings-card">
                        <h3>Colors</h3>
                        <div class="color-row">
                            <div class="form-group">
                                <label>Background</label>
                                <div class="color-input-wrap">
                                    <input type="color" name="bg_color" value="<?= e($user['bg_color']) ?>">
                                    <input type="text" class="color-hex" value="<?= e($user['bg_color']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Text Color</label>
                                <div class="color-input-wrap">
                                    <input type="color" name="text_color" value="<?= e($user['text_color']) ?>">
                                    <input type="text" class="color-hex" value="<?= e($user['text_color']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Button Color</label>
                                <div class="color-input-wrap">
                                    <input type="color" name="button_color" value="<?= e($user['button_color']) ?>">
                                    <input type="text" class="color-hex" value="<?= e($user['button_color']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Button Text</label>
                                <div class="color-input-wrap">
                                    <input type="color" name="button_text_color" value="<?= e($user['button_text_color']) ?>">
                                    <input type="text" class="color-hex" value="<?= e($user['button_text_color']) ?>" maxlength="7">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Save Theme</button>
                    </div>

                    <div class="settings-card">
                        <h3>Live Preview</h3>
                        <div class="theme-preview-box" id="theme-preview"
                            style="background:<?= e($user['bg_color']) ?>; color:<?= e($user['text_color']) ?>">
                            <div class="preview-avatar"></div>
                            <div class="preview-name"><?= e($user['display_name'] ?? $user['username']) ?></div>
                            <div class="preview-link-btn" id="preview-btn"
                                style="background:<?= e($user['button_color']) ?>; color:<?= e($user['button_text_color']) ?>">
                                Sample Link
                            </div>
                            <div class="preview-link-btn" id="preview-btn2"
                                style="background:<?= e($user['button_color']) ?>; color:<?= e($user['button_text_color']) ?>">
                                Another Link
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ── Premium / Payment Panel ──────────────────────────────────────── -->
        <div id="tab-premium" class="tab-panel">
            <div class="section-header">
                <h2><?= e($paymentSettings['plan_label'] ?? 'Premium') ?></h2>
                <?php if ($isPremium): ?>
                    <span class="premium-badge"><?= icon('check', 12) ?> Unlimited links · No expiration</span>
                <?php else: ?>
                    <span class="premium-badge inactive">Free · <?= FREE_LINK_LIMIT ?> links max</span>
                <?php endif; ?>
            </div>

            <?php if ($pendingPayment): ?>
            <div class="payment-alert warning">
                <?= icon('clock', 16) ?>
                <span>Your payment proof is <strong>pending verification</strong>. Submitted on <?= date('M j, Y g:i A', strtotime($pendingPayment['created_at'])) ?>. We'll notify you once reviewed.</span>
            </div>
            <?php endif; ?>

            <div class="payment-layout">
                <div class="payment-card">
                    <h3>Pay via GCash</h3>
                    <?php if (empty($paymentSettings['gcash_qr']) && empty($paymentSettings['gcash_number'])): ?>
                        <p style="color:var(--text-3);">Payment is not set up yet. Please check back later.</p>
                    <?php else: ?>
                        <?php if (!empty($paymentSettings['instructions'])): ?>
                            <div class="payment-instructions"><?= e($paymentSettings['instructions']) ?></div>
                        <?php endif; ?>

                        <div class="gcash-qr-wrap">
                            <?php if (!empty($paymentSettings['gcash_qr'])): ?>
                                <img src="<?= BASE_URL ?>/<?= e($paymentSettings['gcash_qr']) ?>" alt="GCash QR Code" class="gcash-qr" id="gcash-qr-img">
                                <a href="<?= BASE_URL ?>/<?= e($paymentSettings['gcash_qr']) ?>" download="gcash-qr.png" class="btn btn-outline btn-download">
                                    <?= icon('download', 14) ?> Download QR Code
                                </a>
                            <?php else: ?>
                                <p style="color:var(--text-3);margin-bottom:12px;">Scan not available — send manually to the number below.</p>
                            <?php endif; ?>
                        </div>

                        <div class="gcash-info">
                            <div><strong>Amount:</strong> ₱<?= number_format((float) ($paymentSettings['amount'] ?? 0), 2) ?></div>
                            <?php if (!empty($paymentSettings['gcash_number'])): ?>
                                <div><strong>GCash No.:</strong> <?= e($paymentSettings['gcash_number']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($paymentSettings['gcash_name'])): ?>
                                <div><strong>Account Name:</strong> <?= e($paymentSettings['gcash_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="payment-card">
                    <h3>Upload Payment Proof</h3>
                    <?php if ($pendingPayment): ?>
                        <div class="payment-alert info">
                            <?= icon('clock', 16) ?>
                            <span>You already have a submission waiting for review. Please wait before uploading again.</span>
                        </div>
                    <?php elseif (empty($paymentSettings['gcash_qr']) && empty($paymentSettings['gcash_number'])): ?>
                        <p style="color:var(--text-3);">Upload will be available once admin configures GCash payment.</p>
                    <?php else: ?>
                        <form id="payment-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>GCash Reference No. <small>(optional)</small></label>
                                <input type="text" name="reference_number" maxlength="100" placeholder="e.g. 1234567890">
                            </div>
                            <div class="form-group">
                                <label>Payment Screenshot <small>(required)</small></label>
                                <input type="file" name="proof" accept="image/*" required>
                                <small style="color:var(--text-3);font-size:11px;display:block;margin-top:5px;">Upload a screenshot of your successful GCash payment</small>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><?= icon('upload', 14) ?> Submit for Verification</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($paymentSubmissions)): ?>
                    <div class="payment-history">
                        <h3 style="margin-top:24px;margin-bottom:8px;">Your Submissions</h3>
                        <?php foreach ($paymentSubmissions as $ps): ?>
                        <div class="payment-history-item">
                            <div>
                                <div>₱<?= number_format((float) $ps['amount'], 2) ?>
                                    <?php if ($ps['reference_number']): ?> · Ref: <?= e($ps['reference_number']) ?><?php endif; ?>
                                </div>
                                <div style="color:var(--text-3);font-size:12px;"><?= date('M j, Y', strtotime($ps['created_at'])) ?></div>
                            </div>
                            <span class="status-<?= e($ps['status']) ?>"><?= paymentStatusLabel($ps['status']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

    <!-- ── Mobile Bottom Nav ────────────────────────────────────────────────── -->
    <nav class="mobile-bottom-nav" aria-label="Main navigation">
        <div class="mobile-bottom-nav-inner">
            <button type="button" class="mob-nav-item active" data-tab="links">
                <span class="mob-nav-icon"><?= icon('link', 20) ?></span>
                <span>Links</span>
            </button>
            <button type="button" class="mob-nav-item" data-tab="profile">
                <span class="mob-nav-icon"><?= icon('user', 20) ?></span>
                <span>Profile</span>
            </button>
            <button type="button" class="mob-nav-item" data-tab="theme">
                <span class="mob-nav-icon"><?= icon('palette', 20) ?></span>
                <span>Theme</span>
            </button>
            <button type="button" class="mob-nav-item" data-tab="premium">
                <span class="mob-nav-icon"><?= icon('wallet', 20) ?></span>
                <span>Premium</span>
            </button>
            <a href="<?= BASE_URL ?>/logout.php" class="mob-nav-item">
                <span class="mob-nav-icon"><?= icon('logout', 20) ?></span>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</div>

<!-- ── Add / Edit Link Modal ──────────────────────────────────────────────── -->
<div id="link-modal" class="modal" hidden>
    <div class="modal-backdrop" id="modal-backdrop"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modal-title">Add Link</h3>
            <button type="button" class="btn-close" id="modal-close"><?= icon('x', 16) ?></button>
        </div>
        <form id="link-form">
            <input type="hidden" name="id" value="">
            <div class="form-group">
                <label>Platform</label>
                <select name="platform" id="link-platform">
                    <?php foreach ($platforms as $key => $p): ?>
                    <option value="<?= $key ?>"><?= e($p['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required maxlength="100" placeholder="e.g. Follow me on Instagram">
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="url" name="url" required placeholder="https://...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" id="modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Link</button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const PLATFORMS = <?= json_encode($platforms) ?>;
const THEMES = <?= json_encode($themes) ?>;
const CAN_ADD_LINK = <?= $canAddLink && !$isExpired ? 'true' : 'false' ?>;
const IS_EXPIRED = <?= $isExpired ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>