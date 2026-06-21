<?php
include "config.php";

// Start output buffering to prevent "headers already sent" issues
ob_start();

// Example: Get user ID from session or default to 1
$userId = 1;

// Initialize variables
$name = "";
$email = "";

// Fetch user info from DB (for pre-filling)
$sql = "SELECT name, email FROM user WHERE id = $userId";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = htmlspecialchars($user['name']);
    $email = htmlspecialchars($user['email']);
}


// Verifies current password and updates to the new one if confirmed.
if (isset($_POST["action"]) && $_POST["action"] === "change_password") {
    $id = intval($_POST["id"]);
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    // Fetch current plain-text password from DB
    $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    $stmt->fetch();
    $stmt->close();

    // Validate current password
    if ($currentPassword !== $storedPassword) {
        echo "<script>alert('❌ Current password is incorrect.'); window.history.back();</script>";
        exit();
    } elseif ($newPassword !== $confirmPassword) {
        echo "<script>alert('❌ New passwords do not match.'); window.history.back();</script>";
        exit();
    } else {
        // Update password as plain text
        $updateStmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newPassword, $id);

        if ($updateStmt->execute()) {
            echo "<script>alert('✅ Password updated successfully.'); window.location.href = '" . BASE_URL . "templates/settings.php';</script>";
        } else {
            echo "<script>alert('❌ Failed to update password.'); window.history.back();</script>";
        }
        $updateStmt->close();
        exit();
    }
} else {
    echo "<script>alert('❌ Invalid request.');</script>";
}


// Saves updated name, email, and optional profile photo to the user record.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id    = intval($_POST["id"]);
    $name  = $conn->real_escape_string($_POST["name"]);
    $email = $conn->real_escape_string($_POST["email"]);
    $profilePath = null;

    // Handle image upload
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES["photo"]["tmp_name"];
        $fileName = uniqid("img_") . "." . pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $profilePath = $targetDir . $fileName;
        move_uploaded_file($fileTmp, $profilePath);
    }

    // Prepare SQL update
    if ($profilePath) {
        $sql = "UPDATE user SET name='$name', email='$email', photo='$profilePath' WHERE id=$id";
    } else {
        $sql = "UPDATE user SET name='$name', email='$email' WHERE id=$id";
    }

    // Execute and handle result
    if ($conn->query($sql) === TRUE) {
        // Check if headers are already sent
        if (!headers_sent()) {
            header("Location: " . BASE_URL . "templates/settings.php");
            exit();
        } else {
            echo "<pre>";
            echo "⚠️ Headers already sent.\n";
            echo "Cannot redirect using header().\n";
            echo "Make sure there's no echo, space, or BOM before this script starts.";
            echo "</pre>";
        }
    } else {
        echo "<pre>";
        echo "❌ SQL Error: " . $conn->error;
        echo "</pre>";
    }
} else {
    echo "<pre>❌ Invalid request method.</pre>";
}

ob_end_flush();
