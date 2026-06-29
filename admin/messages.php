<?php
$pageTitle = 'Messages — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin','moderator'])) {
    redirect('auth/login.php');
}

$messages = mysqli_query($con,
    "SELECT m.*, us.username AS sender_name, ur.username AS receiver_name
     FROM messages m
     JOIN users us ON m.sender_id = us.user_id
     JOIN users ur ON m.receiver_id = ur.user_id
     ORDER BY m.created_at DESC
     LIMIT 100");
?>
<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h4 class="fw-800 mb-0" style="font-family:'Sora',sans-serif">💬 Message Monitor</h4>
    <div class="small">Overview of buyer-seller communications.</div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Message</th>
                    <th>Read</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
                    <?php while ($msg = mysqli_fetch_assoc($messages)): ?>
                    <tr>
                        <td class="ps-4 small"><?= date('M j, H:i', strtotime($msg['created_at'])) ?></td>
                        <td class="fw-600"><?= htmlspecialchars($msg['sender_name']) ?></td>
                        <td><?= htmlspecialchars($msg['receiver_name']) ?></td>
                        <td class="small" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?= htmlspecialchars($msg['body']) ?>
                        </td>
                        <td>
                            <?php if ($msg['is_read']): ?>
                                <span class="badge bg-success">Read</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unread</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">No messages yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>
