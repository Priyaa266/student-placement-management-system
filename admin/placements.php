<?php
/**
 * Campus Placements Registry & Offer Analytics
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

// Handle Record New Placement (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_placement'])) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $company_id = intval($_POST['company_id'] ?? 0);
    $job_role = trim($_POST['job_role'] ?? '');
    $package_lpa = floatval($_POST['package_lpa'] ?? 0);
    $offer_date = $_POST['offer_date'] ?? date('Y-m-d');

    if ($student_id <= 0 || $company_id <= 0 || empty($job_role) || $package_lpa <= 0) {
        set_flash('danger', 'Please enter valid student, company, role and package details.');
    } else {
        try {
            $pdo->beginTransaction();

            // Insert into placement_records
            $stmt = $pdo->prepare("INSERT INTO placement_records (student_id, company_id, job_role, package_lpa, offer_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $company_id, $job_role, $package_lpa, $offer_date]);

            // Update student placement status
            $s_stmt = $pdo->prepare("UPDATE students SET placement_status = 'Placed' WHERE id = ?");
            $s_stmt->execute([$student_id]);

            // Add celebration notification
            $c_name = $pdo->query("SELECT name FROM companies WHERE id = {$company_id}")->fetchColumn() ?: 'Company';
            $s_name = $pdo->query("SELECT u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = {$student_id}")->fetchColumn() ?: 'Student';

            $notif_stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, target_role) VALUES (?, ?, 'selection', 'all')");
            $notif_stmt->execute([
                "🎉 Placement Success: {$s_name} placed at {$c_name}!",
                "Hearty congratulations to {$s_name} on securing an offer as {$job_role} at {$c_name} with ₹{$package_lpa} LPA!",
            ]);

            $pdo->commit();

            set_flash('success', "Placement record for {$s_name} saved successfully!");
            header('Location: ' . base_url('admin/placements.php'));
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', 'Error recording placement: ' . $e->getMessage());
        }
    }
}

// Fetch all placed records
$records_stmt = $pdo->query("SELECT pr.*, s.register_no, u.name as student_name, s.department, s.cgpa, 
                                     c.name as company_name, c.logo 
                              FROM placement_records pr 
                              JOIN students s ON pr.student_id = s.id 
                              JOIN users u ON s.user_id = u.id 
                              JOIN companies c ON pr.company_id = c.id 
                              ORDER BY pr.offer_date DESC");
$placements = $records_stmt->fetchAll();

// Statistics
$total_students = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_placed = count($placements);
$placement_pct = ($total_students > 0) ? round(($total_placed / $total_students) * 100, 1) : 0;
$max_pkg = (float)$pdo->query("SELECT MAX(package_lpa) FROM placement_records")->fetchColumn() ?: 0;
$avg_pkg = (float)$pdo->query("SELECT AVG(package_lpa) FROM placement_records")->fetchColumn() ?: 0;

// Students and Companies for form dropdowns
$students_list = $pdo->query("SELECT s.id, s.register_no, u.name, s.department FROM students s JOIN users u ON s.user_id = u.id ORDER BY u.name ASC")->fetchAll();
$companies_list = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll();

$page_title = 'Campus Placements Registry';
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
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Official Campus Placements Registry</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Certified repository of job offers, compensation packages, and recruitment milestones.
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="window.print()">
                        🖨️ Print Placement Report
                    </button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('record-placement-card').scrollIntoView({behavior: 'smooth'})">
                        + Record Job Offer
                    </button>
                </div>
            </div>

            <!-- Analytics Cards -->
            <div class="grid-cols-4">
                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Placed Students</div>
                        <div class="kpi-value" style="color: #059669;"><?= $total_placed ?></div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Recruitment offers</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-emerald">🎖️</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Placement Rate</div>
                        <div class="kpi-value"><?= $placement_pct ?>%</div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Of enrolled batch</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-blue">📈</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Highest Package</div>
                        <div class="kpi-value" style="color: #047857;">₹<?= number_format($max_pkg, 2) ?> LPA</div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Top campus offer</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-amber">⭐</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Average Package</div>
                        <div class="kpi-value">₹<?= number_format($avg_pkg, 2) ?> LPA</div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Mean campus CTC</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-purple">💼</div>
                </div>
            </div>

            <!-- Placements Table Card -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <div class="card-title">🎖️ Verified Placement Records (<?= count($placements) ?>)</div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Dept</th>
                                <th>CGPA</th>
                                <th>Recruiting Company</th>
                                <th>Job Role</th>
                                <th>Annual CTC (LPA)</th>
                                <th>Offer Date</th>
                                <th>Offer Letter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($placements as $p): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('admin/student-view.php?id=' . $p['student_id']) ?>" style="font-weight: 700; color: var(--text-main);">
                                            <?= e($p['student_name']) ?>
                                        </a>
                                        <div style="font-size: 11.5px; color: var(--text-muted);">
                                            <code><?= e($p['register_no']) ?></code>
                                        </div>
                                    </td>
                                    <td><?= e($p['department']) ?></td>
                                    <td>⭐ <?= number_format($p['cgpa'], 2) ?></td>
                                    <td>
                                        <strong><?= e($p['company_name']) ?></strong>
                                    </td>
                                    <td><?= e($p['job_role']) ?></td>
                                    <td>
                                        <strong style="color: #059669; font-size: 14px;">₹ <?= number_format($p['package_lpa'], 2) ?> LPA</strong>
                                    </td>
                                    <td><?= date('d M Y', strtotime($p['offer_date'])) ?></td>
                                    <td>
                                        <?php if (!empty($p['offer_letter_path'])): ?>
                                            <span class="status-badge badge-primary">📄 Verified</span>
                                        <?php else: ?>
                                            <span style="font-size: 11.5px; color: var(--text-muted);">Letter Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form to Record a Job Offer -->
            <div class="card" id="record-placement-card">
                <div class="card-header">
                    <div class="card-title">📝 Record Official Student Job Offer</div>
                </div>

                <form method="POST" action="<?= base_url('admin/placements.php') ?>">
                    <input type="hidden" name="record_placement" value="1">

                    <div class="grid-cols-2">
                        <div class="form-group">
                            <label class="form-label">Placed Student <span class="req">*</span></label>
                            <select name="student_id" class="form-control" required>
                                <option value="">-- Select Candidate --</option>
                                <?php foreach ($students_list as $sl): ?>
                                    <option value="<?= $sl['id'] ?>">
                                        <?= e($sl['name']) ?> (<?= e($sl['register_no']) ?> - <?= e($sl['department']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Hiring Company <span class="req">*</span></label>
                            <select name="company_id" class="form-control" required>
                                <option value="">-- Select Company --</option>
                                <?php foreach ($companies_list as $cl): ?>
                                    <option value="<?= $cl['id'] ?>"><?= e($cl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-cols-3">
                        <div class="form-group">
                            <label class="form-label">Designation / Job Role <span class="req">*</span></label>
                            <input type="text" name="job_role" class="form-control" placeholder="e.g. Systems Engineer" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Annual CTC Package (in LPA) <span class="req">*</span></label>
                            <input type="number" step="0.01" min="1" max="100" name="package_lpa" class="form-control" placeholder="e.g. 7.50" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Offer Date <span class="req">*</span></label>
                            <input type="date" name="offer_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 10px 28px;">
                        Record Placement & Celebrate Offer
                    </button>
                </form>
            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
