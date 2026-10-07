<?php $user = current_user(); ?>
<header class="app-navbar">
    <div class="navbar-left">
        <button class="mobile-toggle" aria-label="Toggle navigation">☰</button>
        <div class="page-title-header"><?= e($page_title ?? 'Dashboard') ?></div>
    </div>

    <div class="navbar-right">
        <a href="<?= base_url('admin/notifications.php') ?>" class="nav-btn" title="Announcements">
            📢 Announcements
        </a>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="user-avatar"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></span>
            <div style="display:flex;flex-direction:column;line-height:1.2;">
                <span style="font-size:13px;font-weight:700;color:var(--text-main);"><?= e($user['name'] ?? 'Administrator') ?></span>
                <span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;">Administrator</span>
            </div>
        </div>
        <a href="<?= base_url('logout.php') ?>" class="nav-btn nav-btn-primary" style="padding:6px 12px;font-size:12.5px;">
            Logout
        </a>
    </div>
</header>
