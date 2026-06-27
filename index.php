<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - One link for everything</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --obsidian:      #08080F;
            --obsidian-2:    #0E0E1A;
            --obsidian-3:    #15152A;
            --violet:        #7C3AED;
            --violet-soft:   #9B5AF7;
            --rose:          #F43F8A;
            --amber:         #F59E0B;
            --pearl:         #F8F4FF;
            --lavender:      #C4B5FD;
            --muted:         rgba(196,181,253,0.45);
            --glass-bg:      rgba(255,255,255,0.04);
            --glass-border:  rgba(255,255,255,0.08);
            --radius-sm:     10px;
            --radius-md:     16px;
            --radius-lg:     24px;
            --radius-xl:     32px;
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--obsidian);
            color: var(--pearl);
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Aurora Background ── */
        .aurora {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .aurora::before {
            content: '';
            position: absolute;
            top: -30%;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 900px;
            background: radial-gradient(ellipse, rgba(124,58,237,0.18) 0%, rgba(244,63,138,0.10) 40%, transparent 70%);
            animation: auroraShift 14s ease-in-out infinite alternate;
            filter: blur(60px);
        }
        .aurora::after {
            content: '';
            position: absolute;
            top: -10%;
            left: 60%;
            width: 600px;
            height: 600px;
            background: radial-gradient(ellipse, rgba(245,158,11,0.10) 0%, rgba(124,58,237,0.08) 50%, transparent 70%);
            animation: auroraShift2 18s ease-in-out infinite alternate;
            filter: blur(70px);
        }
        @keyframes auroraShift {
            0%   { transform: translateX(-50%) scale(1) rotate(0deg);    opacity: 0.7; }
            100% { transform: translateX(-40%) scale(1.2) rotate(8deg);  opacity: 1; }
        }
        @keyframes auroraShift2 {
            0%   { transform: scale(1) translateY(0);        opacity: 0.5; }
            100% { transform: scale(1.15) translateY(40px);  opacity: 0.9; }
        }

        /* ── Layout ── */
        .container {
            position: relative;
            z-index: 1;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ── Nav ── */
        .landing-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(8,8,15,0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }

        .logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(1.1rem, 4vw, 1.35rem);
            letter-spacing: -0.01em;
            text-decoration: none;
            background: linear-gradient(135deg, var(--pearl) 30%, var(--lavender) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .nav-links a.nav-signin {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(248,244,255,0.65);
            text-decoration: none;
            transition: color 0.2s;
            white-space: nowrap;
            padding: 8px 4px;
        }
        .nav-links a.nav-signin:hover { color: var(--pearl); }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.01em;
            text-decoration: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            border: none;
            white-space: nowrap;
            transition: transform 0.18s, box-shadow 0.18s, background 0.18s, opacity 0.18s;
        }
        .btn:hover  { transform: translateY(-2px); }
        .btn:active { transform: translateY(0); }

        .btn-primary {
            background: linear-gradient(135deg, var(--violet) 0%, var(--rose) 100%);
            color: #fff;
            padding: 10px 20px;
            box-shadow: 0 4px 24px rgba(124,58,237,0.35);
        }
        .btn-primary:hover { box-shadow: 0 8px 36px rgba(244,63,138,0.45); }

        .btn-outline {
            background: var(--glass-bg);
            color: var(--lavender);
            border: 1px solid var(--glass-border);
            padding: 9px 18px;
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(196,181,253,0.3);
            color: var(--pearl);
        }

        .btn-lg {
            font-size: 1rem;
            padding: 14px 28px;
            border-radius: var(--radius-md);
        }
        .btn-primary.btn-lg { box-shadow: 0 6px 32px rgba(124,58,237,0.4); }

        /* ── Hero ── */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 20px 72px;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(124,58,237,0.15);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--lavender);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 28px;
        }
        .hero-eyebrow span {
            width: 6px; height: 6px;
            background: var(--rose);
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 0 0 8px var(--rose);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(0.75); }
        }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(2.2rem, 8vw, 5.5rem);
            line-height: 1.05;
            letter-spacing: -0.03em;
            color: var(--pearl);
            margin-bottom: 20px;
            max-width: 820px;
        }

        .hero h1 .highlight {
            background: linear-gradient(135deg, var(--violet-soft) 0%, var(--rose) 50%, var(--amber) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: clamp(0.95rem, 2.5vw, 1.2rem);
            font-weight: 400;
            color: rgba(248,244,255,0.55);
            max-width: 480px;
            margin-bottom: 40px;
            line-height: 1.7;
            padding: 0 4px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 40px;
            width: 100%;
            max-width: 400px;
        }

        .hero-url {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm);
            padding: 12px 20px;
            max-width: 100%;
            overflow: hidden;
        }
        .hero-url code {
            font-family: 'Inter', monospace;
            font-size: clamp(0.8rem, 3vw, 0.95rem);
            font-weight: 400;
            color: rgba(248,244,255,0.45);
            letter-spacing: 0.01em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .hero-url code strong {
            font-weight: 600;
            color: var(--lavender);
        }

        /* ── Divider ── */
        .section-divider {
            position: relative;
            z-index: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(196,181,253,0.2), transparent);
            margin: 0 auto;
            max-width: 800px;
        }

        /* ── Features ── */
        .features {
            position: relative;
            z-index: 1;
            padding: 80px 20px 100px;
        }

        .features-header {
            text-align: center;
            margin-bottom: 52px;
        }

        .features-label {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--violet-soft);
            margin-bottom: 14px;
            display: block;
        }

        .features-header h2 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: clamp(1.6rem, 5vw, 2.8rem);
            letter-spacing: -0.025em;
            color: var(--pearl);
            line-height: 1.15;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            transition: transform 0.25s, border-color 0.25s, background 0.25s;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .feature-card:nth-child(1)::before { background: linear-gradient(90deg, var(--violet), var(--rose)); }
        .feature-card:nth-child(2)::before { background: linear-gradient(90deg, var(--rose), var(--amber)); }
        .feature-card:nth-child(3)::before { background: linear-gradient(90deg, var(--amber), var(--violet)); }

        .feature-card:hover {
            transform: translateY(-6px);
            background: rgba(255,255,255,0.06);
            border-color: rgba(196,181,253,0.2);
        }
        .feature-card:hover::before { opacity: 1; }

        .feature-card h3 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--pearl);
            margin-bottom: 10px;
            letter-spacing: -0.01em;
        }

        .feature-card p {
            font-size: 0.92rem;
            color: rgba(248,244,255,0.5);
            line-height: 1.7;
        }

        /* ── CTA Band ── */
        .cta-band {
            position: relative;
            z-index: 1;
            margin: 0 20px 80px;
            border-radius: var(--radius-xl);
            padding: 64px 32px;
            text-align: center;
            background: linear-gradient(135deg, rgba(124,58,237,0.25) 0%, rgba(244,63,138,0.15) 60%, rgba(245,158,11,0.1) 100%);
            border: 1px solid rgba(196,181,253,0.15);
            overflow: hidden;
        }
        .cta-band::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 600px; height: 300px;
            background: radial-gradient(ellipse, rgba(124,58,237,0.25), transparent 70%);
            pointer-events: none;
        }

        .cta-band h2 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(1.6rem, 5vw, 3rem);
            color: var(--pearl);
            margin-bottom: 14px;
            letter-spacing: -0.025em;
            line-height: 1.15;
            position: relative;
        }
        .cta-band p {
            font-size: clamp(0.9rem, 2.5vw, 1.05rem);
            color: rgba(248,244,255,0.55);
            margin-bottom: 36px;
            position: relative;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .cta-band .btn { position: relative; }

        /* ── Footer ── */
        .landing-footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--glass-border);
            padding: 32px 20px;
            text-align: center;
        }
        .landing-footer p {
            font-size: 0.82rem;
            color: rgba(248,244,255,0.3);
        }

        /* ── Fade-in on scroll ── */
        .fade-up {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── Tablet (641px – 1023px) ── */
        @media (min-width: 641px) {
            .landing-nav { padding: 18px 36px; }
            .hero         { padding: 130px 32px 80px; }
            .cta-band     { margin: 0 32px 88px; padding: 72px 48px; }
            .features     { padding: 88px 32px 100px; }
        }

        /* ── Desktop (1024px+) ── */
        @media (min-width: 1024px) {
            .landing-nav  { padding: 20px 48px; }
            .hero         { padding: 140px 24px 80px; }
            .cta-band     { margin: 0 24px 100px; padding: 80px 48px; }
            .features     { padding: 100px 24px 120px; }
            .features-grid { gap: 20px; }
        }

        /* ── Mobile-only overrides (≤ 480px) ── */
        @media (max-width: 480px) {
            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .hero-actions .btn {
                text-align: center;
                width: 100%;
            }
            .cta-band {
                border-radius: var(--radius-lg);
            }
            .feature-card {
                padding: 28px 22px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .aurora::before, .aurora::after { animation: none; }
            .fade-up { opacity: 1; transform: none; transition: none; }
            @keyframes pulse { 0%, 100% { opacity: 1; } }
        }
    </style>
</head>
<body>

    <!-- Living aurora aura -->
    <div class="aurora" aria-hidden="true"></div>

    <!-- Nav -->
    <nav class="landing-nav">
        <a href="<?= BASE_URL ?>/" class="logo"><?= SITE_NAME ?></a>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/dashboard/" class="btn btn-outline">Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="nav-signin">Sign in</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero">
        <div class="hero-eyebrow">
            <span></span>
            Built for creators
        </div>
        <h1>One link for<br><span class="highlight">everything you make</span></h1>
        <p>Your socials, your work, your world — all in one beautiful page. Share it everywhere.</p>
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-lg">Create Your Page</a>
        </div>
        <div class="hero-url">
            <code>linktreeultra/<strong>yourname</strong></code>
        </div>
    </header>

    <div class="section-divider"></div>

    <!-- Features -->
    <section class="features">
        <div class="features-header fade-up">
            <span class="features-label">Why creators choose us</span>
            <h2>Everything you need,<br>nothing you don't</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card fade-up">
                <h3>Add Any Link</h3>
                <p>Instagram, TikTok, YouTube, Patreon, your shop — every link you need in one place, always up to date.</p>
            </div>
            <div class="feature-card fade-up">
                <h3>Your Brand, Your Theme</h3>
                <p>Craft a page that looks like you, not a template. Colors, fonts, and layouts that match your creative identity.</p>
            </div>
            <div class="feature-card fade-up">
                <h3>Pixel-Perfect on Any Screen</h3>
                <p>Whether your fans are on a phone, tablet, or desktop — your page always looks flawless.</p>
            </div>
        </div>
    </section>

    <!-- CTA Band -->
    <section class="cta-band fade-up">
        <h2>Your audience is waiting.</h2>
        <p>Join thousands of creators who simplified their online presence in minutes.</p>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-lg">Claim Your Link — It's Free</a>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Built with trust and support.</p>
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('.fade-up').forEach((el, i) => {
            el.style.transitionDelay = (i * 80) + 'ms';
            observer.observe(el);
        });
    </script>
</body>
</html>