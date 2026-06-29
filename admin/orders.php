<?php
$pageTitle = 'Manage Orders — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin','moderator'])) {
    redirect('auth/login.php');
}

$success = '';
$error = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'confirm_payment') {
        mysqli_begin_transaction($con);
        try {
            $stmt = $con->prepare("UPDATE orders SET payment_status = 'paid', status = 'paid' WHERE order_id = ? AND payment_status != 'paid'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $oStmt = $con->prepare("SELECT seller_id, total_amount, product_id FROM orders WHERE order_id = ?");
                $oStmt->bind_param("i", $id);
                $oStmt->execute();
                $o = $oStmt->get_result()->fetch_assoc();
                
                $uStmt = $con->prepare("UPDATE users SET revenue = revenue + ?, total_sales = total_sales + 1 WHERE user_id = ?");
                $uStmt->bind_param("di", $o['total_amount'], $o['seller_id']);
                $uStmt->execute();
                
                $pStmt = $con->prepare("UPDATE products SET status = 'sold' WHERE product_id = ? AND quantity <= 0");
                $pStmt->bind_param("i", $o['product_id']);
                $pStmt->execute();

                $nStmt = $con->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, 'success', 'Payment Received!', ?, '../orders.php')");
                $nBody = "Payment of R" . number_format($o['total_amount'],2) . " for order #$id has been confirmed.";
                $nStmt->bind_param("is", $o['seller_id'], $nBody);
                $nStmt->execute();

                mysqli_commit($con);
                $success = "Payment for Order #$id confirmed and revenue updated.";
            } else {
                mysqli_rollback($con);
                $error = "Order already paid or not found.";
            }
        } catch (Exception $e) {
            mysqli_rollback($con);
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

$orders = mysqli_query($con, 
    "SELECT o.*, p.product_name, u_b.username AS buyer_name, u_s.username AS seller_name 
     FROM orders o 
     JOIN products p ON o.product_id = p.product_id 
     JOIN users u_b ON o.buyer_id = u_b.user_id 
     JOIN users u_s ON o.seller_id = u_s.user_id 
     ORDER BY o.created_at DESC");
?>

<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h4 class="fw-800 mb-0" style="font-family:'Sora',sans-serif">🛒 Order Management</h4>
    <div class="text small">Monitor and confirm transactions.</div>
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
                    <th class="ps-4">Order ID</th>
                    <th>Asset Details</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th class="text-end pe-4">Settlement</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($o = mysqli_fetch_assoc($orders)): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-700" style="color:#fff">#<?= str_pad($o['order_id'], 6, '0', STR_PAD_LEFT) ?></div>
                        <div class="small"><?= date('M j, Y', strtotime($o['created_at'])) ?></div>
                    </td>
                    <td class="fw-600"><?= htmlspecialchars($o['product_name']) ?></td>
                    <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                    <td><?= htmlspecialchars($o['seller_name']) ?></td>
                    <td class="fw-800" style="color:var(--accent)">R<?= number_format($o['total_amount'], 2) ?></td>
                    <td>
                        <?php 
                        $statusColors = [
                            'pending'  => ['bg' => 'rgba(245, 158, 11, 0.1)', 'text' => '#f59e0b'],
                            'paid'     => ['bg' => 'rgba(16, 185, 129, 0.1)', 'text' => '#10b981'],
                            'refunded' => ['bg' => 'rgba(244, 63, 94, 0.1)',  'text' => '#f43f5e']
                        ];
                        $c = $statusColors[$o['payment_status']] ?? $statusColors['pending'];
                        ?>
                        <span class="status-badge" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>">
                            <?= strtoupper($o['payment_status']) ?>
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <?php if ($o['payment_status'] !== 'paid'): ?>
                            <a href="?action=confirm_payment&id=<?= $o['order_id'] ?>" 
                               class="btn btn-sm btn-primary py-1 px-3" style="font-size:.75rem"
                               onclick="return confirm('Confirm settlement for this order?')">Verify Payment</a>
                        <?php else: ?>
                            <span class="text small fw-700"><i data-feather="check-circle" style="width:14px" class="me-1"></i> SETTLED</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($orders) == 0): ?>
                <tr><td colspan="7" class="text-center py-5 text">No transaction history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>

