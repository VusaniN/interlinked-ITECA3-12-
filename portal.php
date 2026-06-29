<?php
$pageTitle = 'Dashboard — Interlinked';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin();

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user info
$userQuery = mysqli_query($con, "SELECT * FROM users WHERE user_id = $uid");
$user = mysqli_fetch_assoc($userQuery);

// Role-based stats
if ($role === 'seller' || $role === 'admin') {
    $myListings = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM products WHERE seller_id = $uid"))[0];
    $pendingListings = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM products WHERE seller_id = $uid AND status = 'pending'"))[0];
    $mySales = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM orders WHERE seller_id = $uid"))[0];
    $myRevenue = isset($user['revenue']) ? $user['revenue'] : 0;
    $recentSales = mysqli_query($con, "SELECT o.*, p.product_name FROM orders o JOIN products p ON o.product_id = p.product_id WHERE o.seller_id = $uid ORDER BY o.created_at DESC LIMIT 5");
}

if ($role === 'buyer' || $role === 'admin') {
    $myOrders = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM orders WHERE buyer_id = $uid"))[0];
    $wishlistCount = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM wishlist WHERE user_id = $uid"))[0];
    $recentOrders = mysqli_query($con, "SELECT o.*, p.product_name, p.product_image, u.username AS seller_name FROM orders o JOIN products p ON o.product_id = p.product_id JOIN users u ON o.seller_id = u.user_id WHERE o.buyer_id = $uid ORDER BY o.created_at DESC LIMIT 5");
}

