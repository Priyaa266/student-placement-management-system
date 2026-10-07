<?php
/**
 * Student Directory & Academic Roster Management
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$search = trim($_GET['search'] ?? '');
$dept = trim($_GET['dept'] ?? '');
$status = trim($_GET['status'] ?? '');
$min_cgpa = floatval($_GET['min_cgpa'] ?? 0);

$query = "SELECT s.*, u.name, u.email, u.status as user_status 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR s.register_no LIKE ? OR u.email LIKE ? OR s.phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($dept)) {
    $query .= " AND s.department = ?";
    $params[] = $dept;
}

if (!empty($status)) {
    $query .= " AND s.placement_status = ?";
    $params[] = $status;
}

if ($min_cgpa > 0) {
    $query .= " AND s.cgpa >= ?";
    $params[] = $min_cgpa;
}

$query .= " ORDER BY s.cgpa DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

$page_title = 'Manage Students Directory';
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
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Campus Student Directory</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Manage student placement profiles, verify academic credentials, and monitor placement progress.
                    </p>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                    Total Enrolled: <strong style="color:var(--text-main);"><?= count($students) ?> Students</strong>
                </div>
            </div>

            <!-- Search and Filter Form -->
            <div class="card" style="margin-bottom: 24px; padding: 18px 24px;">
                <form method="GET" action="<?= base_url('admin/students.php') ?>" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 14px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Search Student</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, Register No or Email..." value="<?= e($search) ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Department</label>
                        <select name="dept" class="form-control">
                            <option value="">All Departments</option>
                            <option value="CSE" <?= ($dept === 'CSE') ? 'selected' : '' ?>>CSE</option>
                            <option value="IT" <?= ($dept === 'IT') ? 'selected' : '' ?>>IT</option>
                            <option value="ECE" <?= ($dept === 'ECE') ? 'selected' : '' ?>>ECE</option>
                            <option value="EEE" <?= ($dept === 'EEE') ? 'selected' : '' ?>>EEE</option>
                            <option value="MECH" <?= ($dept === 'MECH') ? 'selected' : '' ?>>MECH</option>
                            <option value="CIVIL" <?= ($dept === 'CIVIL') ? 'selected' : '' ?>>CIVIL</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Placed" <?= ($status === 'Placed') ? 'selected' : '' ?>>Placed</option>
                            <option value="In Process" <?= ($status === 'In Process') ? 'selected' : '' ?>>In Process</option>
                            <option value="Not Placed" <?= ($status === 'Not Placed') ? 'selected' : '' ?>>Not Placed</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Min CGPA</label>
                        <select name="min_cgpa" class="form-control">
                            <option value="0">Any CGPA</option>
                            <option value="8.0" <?= ($min_cgpa == 8.0) ? 'selected' : '' ?>>8.0+ CGPA</option>
                            <option value="7.5" <?= ($min_cgpa == 7.5) ? 'selected' : '' ?>>7.5+ CGPA</option>
                            <option value="6.5" <?= ($min_cgpa == 6.5) ? 'selected' : '' ?>>6.5+ CGPA</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
                        <?php if (!empty($search) || !empty($dept) || !empty($status) || $min_cgpa > 0): ?>
                            <a href="<?= base_url('admin/students.php') ?>" class="btn btn-outline" style="height: 42px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Student Directory Table Card -->
            <div class="card">
                <?php if (empty($students)): ?>
                    <div style="text-align: center; padding: 48px 24px;">
                        <div style="font-size: 36px; margin-bottom: 12px;">🔍</div>
                        <h3 style="font-size: 16px; font-weight: 700;">No students match criteria</h3>
                        <p style="color: var(--text-muted); font-size: 13.5px; margin-top: 4px;">Try modifying search parameters or filter settings.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Register No</th>
                                    <th>Student Details</th>
                                    <th>Dept & Year</th>
                                    <th>CGPA</th>
                                    <th>Backlogs</th>
                                    <th>10th / 12th (%)</th>
                                    <th>Status</th>
                                    <th>Resume</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><code><?= e($s['register_no']) ?></code></td>
                                        <td>
                                            <strong style="color: var(--text-main); font-size: 14px;"><?= e($s['name']) ?></strong>
                                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($s['email']) ?> • <?= e($s['phone']) ?></div>
                                        </td>
                                        <td><?= e($s['department']) ?> (Yr <?= e($s['year_of_study']) ?>, Sem <?= e($s['semester']) ?>)</td>
                                        <td><strong style="color: var(--primary); font-size: 14px;">⭐ <?= number_format($s['cgpa'], 2) ?></strong></td>
                                        <td>
                                            <?php if ($s['backlogs'] > 0): ?>
                                                <span class="status-badge badge-danger"><?= $s['backlogs'] ?> Active</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-success">0 (Clear)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($s['tenth_percentage'], 1) ?>% / <?= number_format($s['twelfth_percentage'], 1) ?>%</td>
                                        <td>
                                            <?php if ($s['placement_status'] === 'Placed'): ?>
                                                <span class="status-badge badge-success">Placed</span>
                                            <?php elseif ($s['placement_status'] === 'In Process'): ?>
                                                <span class="status-badge badge-warning">In Process</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-secondary">Not Placed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($s['resume_path'])): ?>
                                                <a href="<?= base_url('uploads/resumes/' . $s['resume_path']) ?>" target="_blank" class="btn btn-outline btn-sm" title="Download Resume">
                                                    📄 PDF
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 11px;">Not uploaded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('admin/student-view.php?id=' . $s['id']) ?>" class="btn btn-primary btn-sm">
                                                View Profile
                                            </a>
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
