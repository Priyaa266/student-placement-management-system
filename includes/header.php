<?php
/**
 * Global Header Include
 * Student Placement Management System
 */
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/database.php';
}

$page_title = $page_title ?? 'Campus Placement Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | College Placement Portal</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
