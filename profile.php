<?php
$pageTitle = 'My Profile — Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

$uid     = $_SESSION['user_id'];
$error   = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $error = 'All password fields are required.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $pwStmt = $con->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $pwStmt->bind_param("i", $uid);
            $pwStmt->execute();
            $pwRow = $pwStmt->get_result()->fetch_assoc();

            if (!password_verify($current, $pwRow['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upStmt = $con->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $upStmt->bind_param("si", $newHash, $uid);
                if ($upStmt->execute()) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Failed to update password.';
                }
            }
        }
    }
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $confirmPw = $_POST['delete_password'] ?? '';
        $confirmText = trim($_POST['delete_confirm'] ?? '');

        // Verify password
        $pwStmt = $con->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $pwStmt->bind_param("i", $uid);
        $pwStmt->execute();
        $pwRow = $pwStmt->get_result()->fetch_assoc();

        if (!password_verify($confirmPw, $pwRow['password_hash'])) {
            $error = 'Incorrect password. Account deletion cancelled.';
        } elseif (strtoupper($confirmText) !== 'DELETE') {
            $error = 'Please type DELETE to confirm account deletion.';
        } else {
            // Delete user (cascades handle orders, products, messages, etc.)
            $delStmt = $con->prepare("DELETE FROM users WHERE user_id = ?");
            $delStmt->bind_param("i", $uid);

            if ($delStmt->execute()) {
                // Destroy session and redirect
                session_destroy();
                header("Location: " . url('auth/login.php'));
                exit;
            } else {
                $error = 'Failed to delete account. Please contact support.';
            }
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password']) && !isset($_POST['delete_account'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $phone     = trim($_POST['phone']     ?? '');

        if (!$full_name || !$email) {
            $error = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            // Check email uniqueness (exclude self)
            $cStmt = $con->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $cStmt->bind_param("si", $email, $uid);
            $cStmt->execute();
            if ($cStmt->get_result()->num_rows > 0) {
                $error = 'Email address is already in use by another account.';
            } else {
                $avatarName = '';
                $hasAvatar  = false;

                // Handle avatar upload
                if (!empty($_FILES['avatar']['name'])) {
                    $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','webp'];
                    if (!in_array($ext, $allowed)) {
                        $error = 'Invalid image format. Use JPG, PNG or WebP.';
                    } elseif ($_FILES['avatar']['size'] > 3 * 1024 * 1024) {
                        $error = 'Image must be under 3MB.';
                    } else {
                        $avatarDir = __DIR__ . '/uploads/avatars/';
                        // Auto-create directory if missing
                        if (!is_dir($avatarDir)) {
                            mkdir($avatarDir, 0755, true);
                        }
                        $avatarName = 'avatar_' . $uid . '_' . time() . '.' . $ext;
                        $dest       = $avatarDir . $avatarName;
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                            $hasAvatar = true;
                        } else {
                            $error = 'Failed to upload image. Directory may not have write permissions.';
                        }
                    }
                }

                if (!$error) {
                    if ($hasAvatar) {
                        $uStmt = $con->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, avatar = ? WHERE user_id = ?");
                        $uStmt->bind_param("ssssi", $full_name, $email, $phone, $avatarName, $uid);
                    } else {
                        $uStmt = $con->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                        $uStmt->bind_param("sssi", $full_name, $email, $phone, $uid);
                    }

                    if ($uStmt->execute()) {
                        $_SESSION['full_name'] = $full_name;
                        $_SESSION['email']     = $email;
                        if ($hasAvatar) {
                            $_SESSION['avatar'] = $avatarName;
                        }
                        $success = 'Profile updated successfully.';
                    } else {
                        $error = 'Update failed. Please try again.';
                    }
                }
            }
        }
    }
}

