<?php
/**
 * Database & Core Configuration
 * Student Placement Management System
 */

// Report errors in development, hide raw exceptions in production
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'student_placement_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Establish PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Graceful error display without exposing database credentials
    die("<div style='font-family:Segoe UI,sans-serif;margin:40px auto;max-width:600px;padding:24px;border:1px solid #fecaca;background:#fef2f2;border-radius:12px;color:#991b1b;'>
        <h2 style='margin-top:0;'>Database Connection Error</h2>
        <p>Could not connect to the database <code>student_placement_system</code>. Please ensure Apache and MySQL are running in XAMPP and the database is imported.</p>
        <p style='font-size:13px;color:#7f1d1d;'><strong>Technical detail:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>
    </div>");
}

// Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Dynamically compute Base URL regardless of folder name or host
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine the root directory relative to the document root
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $baseDir = '';
    
    // Check known root folders
    if (strpos($scriptName, '/student-placement-system/') === 0) {
        $baseDir = '/student-placement-system';
    } elseif (strpos($scriptName, '/student-placement-management-system/') === 0) {
        $baseDir = '/student-placement-management-system';
    } else {
        // Fallback: extract first directory segment
        $parts = explode('/', trim($scriptName, '/'));
        if (!empty($parts[0]) && !preg_match('/\.php$/', $parts[0])) {
            $baseDir = '/' . $parts[0];
        }
    }
    
    $cleanPath = ltrim($path, '/');
    return $protocol . $host . $baseDir . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Flash message helpers
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Security: HTML escaping
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Authentication Helpers
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? ''
    ];
}

function require_role($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!is_logged_in() || ($_SESSION['user_role'] ?? '') !== 'admin' || !in_array('admin', $allowed_roles, true)) {
        set_flash('warning', 'Sign in with an administrator account to access the placement portal.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Status and Badge Rendering Helpers
 */
function stage_badge($stage) {
    $colors = [
        'Applied' => 'badge-secondary',
        'Under Review' => 'badge-info',
        'Shortlisted' => 'badge-primary',
        'Aptitude' => 'badge-warning',
        'Coding' => 'badge-purple',
        'Technical Interview' => 'badge-indigo',
        'HR Interview' => 'badge-amber',
        'Selected' => 'badge-success',
        'Rejected' => 'badge-danger'
    ];
    $badge_class = $colors[$stage] ?? 'badge-secondary';
    return '<span class="status-badge ' . $badge_class . '">' . e($stage) . '</span>';
}
