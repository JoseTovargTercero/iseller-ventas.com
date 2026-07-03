<?php
session_start();

// ─── Hardcoded credentials (change as needed) ─────────────────────────────
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'iseller2025');
// ──────────────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user = trim($_POST['admin_user'] ?? '');
$pass = trim($_POST['admin_pass'] ?? '');

if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user']      = $user;
    $_SESSION['admin_login_at']  = time();
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: index.php?error=1');
    exit;
}
