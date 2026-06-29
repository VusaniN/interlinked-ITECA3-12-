<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$pageTitle = 'Verification Queue';
require_once 'includes/admin_header.php';

$docs = mysqli_query($con, "
    SELECT d.*, u.username, u.email, u.full_name, u.avatar
    FROM document_verification d
    JOIN users u ON d.user_id = u.user_id
    WHERE d.status = 'pending'
    ORDER BY d.submitted_at DESC
");
?>

<h3 class="fw-800 mb-4" style="font-family:'Sora',sans-serif">Document Verification Queue</h3>

<p class="text mb-4">Review seller documents and approve or reject verification requests.</p>

<?php if ($docs && mysqli_num_rows($docs) > 0): ?>
<div class="row g-4">
    <?php while ($d = mysqli_fetch_assoc($docs)): ?>
    <div class="col-lg-6">
        <div class="card p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?= url('uploads/avatars/' . e($d['avatar'])) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover" onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'">
                <div>
                    <div class="fw-700"><?= e($d['full_name']) ?></div>
                    <div class="small text">@<?= e($d['username']) ?> &middot; <?= e($d['email']) ?></div>
                </div>
            </div>

            <div class="mb-3 p-3" style="background:var(--surface2); border-radius:10px">
                <div class="row g-2 small">
                    <div class="col-6"><strong>Type:</strong> <?= e(ucfirst(str_replace('_',' ',$d['doc_type']))) ?></div>
                    <div class="col-6"><strong>Submitted:</strong> <?= date('j M Y, H:i', strtotime($d['submitted_at'])) ?></div>
                    <div class="col-12"><strong>File:</strong> <?= e($d['file_name']) ?></div>
                </div>
            </div>

            <!-- Document Preview -->
            <div class="mb-3">
                <?php
                $ext = pathinfo($d['file_path'], PATHINFO_EXTENSION);
                $filePath = url('uploads/verification/' . $d['file_path']);
                ?>
                <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                <a href="<?= $filePath ?>" target="_blank">
                    <img src="<?= $filePath ?>" style="max-width:100%; max-height:200px; border-radius:8px; cursor:pointer" alt="Document">
                </a>
                <?php else: ?>
                <a href="<?= $filePath ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i data-feather="file" class="me-1" style="width:14px"></i> View Document (PDF)
                </a>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="process_verification">
                <input type="hidden" name="doc_id" value="<?= $d['doc_id'] ?>">
                <?= csrfField() ?>
                <div class="mb-2">
                    <textarea class="form-control" name="admin_note" rows="2" placeholder="Admin note (optional, shown to user)..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="decision" value="approved" class="btn btn-success flex-grow-1">
                        <i data-feather="check" class="me-1" style="width:14px"></i> Approve
                    </button>
                    <button type="submit" name="decision" value="rejected" class="btn btn-danger flex-grow-1">
                        <i data-feather="x" class="me-1" style="width:14px"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="card p-5 text-center">
    <i data-feather="check-circle" style="width:48px;height:48px;color:var(--success)"></i>
    <p class="text mt-3">No pending verifications. All caught up!</p>
</div>
<?php endif; ?>

<?php require_once 'includes/admin_footer.php'; ?>
