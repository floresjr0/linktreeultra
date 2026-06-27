<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('/dashboard/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($username, $email, $password);
        if ($result['success']) {
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = $username;
            redirect('/dashboard/');
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= SITE_NAME ?></title>
    
            <style>
        /* ─── Tokens ──────────────────────────────────────────────── */
        :root {
            --obsidian:      #0A0A0F;
            --obsidian-2:    #12121A;
            --obsidian-3:    #1A1A26;
            --violet:        #7C3AED;
            --violet-soft:   #9D5CF6;
            --rose:          #F43F8A;
            --amber:         #F59E0B;
            --pearl:         #F8F4FF;
            --lavender:      #C4B5FD;
            --lavender-dim:  #8B7FBF;
            --red-alert:     #FF4D6A;
            --glass-bg:      rgba(255,255,255,0.04);
            --glass-border:  rgba(196,181,253,0.14);
            --glass-hover:   rgba(255,255,255,0.07);
            --input-bg:      rgba(255,255,255,0.05);
            --input-border:  rgba(196,181,253,0.18);
            --input-focus:   rgba(124,58,237,0.6);

            --font-display:  'Syne', sans-serif;
            --font-body:     'Inter', sans-serif;
            --radius-card:   24px;
            --radius-input:  12px;
            --radius-pill:   9999px;
            --transition:    0.25s ease;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; }

        body {
            background: var(--obsidian);
            color: var(--pearl);
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* ─── Aurora ──────────────────────────────────────────────── */
        .aurora-stage {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .aurora-orb {
            position: absolute;
            top: -30vh;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 900px;
            border-radius: 50%;
            background: conic-gradient(
                from 0deg,
                #7C3AED 0deg, #F43F8A 90deg,
                #F59E0B 160deg, #7C3AED 220deg,
                #F43F8A 290deg, #7C3AED 360deg
            );
            filter: blur(130px);
            opacity: 0.2;
            animation: spin 18s linear infinite;
        }

        .aurora-orb-2 {
            position: absolute;
            bottom: -20vh;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, #7C3AED 0%, #F43F8A 55%, transparent 75%);
            filter: blur(100px);
            opacity: 0.12;
            animation: pulse 7s ease-in-out infinite alternate;
        }

        @keyframes spin  { to { transform: translateX(-50%) rotate(360deg); } }
        @keyframes pulse { from { opacity: 0.08; transform: translateX(-50%) scale(0.9); }
                           to   { opacity: 0.18; transform: translateX(-50%) scale(1.1); } }

        /* ─── Page layout ─────────────────────────────────────────── */
        .auth-page {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 20px;
        }

        /* ─── Logo ────────────────────────────────────────────────── */
        .logo {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            text-decoration: none;
            color: var(--pearl);
            margin-bottom: 36px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            opacity: 0.9;
            transition: opacity var(--transition);
        }

        .logo:hover { opacity: 1; }

        .logo-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--violet), var(--rose));
            box-shadow: 0 0 10px var(--violet);
            animation: pulse 2.5s ease-in-out infinite alternate;
        }

        /* ─── Card ────────────────────────────────────────────────── */
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 44px 40px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            animation: fade-up 0.5s ease both;
        }

        @keyframes fade-up {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Card top gradient line */
        .auth-card::before {
            content: '';
            display: block;
            height: 1px;
            background: linear-gradient(
                to right,
                transparent,
                var(--violet) 30%,
                var(--rose) 70%,
                transparent
            );
            margin-bottom: 36px;
            border-radius: 1px;
        }

        /* ─── Headings ────────────────────────────────────────────── */
        .auth-card h1 {
            font-family: var(--font-display);
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            background: linear-gradient(135deg, #fff 40%, var(--lavender) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            font-size: 0.875rem;
            font-weight: 300;
            color: var(--lavender-dim);
            margin-bottom: 32px;
        }

        /* ─── Alerts ──────────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 400;
            line-height: 1.5;
            margin-bottom: 24px;
            animation: fade-up 0.3s ease both;
        }

        .alert-error {
            background: rgba(255, 77, 106, 0.1);
            border: 1px solid rgba(255, 77, 106, 0.28);
            color: #FF8FA3;
        }

        .alert-icon {
            flex-shrink: 0;
            width: 16px;
            height: 16px;
            margin-top: 1px;
            color: var(--red-alert);
        }

        /* ─── Form ────────────────────────────────────────────────── */
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--lavender-dim);
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--lavender-dim);
            pointer-events: none;
            transition: color var(--transition);
        }

        .auth-form input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-input);
            padding: 13px 16px 13px 42px;
            color: var(--pearl);
            font-family: var(--font-body);
            font-size: 0.925rem;
            font-weight: 400;
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
        }

        .auth-form input::placeholder { color: var(--lavender-dim); opacity: 0.6; }

        .auth-form input:focus {
            border-color: var(--violet);
            background: rgba(124, 58, 237, 0.07);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
        }

        .auth-form input:focus + svg,
        .input-wrap:focus-within svg {
            color: var(--violet-soft);
        }

        /* Move icon before input in DOM but display after for focus trick */
        .input-wrap input:focus ~ svg { color: var(--violet-soft); }

        /* ─── Submit button ───────────────────────────────────────── */
        .btn-submit {
            position: relative;
            width: 100%;
            margin-top: 8px;
            padding: 14px 24px;
            border: none;
            border-radius: var(--radius-pill);
            background: linear-gradient(135deg, var(--violet) 0%, #A855F7 50%, var(--rose) 100%);
            background-size: 200% 200%;
            color: #fff;
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-position 0.4s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 24px rgba(124, 58, 237, 0.35);
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.12) 60%, transparent 80%);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }

        .btn-submit:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(124, 58, 237, 0.5);
        }

        .btn-submit:hover::after { transform: translateX(100%); }
        .btn-submit:active { transform: translateY(0); }

        /* ─── Footer link ─────────────────────────────────────────── */
        .auth-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 0.85rem;
            color: var(--lavender-dim);
        }

        .auth-footer a {
            color: var(--lavender);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition);
            position: relative;
        }

        .auth-footer a::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, var(--violet), var(--rose));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform var(--transition);
        }

        .auth-footer a:hover { color: var(--pearl); }
        .auth-footer a:hover::after { transform: scaleX(1); }

        /* ─── Reduced motion ──────────────────────────────────────── */
        @media (prefers-reduced-motion: reduce) {
            .aurora-orb, .aurora-orb-2, .logo-dot { animation: none; }
            * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }

        /* ─── Mobile ──────────────────────────────────────────────── */
        @media (max-width: 480px) {
            .auth-card  { padding: 32px 24px; border-radius: 20px; }
            .auth-card h1 { font-size: 1.6rem; }
            .aurora-orb { width: 600px; height: 600px; }
        }
    </style>
   
