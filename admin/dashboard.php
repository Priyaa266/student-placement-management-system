<?php
/**
 * Placement Officer / Admin Dashboard
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$user = current_user();

// Fetch Aggregate Statistics
$total_students = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_companies = (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$active_drives = (int)$pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'active'")->fetchColumn();
$total_apps = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$shortlisted_apps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE current_stage IN ('Shortlisted', 'Aptitude', 'Coding', 'Technical Interview', 'HR Interview')")->fetchColumn();
$placed_students = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE placement_status = 'Placed'")->fetchColumn();

$placement_rate = ($total_students > 0) ? round(($placed_students / $total_students) * 100, 1) : 0;
$highest_pkg = (float)$pdo->query("SELECT MAX(package_lpa) FROM placement_drives")->fetchColumn() ?: 0;
$avg_pkg = (float)$pdo->query("SELECT AVG(package_lpa) FROM placement_drives")->fetchColumn() ?: 0;

// Department Wise Statistics
$dept_stats = $pdo->query("SELECT department, 
                                  COUNT(*) as total_dept_students,
                                  SUM(CASE WHEN placement_status = 'Placed' THEN 1 ELSE 0 END) as placed_dept_students,
                                  AVG(cgpa) as avg_dept_cgpa 
                           FROM students 
                           GROUP BY department")->fetchAll();

// Recent Applications (Top 6)
$recent_apps = $pdo->query("SELECT a.*, s.register_no, u.name as student_name, s.department, s.cgpa, 
                                   d.job_role, d.package_lpa, c.name as company_name 
                            FROM applications a 
                            JOIN students s ON a.student_id = s.id 
                            JOIN users u ON s.user_id = u.id 
                            JOIN placement_drives d ON a.drive_id = d.id 
                            JOIN companies c ON d.company_id = c.id 
                            ORDER BY a.applied_at DESC LIMIT 6")->fetchAll();

// Pending Contact Inquiries
$pending_inquiries = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();

$page_title = 'Placement Officer Dashboard';
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

            <!-- Admin Banner -->
            <div class="card" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); color: #ffffff; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <span style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #38bdf8;">
                            Academic Placement Office • Central Administration
                        </span>
                        <h2 style="color: #ffffff; font-size: 24px; font-weight: 800; margin-top: 4px;">
                            Placement Directorate Overview
                        </h2>
                        <p style="color: #94a3b8; font-size: 13.5px; margin-top: 4px;">
                            Campus recruitment analytics, drive coordination, student eligibility screening, and company relations.
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="<?= base_url('admin/drives.php?action=create') ?>" class="btn btn-primary" style="background:#2563eb;">
                            + Post New Drive
                        </a>
                        <a href="<?= base_url('admin/notifications.php') ?>" class="btn btn-outline" style="color:#fff; border-color:rgba(255,255,255,0.3);">
                            📢 Broadcast Notice
                        </a>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid-cols-4">
                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Registered Students</div>
                        <div class="kpi-value"><?= $total_students ?></div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Final & pre-final years</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-blue">🎓</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Partner Companies</div>
                        <div class="kpi-value"><?= $total_companies ?></div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Recruiting partners</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-amber">🏢</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Active Drives</div>
                        <div class="kpi-value"><?= $active_drives ?></div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Applications open</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-purple">💼</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-label">Placement Rate</div>
                        <div class="kpi-value" style="color: #059669;"><?= $placement_rate ?>%</div>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;"><?= $placed_students ?> placed students</div>
                    </div>
                    <div class="kpi-icon-wrap kpi-emerald">🎖️</div>
                </div>
            </div>

            <!-- Secondary Metrics Bar -->
            <div class="grid-cols-3">
                <div class="card" style="padding: 18px 24px;">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Submissions</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 4px;"><?= $total_apps ?></div>
                    <span style="font-size: 12px; color: var(--accent); font-weight: 600;"><?= $shortlisted_apps ?> currently in screening/interview</span>
                </div>

                <div class="card" style="padding: 18px 24px;">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Highest Salary Package</div>
                    <div style="font-size: 24px; font-weight: 800; color: #047857; margin-top: 4px;">₹ <?= number_format($highest_pkg, 2) ?> LPA</div>
                    <span style="font-size: 12px; color: var(--text-muted);">Campus record package</span>
                </div>

                <div class="card" style="padding: 18px 24px;">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Student Inquiries</div>
                    <div style="font-size: 24px; font-weight: 800; color: <?= ($pending_inquiries > 0) ? '#dc2626' : '#10b981' ?>; margin-top: 4px;">
                        <?= $pending_inquiries ?> Unread
                    </div>
                    <a href="<?= base_url('admin/settings.php') ?>" style="font-size: 12px; font-weight: 600; color: var(--accent);">View message inbox →</a>
                </div>
            </div>

            <!-- Department-wise Placement Statistics & Distribution -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div class="card-title">📊 Department-wise Placement Performance</div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Enrolled Students</th>
                                <th>Placed Students</th>
                                <th>Placement Rate (%)</th>
                                <th>Progress Visualizer</th>
                                <th>Average Dept CGPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dept_stats as $ds): 
                                $rate = ($ds['total_dept_students'] > 0) ? round(($ds['placed_dept_students'] / $ds['total_dept_students']) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><strong><?= e($ds['department']) ?></strong></td>
                                    <td><?= $ds['total_dept_students'] ?></td>
                                    <td><strong style="color: #059669;"><?= $ds['placed_dept_students'] ?></strong></td>
                                    <td><strong><?= $rate ?>%</strong></td>
                                    <td style="width: 200px;">
                                        <div class="progress-bar-container" style="height: 8px;">
                                            <div class="progress-bar-fill" style="width: <?= max($rate, 4) ?>%;"></div>
                                        </div>
                                    </td>
                                    <td>⭐ <?= number_format($ds['avg_dept_cgpa'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Applications Table with Stage Update CTA -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📑 Recent Candidate Applications</div>
                    <a href="<?= base_url('admin/applications.php') ?>" class="btn btn-outline btn-sm">Manage All Applications →</a>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Dept & CGPA</th>
                                <th>Drive Applied</th>
                                <th>Applied Date</th>
                                <th>Current Stage</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_apps as $app): ?>
                                <tr>
                                    <td><code><?= e($app['register_no']) ?></code></td>
                                    <td>
                                        <strong><?= e($app['student_name']) ?></strong>
                                    </td>
                                    <td><?= e($app['department']) ?> (CGPA: <?= number_format($app['cgpa'], 2) ?>)</td>
                                    <td>
                                        <strong><?= e($app['company_name']) ?></strong>
                                        <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($app['job_role']) ?> (₹<?= $app['package_lpa'] ?> LPA)</div>
                                    </td>
                                    <td><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
                                    <td><?= stage_badge($app['current_stage']) ?></td>
                                    <td>
                                        <a href="<?= base_url('admin/applications.php?drive_id=' . $app['drive_id']) ?>" class="btn btn-outline btn-sm">
                                            Update Stage
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
