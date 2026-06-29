<?php
// Password Reset - Step 2: Set new password using token
$pageTitle = 'Set New Password — Interlinked Marketplace';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error   = '';
$success = '';
$valid   = false;
$userId  = intval($_GET['uid'] ?? 0);
$token   = $_GET['token'] ?? '';

// Validate token
if (!$userId || !$token) {
    $error = 'Invalid reset link. Please request a new one.';
} else {
    $tokenHash = hash('sha256', $token);
    $stmt = $con->prepare("SELECT user_id, username FROM users WHERE user_id = ? AND verify_token = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("is", $userId, $tokenHash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $valid = true;
    }
}

// Handle new password submission
if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please refresh.';
    } else {
        $password     = $_POST['password'] ?? '';
        $confirm      = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Update password and clear reset token
            $upd = $con->prepare("UPDATE users SET password_hash = ?, verify_token = NULL WHERE user_id = ?");
            $upd->bind_param("si", $hash, $userId);

            if ($upd->execute()) {
                $success = 'Password updated successfully! You can now sign in.';
                $valid = false; // prevent showing form again
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="auth-wrapper">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="auth-card card">
          <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-5">
              <a href="../index.php" class="text-decoration-none">
                <div class="brand-logo" style="font-size:2.2rem;font-family:'Sora',sans-serif;font-weight:800;color:#fff">Interlinked<span class="brand-accent">.</span></div>
              </a>
              <p class="text mt-2">Set your new password</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert mb-4" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.2); color:#10b981"><?= htmlspecialchars($success) ?></div>
            <a href="login.php" class="btn btn-primary w-100 py-3">Sign In →</a>
            <?php elseif ($valid): ?>
            <form method="POST" action="">
              <?= csrfField() ?>
              <div class="mb-4">
                <label class="form-label text small fw-700">NEW PASSWORD</label>
                <input type="password" class="form-control" name="password" id="pwField" placeholder="••••••••" required minlength="8" autofocus>
                <small class="text mt-1 d-block">Minimum 8 characters</small>
              </div>
              <div class="mb-4">
                <label class="form-label text small fw-700">CONFIRM PASSWORD</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="confirm_password" id="pwConfirm" placeholder="••••••••" required>
                  <button type="button" class="btn btn-outline-secondary border-0" id="togglePw" style="background:rgba(255,255,255,0.05)">👁</div>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Update Password</button>
            </form>
            <?php else: ?>
            <a href="forgot_password.php" class="btn btn-primary w-100 py-3">Request New Reset Link</a>
            <?php endif; ?>

            <div class="text-center mt-4">
              <a href="login.php" class="text-accent small fw-700">← Back to Sign In</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const toggle = document.getElementById('togglePw');
  if (toggle) {
    toggle.addEventListener('click', () => {
      const f = document.getElementById('pwField');
      const c = document.getElementById('pwConfirm');
      if (f.type === 'password') { f.type = 'text'; c.type = 'text'; }
      else { f.type = 'password'; c.type = 'password'; }
    });
  }
</script>
</body>
</html>
