<?php
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config for BASE_URL
require_once __DIR__ . '/config.php';

// Optional: Log the logout activity
if (isset($_SESSION['userid'])) {
    $userId = $_SESSION['userid'];

    // You can log to database (optional)
    /*
    $stmt = $conn->prepare("INSERT INTO logout_log (user_id, logout_time) VALUES (?, NOW())");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    */

    error_log("User ID {$userId} logged out at " . date('Y-m-d H:i:s'));
}

// Clears session data, expires the session cookie, destroys the session, then redirects to login.
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session to store the success message
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';

// Redirect to login page
header("Location: " . BASE_URL . "templates/login.php");
ob_end_flush();
exit;