</head>
<body class="auth-page">

    <!-- Aurora Background -->
    <div class="aurora-stage">
        <div class="aurora-orb"></div>
        <div class="aurora-orb-2"></div>
    </div>

    <a href="<?= BASE_URL ?>/" class="logo">
        <span class="logo-dot"></span>
        <?= SITE_NAME ?>
    </a>

    <div class="auth-card">
        <h1>Create Account</h1>
        <p class="auth-subtitle">
            Your profile page will be <?= BASE_URL ?>/u/<strong>username</strong>
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Choose a username"
                        pattern="[a-zA-Z0-9_]{3,30}"
                        required
                        autofocus>

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A9 9 0 1118.88 17.8M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <small>Letters, numbers and underscore only</small>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required>

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l9 6 9-6m-18 8h18V8H3v8z" />
                    </svg>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        minlength="6"
                        required>

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 11c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6-1V8a6 6 0 10-12 0v2H4v10h16V10h-2z" />
                    </svg>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="confirm"
                        name="confirm"
                        placeholder="Confirm your password"
                        required>

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 11c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6-1V8a6 6 0 10-12 0v2H4v10h16V10h-2z" />
                    </svg>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Create Account
            </button>

        </form>

        <p class="auth-footer">
            Already have an account?
            <a href="<?= BASE_URL ?>/login.php">Sign In</a>
        </p>
    </div>

</body>
</html>
