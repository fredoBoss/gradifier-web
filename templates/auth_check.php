<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/config.php';

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid']) || !is_numeric($_SESSION['userid'])) {
    // Not logged in - redirect to login
    $_SESSION['error'] = 'Please log in to access this page.';
    header("Location: " . BASE_URL . "templates/login.php");
    exit();
}

// Optional: Session timeout (1 hour)
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > 3600) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Your session has expired. Please log in again.';
        header("Location: " . BASE_URL . "templates/login.php");
        exit();
    }
}
$_SESSION['last_activity'] = time();