// Fetch current user data
$uStmt = $con->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?");
$uStmt->bind_param("i", $uid);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5" style="max-width:700px">
  <h2 class="fw-800 mb-4" style="font-family:'Sora',sans-serif">👤 My Profile</h2>

  <?php if ($error): ?><div class="alert mb-4" style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.2); color:#f43f5e; border-radius:12px; padding:1rem 1.5rem"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert mb-4" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.2); color:#10b981; border-radius:12px; padding:1rem 1.5rem"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="card p-4 p-lg-5">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>

      <!-- Avatar -->
      <div class="text-center mb-5">
        <img src="<?= url('uploads/avatars/' . htmlspecialchars($user['avatar'] ?? 'default.png')) ?>"
             style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);box-shadow:0 10px 30px rgba(0,0,0,0.3)"
             onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'"
             id="avatarPreview" class="mb-3">
        <div>
          <label class="btn btn-outline-primary btn-sm" for="avatarInput">Change Photo</label>
          <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none"
                 onchange="const r=new FileReader();r.onload=e=>{document.getElementById('avatarPreview').src=e.target.result};r.readAsDataURL(this.files[0])">
        </div>
      </div>

      <div class="row g-4">
        <div class="col-md-12">
          <label class="form-label small fw-700" style="color:#fff">USERNAME</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
          <small style="color:#f43f5e">Username cannot be changed</small>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-700" style="color:#fff">FULL NAME</label>
          <input type="text" class="form-control" name="full_name"
                 value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-700" style="color:#fff">EMAIL</label>
          <input type="email" class="form-control" name="email"
                 value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
      </div>

      <!-- Verification Status -->
      <div class="mt-4 p-4 rounded-4" style="background:rgba(255,255,255,0.03);border:1px solid var(--border)">
        <h6 class="fw-700 mb-2" style="color:var(--accent)">
          <?= ($user['is_verified'] ?? 0) ? '✅ Verified Member' : '🛡️ Verification Status' ?>
        </h6>
        <?php if ($user['is_verified'] ?? 0): ?>
          <p class="small mb-0" style="color:#10b981">Your identity has been verified. You have the Trusted Seller badge.</p>
        <?php elseif ($user['verification_doc'] ?? ''): ?>
          <p class="small mb-0" style="color:#d4af37">Verification pending review. We'll notify you once approved.</p>
        <?php else: ?>
          <p class="small mb-2" style="color:#fff; opacity:0.7">Verify your identity to build trust with buyers.</p>
          <a href="<?= url('verification.php') ?>" class="btn btn-sm btn-primary">Verify Now</a>
        <?php endif; ?>
      </div>

      <div class="mt-5 text-end">
        <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-light px-4 me-2">Cancel</a>
        <button type="submit" class="btn btn-primary px-5">Save Changes</button>
      </div>
    </form>
  </div>

  <!-- Danger zone: Change password -->
  <div class="card p-4 p-lg-5 mt-4" style="border:1px solid rgba(244,63,94,0.2)">
    <h5 class="fw-700 text-danger mb-3">Change Password</h5>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="change_password" value="1">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label small fw-700" style="color:#fff">CURRENT PASSWORD</label>
          <input type="password" class="form-control" name="current_password" placeholder="Current password" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-700" style="color:#fff">NEW PASSWORD</label>
          <input type="password" class="form-control" name="new_password" placeholder="Min 8 chars" required minlength="8">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-700" style="color:#fff">CONFIRM PASSWORD</label>
          <input type="password" class="form-control" name="confirm_password" placeholder="Confirm" required>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-danger w-100">Update</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Danger zone: Delete Account -->
  <div class="card p-4 p-lg-5 mt-4" style="border:1px solid rgba(244,63,94,0.4); background:rgba(244,63,94,0.03)">
    <h5 class="fw-700 mb-1" style="color:#f43f5e">Delete My Account</h5>
    <p class="text small mb-4" style="color:rgba(244,63,94,0.7)">This action is permanent. All your listings, orders, and data will be permanently removed.</p>
    <form method="POST" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.')">
      <?= csrfField() ?>
      <input type="hidden" name="delete_account" value="1">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label small fw-700" style="color:#f43f5e">YOUR PASSWORD</label>
          <input type="password" class="form-control" name="delete_password" placeholder="Enter your password" required>
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-700" style="color:#f43f5e">TYPE "DELETE" TO CONFIRM</label>
          <input type="text" class="form-control" name="delete_confirm" placeholder="Type DELETE" required>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-danger w-100 py-2">Delete Account</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
