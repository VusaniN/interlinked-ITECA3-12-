<?php
// Password Reset - Step 1: Request reset link
$pageTitle = 'Reset Password — Interlinked Marketplace';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error   = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please refresh.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!$email) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $con->prepare("SELECT user_id, username, full_name FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                // Store token in DB (reuse verify_token column for reset)
                $upd = $con->prepare("UPDATE users SET verify_token = ? WHERE user_id = ?");
                $upd->bind_param("si", $tokenHash, $user['user_id']);
                $upd->execute();

                // Build reset link
                $resetLink = url('auth/reset_password.php?token=' . $token . '&uid=' . $user['user_id']);
                $success = 'Reset link generated! Click the link below to set a new password.';
            } else {
                // Don't reveal if email exists
                $success = 'If an account with that email exists, a reset link has been generated.';
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
              <p class="text mt-2">Reset your password</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success mb-4" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.2); color:#10b981"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($resetLink): ?>
            <div class="mb-4 p-3 rounded-4" style="background:rgba(212,175,55,0.05); border:1px solid var(--border)">
              <label class="form-label text small fw-700 mb-2">RESET LINK</label>
              <div class="input-group">
                <input type="text" class="form-control" id="resetLink" value="<?= htmlspecialchars($resetLink) ?>" readonly style="font-size:.75rem; background:rgba(255,255,255,0.03)">
                <button class="btn btn-primary" type="button" onclick="copyLink()">Copy</button>
              </div>
              <a href="<?= htmlspecialchars($resetLink) ?>" class="btn btn-sm btn-outline-light mt-2 w-100">Go to Reset Password →</a>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
              <?= csrfField() ?>
              <div class="mb-4">
                <label class="form-label text small fw-700">EMAIL ADDRESS</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" required autofocus>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Get Reset Link</button>
            </form>

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
function copyLink() {
  const input = document.getElementById('resetLink');
  input.select();
  navigator.clipboard.writeText(input.value);
}
</script>
</body>
</html>
