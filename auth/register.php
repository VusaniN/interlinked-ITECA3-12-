<?php
$pageTitle = 'Register — Interlinked Marketplace';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) { redirect('dashboard.php'); }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $username  = trim($_POST['username']   ?? '');
        $email     = trim($_POST['email']      ?? '');
        $full_name = trim($_POST['full_name']  ?? '');
        $phone     = trim($_POST['phone']      ?? '');
        $password  = $_POST['password']         ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $roleId    = ($_POST['account_type'] ?? 'buyer') === 'seller' ? 3 : 4;

        if (!$username || !$email || !$full_name || !$password) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username can only contain letters, numbers, and underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($email) > 255) {
            $error = 'Email address is too long.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (strlen($password) > 255) {
            $error = 'Password is too long.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($phone) > 20) {
            $error = 'Phone number is too long.';
        } else {
            // Check for existing user with Prepared Statement
            $stmt = $con->prepare("SELECT user_id FROM users WHERE email=? OR username=?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Email or username is already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $insertStmt = $con->prepare("INSERT INTO users (role_id, username, email, password_hash, full_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("isssss", $roleId, $username, $email, $hash, $full_name, $phone);
                
                if ($insertStmt->execute()) {
                    $success = 'Account created! You can now <a href="login.php" class="text-accent">log in</a>.';
                } else {
                    $error = 'Registration failed. Please try again later.';
                }
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
<div class="auth-wrapper py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-5">
        <div class="auth-card card">
          <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-5">
              <a href="../index.php" class="text-decoration-none">
                <div class="brand-logo" style="font-size:2.2rem;font-family:'Sora',sans-serif;font-weight:800;color:#fff">
                  Interlinked<span class="brand-accent">.</span>
                </div>
              </a>
              <p class="text mt-2">Join the community.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success mb-4"><?= $success ?></div><?php endif; ?>

            <form method="POST">
              <?= csrfField() ?>

              <div class="mb-5">
                <label class="form-label text small fw-700 mb-3">SELECT ACCOUNT TYPE</label>
                <div class="row g-3">
                  <div class="col-6">
                    <input type="radio" name="account_type" id="typeBuyer" value="buyer" class="btn-check" checked>
                    <label for="typeBuyer" class="btn btn-outline-primary w-100 py-3 d-flex flex-column gap-1">
                        <span style="font-size:1.2rem">🛒</span>
                        <span class="small fw-700">Buyer</span>
                    </label>
                  </div>
                  <div class="col-6">
                    <input type="radio" name="account_type" id="typeSeller" value="seller" class="btn-check">
                    <label for="typeSeller" class="btn btn-outline-primary w-100 py-3 d-flex flex-column gap-1">
                        <span style="font-size:1.2rem">💎</span>
                        <span class="small fw-700">Seller</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="row g-4">
                <div class="col-12">
                  <label class="form-label text small fw-700">FULL LEGAL NAME <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="e.g. Vusani Nkumane" required maxlength="100">
                </div>
                <div class="col-md-6">
                  <label class="form-label text small fw-700">USERNAME <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="user123" maxlength="50" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
                </div>
                <div class="col-md-6">
                  <label class="form-label text small fw-700">CONTACT PHONE</label>
                  <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+27 xx xxx xxxx" maxlength="20">
                </div>
                <div class="col-12">
                  <label class="form-label text small fw-700">EMAIL ADDRESS <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="VusaniNkumane@gmail.com" maxlength="255">
                </div>
                <div class="col-md-6">
                  <label class="form-label text small fw-700">PASSWORD <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" name="password" id="pw1" placeholder="••••••••" required maxlength="255" autocomplete="new-password">
                </div>
                <div class="col-md-6">
                  <label class="form-label text small fw-700">CONFIRM <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" name="confirm_password" id="pw2" placeholder="••••••••" required maxlength="255" autocomplete="new-password">
                </div>
              </div>

              <div class="mt-3 mb-4">
                <div id="pw-strength" style="height:3px;border-radius:2px;background:rgba(255,255,255,0.05);width:100%">
                  <div id="pw-bar" style="height:100%;border-radius:2px;width:0;transition:all .3s"></div>
                </div>
                <small id="pw-label" class="small fw-700" style="font-size:0.65rem"></small>
              </div>

              <div class="mb-4">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="agreeTerms" name="agree" required>
                  <label class="form-check-label small text" for="agreeTerms">
                    I accept the <a href="../membership_terms.txt" target="_blank" class="text-accent">Membership Terms</a> and <a href="../privacy_policy.txt" target="_blank" class="text-accent">Privacy Policy</a>.
                  </label>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-3 fw-700">Create Account</button>
            </form>

            <div class="text-center mt-5">
              <span class="text small">Already a member?</span>
              <a href="login.php" class="text-accent small fw-700 ms-1">Sign In</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('pw1').addEventListener('input', function() {
  const v = this.value, bar = document.getElementById('pw-bar'), lbl = document.getElementById('pw-label');
  const score = [/[A-Z]/.test(v),/[0-9]/.test(v),/[^A-Za-z0-9]/.test(v),v.length>=8,v.length>=12].filter(Boolean).length;
  const levels = [{c:'#f43f5e',t:'Vulnerable'},{c:'#f59e0b',t:'Standard'},{c:'#3b82f6',t:'Secure'},{c:'#10b981',t:'Elite'}];
  const l = score < 2 ? levels[0] : score < 3 ? levels[1] : score < 4 ? levels[2] : levels[3];
  bar.style.width = (score*20)+'%'; bar.style.background = l.c;
  lbl.textContent = v.length ? l.t.toUpperCase() : ''; lbl.style.color = l.c;
});
</script>
</body>
</html>
