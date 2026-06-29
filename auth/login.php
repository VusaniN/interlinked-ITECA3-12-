<?php
// Page to login users
$pageTitle = 'Sign In — Interlinked Marketplace';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

// If already logged in, go to dashboard
if (isLoggedIn()) { 
    redirect('dashboard.php'); 
}

$error   = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if security token is okay
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if ($identifier == '' || $password == '') {
            $error = 'Please enter your email/username and password.';
        } else {
            // Find the user in db
            $stmt = $con->prepare("SELECT u.*, r.role_name
                                 FROM users u
                                 JOIN roles r ON u.role_id = r.role_id
                                 WHERE (u.email = ? OR u.username = ?)
                                   AND u.is_active = 1
                                 LIMIT 1");
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $res  = $stmt->get_result();
            $user = $res->fetch_assoc();

            // Check if password is correct
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                regenerateSession();

                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role_name'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['avatar']    = $user['avatar'];

                // Update the last login time
                $updateStmt = $con->prepare("UPDATE users SET last_login=NOW() WHERE user_id = ?");
                $updateStmt->bind_param("i", $user['user_id']);
                $updateStmt->execute();

                // Redirect based on role
                if ($user['role_name'] == 'admin' || $user['role_name'] == 'moderator') {
                    redirect('admin/index.php');
                } else {
                    redirect('dashboard.php');
                }
            } else {
                $error = 'Invalid credentials. Please check your email/username and password.';
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
                <div class="brand-logo" style="font-size:2.2rem;font-family:'Sora',sans-serif;font-weight:800;color:#fff">
                  Interlinked<span class="brand-accent">.</span>
                </div>
              </a>
              <p class="text mt-2">Welcome back! Please sign in.</p>
            </div>

            <?php if ($error != ''): ?>
            <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
              <?= csrfField() ?>
              <div class="mb-4">
                <label class="form-label text small fw-700">EMAIL OR USERNAME</label>
                <input type="text" class="form-control" name="identifier"
                       value="<?php if (isset($_POST['identifier'])) echo htmlspecialchars($_POST['identifier']); ?>"
                       placeholder="you@example.com" required autofocus
                       maxlength="255" autocomplete="username">
              </div>

              <div class="mb-4">
                <label class="form-label d-flex justify-content-between text small fw-700">
                  PASSWORD
                  <a href="forgot_password.php" class="text-decoration-none text-accent" style="font-size:.75rem">Forgot?</a>
                </label>
                <div class="input-group">
                  <input type="password" class="form-control" name="password" id="pwField" placeholder="••••••••" required autocomplete="current-password">
                  <button type="button" class="btn btn-outline-secondary border-0" id="togglePw" style="background:rgba(255,255,255,0.05)">👁</button>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-3 mt-2">Sign In</button>
            </form>

            <div class="text-center mt-5">
              <span class="text small" style="color:#fff">New to the marketplace?</span>
              <a href="register.php" class="text-accent small fw-700 ms-1">Create Account</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('togglePw').addEventListener('click', () => {
    const f = document.getElementById('pwField');
    if (f.type === 'password') {
        f.type = 'text';
    } else {
        f.type = 'password';
    }
  });
</script>
</body>
</html>
