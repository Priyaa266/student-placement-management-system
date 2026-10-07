<?php
/**
 * Authentication Handler
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('login.php'));
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    set_flash('danger', 'Please enter both your email address and password.');
    header('Location: ' . base_url('login.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' AND status = 'active' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session id for security
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        set_flash('success', 'Welcome, Administrator!');
        header('Location: ' . base_url('admin/dashboard.php'));
        exit;
    } else {
        set_flash('danger', 'Invalid email address or password. Please try again.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
} catch (Exception $e) {
    error_log('Admin login failed due to a database error: ' . $e->getMessage());
    set_flash('danger', 'An internal error occurred during login. Please try again later.');
    header('Location: ' . base_url('login.php'));
    exit;
}
