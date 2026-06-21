<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirects to login page if no valid session exists, with no-cache headers.
function requireLogin() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
        header("Location: /Grade/templates/login.php");
        exit();
    }
}

// Returns true if a non-empty userid session key is set.
function isLoggedIn() {
    return isset($_SESSION['userid']) && !empty($_SESSION['userid']);
}
?>