<?php
/**
 * Detailed Student Profile Inspector & Management View
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$student_id = intval($_GET['id'] ?? 0);
if ($student_id <= 0) {
    header('Location: ' . base_url('admin/students.php'));
    exit;
}

// Fetch student details
$stmt = $pdo->prepare("SELECT s.*, u.name, u.email, u.created_at as registered_on 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('danger', 'Student record not found.');
    header('Location: ' . base_url('admin/students.php'));
    exit;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['placement_status'] ?? 'Not Placed';
    $up_stmt = $pdo->prepare("UPDATE students SET placement_status = ? WHERE id = ?");
    $up_stmt->execute([$new_status, $student_id]);

    set_flash('success', "Placement status updated to '{$new_status}'.");
    header('Location: ' . base_url('admin/student-view.php?id=' . $student_id));
    exit;
}

// Fetch student applications
$apps_stmt = $pdo->prepare("SELECT a.*, d.job_title, d.job_role, d.package_lpa, c.name as company_name 
                            FROM applications a 
                            JOIN placement_drives d ON a.drive_id = d.id 
                            JOIN companies c ON d.company_id = c.id 
                            WHERE a.student_id = ? 
                            ORDER BY a.applied_at DESC");
$apps_stmt->execute([$student_id]);
$applications = $apps_stmt->fetchAll();

// Fetch student test scores
$tests_stmt = $pdo->prepare("SELECT r.*, 
                                    CASE WHEN r.test_type = 'aptitude' THEN at.title ELSE ct.title END as test_title 
                             FROM test_results r 
                             LEFT JOIN aptitude_tests at ON (r.test_type = 'aptitude' AND r.test_id = at.id) 
                             LEFT JOIN coding_tests ct ON (r.test_type = 'coding' AND r.test_id = ct.id) 
                             WHERE r.student_id = ? 
                             ORDER BY r.taken_at DESC");
$tests_stmt->execute([$student_id]);
$test_results = $tests_stmt->fetchAll();

$page_title = 'Student Profile: ' . $student['name'];
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

            <div style="margin-bottom: 16px;">
                <a href="<?= base_url('admin/students.php') ?>" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                    ← Back to Students Roster
                </a>
            </div>

            <!-- Profile Summary Card -->
            <div class="card" style="margin-bottom: 24px; padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div style="display: flex; align-items: center; gap: 18px;">
                        <span class="user-avatar" style="width: 64px; height: 64px; font-size: 24px; background: #2563eb;">
                            <?= strtoupper(substr($student['name'], 0, 1)) ?>
                        </span>
                        <div>
                            <span style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                <?= e($student['department']) ?> • Year <?= e($student['year_of_study']) ?>, Sem <?= e($student['semester']) ?>
                            </span>
                            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 2px;">
                                <?= e($student['name']) ?>
                            </h2>
                            <div style="display: flex; gap: 14px; font-size: 13px; color: var(--text-muted); margin-top: 4px; flex-wrap: wrap;">
                                <span>🎓 <strong>Reg No:</strong> <?= e($student['register_no']) ?></span>
                                <span>📧 <?= e($student['email']) ?></span>
                                <span>📞 <?= e($student['phone']) ?></span>
                                <span>⭐ <strong>CGPA:</strong> <?= number_format($student['cgpa'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Placement Status Changer -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 18px;">
                        <form method="POST" action="<?= base_url('admin/student-view.php?id=' . $student_id) ?>" style="display: flex; align-items: center; gap: 10px;">
                            <input type="hidden" name="update_status" value="1">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); white-space: nowrap;">Status:</label>
                            <select name="placement_status" class="form-control" style="width: 140px; padding: 6px 10px; font-size: 13px;">
                                <option value="Not Placed" <?= ($student['placement_status'] === 'Not Placed') ? 'selected' : '' ?>>Not Placed</option>
                                <option value="In Process" <?= ($student['placement_status'] === 'In Process') ? 'selected' : '' ?>>In Process</option>
                                <option value="Placed" <?= ($student['placement_status'] === 'Placed') ? 'selected' : '' ?>>Placed</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Update</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Two-Column Layout: Academic & Skills vs Applications & Scores -->
            <div class="grid-cols-2">

                <!-- Left: Full Profile Details -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🎓 Academic Credentials</div>
                        </div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                            <tbody>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 0; color: var(--text-muted);">Current CGPA:</td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 700; color: var(--primary);">⭐ <?= number_format($student['cgpa'], 2) ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 0; color: var(--text-muted);">Standing Backlogs:</td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 700;">
                                        <?= ($student['backlogs'] > 0) ? '<span style="color:#dc2626;">' . $student['backlogs'] . ' Active</span>' : '<span style="color:#059669;">0 (Clear)</span>' ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 0; color: var(--text-muted);">10th Standard Percentage:</td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 700;"><?= number_format($student['tenth_percentage'], 2) ?>%</td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 0; color: var(--text-muted);">12th Standard / Diploma:</td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 700;"><?= number_format($student['twelfth_percentage'], 2) ?>%</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; color: var(--text-muted);">Residential Address:</td>
                                    <td style="padding: 10px 0; text-align: right;"><?= e($student['address'] ?: 'Not provided') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">💡 Skills, Certifications & Resume</div>
                        </div>
                        <div style="font-size: 13px; display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <strong>Programming Languages:</strong><br>
                                <span style="color: var(--text-body);"><?= e($student['programming_languages'] ?: 'None listed') ?></span>
                            </div>
                            <div>
                                <strong>Technical Frameworks & Tools:</strong><br>
                                <span style="color: var(--text-body);"><?= e($student['technical_skills'] ?: 'None listed') ?></span>
                            </div>
                            <div>
                                <strong>Soft Skills:</strong><br>
                                <span style="color: var(--text-body);"><?= e($student['soft_skills'] ?: 'None listed') ?></span>
                            </div>
                            <div>
                                <strong>Certifications:</strong><br>
                                <span style="color: var(--text-body);"><?= e($student['certifications'] ?: 'None listed') ?></span>
                            </div>
                            <div>
                                <strong>Projects:</strong><br>
                                <span style="color: var(--text-body);"><?= e($student['projects'] ?: 'None listed') ?></span>
                            </div>
                            <div style="border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 4px;">
                                <strong>Resume Document:</strong><br>
                                <?php if (!empty($student['resume_path'])): ?>
                                    <a href="<?= base_url('uploads/resumes/' . $student['resume_path']) ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top: 6px;">
                                        📄 View Full Resume (PDF)
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--danger);">No resume uploaded yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right: Applications & Test Results -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <!-- Applications Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">📑 Placement Submissions (<?= count($applications) ?>)</div>
                        </div>

                        <?php if (empty($applications)): ?>
                            <p style="color: var(--text-muted); font-size: 13px;">Student has not submitted any drive applications yet.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($applications as $app): ?>
                                    <div style="padding: 12px 16px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong style="color: var(--text-main); font-size: 13.5px;"><?= e($app['company_name']) ?></strong>
                                            <div style="font-size: 12px; color: var(--text-muted);"><?= e($app['job_role']) ?> (₹<?= $app['package_lpa'] ?> LPA)</div>
                                            <div style="font-size: 11px; color: var(--text-muted);">Applied: <?= date('d M Y', strtotime($app['applied_at'])) ?></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <?= stage_badge($app['current_stage']) ?>
                                            <div style="margin-top: 6px;">
                                                <a href="<?= base_url('admin/applications.php?drive_id=' . $app['drive_id']) ?>" style="font-size: 11.5px; font-weight: 600;">Manage Stage →</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Test Scores Card -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">🏆 Examination Performance</div>
                        </div>

                        <?php if (empty($test_results)): ?>
                            <p style="color: var(--text-muted); font-size: 13px;">No assessment attempts recorded for this student.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($test_results as $tr): ?>
                                    <div style="padding: 12px 16px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong style="font-size: 13px;"><?= e($tr['test_title'] ?: 'Placement Assessment') ?></strong>
                                            <div style="font-size: 11.5px; color: var(--text-muted); text-transform: capitalize;">
                                                Type: <?= e($tr['test_type']) ?> • <?= date('d M Y', strtotime($tr['taken_at'])) ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span style="font-weight: 700; font-size: 13px;"><?= number_format($tr['score'], 1) ?> pts (<?= number_format($tr['percentage'], 1) ?>%)</span>
                                            <div>
                                                <span class="status-badge <?= ($tr['status'] === 'Passed') ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= e($tr['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
