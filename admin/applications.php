<?php
/**
 * Master Application Pipeline & Stage Management
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

// Handle Stage Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stage'])) {
    $app_id = intval($_POST['application_id'] ?? 0);
    $new_stage = $_POST['new_stage'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    // Interview fields if scheduling
    $sched_interview = isset($_POST['schedule_interview']) && $_POST['schedule_interview'] == '1';
    $interview_round = trim($_POST['interview_round'] ?? 'Technical Interview');
    $interview_date = $_POST['interview_date'] ?? date('Y-m-d', strtotime('+3 days'));
    $interview_time = $_POST['interview_time'] ?? '10:00:00';
    $venue_or_link = trim($_POST['venue_or_link'] ?? 'Placement Cell Room 2 / Microsoft Teams');

    if ($app_id > 0 && !empty($new_stage)) {
        try {
            $pdo->beginTransaction();

            // Fetch app details
            $a_stmt = $pdo->prepare("SELECT a.*, s.user_id, s.name as student_name, d.job_role, d.package_lpa, d.company_id, c.name as company_name 
                                     FROM applications a 
                                     JOIN students s ON a.student_id = s.id 
                                     JOIN placement_drives d ON a.drive_id = d.id 
                                     JOIN companies c ON d.company_id = c.id 
                                     WHERE a.id = ?");
            $a_stmt->execute([$app_id]);
            $app = $a_stmt->fetch();

            if ($app) {
                // Update Application Stage
                $up = $pdo->prepare("UPDATE applications SET current_stage = ?, remarks = ? WHERE id = ?");
                $up->execute([$new_stage, $remarks, $app_id]);

                // If candidate is SELECTED:
                if ($new_stage === 'Selected') {
                    // Update Student placement_status to 'Placed'
                    $s_up = $pdo->prepare("UPDATE students SET placement_status = 'Placed' WHERE id = ?");
                    $s_up->execute([$app['student_id']]);

                    // Insert or replace into placement_records
                    $pl_stmt = $pdo->prepare("INSERT INTO placement_records (student_id, company_id, drive_id, job_role, package_lpa, offer_date) VALUES (?, ?, ?, ?, ?, CURDATE())");
                    $pl_stmt->execute([$app['student_id'], $app['company_id'], $app['drive_id'], $app['job_role'], $app['package_lpa']]);

                    // Celebrate Notification
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, target_role, drive_id) VALUES (?, ?, 'selection', 'student', ?)");
                    $notif_stmt->execute([
                        "🎉 Congratulations on Job Offer from {$app['company_name']}!",
                        "You have been officially selected for the position of {$app['job_role']} at {$app['company_name']} with an annual CTC of ₹{$app['package_lpa']} LPA. Check the Placements Registry for details.",
                        $app['drive_id']
                    ]);
                }

                // If interview was scheduled:
                if ($sched_interview) {
                    $int_stmt = $pdo->prepare("INSERT INTO interviews (application_id, round_name, interview_date, interview_time, venue_or_link, notes) VALUES (?, ?, ?, ?, ?, ?)");
                    $int_stmt->execute([$app_id, $interview_round, $interview_date, $interview_time, $venue_or_link, $remarks]);

                    // Interview Notification
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, target_role, drive_id) VALUES (?, ?, 'interview', 'student', ?)");
                    $notif_stmt->execute([
                        "Interview Scheduled: {$app['company_name']} ({$interview_round})",
                        "Your {$interview_round} with {$app['company_name']} has been scheduled on " . date('d M Y', strtotime($interview_date)) . " at " . date('h:i A', strtotime($interview_time)) . ". Venue/Link: {$venue_or_link}.",
                        $app['drive_id']
                    ]);
                }

                $pdo->commit();
                set_flash('success', "Candidate application stage updated to '{$new_stage}' successfully!");
            } else {
                $pdo->rollBack();
                set_flash('danger', 'Application record not found.');
            }
            header('Location: ' . base_url('admin/applications.php'));
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', 'Error updating stage: ' . $e->getMessage());
        }
    }
}

// Filters
$filter_drive = intval($_GET['drive_id'] ?? 0);
$filter_stage = trim($_GET['stage'] ?? '');

$query = "SELECT a.*, s.register_no, u.name as student_name, s.department, s.cgpa, s.backlogs, s.resume_path, 
                 d.job_title, d.job_role, d.package_lpa, c.name as company_name 
          FROM applications a 
          JOIN students s ON a.student_id = s.id 
          JOIN users u ON s.user_id = u.id 
          JOIN placement_drives d ON a.drive_id = d.id 
          JOIN companies c ON d.company_id = c.id 
          WHERE 1=1";
$params = [];

if ($filter_drive > 0) {
    $query .= " AND a.drive_id = ?";
    $params[] = $filter_drive;
}

if (!empty($filter_stage)) {
    $query .= " AND a.current_stage = ?";
    $params[] = $filter_stage;
}

$query .= " ORDER BY a.applied_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Fetch drives for dropdown
$drives_list = $pdo->query("SELECT d.id, d.job_role, c.name as company_name FROM placement_drives d JOIN companies c ON d.company_id = c.id ORDER BY c.name ASC")->fetchAll();

$page_title = 'Application Screening Pipeline';
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
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Master Recruitment Pipeline</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Screen applicants, advance recruitment stages, schedule interviews, and finalize job selections.
                    </p>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                    Total Submissions: <strong style="color:var(--text-main);"><?= count($applications) ?></strong>
                </div>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom: 24px; padding: 18px 24px;">
                <form method="GET" action="<?= base_url('admin/applications.php') ?>" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 14px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Filter by Placement Drive</label>
                        <select name="drive_id" class="form-control">
                            <option value="0">All Placement Drives</option>
                            <?php foreach ($drives_list as $dl): ?>
                                <option value="<?= $dl['id'] ?>" <?= ($filter_drive == $dl['id']) ? 'selected' : '' ?>>
                                    <?= e($dl['company_name']) ?> - <?= e($dl['job_role']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Filter by Stage</label>
                        <select name="stage" class="form-control">
                            <option value="">All Stages</option>
                            <option value="Applied" <?= ($filter_stage === 'Applied') ? 'selected' : '' ?>>Applied</option>
                            <option value="Under Review" <?= ($filter_stage === 'Under Review') ? 'selected' : '' ?>>Under Review</option>
                            <option value="Shortlisted" <?= ($filter_stage === 'Shortlisted') ? 'selected' : '' ?>>Shortlisted</option>
                            <option value="Aptitude" <?= ($filter_stage === 'Aptitude') ? 'selected' : '' ?>>Aptitude</option>
                            <option value="Coding" <?= ($filter_stage === 'Coding') ? 'selected' : '' ?>>Coding</option>
                            <option value="Technical Interview" <?= ($filter_stage === 'Technical Interview') ? 'selected' : '' ?>>Technical Interview</option>
                            <option value="HR Interview" <?= ($filter_stage === 'HR Interview') ? 'selected' : '' ?>>HR Interview</option>
                            <option value="Selected" <?= ($filter_stage === 'Selected') ? 'selected' : '' ?>>Selected (Offer)</option>
                            <option value="Rejected" <?= ($filter_stage === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
                        <?php if ($filter_drive > 0 || !empty($filter_stage)): ?>
                            <a href="<?= base_url('admin/applications.php') ?>" class="btn btn-outline" style="height: 42px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Applications Master Table -->
            <div class="card">
                <?php if (empty($applications)): ?>
                    <div style="text-align: center; padding: 48px 24px;">
                        <div style="font-size: 36px; margin-bottom: 12px;">📑</div>
                        <h3 style="font-size: 16px; font-weight: 700;">No applications found</h3>
                        <p style="color: var(--text-muted); font-size: 13.5px; margin-top: 4px;">There are no candidate submissions matching your selected filter criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Candidate Name & Reg No</th>
                                    <th>Dept & CGPA</th>
                                    <th>Drive & Package</th>
                                    <th>Applied On</th>
                                    <th>Current Stage</th>
                                    <th>Resume</th>
                                    <th>Update Recruitment Stage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('admin/student-view.php?id=' . $app['student_id']) ?>" style="font-weight: 700; color: var(--text-main);">
                                                <?= e($app['student_name']) ?>
                                            </a>
                                            <div style="font-size: 11.5px; color: var(--text-muted);">
                                                <code><?= e($app['register_no']) ?></code>
                                            </div>
                                        </td>
                                        <td>
                                            <?= e($app['department']) ?><br>
                                            <strong style="color: var(--primary); font-size: 12.5px;">⭐ <?= number_format($app['cgpa'], 2) ?></strong>
                                            <span style="font-size: 11px; color: <?= ($app['backlogs'] > 0) ? '#dc2626' : '#059669' ?>">
                                                (<?= $app['backlogs'] ?> backlogs)
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= e($app['company_name']) ?></strong>
                                            <div style="font-size: 12px; color: var(--text-muted);"><?= e($app['job_role']) ?></div>
                                            <div style="font-size: 11.5px; color: #059669; font-weight: 700;">₹<?= number_format($app['package_lpa'], 2) ?> LPA</div>
                                        </td>
                                        <td><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
                                        <td><?= stage_badge($app['current_stage']) ?></td>
                                        <td>
                                            <?php if (!empty($app['resume_path'])): ?>
                                                <a href="<?= base_url('uploads/resumes/' . $app['resume_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                                                    📄 PDF
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size: 11px; color: var(--text-muted);">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="min-width: 220px;">
                                            <form method="POST" action="<?= base_url('admin/applications.php') ?>" style="display: flex; flex-direction: column; gap: 6px;">
                                                <input type="hidden" name="update_stage" value="1">
                                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

                                                <div style="display: flex; gap: 6px;">
                                                    <select name="new_stage" class="form-control" style="font-size: 12px; padding: 4px 8px; height: 32px;">
                                                        <option value="Applied" <?= ($app['current_stage'] === 'Applied') ? 'selected' : '' ?>>Applied</option>
                                                        <option value="Under Review" <?= ($app['current_stage'] === 'Under Review') ? 'selected' : '' ?>>Under Review</option>
                                                        <option value="Shortlisted" <?= ($app['current_stage'] === 'Shortlisted') ? 'selected' : '' ?>>Shortlisted</option>
                                                        <option value="Aptitude" <?= ($app['current_stage'] === 'Aptitude') ? 'selected' : '' ?>>Aptitude</option>
                                                        <option value="Coding" <?= ($app['current_stage'] === 'Coding') ? 'selected' : '' ?>>Coding</option>
                                                        <option value="Technical Interview" <?= ($app['current_stage'] === 'Technical Interview') ? 'selected' : '' ?>>Tech Interview</option>
                                                        <option value="HR Interview" <?= ($app['current_stage'] === 'HR Interview') ? 'selected' : '' ?>>HR Interview</option>
                                                        <option value="Selected" <?= ($app['current_stage'] === 'Selected') ? 'selected' : '' ?>>🎉 Selected (Hire)</option>
                                                        <option value="Rejected" <?= ($app['current_stage'] === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm" style="height: 32px; padding: 4px 10px;">
                                                        Save
                                                    </button>
                                                </div>

                                                <input type="text" name="remarks" class="form-control" placeholder="Optional remarks..." value="<?= e($app['remarks']) ?>" style="font-size: 11.5px; padding: 4px 8px; height: 28px;">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
