<?php
include "config.php";

// Fetch user (for example, user with ID 1)
$userId = 1;
$sql = "SELECT * FROM user WHERE id = $userId";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Update Profile</title>
</head>

<body>
    <h2>Update Profile</h2>

    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br><br>

        <label>Profile Picture:</label><br>
        <?php if ($user['photo']): ?>
            <img src="<?= $user['photo'] ?>" width="100"><br>
        <?php endif; ?>
        <input type="file" name="photo" accept="image/*"><br><br>

        <button type="submit">Update</button>
    </form>
</body>

</html>