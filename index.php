<?php
require_once __DIR__ . '/config/database.php';

if (is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: ' . base_url('admin/dashboard.php'));
} else {
    header('Location: ' . base_url('login.php'));
}
exit;
