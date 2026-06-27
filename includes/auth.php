<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
    require_once __DIR__ . '/payments.php';
    $user = currentUser();
    if ($user && $user['is_banned']) {
        session_destroy();
        redirect('/login.php?banned=1');
    }
    if ($user) {
        syncPremiumStatus($user['id']);
    }
}

function requireActiveAccount(): void
{
    requireLogin();
    $user = currentUser();
    if ($user && !$user['is_admin'] && isAccountExpired($user)) {
        jsonResponse([
            'success' => false,
            'error' => 'Your free trial has expired. Go to Premium and submit payment to restore your account.',
            'expired' => true,
        ], 403);
    }
}

function requireAdmin(): void
{
    requireLogin();
    $user = currentUser();
    if (!$user || !$user['is_admin']) {
        redirect('/dashboard/');
    }
}

function loginUser(string $email, string $password): bool|string
{
    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }
    if ($user['is_banned']) {
        return false;
    }
    require_once __DIR__ . '/payments.php';
    syncPremiumStatus($user['id']);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    return true;
}

function registerUser(string $username, string $email, string $password): array
{
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        return ['success' => false, 'error' => 'Username must be 3-30 characters (letters, numbers, underscore only).'];
    }
    $stmt = getDB()->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username or email already exists.'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = getDB()->prepare('INSERT INTO users (username, email, password, display_name) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hash, $username]);
    $userId = (int) getDB()->lastInsertId();
    require_once __DIR__ . '/payments.php';
    setFreeTrialExpiry($userId);
    return ['success' => true, 'id' => $userId];
}
