<?php
ob_start();
require_once "config.php";

// Set session settings before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$error = '';
$success = '';
$debug = '';

// Validates CSRF token, verifies credentials, starts session, and redirects on success.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error .= "❌ Invalid CSRF token.<br>";
        $debug .= "<p>❌ Login failed: Invalid CSRF token for email: {$_POST['email']}</p>";
    } else {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $debug .= "<p>📨 Email submitted: $email</p>";

        if (empty($email) || empty($password)) {
            $error .= "❌ Email and password are required.<br>";
            $debug .= "<p>❌ Login failed: Missing fields for email: $email</p>";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error .= "❌ Invalid email format.<br>";
            $debug .= "<p>❌ Login failed: Invalid email format for: $email</p>";
        } else {
            try {
                $query = $conn->prepare("SELECT id, email, password FROM user WHERE email = ? LIMIT 1");
                $query->bind_param("s", $email);
                $query->execute();
                $result = $query->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $debug .= "<p>✅ User found: {$user['email']}</p>";

                    if (password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['userid'] = $user['id'];
                        $_SESSION['user'] = ['email' => $user['email']];
                        $success = "✅ Login successful! Redirecting...";
                        $debug .= "<p>✅ Login successful for email: {$user['email']}</p>";
                        logLoginAttempt($conn, $email, 'success');
                        echo "<script>alert('Credential correct, Redirecting to dashboard'); window.location.href = '" . BASE_URL . "templates/dashboard.php';</script>";
                        ob_end_flush();
                        exit;
                    } else {
                        $error .= "❌ Incorrect credentials.<br>";
                        $debug .= "<p>❌ Login failed: Incorrect password for email: $email</p>";
                        logLoginAttempt($conn, $email, 'failed');
                    }
                } else {
                    $error .= "❌ Incorrect credentials.<br>";
                    $debug .= "<p>❌ Login failed: No user found for email: $email</p>";
                    logLoginAttempt($conn, $email, 'failed');
                }
                $query->close();
            } catch (Exception $e) {
                error_log("DB error: " . $e->getMessage());
                $error .= "❌ An unexpected error occurred.<br>";
                $debug .= "<p>❌ Login failed: Database error for email: $email</p>";
            }
        }
    }

    if (!empty($error)) {
        $_SESSION['debug'] = $debug;
        echo "<script>alert('Incorrect credentials'); window.location.href = '" . BASE_URL . "templates/login.php';</script>";
        ob_end_flush();
        exit;
    }

    $_SESSION['debug'] = $debug;
    mysqli_close($conn);
    ob_end_flush();
}

// Inserts a login attempt record (success or failed) into the login_attempts table.
function logLoginAttempt($conn, $email, $status)
{
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, status, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $email, $status);
    $stmt->execute();
    $stmt->close();
}
