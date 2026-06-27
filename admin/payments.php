<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
requireAdmin();

ensurePaymentSchema();
$admin = currentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action) {
    switch ($action) {
        case 'save_settings':
            $data = [
                'gcash_number' => trim($_POST['gcash_number'] ?? ''),
                'gcash_name' => trim($_POST['gcash_name'] ?? ''),
                'amount' => (float) ($_POST['amount'] ?? 99),
                'plan_label' => trim($_POST['plan_label'] ?? 'Premium (30 days)'),
                'duration_days' => max(1, (int) ($_POST['duration_days'] ?? 30)),
                'instructions' => trim($_POST['instructions'] ?? ''),
            ];
            if (isset($_FILES['gcash_qr']) && $_FILES['gcash_qr']['error'] === UPLOAD_ERR_OK) {
                $qr = uploadGcashQr($_FILES['gcash_qr']);
                if ($qr) {
                    $data['gcash_qr'] = $qr;
                }
            }
            updatePaymentSettings($data);
            jsonResponse(['success' => true]);

        case 'approve':
            $id = (int) ($_POST['id'] ?? 0);
            $note = trim($_POST['admin_note'] ?? '');
            $days = isset($_POST['duration_days']) ? (int) $_POST['duration_days'] : null;
            $result = reviewPayment($id, $admin['id'], 'approve', $note ?: null, $days);
            jsonResponse($result, $result['success'] ? 200 : 400);

        case 'reject':
            $id = (int) ($_POST['id'] ?? 0);
            $note = trim($_POST['admin_note'] ?? '');
            $result = reviewPayment($id, $admin['id'], 'reject', $note);
            jsonResponse($result, $result['success'] ? 200 : 400);

        default:
            jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
    }
}

