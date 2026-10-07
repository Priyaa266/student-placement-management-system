<?php
/**
 * Partner Companies Management
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

// Handle Add Company
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $industry = trim($_POST['industry'] ?? 'Information Technology');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($email)) {
        set_flash('danger', 'Company name and official email are required.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO companies (name, email, phone, website, location, industry, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $website, $location, $industry, $description]);
            set_flash('success', "Company '{$name}' registered successfully into placement system.");
            header('Location: ' . base_url('admin/companies.php'));
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error adding company: ' . $e->getMessage());
        }
    }
}

// Fetch all companies with drive counts
$stmt = $pdo->query("SELECT c.*, COUNT(d.id) as drive_count 
                    FROM companies c 
                    LEFT JOIN placement_drives d ON c.id = d.company_id 
                    GROUP BY c.id 
                    ORDER BY c.created_at DESC");
$companies = $stmt->fetchAll();

$page_title = 'Manage Recruiting Partners';
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
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Campus Recruiting Partners</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Manage corporate recruiters, brand profiles, and active campus recruitment opportunities.
                    </p>
                </div>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('add-company-card').scrollIntoView({behavior: 'smooth'})">
                    + Add New Company
                </button>
            </div>

            <!-- Existing Companies List -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <div class="card-title">🏢 Registered Companies (<?= count($companies) ?>)</div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Industry Sector</th>
                                <th>Contact Email & Phone</th>
                                <th>Location</th>
                                <th>Active Drives</th>
                                <th>Website</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $c): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div class="company-logo" style="width: 40px; height: 40px; font-size: 16px;">
                                                <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong style="color: var(--text-main); font-size: 14px;"><?= e($c['name']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($c['industry']) ?></td>
                                    <td>
                                        <div><?= e($c['email']) ?></div>
                                        <small style="color: var(--text-muted);"><?= e($c['phone'] ?: 'N/A') ?></small>
                                    </td>
                                    <td>📍 <?= e($c['location'] ?: 'Multi-Location') ?></td>
                                    <td>
                                        <span class="status-badge badge-primary">
                                            <?= $c['drive_count'] ?> Drives
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['website'])): ?>
                                            <a href="<?= e($c['website']) ?>" target="_blank" style="font-size: 12.5px;">Link ↗</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/drives.php?company_id=' . $c['id']) ?>" class="btn btn-outline btn-sm">
                                            View Drives
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Company Form Card -->
            <div class="card" id="add-company-card">
                <div class="card-header">
                    <div class="card-title">🏢 Register New Recruiting Partner Company</div>
                </div>

                <form method="POST" action="<?= base_url('admin/companies.php') ?>">
                    <input type="hidden" name="add_company" value="1">

                    <div class="grid-cols-3">
                        <div class="form-group">
                            <label class="form-label">Company Name <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Cisco Systems" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Official HR Email <span class="req">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="recruiter@cisco.com" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 080-12345678">
                        </div>
                    </div>

                    <div class="grid-cols-3">
                        <div class="form-group">
                            <label class="form-label">Website URL</label>
                            <input type="url" name="website" class="form-control" placeholder="https://careers.company.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Operating Location(s)</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Bengaluru / Chennai">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Industry Domain</label>
                            <select name="industry" class="form-control">
                                <option value="Information Technology">Information Technology</option>
                                <option value="Product & Cloud Software">Product & Cloud Software</option>
                                <option value="Consulting & Financial Services">Consulting & Financial Services</option>
                                <option value="Electronics & VLSI">Electronics & VLSI</option>
                                <option value="Automotive & Core Engineering">Automotive & Core Engineering</option>
                                <option value="E-Commerce & Internet">E-Commerce & Internet</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Company Overview / Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief background of company and work culture..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                        Save & Register Company
                    </button>
                </form>
            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
