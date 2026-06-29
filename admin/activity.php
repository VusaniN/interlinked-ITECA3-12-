<?php
$pageTitle = 'Activity Log — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin'])) {
    redirect('admin/index.php');
}

$logs = mysqli_query($con,
    "SELECT a.*, u.username
     FROM activity_log a
     LEFT JOIN users u ON a.user_id = u.user_id
     ORDER BY a.created_at DESC
     LIMIT 200");
?>
<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h4 class="fw-800 mb-0" style="font-family:'Sora',sans-serif">⚡ Activity Log</h4>
    <div class="small">System events and admin actions.</div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Date</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td class="ps-4 small"><?= date('M j, Y H:i', strtotime($log['created_at'])) ?></td>
                        <td class="fw-600"><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td class="small"><?= htmlspecialchars($log['description'] ?? '') ?></td>
                        <td class="small"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i data-feather="activity" style="width:40px;height:40px;opacity:0.1" class="mb-2"></i>
                            <p>No activity recorded yet.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>
