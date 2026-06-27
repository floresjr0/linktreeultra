<?php
/**
 * Run once to add GCash payment tables to an existing install.
 * Visit: http://localhost/linktreeultra/migrate_payments.php
 */
require_once __DIR__ . '/includes/payments.php';

try {
    ensurePaymentSchema();
    $ok = true;
    $message = 'Payment migration complete. You can delete this file.';
} catch (Exception $e) {
    $ok = false;
    $message = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Migration</title>
    <style>
        body { font-family: system-ui; max-width: 600px; margin: 3rem auto; padding: 2rem; background: #0D0F1A; color: #fff; }
        .ok { color: #34D399; } .err { color: #F87171; }
        a { color: #7C3AED; }
    </style>
</head>
<body>
    <h1>Payment Migration</h1>
    <p class="<?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($message) ?></p>
    <?php if ($ok): ?>
        <p><a href="<?= BASE_URL ?>/admin/payments.php">Configure GCash payments</a></p>
    <?php endif; ?>
</body>
</html>
