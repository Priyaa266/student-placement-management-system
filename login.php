<?php
/** Admin-only sign-in for the placement management portal. */
require_once __DIR__ . '/config/database.php';

if (is_logged_in()) {
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        header('Location: ' . base_url('admin/dashboard.php'));
    } else {
        $_SESSION = [];
        session_regenerate_id(true);
        set_flash('warning', 'Only placement administrators can sign in.');
        header('Location: ' . base_url('login.php'));
    }
    exit;
}

$flash = get_flash();
$page_title = 'Administrator Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | Placement Portal</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;">

    <div style="width: 100%; max-width: 440px;">
        
        <!-- Brand Header -->
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 50px; height: 50px; background: #3b82f6; border-radius: var(--radius-md); display: inline-flex; align-items: center; justify-content: center; font-size: 26px; color: #fff; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);">
                🎓
            </div>
            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800;">Campus Placement Portal</h1>
            <p style="color: #94a3b8; font-size: 13.5px; margin-top: 4px;">Placement office administrator access</p>
        </div>

        <!-- Login Card -->
        <div class="card" style="box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3); border-radius: var(--radius-lg);">
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('auth/login_process.php') ?>" method="POST" id="login-form">
                <div class="form-group">
                    <label class="form-label">Administrator Email <span class="req">*</span></label>
                    <input type="email" name="email" id="input-email" class="form-control" placeholder="admin@college.edu" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label class="form-label" style="margin-bottom: 0;">Password <span class="req">*</span></label>
                    </div>
                    <input type="password" name="password" id="input-password" class="form-control" placeholder="Enter administrator password" required autocomplete="current-password">
                </div>

                <div style="margin-bottom: 20px;">
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 11px;">
                        Sign In as Administrator
                    </button>
                </div>
            </form>

            <div style="margin-top: 16px;">
                <button type="button" class="btn btn-outline btn-block" onclick="fillDemoLogin()">
                    Use Demo Admin Login
                </button>
                <p style="margin-top: 8px; text-align: center; color: var(--text-muted); font-size: 12px;">
                    Demo: admin@college.edu / Admin@123
                </p>
            </div>

            <div style="text-align: center; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 10px;">
                <span style="font-size: 13px; color: var(--text-muted);">The demo login requires the admin account in the database.</span>
            </div>

        </div>

        <div style="text-align: center; margin-top: 20px;">
            <span style="color: #94a3b8; font-size: 13px;">Campus Placement Management System</span>
        </div>
    </div>
    <script>
        function fillDemoLogin() {
            document.getElementById('input-email').value = 'admin@college.edu';
            document.getElementById('input-password').value = 'Admin@123';
            document.getElementById('input-password').focus();
        }
    </script>
</body>
</html>