$recentListings = mysqli_query($con, "SELECT * FROM products WHERE seller_id = $uid ORDER BY created_at DESC LIMIT 5");
$unreadMessages = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM messages WHERE receiver_id = $uid AND is_read = 0"))[0];
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
  <!-- Welcome Header -->
  <div class="d-flex align-items-center gap-4 mb-5 flex-wrap">
    <img src="<?= url('uploads/avatars/' . e($user['avatar'])) ?>" style="width:80px;height:80px;border-radius:24px;object-fit:cover;border:2px solid var(--accent)" onerror="this.src='<?= url('assets/images/default-avatar.png') ?>">
    <div>
      <h2 class="fw-800 mb-1" style="font-family:'Sora',sans-serif">Welcome back, <?= e($_SESSION['username']) ?></h2>
      <div class="d-flex align-items-center gap-2">
        <?php if ($role === 'seller'): ?>
        <span class="badge" style="background:rgba(212,175,55,0.1); color:var(--accent); text-transform:uppercase; font-size:.65rem; letter-spacing:1px; border:1px solid var(--border)">SELLER</span>
        <?php if (isset($user['is_verified']) && $user['is_verified']): ?>
        <span class="badge" style="background:rgba(16,185,129,0.1); color:#10b981; text-transform:uppercase; font-size:.65rem; letter-spacing:1px; border:1px solid rgba(16,185,129,0.3)">VERIFIED</span>
        <?php endif; ?>
        <?php if (isset($user['seller_code']) && $user['seller_code']): ?>
        <span class="text small"><?= e($user['seller_code']) ?></span>
        <?php endif; ?>
        <?php elseif ($role === 'buyer'): ?>
        <span class="badge" style="background:rgba(16,185,129,0.1); color:var(--success); text-transform:uppercase; font-size:.65rem; letter-spacing:1px; border:1px solid var(--border)">BUYER</span>
        <?php elseif ($role === 'admin'): ?>
        <span class="badge" style="background:rgba(244,63,94,0.1); color:var(--danger); text-transform:uppercase; font-size:.65rem; letter-spacing:1px; border:1px solid var(--border)">ADMINISTRATOR</span>
        <?php endif; ?>
        <span class="text small">ID: #<?= str_pad($uid, 6, '0', STR_PAD_LEFT) ?></span>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2">
      <?php if ($role === 'seller' || $role === 'admin'): ?>
      <a href="<?= url('create_product.php') ?>" class="btn btn-primary px-4"><i data-feather="plus" class="me-2" style="width:16px"></i> Create Listing</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- SELLER DASHBOARD -->
  <?php if ($role === 'seller'): ?>
  <div class="row g-4 mb-5">
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">MY LISTINGS</div>
        <div class="stat-value" style="color:var(--accent)"><?= $myListings ?></div>
        <?php if ($pendingListings > 0): ?>
        <div class="small text-warning mt-1"><?= $pendingListings ?> pending approval</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">TOTAL SALES</div>
        <div class="stat-value" style="color:var(--success)"><?= $mySales ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">REVENUE</div>
        <div class="stat-value" style="color:var(--accent)">R<?= number_format($myRevenue, 0) ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">MESSAGES</div>
        <div class="stat-value" style="color:var(--text)"><?= $unreadMessages ?></div>
        <div class="small text mt-1">unread</div>
      </div>
    </div>
  </div>

  <!-- Recent Sales -->
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card p-4">
        <h5 class="fw-700 mb-3">Recent Sales</h5>
        <?php if ($recentSales && mysqli_num_rows($recentSales) > 0): ?>
        <div class="table-responsive">
          <table class="table admin-table mb-0">
            <thead><tr><th>Order</th><th>Product</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              <?php while ($o = mysqli_fetch_assoc($recentSales)): ?>
              <tr>
                <td>#<?= str_pad($o['order_id'],6,'0',STR_PAD_LEFT) ?></td>
                <td><?= e(substr($o['product_name'],0,25)) ?></td>
                <td class="fw-700" style="color:var(--accent)">R<?= number_format($o['total_amount'],2) ?></td>
                <td><span class="badge-status" style="background:rgba(245,158,11,0.2);color:#f59e0b"><?= e($o['status']) ?></span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p class="text">No sales yet.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card p-4">
        <h5 class="fw-700 mb-3">My Listings</h5>
        <?php if ($recentListings && mysqli_num_rows($recentListings) > 0): ?>
        <?php while ($p = mysqli_fetch_assoc($recentListings)): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <div class="fw-600 small"><?= e(substr($p['product_name'],0,30)) ?></div>
            <div class="small text">R<?= number_format($p['product_price'],2) ?></div>
          </div>
          <span class="badge-status" style="background:<?= $p['status']=='approved' ? 'rgba(16,185,129,0.2);color:#10b981' : ($p['status']=='pending' ? 'rgba(245,158,11,0.2);color:#f59e0b' : 'rgba(244,63,94,0.2);color:#f43f5e') ?>"><?= ucfirst($p['status']) ?></span>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <p class="text">No listings yet. <a href="<?= url('create_product.php') ?>" class="text-accent">Create your first listing</a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- BUYER DASHBOARD -->
  <?php elseif ($role === 'buyer'): ?>
  <div class="row g-4 mb-5">
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">MY ORDERS</div>
        <div class="stat-value" style="color:var(--accent)"><?= $myOrders ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">WISHLIST</div>
        <div class="stat-value" style="color:var(--text)"><?= $wishlistCount ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">MESSAGES</div>
        <div class="stat-value" style="color:var(--text)"><?= $unreadMessages ?></div>
        <div class="small text mt-1">unread</div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">BROWSE</div>
        <a href="<?= url('products.php') ?>" class="btn btn-outline-primary btn-sm mt-2">View Products</a>
      </div>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="card p-4">
    <h5 class="fw-700 mb-3">Recent Orders</h5>
    <?php if ($recentOrders && mysqli_num_rows($recentOrders) > 0): ?>
    <div class="table-responsive">
      <table class="table admin-table mb-0">
        <thead><tr><th>Order</th><th>Product</th><th>Seller</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php while ($o = mysqli_fetch_assoc($recentOrders)): ?>
          <tr>
            <td>#<?= str_pad($o['order_id'],6,'0',STR_PAD_LEFT) ?></td>
            <td><?= e(substr($o['product_name'],0,25)) ?></td>
            <td><?= e($o['seller_name']) ?></td>
            <td class="fw-700" style="color:var(--accent)">R<?= number_format($o['total_amount'],2) ?></td>
            <td><span class="badge-status" style="background:<?= $o['status']=='delivered' ? 'rgba(16,185,129,0.2);color:#10b981' : 'rgba(245,158,11,0.2);color:#f59e0b' ?>"><?= ucfirst($o['status']) ?></span></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="text mb-0">No orders yet. <a href="<?= url('products.php') ?>" class="text-accent">Start shopping!</a></p>
    <?php endif; ?>
  </div>

  <!-- ADMIN DASHBOARD -->
  <?php elseif ($role === 'admin' || $role === 'moderator'): ?>
  <div class="row g-4 mb-5">
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">PENDING PRODUCTS</div>
        <?php $pendingCount = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM products WHERE status='pending'"))[0]; ?>
        <div class="stat-value" style="color:var(--warning)"><?= $pendingCount ?></div>
        <a href="<?= url('admin/index.php') ?>" class="small text-accent">Review &rarr;</a>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">PENDING VERIFICATIONS</div>
        <?php $pendingDocs = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM document_verification WHERE status='pending'"))[0]; ?>
        <div class="stat-value" style="color:var(--warning)"><?= $pendingDocs ?></div>
        <a href="<?= url('admin/verification_queue.php') ?>" class="small text-accent">Review &rarr;</a>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">TOTAL USERS</div>
        <?php $totalUsers = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM users"))[0]; ?>
        <div class="stat-value" style="color:var(--text)"><?= $totalUsers ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="dashboard-card">
        <div class="text small fw-700 mb-1">TOTAL ORDERS</div>
        <?php $totalOrders = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM orders"))[0]; ?>
        <div class="stat-value" style="color:var(--text)"><?= $totalOrders ?></div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card p-4">
        <h5 class="fw-700 mb-3">Admin Quick Links</h5>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('admin/index.php') ?>" class="btn btn-outline-primary">Product Approvals</a>
          <a href="<?= url('admin/verification_queue.php') ?>" class="btn btn-outline-primary">Document Verifications</a>
          <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-primary">Manage Users</a>
          <a href="<?= url('admin/orders.php') ?>" class="btn btn-outline-primary">All Orders</a>
          <a href="<?= url('admin/categories.php') ?>" class="btn btn-outline-primary">Categories</a>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card p-4">
        <h5 class="fw-700 mb-3">Recent Activity</h5>
        <?php $activity = mysqli_query($con, "SELECT a.*, u.username FROM activity_log a LEFT JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 5"); ?>
        <?php if ($activity && mysqli_num_rows($activity) > 0): ?>
        <?php while ($a = mysqli_fetch_assoc($activity)): ?>
        <div class="mb-2 small">
          <span class="fw-600"><?= e(isset($a['username']) ? $a['username'] : 'System') ?></span>
          <span class="text"> — <?= e($a['action']) ?></span>
          <div class="text" style="font-size:.75rem"><?= date('j M H:i', strtotime($a['created_at'])) ?></div>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <p class="text">No recent activity.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
