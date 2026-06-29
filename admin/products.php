<?php
$pageTitle = 'Manage Products — Interlinked Admin';
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
    
    if ($action === 'approve') {
        $stmt = $con->prepare("UPDATE products SET status = 'approved' WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Product #$id approved successfully.";
            
            // Notify seller
            $pQuery = $con->prepare("SELECT seller_id, product_name FROM products WHERE product_id = ?");
            $pQuery->bind_param("i", $id);
            $pQuery->execute();
            $p = $pQuery->get_result()->fetch_assoc();
            
            $notifStmt = $con->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, 'success', 'Product Approved!', ?, ?)");
            $body = "Your product \"{$p['product_name']}\" has been approved and is now live.";
            $link = "product.php?id=$id";
            $notifStmt->bind_param("iss", $p['seller_id'], $body, $link);
            $notifStmt->execute();
        }
    } elseif ($action === 'reject') {
        $stmt = $con->prepare("UPDATE products SET status = 'rejected' WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Product #$id rejected.";
        }
    } elseif ($action === 'delete') {
        // Get product name before deleting
        $nameStmt = $con->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $nameStmt->bind_param("i", $id);
        $nameStmt->execute();
        $prodName = $nameStmt->get_result()->fetch_assoc()['product_name'] ?? "Product #$id";
        
        $stmt = $con->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $remaining = $con->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
            $success = '"' . htmlspecialchars($prodName) . '" deleted. $remaining product' . ($remaining !== 1 ? 's' : '') . ' remaining.';
        }
    }
}

$statusFilter = $_GET['status'] ?? 'pending';
$sql = "SELECT p.*, u.username, c.name as cat_name 
        FROM products p 
        JOIN users u ON p.seller_id = u.user_id 
        JOIN categories c ON p.category_id = c.category_id ";

// Get counts for each status
$countStmt = $con->query("SELECT status, COUNT(*) as cnt FROM products GROUP BY status");
$counts = [];
while ($row = $countStmt->fetch_assoc()) {
    $counts[$row['status']] = $row['cnt'];
}
$totalProducts = array_sum($counts);
$pendingCount  = $counts['pending']  ?? 0;
$approvedCount = $counts['approved'] ?? 0;
$rejectedCount = $counts['rejected'] ?? 0;

if ($statusFilter !== 'all') {
    $sql .= " WHERE p.status = ? ";
    $stmt = $con->prepare($sql . " ORDER BY p.created_at DESC");
    $stmt->bind_param("s", $statusFilter);
} else {
    $stmt = $con->prepare($sql . " ORDER BY p.created_at DESC");
}

$stmt->execute();
$products = $stmt->get_result();
?>

<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
    <div>
        <h4 class="fw-800 mb-1" style="font-family:'Sora',sans-serif">📦 Product Inventory</h4>
        <div class="d-flex gap-3 mt-2">
            <span class="badge" style="background:rgba(245,158,11,0.1);color:#f59e0b;border:1px solid rgba(245,158,11,0.2)"><?= $pendingCount ?> Pending</span>
            <span class="badge" style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)"><?= $approvedCount ?> Approved</span>
            <span class="badge" style="background:rgba(244,63,94,0.1);color:#f43f5e;border:1px solid rgba(244,63,94,0.2)"><?= $rejectedCount ?> Rejected</span>
            <span class="badge" style="background:rgba(255,255,255,0.05);color:#fff;border:1px solid var(--border)"><?= $totalProducts ?> Total</span>
        </div>
    </div>
    <div class="btn-group p-1" style="background:rgba(255,255,255,0.05);border-radius:14px">
        <a href="?status=pending" class="btn btn-<?= $statusFilter=='pending'?'primary':'link' ?> btn-sm px-3" style="border-radius:10px">Pending</a>
        <a href="?status=approved" class="btn btn-<?= $statusFilter=='approved'?'primary':'link' ?> btn-sm px-3" style="border-radius:10px">Approved</a>
        <a href="?status=all" class="btn btn-<?= $statusFilter=='all'?'primary':'link' ?> btn-sm px-3" style="border-radius:10px">All Listings</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" style="background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.2); color:#10b981">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Product Details</th>
                    <th>Seller</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $products->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3 py-1">
                            <img src="<?= url('uploads/products/'.$p['product_image']) ?>" 
                                 class="rounded-3" style="width:50px;height:50px;object-fit:cover" 
                                 onerror="this.src='<?= url('assets/images/placeholder.jpg') ?>'">
                            <div>
                                <div class="fw-700" style="color:#fff"><?= htmlspecialchars($p['product_name']) ?></div>
                                <div class="small">SKU: ITE-<?= str_pad($p['product_id'], 5, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="fw-600"><?= htmlspecialchars($p['username']) ?></td>
                    <td><span class="badge" style="background:rgba(255,255,255,0.05);border:1px solid var(--border)"><?= htmlspecialchars($p['cat_name']) ?></span></td>
                    <td class="fw-800" style="color:var(--accent)">R<?= number_format($p['product_price'], 2) ?></td>
                    <td>
                        <?php 
                        $statusColors = [
                            'pending'  => ['bg' => 'rgba(245, 158, 11, 0.1)', 'text' => '#f59e0b'],
                            'approved' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'text' => '#10b981'],
                            'rejected' => ['bg' => 'rgba(244, 63, 94, 0.1)',  'text' => '#f43f5e'],
                            'sold'     => ['bg' => 'rgba(59, 130, 246, 0.1)',  'text' => '#3b82f6']
                        ];
                        $c = $statusColors[$p['status']] ?? $statusColors['pending'];
                        ?>
                        <span class="status-badge" style="background:<?= $c['bg'] ?>;color:<?= $c['text'] ?>">
                            <?= ucfirst($p['status']) ?>
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-2">
                            <?php if ($p['status'] === 'pending'): ?>
                                <a href="?action=approve&id=<?= $p['product_id'] ?>&status=<?= $statusFilter ?>" 
                                   class="btn btn-sm btn-success py-1 px-3" style="font-size:.75rem">Approve</a>
                                <a href="?action=reject&id=<?= $p['product_id'] ?>&status=<?= $statusFilter ?>" 
                                   class="btn btn-sm btn-outline-danger py-1 px-3" style="font-size:.75rem">Reject</a>
                            <?php endif; ?>
                            <a href="<?= url('product.php?id='.$p['product_id']) ?>" target="_blank" 
                               class="btn btn-sm btn-outline-light py-1 px-3" style="font-size:.75rem">View</a>
                            <a href="?action=delete&id=<?= $p['product_id'] ?>&status=<?= $statusFilter ?>" 
                               class="btn btn-sm btn-danger py-1 px-3" style="font-size:.75rem" onclick="return confirm('Permanently delete this product? This cannot be undone.')">
                                Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($products->num_rows == 0): ?>
                <tr><td colspan="6" class="text-center py-5 text">No products found in this category.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>

