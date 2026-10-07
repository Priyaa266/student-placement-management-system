<?php
/**
 * Placement Cell Settings & Inquiries Inbox
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

// Handle Reply to Inquiry (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_inquiry'])) {
    $msg_id = intval($_POST['message_id'] ?? 0);
    $reply_text = trim($_POST['admin_reply'] ?? '');

    if ($msg_id > 0 && !empty($reply_text)) {
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET admin_reply = ?, status = 'replied' WHERE id = ?");
            $stmt->execute([$reply_text, $msg_id]);
            set_flash('success', 'Reply recorded and delivered to student.');
            header('Location: ' . base_url('admin/settings.php'));
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error saving reply: ' . $e->getMessage());
        }
    }
}

// Fetch all contact messages
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY status = 'unread' DESC, created_at DESC")->fetchAll();

$page_title = 'Settings & Student Inquiries';
$flash = get_flash();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="app-main">
        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="app-content">

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 24px;">
                <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Settings & Communication Inbox</h2>
                <p style="color: var(--text-muted); font-size: 13.5px;">
                    Review student inquiries, address feedback, and verify institutional placement configuration.
                </p>
            </div>

            <!-- Inquiries Inbox Card -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <div class="card-title">✉️ Student Support & Inquiries Inbox (<?= count($messages) ?>)</div>
                </div>

                <?php if (empty($messages)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 24px;">No inquiries in inbox.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <?php foreach ($messages as $msg): ?>
                            <div style="padding: 18px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #ffffff;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div>
                                        <strong style="font-size: 14.5px; color: var(--text-main);"><?= e($msg['subject']) ?></strong>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            From: <strong><?= e($msg['name']) ?></strong> (<?= e($msg['register_no']) ?>) • 📧 <?= e($msg['email']) ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="status-badge <?= ($msg['status'] === 'unread') ? 'badge-danger' : 'badge-success' ?>">
                                            <?= strtoupper($msg['status']) ?>
                                        </span>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                            <?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>

                                <div style="background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); font-size: 13px; color: var(--text-body); margin-bottom: 12px;">
                                    <?= e($msg['message']) ?>
                                </div>

                                <?php if (!empty($msg['admin_reply'])): ?>
                                    <div style="background: #ecfdf5; border-left: 3px solid #10b981; padding: 10px 14px; border-radius: var(--radius-sm); font-size: 12.5px; color: #065f46;">
                                        <strong>Officer Reply Sent:</strong> <?= e($msg['admin_reply']) ?>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="<?= base_url('admin/settings.php') ?>" style="display: flex; gap: 10px; align-items: center;">
                                        <input type="hidden" name="reply_inquiry" value="1">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <input type="text" name="admin_reply" class="form-control" placeholder="Type reply message to student..." required style="font-size: 13px;">
                                        <button type="submit" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                                            Send Reply
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Institutional Placement Configuration -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">⚙️ Placement Cell Institutional Profile</div>
                </div>

                <div class="grid-cols-3">
                    <div class="form-group">
                        <label class="form-label">Placement Director</label>
                        <input type="text" class="form-control" value="Dr. K. Placement Officer, M.Tech., Ph.D." disabled style="background:#f1f5f9;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Official Placement Email</label>
                        <input type="text" class="form-control" value="admin@college.edu" disabled style="background:#f1f5f9;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Active Recruitment Season</label>
                        <input type="text" class="form-control" value="<?= date('Y') ?> - <?= date('Y')+1 ?> Placement Session" disabled style="background:#f1f5f9;">
                    </div>
                </div>
            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
