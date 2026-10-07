<?php
$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);

function is_active($page, $current_page) {
    return $page === $current_page ? 'active' : '';
}

$navigation = [
    'Overview' => [
        ['dashboard.php', 'Dashboard', '📊'],
    ],
    'Placement' => [
        ['students.php', 'Students', '🎓'],
        ['companies.php', 'Companies', '🏢'],
        ['drives.php', 'Placement drives', '💼'],
        ['applications.php', 'Applications & interviews', '📑'],
        ['placements.php', 'Placements', '🎖️'],
    ],
    'Assessments' => [
        ['tests.php', 'Tests & questions', '📝'],
        ['results.php', 'Test results', '🏆'],
    ],
    'Updates' => [
        ['notifications.php', 'Announcements', '📢'],
        ['settings.php', 'Inquiries', '✉️'],
    ],
];
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-icon">🎓</div>
        <div class="sidebar-brand-text">
            CAMPUS PLACEMENTS
            <span>Admin portal</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navigation as $section => $items): ?>
            <div class="nav-section-title"><?= e($section) ?></div>
            <?php foreach ($items as [$page, $label, $icon]): ?>
                <a href="<?= base_url('admin/' . $page) ?>" class="nav-item <?= is_active($page, $current_page) ?>">
                    <span class="nav-icon"><?= $icon ?></span> <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-quick-profile">
            <span class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></span>
            <div class="user-details">
                <div class="user-name"><?= e($user['name'] ?? 'Administrator') ?></div>
                <div class="user-role-badge">Administrator</div>
            </div>
        </div>
    </div>
</aside>
