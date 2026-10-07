<?php
/**
 * Logout Handler
 * Student Placement Management System
 */
require_once __DIR__ . '/config/database.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start a fresh session to carry the flash message
session_start();
set_flash('success', 'You have been safely logged out. See you again!');
header('Location: ' . base_url('login.php'));
exit;
