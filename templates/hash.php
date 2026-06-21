<?php
require_once "config.php";
$query = $conn->query("SELECT id, email, password FROM user");
while ($row = $query->fetch_assoc()) {
    $plain_password = $row['password'];
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    $update_query = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
    $update_query->bind_param("si", $hashed_password, $row['id']);
    $update_query->execute();
    $update_query->close();
}
echo "Passwords hashed successfully.";
mysqli_close($conn);
?>