$settings = getPaymentSettings();
$submissions = getPaymentSubmissions('all');
$pendingCount = countPendingPayments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Admin - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <style>
        .payments-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 900px) { .payments-grid { grid-template-columns: 1fr; } }
        .settings-card, .submissions-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .settings-card h2, .submissions-card h2 { margin-bottom: 1rem; font-size: 1.1rem; }
        .qr-preview {
            max-width: 200px;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin: 0.75rem 0;
        }
        .qr-empty {
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
            border: 1px dashed var(--border);
            border-radius: 8px;
            margin: 0.75rem 0;
        }
        .submission-row {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 1rem;
            align-items: start;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }
        .submission-row:last-child { border-bottom: none; }
        .proof-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            cursor: pointer;
        }
        .submission-meta { font-size: 0.85rem; color: var(--text-muted); }
        .submission-meta strong { color: var(--text); }
        .submission-actions { display: flex; flex-direction: column; gap: 0.35rem; min-width: 100px; }
        .badge-pending { background: rgba(255,193,7,0.2); color: #ffc107; }
        .badge-approved { background: rgba(82,183,136,0.2); color: var(--success); }
        .badge-rejected { background: rgba(233,69,96,0.2); color: var(--danger); }
        .nav-badge {
            background: var(--warning);
            color: #000;
            font-size: 0.7rem;
            padding: 0.1rem 0.45rem;
            border-radius: 10px;
            margin-left: 0.35rem;
        }
        .proof-modal img { max-width: 100%; max-height: 80vh; border-radius: 8px; }
    </style>
</head>
<body class="admin-page">
    <aside class="admin-sidebar">
        <a href="<?= BASE_URL ?>/" class="logo"><?= SITE_NAME ?></a>
        <span class="admin-badge">Admin Panel</span>
        <nav>
            <a href="<?= BASE_URL ?>/admin/">Users</a>
            <a href="<?= BASE_URL ?>/admin/payments.php" class="active">
                Payments<?php if ($pendingCount > 0): ?><span class="nav-badge"><?= $pendingCount ?></span><?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/dashboard/">Dashboard</a>
            <a href="<?= BASE_URL ?>/logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>GCash Payments</h1>
            <span>Manual verification — <?= $pendingCount ?> pending</span>
        </header>

        <div class="payments-grid">
            <section class="settings-card">
                <h2>GCash Payment Settings</h2>
                <form id="settings-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>GCash QR Code</label>
                        <?php if (!empty($settings['gcash_qr'])): ?>
                            <img src="<?= BASE_URL ?>/<?= e($settings['gcash_qr']) ?>" alt="GCash QR" class="qr-preview" id="qr-preview">
                        <?php else: ?>
                            <div class="qr-empty" id="qr-empty">No QR uploaded yet</div>
                        <?php endif; ?>
                        <input type="file" name="gcash_qr" accept="image/*">
                        <small>Upload your personal/business GCash receive QR image</small>
                    </div>
                    <div class="form-group">
                        <label>GCash Mobile Number</label>
                        <input type="text" name="gcash_number" value="<?= e($settings['gcash_number'] ?? '') ?>" placeholder="09XX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label>Account Name</label>
                        <input type="text" name="gcash_name" value="<?= e($settings['gcash_name'] ?? '') ?>" placeholder="Name shown on GCash">
                    </div>
                    <div class="form-group">
                        <label>Amount (₱)</label>
                        <input type="number" name="amount" step="0.01" min="1" value="<?= e($settings['amount'] ?? '99.00') ?>">
                    </div>
                    <div class="form-group">
                        <label>Plan Label</label>
                        <input type="text" name="plan_label" value="<?= e($settings['plan_label'] ?? 'Premium (30 days)') ?>">
                    </div>
                    <div class="form-group">
                        <label>Premium Duration (days after approval)</label>
                        <input type="number" name="duration_days" min="1" value="<?= (int) ($settings['duration_days'] ?? 30) ?>">
                    </div>
                    <div class="form-group">
                        <label>Instructions for Users</label>
                        <textarea name="instructions" rows="5"><?= e($settings['instructions'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </section>

            <section class="settings-card">
                <h2>How it works</h2>
                <ol style="padding-left:1.2rem;line-height:1.8;color:var(--text-muted);">
                    <li>Upload your GCash QR and set the payment amount.</li>
                    <li>Users scan or download your QR from their dashboard.</li>
                    <li>They pay via GCash and upload a screenshot as proof.</li>
                    <li>You review the proof here and approve or reject.</li>
                    <li>On approval, the user gets <strong>unlimited links</strong> and <strong>no account expiration</strong>.</li>
                </ol>
                <p style="margin-top:1rem;font-size:0.9rem;color:var(--text-muted);">
                    No automatic payment gateway needed — perfect for manual GCash verification without a business permit.
                </p>
            </section>
        </div>

        <section class="submissions-card">
            <h2>Payment Submissions</h2>
            <?php if (empty($submissions)): ?>
                <p style="color:var(--text-muted);padding:1rem 0;">No payment submissions yet.</p>
            <?php else: ?>
                <?php foreach ($submissions as $sub): ?>
                <div class="submission-row" data-id="<?= $sub['id'] ?>">
                    <img src="<?= BASE_URL ?>/<?= e($sub['proof_image']) ?>"
                         alt="Payment proof"
                         class="proof-thumb btn-view-proof"
                         data-src="<?= BASE_URL ?>/<?= e($sub['proof_image']) ?>">
                    <div>
                        <div><strong>@<?= e($sub['username']) ?></strong>
                            <span class="badge badge-<?= $sub['status'] === 'pending' ? 'pending' : ($sub['status'] === 'approved' ? 'approved' : 'rejected') ?>">
                                <?= paymentStatusLabel($sub['status']) ?>
                            </span>
                        </div>
                        <div class="submission-meta">
                            <?= e($sub['email']) ?> · ₱<?= number_format((float) $sub['amount'], 2) ?>
                            <?php if ($sub['reference_number']): ?> · Ref: <?= e($sub['reference_number']) ?><?php endif; ?>
                        </div>
                        <div class="submission-meta">Submitted <?= date('M j, Y g:i A', strtotime($sub['created_at'])) ?></div>
                        <?php if ($sub['admin_note']): ?>
                            <div class="submission-meta" style="margin-top:0.35rem;">Note: <?= e($sub['admin_note']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($sub['status'] === 'pending'): ?>
                    <div class="submission-actions">
                        <button type="button" class="btn btn-sm btn-success btn-approve" data-id="<?= $sub['id'] ?>">Approve</button>
                        <button type="button" class="btn btn-sm btn-danger btn-reject" data-id="<?= $sub['id'] ?>">Reject</button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <div id="proof-modal" class="modal" hidden>
        <div class="modal-backdrop" id="proof-backdrop"></div>
        <div class="modal-content proof-modal">
            <h3>Payment Proof</h3>
            <img id="proof-full" src="" alt="Payment proof">
            <div class="modal-actions" style="margin-top:1rem;">
                <button type="button" class="btn btn-outline" id="proof-close">Close</button>
            </div>
        </div>
    </div>

    <div id="review-modal" class="modal" hidden>
        <div class="modal-backdrop" id="review-backdrop"></div>
        <div class="modal-content">
            <h3 id="review-title">Review Payment</h3>
            <form id="review-form">
                <input type="hidden" name="id" id="review-id">
                <input type="hidden" name="decision" id="review-decision">
                <div class="form-group" id="duration-group" hidden>
                    <label>Premium days (optional override)</label>
                    <input type="number" name="duration_days" min="1" value="<?= (int) ($settings['duration_days'] ?? 30) ?>">
                </div>
                <div class="form-group">
                    <label id="review-note-label">Note to user</label>
                    <textarea name="admin_note" id="review-note" rows="3" placeholder="Optional message for approval, required for rejection"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="review-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="review-submit">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>const BASE_URL = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/admin-payments.js"></script>
</body>
</html>
