<?php
/**
 * Master Student Examination & Assessment Results
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$filter_type = trim($_GET['type'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$query = "SELECT r.*, s.register_no, u.name as student_name, s.department,
                 CASE WHEN r.test_type = 'aptitude' THEN at.title ELSE ct.title END as test_title,
                 CASE WHEN r.test_type = 'aptitude' THEN c1.name ELSE c2.name END as company_name
          FROM test_results r
          JOIN students s ON r.student_id = s.id
          JOIN users u ON s.user_id = u.id
          LEFT JOIN aptitude_tests at ON (r.test_type = 'aptitude' AND r.test_id = at.id)
          LEFT JOIN placement_drives d1 ON at.drive_id = d1.id
          LEFT JOIN companies c1 ON d1.company_id = c1.id
          LEFT JOIN coding_tests ct ON (r.test_type = 'coding' AND r.test_id = ct.id)
          LEFT JOIN placement_drives d2 ON ct.drive_id = d2.id
          LEFT JOIN companies c2 ON d2.company_id = c2.id
          WHERE 1=1";
$params = [];

if (!empty($filter_type)) {
    $query .= " AND r.test_type = ?";
    $params[] = $filter_type;
}

if (!empty($filter_status)) {
    $query .= " AND r.status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR s.register_no LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
}

$query .= " ORDER BY r.taken_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

$page_title = 'Assessment Scores & Evaluation Roster';
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
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Campus Assessment Score Log</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Examine proctored aptitude performance metrics, coding evaluations, and candidate cutoffs.
                    </p>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                    Evaluated Submissions: <strong style="color:var(--text-main);"><?= count($results) ?></strong>
                </div>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom: 24px; padding: 18px 24px;">
                <form method="GET" action="<?= base_url('admin/results.php') ?>" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 14px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Search Student</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by student name or register number..." value="<?= e($search) ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Test Format</label>
                        <select name="type" class="form-control">
                            <option value="">All Formats</option>
                            <option value="aptitude" <?= ($filter_type === 'aptitude') ? 'selected' : '' ?>>MCQ Aptitude</option>
                            <option value="coding" <?= ($filter_type === 'coding') ? 'selected' : '' ?>>Algorithmic Coding</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Status Result</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Passed" <?= ($filter_status === 'Passed') ? 'selected' : '' ?>>Passed Cutoff</option>
                            <option value="Failed" <?= ($filter_status === 'Failed') ? 'selected' : '' ?>>Failed Cutoff</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
                        <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)): ?>
                            <a href="<?= base_url('admin/results.php') ?>" class="btn btn-outline" style="height: 42px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Results Table -->
            <div class="card">
                <?php if (empty($results)): ?>
                    <div style="text-align: center; padding: 48px 24px;">
                        <div style="font-size: 36px; margin-bottom: 12px;">📊</div>
                        <h3 style="font-size: 16px; font-weight: 700;">No test results found</h3>
                        <p style="color: var(--text-muted); font-size: 13.5px; margin-top: 4px;">No evaluation attempts match your selected criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Candidate Name & Reg No</th>
                                    <th>Dept</th>
                                    <th>Assessment Title</th>
                                    <th>Format</th>
                                    <th>Affiliated Company</th>
                                    <th>Breakdown</th>
                                    <th>Score</th>
                                    <th>Percent</th>
                                    <th>Status</th>
                                    <th>Date Taken</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $res): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('admin/student-view.php?id=' . $res['student_id']) ?>" style="font-weight: 700; color: var(--text-main);">
                                                <?= e($res['student_name']) ?>
                                            </a>
                                            <div style="font-size: 11.5px; color: var(--text-muted);">
                                                <code><?= e($res['register_no']) ?></code>
                                            </div>
                                        </td>
                                        <td><?= e($res['department']) ?></td>
                                        <td><strong><?= e($res['test_title'] ?: 'Placement Assessment') ?></strong></td>
                                        <td>
                                            <?php if ($res['test_type'] === 'aptitude'): ?>
                                                <span class="status-badge badge-primary">Aptitude</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-purple">Coding</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($res['company_name'] ?: 'Institutional Benchmark') ?></td>
                                        <td>
                                            <?php if ($res['test_type'] === 'aptitude'): ?>
                                                <span style="color:#059669;"><?= $res['correct_answers'] ?> Correct</span> / 
                                                <span style="color:#dc2626;"><?= $res['wrong_answers'] ?> Wrong</span>
                                            <?php else: ?>
                                                <span>1/1 Tasks</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= number_format($res['score'], 1) ?></strong></td>
                                        <td><strong><?= number_format($res['percentage'], 1) ?>%</strong></td>
                                        <td>
                                            <span class="status-badge <?= ($res['status'] === 'Passed') ? 'badge-success' : 'badge-danger' ?>">
                                                <?= e($res['status']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 12px; color: var(--text-muted);">
                                            <?= date('d M Y, h:i A', strtotime($res['taken_at'])) ?>
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
