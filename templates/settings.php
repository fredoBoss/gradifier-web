<?php
require_once '../php/auth.php';
requireLogin();
require_once 'config.php';

// Fetches current user's name, email, and photo from DB to pre-fill the settings form.
if (session_status() === PHP_SESSION_NONE) session_start();
$userId      = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 1;
$currentName  = '';
$currentEmail = '';
$currentPhoto = '/grade/img/default.png';

$stmt = $conn->prepare("SELECT name, email, photo FROM user WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  $user = $res->fetch_assoc();
  $currentName  = htmlspecialchars($user['name']  ?? '');
  $currentEmail = htmlspecialchars($user['email'] ?? '');
  if (!empty($user['photo']) && file_exists($user['photo'])) $currentPhoto = $user['photo'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Settings — Gradifier</title>
  <?php include '../php/pwa_head.php'; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../src/styles.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Poppins:wght@300;400;500;600&display=swap');
    * { font-family: 'Poppins', sans-serif; }
    .font-brand { font-family: 'Montserrat', sans-serif; }
    .input-field {
      width: 100%; background: #f9fafb; border: 1px solid #e5e7eb; color: #374151;
      border-radius: 0.75rem; padding: 0.625rem 0.875rem; font-size: 0.875rem;
      transition: border-color 0.2s, box-shadow 0.2s; outline: none;
    }
    .input-field:focus { border-color: #34d399; box-shadow: 0 0 0 3px rgba(52,211,153,0.1); }
    .input-field::placeholder { color: #9ca3af; }
    .btn-primary {
      background: linear-gradient(135deg, #10b981, #059669);
      box-shadow: 0 4px 14px rgba(16,185,129,0.3);
      transition: all 0.25s;
    }
    .btn-primary:hover { box-shadow: 0 6px 20px rgba(16,185,129,0.45); transform: translateY(-1px); }
    .eye-btn { position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); color:#9ca3af; cursor:pointer; }
    .eye-btn:hover { color:#6b7280; }
  </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">

  <div id="header-placeholder" class="sticky top-0 z-30"></div>

  <div class="flex flex-1">
    <div id="sidebar-placeholder" class="flex-shrink-0"></div>

    <main class="flex-1 p-6 overflow-x-hidden min-w-0">

      <!-- Page Title -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 font-brand">Settings</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage your account information and security</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Personal Information -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
              <i class="fa-solid fa-user text-sm"></i>
            </div>
            <div>
              <h2 class="text-base font-semibold text-gray-800">Personal Information</h2>
              <p class="text-xs text-gray-400">Update your name, email, and photo</p>
            </div>
          </div>

          <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
            <input type="hidden" name="id" value="<?= $userId ?>" />

            <!-- Photo Preview -->
            <div class="flex items-center gap-4">
              <div class="relative flex-shrink-0">
                <img id="photoPreview" src="<?= htmlspecialchars($currentPhoto) ?>" alt="Profile"
                  class="w-16 h-16 rounded-full object-cover border-2 border-gray-200" />
                <label for="profilePic"
                  class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center cursor-pointer shadow-sm"
                  style="background:#065f46;" title="Change photo">
                  <i class="fa-solid fa-pen text-white" style="font-size:9px;"></i>
                </label>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-700"><?= $currentName ?: 'Your Name' ?></p>
                <p class="text-xs text-gray-400 truncate"><?= $currentEmail ?: 'your@email.com' ?></p>
                <input type="file" id="profilePic" name="photo" accept="image/*" class="hidden"
                  onchange="previewPhoto(this)" />
                <label for="profilePic" class="text-xs text-emerald-600 hover:text-emerald-700 cursor-pointer mt-0.5 inline-block">
                  Choose new photo
                </label>
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">Full Name</label>
              <input type="text" name="name" value="<?= $currentName ?>" placeholder="Your full name"
                class="input-field" required />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">Email Address</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                  <i class="fa-solid fa-envelope"></i>
                </span>
                <input type="email" name="email" value="<?= $currentEmail ?>" placeholder="your@email.com"
                  class="input-field" style="padding-left:2.25rem;" required />
              </div>
            </div>

            <button type="submit" class="btn-primary text-white text-sm font-semibold py-2.5 rounded-xl flex items-center justify-center gap-2 mt-1">
              <i class="fa-solid fa-floppy-disk"></i>
              Save Changes
            </button>
          </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500">
              <i class="fa-solid fa-lock text-sm"></i>
            </div>
            <div>
              <h2 class="text-base font-semibold text-gray-800">Change Password</h2>
              <p class="text-xs text-gray-400">Use a strong password with 8+ characters</p>
            </div>
          </div>

          <form action="update_profile.php" method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="id" value="<?= $userId ?>" />
            <input type="hidden" name="action" value="change_password" />

            <!-- Current Password -->
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">Current Password</label>
              <div class="relative">
                <input type="password" name="current_password" id="currentPassword"
                  placeholder="Enter current password" class="input-field" style="padding-right:2.5rem;" required />
                <i id="toggleCurrentPassword" class="fa-solid fa-eye eye-btn"></i>
              </div>
            </div>

            <!-- New Password -->
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">New Password</label>
              <div class="relative">
                <input type="password" name="new_password" id="newPassword"
                  placeholder="Enter new password" class="input-field" style="padding-right:2.5rem;" required />
                <i id="toggleNewPassword" class="fa-solid fa-eye eye-btn"></i>
              </div>
              <p id="newPasswordMessage" class="text-xs mt-1 min-h-[1rem]"></p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">Confirm Password</label>
              <div class="relative">
                <input type="password" name="confirm_password" id="confirmPassword"
                  placeholder="Re-enter new password" class="input-field" style="padding-right:2.5rem;" required />
                <i id="toggleConfirmPassword" class="fa-solid fa-eye eye-btn"></i>
              </div>
              <p id="confirmPasswordMessage" class="text-xs mt-1 min-h-[1rem]"></p>
            </div>

            <button type="submit" id="savePassword"
              class="btn-primary text-white text-sm font-semibold py-2.5 rounded-xl flex items-center justify-center gap-2 mt-1">
              <i class="fa-solid fa-shield-halved"></i>
              Update Password
            </button>
          </form>
        </div>

      </div>
    </main>
  </div>

  <script>
    $(function () {
      $("#header-placeholder").load("header.php");
      $("#sidebar-placeholder").load("sidebar.html");
    });

    // Reads the selected image file and updates the preview <img> immediately.
    function previewPhoto(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Toggles a password field between masked and plain text, swapping the eye icon.
    function togglePasswordVisibility(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon  = document.getElementById(iconId);
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      icon.classList.toggle('fa-eye',      !isHidden);
      icon.classList.toggle('fa-eye-slash', isHidden);
    }

    document.addEventListener("DOMContentLoaded", function () {
      ['toggleCurrentPassword', 'toggleNewPassword', 'toggleConfirmPassword'].forEach(id => {
        const inputId = { toggleCurrentPassword:'currentPassword', toggleNewPassword:'newPassword', toggleConfirmPassword:'confirmPassword' }[id];
        document.getElementById(id).addEventListener('click', () => togglePasswordVisibility(inputId, id));
      });

      const pwRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

      document.getElementById('newPassword').addEventListener('input', function () {
        const msg = document.getElementById('newPasswordMessage');
        if (!this.value) { msg.textContent = ''; return; }
        if (!pwRegex.test(this.value)) {
          msg.textContent = 'Min 8 chars with uppercase, lowercase, number & special character.';
          msg.className = 'text-xs mt-1 text-red-500';
        } else {
          msg.textContent = '✓ Strong password';
          msg.className = 'text-xs mt-1 text-emerald-600';
        }
        checkMatch();
      });

      document.getElementById('confirmPassword').addEventListener('input', checkMatch);

      // Shows a match/mismatch message between new and confirm password fields.
      function checkMatch() {
        const msg  = document.getElementById('confirmPasswordMessage');
        const newP = document.getElementById('newPassword').value;
        const conP = document.getElementById('confirmPassword').value;
        if (!conP) { msg.textContent = ''; return; }
        if (newP !== conP) {
          msg.textContent = 'Passwords do not match.';
          msg.className = 'text-xs mt-1 text-red-500';
        } else {
          msg.textContent = '✓ Passwords match';
          msg.className = 'text-xs mt-1 text-emerald-600';
        }
      }
    });
  </script>

</body>

</html>
