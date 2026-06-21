<?php
// Start session to access CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include 'config.php';

// Initialize variables for error and success messages
$error = '';
$success = '';
$debug = '';

// Define DEBUG_MODE for debugging (set to false in production)
define('DEBUG_MODE', true);

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token. Please try again.';
        if (DEBUG_MODE) {
            $debug = 'CSRF token validation failed. Submitted: ' . ($_POST['csrf_token'] ?? 'none') . ', Expected: ' . $_SESSION['csrf_token'];
        }
    } else {
        // Retrieve form data
        $email = trim($_POST['email'] ?? '');
        $newPassword = trim($_POST['newPassword'] ?? '');
        $confirmPassword = trim($_POST['confirmPassword'] ?? '');

        // Validate inputs
        if (empty($email)) {
            $error = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Both password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            // Check if the email exists in the users table
            $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $error = 'Email not found.';
                if (DEBUG_MODE) {
                    $debug = 'No user found with email: ' . $email;
                }
            } else {
                // Update the user's password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashedPassword, $email);
                if ($stmt->execute()) {
                    $success = 'New password is successfully set';
                    // echo "<script>alert('Credential correct, Redirecting to login page'); window.location.href = '" . BASE_URL . "templates/login.php';</script>";
                    // Regenerate CSRF token for security
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = 'Failed to update password. Please try again.';
                    if (DEBUG_MODE) {
                        $debug = 'Database error: ' . $conn->error;
                    }
                }
                $stmt->close();
            }
        }
    }

    // Store debug message in session for display
    if (DEBUG_MODE && !empty($debug)) {
        $_SESSION['debug'] = $debug;
    }
}

// Redirect back to the form with error/success messages
header('Location: forgot_password.php?error=' . urlencode($error) . '&success=' . urlencode($success));
exit();
