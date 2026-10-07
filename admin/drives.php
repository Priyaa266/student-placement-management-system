<?php
/**
 * Placement Drives Management
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

// Fetch all companies for drive creation
$companies = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll();

// Handle Create New Drive (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_drive'])) {
    $company_id = intval($_POST['company_id'] ?? 0);
    $job_title = trim($_POST['job_title'] ?? '');
    $job_role = trim($_POST['job_role'] ?? '');
    $package_lpa = floatval($_POST['package_lpa'] ?? 0);
    $job_location = trim($_POST['job_location'] ?? '');
    $job_description = trim($_POST['job_description'] ?? '');
    $required_skills = trim($_POST['required_skills'] ?? '');
    $min_cgpa = floatval($_POST['min_cgpa'] ?? 6.0);
    $max_backlogs = intval($_POST['max_backlogs'] ?? 0);
    $min_tenth = floatval($_POST['min_tenth_percentage'] ?? 60.0);
    $min_twelfth = floatval($_POST['min_twelfth_percentage'] ?? 60.0);
    
    // Eligible departments
    $depts = $_POST['eligible_departments'] ?? ['CSE', 'IT', 'ECE'];
    $eligible_departments = is_array($depts) ? implode(', ', $depts) : trim($depts);
    
    $selection_process = trim($_POST['selection_process'] ?? '');
    $application_deadline = $_POST['application_deadline'] ?? date('Y-m-d', strtotime('+15 days'));
    $drive_date = !empty($_POST['drive_date']) ? $_POST['drive_date'] : null;
    $status = $_POST['status'] ?? 'active';

    if ($company_id <= 0 || empty($job_title) || empty($job_role) || $package_lpa <= 0) {
        set_flash('danger', 'Please fill all required drive details with a valid package and company.');
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO placement_drives 
                (company_id, job_title, job_role, package_lpa, job_location, job_description, 
                 required_skills, min_cgpa, max_backlogs, min_tenth_percentage, min_twelfth_percentage, 
                 eligible_departments, selection_process, application_deadline, drive_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $company_id, $job_title, $job_role, $package_lpa, $job_location, $job_description,
                $required_skills, $min_cgpa, $max_backlogs, $min_tenth, $min_twelfth,
                $eligible_departments, $selection_process, $application_deadline, $drive_date, $status
            ]);
            $drive_id = $pdo->lastInsertId();

            // Fetch company name for broadcast notice
            $c_stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
            $c_stmt->execute([$company_id]);
            $comp_name = $c_stmt->fetchColumn() ?: 'Company';

            // Auto-post Notification
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, target_role, drive_id) VALUES (?, ?, 'drive', 'student', ?)");
            $notif_stmt->execute([
                "New Drive: {$comp_name} ({$job_role} - ₹{$package_lpa} LPA)",
                "{$comp_name} has announced campus recruitment for {$job_role}. Min CGPA: {$min_cgpa}. Eligible branches: {$eligible_departments}. Deadline: " . date('d M Y', strtotime($application_deadline)),
                $drive_id
            ]);

            $pdo->commit();

            set_flash('success', "Placement drive for {$comp_name} posted successfully and notification broadcast to students!");
            header('Location: ' . base_url('admin/drives.php'));
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', 'Error creating drive: ' . $e->getMessage());
        }
    }
}

// Fetch all drives with company name & applicant count
$drives_stmt = $pdo->query("SELECT d.*, c.name as company_name, c.logo, 
                                   COUNT(a.id) as applicant_count 
                            FROM placement_drives d 
                            JOIN companies c ON d.company_id = c.id 
                            LEFT JOIN applications a ON d.id = a.drive_id 
                            GROUP BY d.id 
                            ORDER BY d.created_at DESC");
$drives = $drives_stmt->fetchAll();

$page_title = 'Manage Placement Drives';
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
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Campus Placement Drives</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Configure recruitment drives, set institutional cutoff parameters, and manage candidate pipelines.
                    </p>
                </div>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('post-drive-card').scrollIntoView({behavior: 'smooth'})">
                    + Post New Placement Drive
                </button>
            </div>

            <!-- Existing Drives List Card -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <div class="card-title">💼 Active & Scheduled Recruitment Drives (<?= count($drives) ?>)</div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company & Role</th>
                                <th>Package (CTC)</th>
                                <th>Location</th>
                                <th>Criteria (CGPA/Backlogs)</th>
                                <th>Eligible Branches</th>
                                <th>Deadline</th>
                                <th>Applicants</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drives as $d): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--text-main); font-size: 14px;"><?= e($d['company_name']) ?></strong>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= e($d['job_role']) ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: #059669; font-size: 13.5px;">₹<?= number_format($d['package_lpa'], 2) ?> LPA</strong>
                                    </td>
                                    <td>📍 <?= e($d['job_location']) ?></td>
                                    <td>
                                        Min <strong><?= number_format($d['min_cgpa'], 2) ?></strong> CGPA<br>
                                        <small style="color: var(--text-muted);">Max <?= $d['max_backlogs'] ?> Backlogs</small>
                                    </td>
                                    <td style="font-size: 12px; max-width: 160px;"><?= e($d['eligible_departments']) ?></td>
                                    <td>
                                        <span style="color: var(--danger); font-weight: 600; font-size: 12px;">
                                            <?= date('d M Y', strtotime($d['application_deadline'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-primary">
                                            <?= $d['applicant_count'] ?> Candidates
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($d['status'] === 'active'): ?>
                                            <span class="status-badge badge-success">Active</span>
                                        <?php elseif ($d['status'] === 'upcoming'): ?>
                                            <span class="status-badge badge-warning">Upcoming</span>
                                        <?php else: ?>
                                            <span class="status-badge badge-secondary">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/applications.php?drive_id=' . $d['id']) ?>" class="btn btn-outline btn-sm">
                                            View Applicants
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Post New Drive Form Card -->
            <div class="card" id="post-drive-card">
                <div class="card-header">
                    <div class="card-title">📝 Create & Launch New Placement Drive</div>
                </div>

                <form method="POST" action="<?= base_url('admin/drives.php') ?>">
                    <input type="hidden" name="create_drive" value="1">

                    <h4 style="font-size: 13px; text-transform: uppercase; color: var(--accent); margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                        1. Job Role & Compensation
                    </h4>

                    <div class="grid-cols-3">
                        <div class="form-group">
                            <label class="form-label">Recruiting Company <span class="req">*</span></label>
                            <select name="company_id" class="form-control" required>
                                <option value="">-- Select Company --</option>
                                <?php foreach ($companies as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Drive Title <span class="req">*</span></label>
                            <input type="text" name="job_title" class="form-control" placeholder="e.g. Software Engineer - Graduate Hiring" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Job Role Designation <span class="req">*</span></label>
                            <input type="text" name="job_role" class="form-control" placeholder="e.g. Associate SDE" required>
                        </div>
                    </div>

                    <div class="grid-cols-3">
                        <div class="form-group">
                            <label class="form-label">Annual CTC Package (in LPA) <span class="req">*</span></label>
                            <input type="number" step="0.01" min="1" max="100" name="package_lpa" class="form-control" placeholder="e.g. 12.50" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Job Location <span class="req">*</span></label>
                            <input type="text" name="job_location" class="form-control" placeholder="e.g. Bengaluru / Chennai / Hybrid" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Drive Status</label>
                            <select name="status" class="form-control">
                                <option value="active" selected>Active (Accepting Applications)</option>
                                <option value="upcoming">Upcoming (Announced)</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>

                    <h4 style="font-size: 13px; text-transform: uppercase; color: var(--accent); margin-top: 14px; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                        2. Institutional Eligibility Criteria
                    </h4>

                    <div class="grid-cols-4">
                        <div class="form-group">
                            <label class="form-label">Minimum CGPA <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" max="10" name="min_cgpa" class="form-control" value="7.00" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Max Standing Backlogs <span class="req">*</span></label>
                            <input type="number" min="0" max="10" name="max_backlogs" class="form-control" value="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Min 10th Standard (%) <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" name="min_tenth_percentage" class="form-control" value="65.00" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Min 12th Standard (%) <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" name="min_twelfth_percentage" class="form-control" value="65.00" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Eligible Departments (Hold Ctrl to select multiple):</label>
                        <select name="eligible_departments[]" class="form-control" multiple style="height: 90px;">
                            <option value="CSE" selected>Computer Science & Engineering (CSE)</option>
                            <option value="IT" selected>Information Technology (IT)</option>
                            <option value="ECE" selected>Electronics & Communication Engg (ECE)</option>
                            <option value="EEE">Electrical & Electronics Engg (EEE)</option>
                            <option value="MECH">Mechanical Engineering (MECH)</option>
                            <option value="CIVIL">Civil Engineering (CIVIL)</option>
                        </select>
                    </div>

                    <h4 style="font-size: 13px; text-transform: uppercase; color: var(--accent); margin-top: 14px; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                        3. Description & Deadlines
                    </h4>

                    <div class="grid-cols-2">
                        <div class="form-group">
                            <label class="form-label">Application Deadline <span class="req">*</span></label>
                            <input type="date" name="application_deadline" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tentative Drive / Assessment Date</label>
                            <input type="date" name="drive_date" class="form-control" value="<?= date('Y-m-d', strtotime('+20 days')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Required Skills & Technologies (Comma-separated)</label>
                        <input type="text" name="required_skills" class="form-control" placeholder="e.g. Java, Python, Data Structures, OOP, SQL, Problem Solving">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Detailed Job Description <span class="req">*</span></label>
                        <textarea name="job_description" class="form-control" rows="4" placeholder="Detail the job responsibilities, project domains, and requirements..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Selection Process Overview</label>
                        <input type="text" name="selection_process" class="form-control" placeholder="e.g. Online Aptitude Test -> Technical Coding Assessment -> System Design Interview -> HR Round">
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 14px;">
                        Publish Placement Drive & Notify Students
                    </button>
                </form>
            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
