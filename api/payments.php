<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
requireLogin();

$user = currentUser();
syncPremiumStatus($user['id']);
$user = currentUser();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'submit':
        $reference = trim($_POST['reference_number'] ?? '');
        if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'error' => 'Please upload a payment screenshot.'], 400);
        }
        $result = submitPayment($user['id'], $reference, $_FILES['proof']);
        jsonResponse($result, $result['success'] ? 200 : 400);

    default:
        jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
}
