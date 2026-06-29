<?php
$pageTitle = 'Manage Users — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin'])) {
    redirect('admin/index.php');
}

$success = '';
$error = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'activate') {
        $stmt = $con->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $success = "User account activated.";
    } elseif ($action === 'deactivate') {
        $stmt = $con->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $success = "User account deactivated.";
    } elseif ($action === 'delete') {
        if ($id != $_SESSION['user_id']) {
            $con->begin_transaction();
            try {
                // Delete from tables (only tables that exist)
                $childDeletes = [
                    'DELETE FROM wishlist WHERE user_id = ?',
                    'DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?',
                    'DELETE FROM orders WHERE buyer_id = ? OR seller_id = ?',
                    'DELETE FROM products WHERE seller_id = ?',
                ];
                foreach ($childDeletes as $sql) {
                    $s = $con->prepare($sql);
                    if (substr_count($sql, '?') === 1) {
                        $s->bind_param("i", $id);
                    } else {
                        $s->bind_param("ii", $id, $id);
                    }
                    $s->execute();
                }
                // Finally delete the user
                $stmt = $con->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $con->commit();
                $success = "User account and all related data permanently removed.";
            } catch (Exception $e) {
                $con->rollback();
                $error = "Cannot delete user: " . $e->getMessage();
            }
        } else {
            $error = "Self-deletion is prohibited.";
        }
    }
}

$users = mysqli_query($con, 
    "SELECT u.*, r.role_name 
     FROM users u 
     JOIN roles r ON u.role_id = r.role_id 
     ORDER BY u.created_at DESC");
?>

<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h4 class="fw-800 mb-0" style="font-family:'Sora',sans-serif">👥 User Directory</h4>
    <div class="text small">Manage access and account status for all members.</div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" style="background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.2); color:#10b981">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" style="background:rgba(244, 63, 94, 0.1); border:1px solid rgba(244, 63, 94, 0.2); color:#f43f5e">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Identity</th>
                  	<th>Phone</th>
                    <th>Revenue</th>
                    <th>Sales</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3 py-1">
                            <img src="<?= url('uploads/avatars/'.$u['avatar']) ?>" 
                                 class="rounded-circle" style="width:40px;height:40px;object-fit:cover;border:1px solid var(--border)"
                                 onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'">
                            <div>
                                <div class="fw-700" style="color:#fff"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="small text"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="background:rgba(255,255,255,0.05); border:1px solid var(--border); color:var(--accent)">
                            <?= strtoupper($u['role_name']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td class="fw-700">R<?= number_format($u['revenue'], 2) ?></td>
                    <td><?= $u['total_sales'] ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="status-badge" style="background:rgba(16, 185, 129, 0.1); color:#10b981">Active</span>
                        <?php else: ?>
                            <span class="status-badge" style="background:rgba(244, 63, 94, 0.1); color:#f43f5e">Suspended</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-2">
                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                <?php if ($u['is_active']): ?>
                                    <a href="?action=deactivate&id=<?= $u['user_id'] ?>" class="btn btn-sm btn-outline-warning py-1 px-3" style="font-size:.7rem">Suspend</a>
                                <?php else: ?>
                                    <a href="?action=activate&id=<?= $u['user_id'] ?>" class="btn btn-sm btn-success py-1 px-3" style="font-size:.7rem">Reactivate</a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?= $u['user_id'] ?>" 
                                   class="btn btn-sm btn-link text-danger p-0" 
                                   onclick="return confirm('Permanently delete this user account?')">
                                    <i data-feather="trash-2" style="width:16px"></i>
                                </a>
                            <?php else: ?>
                                <span class="small italic">Current Admin</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>
