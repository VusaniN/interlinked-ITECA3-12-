<?php
$pageTitle = 'My Orders — Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

$uid = $_SESSION['user_id'];

$myOrders = mysqli_query($con, "
    SELECT o.*, p.product_name, p.product_image, u.username AS seller_name
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON o.seller_id = u.user_id
    WHERE o.buyer_id = $uid
    ORDER BY o.created_at DESC
");

$mySales = mysqli_query($con, "
    SELECT o.*, p.product_name, p.product_image, u.username AS buyer_name
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON o.buyer_id = u.user_id
    WHERE o.seller_id = $uid
    ORDER BY o.created_at DESC
");
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
  <h2 class="fw-800 mb-4" style="font-family:'Sora',sans-serif">📋 Order History</h2>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success mb-4"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <h5 class="fw-700 mb-3" style="color:var(--accent)">My Purchases</h5>
  <?php if ($myOrders && mysqli_num_rows($myOrders) > 0): ?>
  <div class="table-responsive mb-5">
    <table class="table admin-table mb-0">
      <thead><tr><th>Order</th><th>Product</th><th>Seller</th><th>Amount</th><th>Status</th><th>Payment</th></tr></thead>
      <tbody>
        <?php while ($o = mysqli_fetch_assoc($myOrders)): ?>
        <tr>
          <td>#<?= str_pad($o['order_id'],6,'0',STR_PAD_LEFT) ?></td>
          <td><?= htmlspecialchars(substr($o['product_name'],0,30)) ?></td>
          <td><?= htmlspecialchars($o['seller_name']) ?></td>
          <td class="fw-800" style="color:var(--accent)">R<?= number_format($o['total_amount'],2) ?></td>
          <td><span class="badge bg-secondary"><?= ucfirst($o['status']) ?></span></td>
          <td><span class="badge" style="background:<?= $o['payment_status']=='paid' ? 'rgba(16,185,129,0.2);color:#10b981' : 'rgba(245,158,11,0.2);color:#f59e0b' ?>"><?= ucfirst($o['payment_status']) ?></span></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p class="mb-4">No purchases yet. <a href="<?= url('products.php') ?>" class="text-accent">Browse listings</a></p>
  <?php endif; ?>

  <h5 class="fw-700 mb-3" style="color:var(--accent)">My Sales</h5>
  <?php if ($mySales && mysqli_num_rows($mySales) > 0): ?>
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead><tr><th>Order</th><th>Product</th><th>Buyer</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php while ($s = mysqli_fetch_assoc($mySales)): ?>
        <tr>
          <td>#<?= str_pad($s['order_id'],6,'0',STR_PAD_LEFT) ?></td>
          <td><?= htmlspecialchars(substr($s['product_name'],0,30)) ?></td>
          <td><?= htmlspecialchars($s['buyer_name']) ?></td>
          <td class="fw-800" style="color:var(--accent)">R<?= number_format($s['total_amount'],2) ?></td>
          <td><span class="badge bg-secondary"><?= ucfirst($s['status']) ?></span></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p>No sales yet.</p>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
