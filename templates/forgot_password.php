<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Change Password</title>
    <link rel="stylesheet" href="../src/styles.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSB7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <style>
        .alert-success {
            color: green;
            background: #e1f8e6;
            padding: 10px;
            border: 1px solid green;
        }

        .alert-error {
            color: red;
            background: #ffe0e0;
            padding: 10px;
            border: 1px solid red;
        }

        .alert-debug {
            color: blue;
            background: #e0e7ff;
            padding: 10px;
            border: 1px solid blue;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php
    // Start session to access CSRF token and messages
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        // Generate CSRF token if not already set
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    // Retrieve error, success, and debug messages from GET or session
    $error = isset($_GET['error']) && !empty($_GET['error']) ? urldecode($_GET['error']) : '';
    $success = isset($_GET['success']) && !empty($_GET['success']) ? urldecode($_GET['success']) : '';
    $debug = isset($_SESSION['debug']) ? $_SESSION['debug'] : '';
    // Clear debug from session
    unset($_SESSION['debug']);
    ?>

    <?php if (!empty($error)) echo "<div class='alert-error'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='alert-success'>$success</div>"; ?>
    <?php if (!empty($debug) && defined('DEBUG_MODE') && DEBUG_MODE) echo "<div class='alert-debug'>$debug</div>"; ?>

    <div class="flex justify-center items-center bg-slate-400 h-screen">
        <div class="bg-white p-5 w-96 shadow-lg rounded-md">
            <hr class="mt-3" />
            <form action="forgot_password_backend.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="mt-3">
                    <label for="email" class="block text-base mb-2">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        class="border rounded-md w-full text-base px-2 py-1 focus:outline-none focus:ring-0 focus:border-yellow-400"
                        aria-describedby="email-error" />
                </div>
                <div class="mt-3">
                    <label for="newPassword" class="block text-base mb-2">New Password</label>
                    <input
                        type="password"
                        id="newPassword"
                        name="newPassword"
                        required
                        class="border rounded-md w-full text-base px-2 py-1 focus:outline-none focus:ring-0 focus:border-yellow-400"
                        aria-describedby="newPassword-error" />
                </div>
                <div class="mt-3">
                    <label for="confirmPassword" class="block text-base mb-2">Confirm Password</label>
                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirmPassword"
                        required
                        class="border rounded-md w-full text-base px-2 py-1 focus:outline-none focus:ring-0 focus:border-yellow-400"
                        aria-describedby="password-error" />
                </div>
                <div class="mt-3 flex justify-between items-center">
                    <div>
                        <a href="/Grade/templates/forgot_password.php" class="text-blue-800">Try another email</a>
                    </div>
                </div>
                <div class="mt-5">
                    <button
                        type="submit"
                        name="submit"
                        class="border-2 border-yellow-500 bg-yellow-500 text-white py-1 w-full rounded-md hover:bg-transparent hover:text-yellow-500 font-semibold">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <script>
            // Redirect to login.php after 3 seconds if success message is present
            setTimeout(function() {
                window.location.href = '/Grade/templates/login.php';
            }, 3000);
        </script>
    <?php endif; ?>
</body>

</html>