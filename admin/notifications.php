<?php
/**
 * Announcements & Notification Broadcast Management
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

// Handle Post Announcement (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_notice'])) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'announcement';
    $target_role = $_POST['target_role'] ?? 'all';
    $drive_id = !empty($_POST['drive_id']) ? intval($_POST['drive_id']) : null;

    if (empty($title) || empty($message)) {
        set_flash('danger', 'Please enter both an announcement title and message content.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, target_role, drive_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $message, $type, $target_role, $drive_id]);
            set_flash('success', 'Announcement published and broadcast successfully.');
            header('Location: ' . base_url('admin/notifications.php'));
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error publishing notice: ' . $e->getMessage());
        }
    }
}

// Handle Delete Notice
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id > 0) {
        $del_stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $del_stmt->execute([$del_id]);
        set_flash('success', 'Announcement deleted.');
        header('Location: ' . base_url('admin/notifications.php'));
        exit;
    }
}

// Fetch all notifications
$stmt = $pdo->query("SELECT n.*, d.job_title, c.name as company_name 
                    FROM notifications n 
                    LEFT JOIN placement_drives d ON n.drive_id = d.id 
                    LEFT JOIN companies c ON d.company_id = c.id 
                    ORDER BY n.created_at DESC");
$notifications = $stmt->fetchAll();

// Drives for dropdown
$drives = $pdo->query("SELECT d.id, d.job_role, c.name as company_name FROM placement_drives d JOIN companies c ON d.company_id = c.id ORDER BY c.name ASC")->fetchAll();

$page_title = 'Announcements & Broadcast Notices';
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

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Campus Announcements Broadcast</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Issue recruitment bulletins, schedule updates, assessment guidelines, and important circulars.
                    </p>
                </div>
            </div>

            <!-- Two-Column Layout: Post Notice Form vs Existing Bulletins -->
            <div class="grid-cols-1-2">

                <!-- Left: Compose Announcement -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📢 Broadcast New Notice</div>
                    </div>

                    <form method="POST" action="<?= base_url('admin/notifications.php') ?>">
                        <input type="hidden" name="post_notice" value="1">

                        <div class="form-group">
                            <label class="form-label">Notice Title <span class="req">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Schedule for TCS Technical Assessment" required>
                        </div>

                        <div class="grid-cols-2">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="type" class="form-control">
                                    <option value="announcement">General Bulletin</option>
                                    <option value="drive">Placement Drive</option>
                                    <option value="test">Assessment Schedule</option>
                                    <option value="shortlist">Shortlist Update</option>
                                    <option value="interview">Interview Alert</option>
                                    <option value="selection">Selection Result</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Target Audience</label>
                                <select name="target_role" class="form-control">
                                    <option value="all">Everyone (All Roles)</option>
                                    <option value="student" selected>Students Only</option>
                                    <option value="recruiter">Recruiters Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attach to Placement Drive (Optional)</label>
                            <select name="drive_id" class="form-control">
                                <option value="">None (General Circular)</option>
                                <?php foreach ($drives as $dr): ?>
                                    <option value="<?= $dr['id'] ?>"><?= e($dr['company_name']) ?> - <?= e($dr['job_role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Message Details <span class="req">*</span></label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Write the full circular announcement text here..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            📢 Broadcast Announcement
                        </button>
                    </form>
                </div>

                <!-- Right: Published Bulletins -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📜 Published Circulars & Notices (<?= count($notifications) ?>)</div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($notifications as $n): ?>
                            <div style="padding: 16px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #ffffff;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="status-badge badge-primary"><?= strtoupper($n['type']) ?></span>
                                        <span class="status-badge badge-secondary">Target: <?= e($n['target_role']) ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 11.5px; color: var(--text-muted);"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></span>
                                        <a href="<?= base_url('admin/notifications.php?delete=' . $n['id']) ?>" onclick="return confirm('Delete this announcement?');" style="color: var(--danger); font-size: 13px;" title="Delete Notice">
                                            🗑️
                                        </a>
                                    </div>
                                </div>

                                <h4 style="font-size: 14.5px; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">
                                    <?= e($n['title']) ?>
                                </h4>

                                <p style="font-size: 13px; color: var(--text-body); line-height: 1.6; margin-bottom: 6px;">
                                    <?= e($n['message']) ?>
                                </p>

                                <?php if (!empty($n['company_name'])): ?>
                                    <div style="font-size: 11.5px; color: var(--accent); font-weight: 600;">
                                        Related to: <?= e($n['company_name']) ?> (<?= e($n['job_title']) ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
