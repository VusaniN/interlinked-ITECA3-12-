<?php
$pageTitle = 'Account Verification — Interlinked';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin();
requireSeller();

$uid = $_SESSION['user_id'];

// Get current verification status
$currentDoc = false;
$docResult = mysqli_query($con, "SELECT * FROM document_verification WHERE user_id = $uid ORDER BY submitted_at DESC LIMIT 1");
if ($docResult) {
    $currentDoc = mysqli_fetch_assoc($docResult);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = 'Security expired.';
    } else {
        $docType = isset($_POST['doc_type']) ? $_POST['doc_type'] : 'id_document';

        if (empty($_FILES['document']['name'])) {
            $error = 'Please select a file to upload.';
        } else {
            $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','jpg','jpeg','png'];
            if (!in_array($ext, $allowed)) {
                $error = 'Only PDF, JPG, and PNG files are accepted.';
            } elseif ($_FILES['document']['size'] > 5 * 1024 * 1024) {
                $error = 'File must be under 5MB.';
            } else {
                $fileName = 'verify_' . $uid . '_' . time() . '.' . $ext;
                $dest = dirname(__FILE__) . '/uploads/verification/' . $fileName;
                if (move_uploaded_file($_FILES['document']['tmp_name'], $dest)) {
                    $stmt = $con->prepare("INSERT INTO document_verification (user_id, doc_type, file_path, file_name) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $uid, $docType, $fileName, $_FILES['document']['name']);
                    $stmt->execute();
                    $success = 'Document submitted! Admin will review it within 24-48 hours.';
                    $currentDoc = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM document_verification WHERE user_id = $uid ORDER BY submitted_at DESC LIMIT 1"));
                } else {
                    $error = 'Failed to upload file.';
                }
            }
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5" style="max-width:600px">
  <h2 class="fw-800 mb-4" style="font-family:'Sora',sans-serif">Account Verification</h2>

  <p class="text mb-4">Upload your ID document or business registration to get a <strong style="color:var(--accent)">Trusted Seller</strong> badge. Verified sellers get more visibility and trust.</p>

  <?php if ($error): ?>
  <div class="alert alert-danger mb-4"><?= e($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="alert alert-success mb-4"><?= e($success) ?></div>
  <?php endif; ?>

  <!-- Current Status -->
  <?php if ($currentDoc): ?>
  <div class="card p-4 mb-4">
    <h5 class="fw-700 mb-3">Current Verification Status</h5>
    <div class="d-flex align-items-center gap-3 mb-3">
      <?php if ($currentDoc['status'] === 'pending'): ?>
      <span class="badge-status" style="background:rgba(245,158,11,0.2);color:#f59e0b; padding:8px 16px; font-size:.85rem">PENDING REVIEW</span>
      <?php elseif ($currentDoc['status'] === 'approved'): ?>
      <span class="badge-status" style="background:rgba(16,185,129,0.2);color:#10b981; padding:8px 16px; font-size:.85rem">APPROVED</span>
      <?php elseif ($currentDoc['status'] === 'rejected'): ?>
      <span class="badge-status" style="background:rgba(244,63,94,0.2);color:#f43f5e; padding:8px 16px; font-size:.85rem">REJECTED</span>
      <?php endif; ?>
    </div>
    <div class="small text">
      <div>Document: <?= e($currentDoc['file_name']) ?></div>
      <div>Type: <?= e(ucfirst(str_replace('_',' ',$currentDoc['doc_type']))) ?></div>
      <div>Submitted: <?= date('j M Y, H:i', strtotime($currentDoc['submitted_at'])) ?></div>
      <?php if ($currentDoc['admin_note']): ?>
      <div class="mt-2 p-2" style="background:var(--surface2); border-radius:8px">
        <strong>Admin note:</strong> <?= e($currentDoc['admin_note']) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Upload Form -->
  <?php if (!$currentDoc || $currentDoc['status'] === 'rejected'): ?>
  <div class="card p-4">
    <h5 class="fw-700 mb-4">Upload Document</h5>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>

      <div class="mb-3">
        <label class="form-label fw-600">Document Type</label>
        <select class="form-select" name="doc_type">
          <option value="id_document">ID Document (National ID / Passport)</option>
          <option value="business_registration">Business Registration Certificate</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div class="mb-4">
        <label class="form-label fw-600">Upload File</label>
        <input type="file" class="form-control" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
        <div class="small text mt-1">Accepted: PDF, JPG, PNG. Max 5MB.</div>
      </div>

      <button type="submit" class="btn btn-primary w-100">Submit for Review</button>
    </form>
  </div>
  <?php elseif ($currentDoc['status'] === 'pending'): ?>
  <div class="card p-4 text-center">
    <i data-feather="clock" style="width:48px;height:48px;color:var(--warning)"></i>
    <p class="text mt-3">Your document is being reviewed. You'll be notified once a decision is made.</p>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
