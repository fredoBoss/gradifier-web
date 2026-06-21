<?php
// Start the session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✅ Session started.<br>";
} else {
    echo "⚠️ Session already active.<br>";
}

// Check if already logged in
if (isset($_SESSION["userid"])) {
    echo "✅ Session user ID found: " . $_SESSION["userid"] . "<br>";
    header("Location: /Grade/templates/dashboard.php");
    exit;
} else {
    echo "❌ No session user ID found.<br>";
}
