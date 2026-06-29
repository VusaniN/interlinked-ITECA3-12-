<?php
// Dashboard for the user
$pageTitle = 'Portal — Interlinked SA';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

$uid = $_SESSION['user_id'];

// Get some counts for the dashboard stats
$myListings_q = mysqli_query($con,"SELECT COUNT(*) FROM products WHERE seller_id=$uid");
$myListings_r = mysqli_fetch_row($myListings_q);
$myListings = $myListings_r[0];

$myOrders_q = mysqli_query($con,"SELECT COUNT(*) FROM orders WHERE buyer_id=$uid");
$myOrders_r = mysqli_fetch_row($myOrders_q);
$myOrders = $myOrders_r[0];

$mySales_q = mysqli_query($con,"SELECT COUNT(*) FROM orders WHERE seller_id=$uid");
$mySales_r = mysqli_fetch_row($mySales_q);
$mySales = $mySales_r[0];

$userRow_q = mysqli_query($con,"SELECT revenue FROM users WHERE user_id=$uid");
$userRow = mysqli_fetch_assoc($userRow_q);
$myRevenue = 0;
if (isset($userRow['revenue'])) {
    $myRevenue = $userRow['revenue'];
}

// Get verification status
$vStmt = mysqli_query($con, "SELECT is_verified, verification_doc FROM users WHERE user_id=$uid");
$vData = mysqli_fetch_assoc($vStmt);
$isVerified = $vData['is_verified'] ?? 0;
$hasDoc = !empty($vData['verification_doc']);

// Get recent activity
$recentOrders = mysqli_query($con,"SELECT o.*, p.product_name FROM orders o
    JOIN products p ON o.product_id=p.product_id
    WHERE o.buyer_id=$uid ORDER BY o.created_at DESC LIMIT 5");

$recentListings = mysqli_query($con,"SELECT * FROM products WHERE seller_id=$uid ORDER BY created_at DESC LIMIT 5");
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5">

  <!-- Welcome message -->
  <div class="d-flex align-items-center gap-4 mb-5 flex-wrap">
    <div class="position-relative">
        <?php
            $avatar = 'default.png';
            if (isset($_SESSION['avatar'])) {
                $avatar = $_SESSION['avatar'];
            }
        ?>
        <img src="<?= url('uploads/avatars/' . $avatar) ?>"
             style="width:80px;height:80px;border-radius:24px;object-fit:cover;border:2px solid var(--accent); box-shadow: 0 10px 20px rgba(0,0,0,0.3)"
             onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'">
        <div style="position:absolute; bottom:-5px; right:-5px; background:var(--success); width:20px; height:20px; border-radius:50%; border:3px solid var(--surface)"></div>
    </div>
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger mb-4" style="background:rgba(244, 63, 94, 0.1); border:1px solid rgba(244, 63, 94, 0.2); color:#f43f5e; border-radius:12px; padding:1rem 1.5rem">
      <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <div>
      <h2 class="fw-800 mb-1" style="font-family:'Sora',sans-serif">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></h2>
      <div class="d-flex align-items-center gap-2">
          <span class="badge" style="background:rgba(212,175,55,0.1); color:var(--accent); text-transform:uppercase; font-size:.65rem; letter-spacing:1px; border:1px solid var(--border)"><?php if (isset($_SESSION['role'])) echo htmlspecialchars($_SESSION['role']); else echo 'MEMBER'; ?></span>
          <?php if ($isVerified): ?>
          <span class="badge d-flex align-items-center gap-1" style="background:rgba(16,185,129,0.15); color:#10b981; text-transform:uppercase; font-size:.65rem; letter-spacing:1px; border:1px solid rgba(16,185,129,0.3)">✅ VERIFIED</span>
          <?php endif; ?>
          <span class="text small">ID: #<?= str_pad($uid, 6, '0', STR_PAD_LEFT) ?></span>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= url('create_product.php') ?>" class="btn btn-primary px-4">
        <i data-feather="plus" class="me-2" style="width:16px"></i> Create Listing
      </a>
    </div>
  </div>

  <!-- Verification Status Banner */
  <?php if ($isVerified): ?>
  <div class="alert mb-4 d-flex align-items-center gap-3" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); color:#10b981; border-radius:12px; padding:1rem 1.5rem">
    <span style="font-size:1.5rem">✅</span>
    <div>
      <strong>Verified Account</strong>
      <p class="mb-0 small" style="color:rgba(16,185,129,0.8)">Your identity has been confirmed. You have the Trusted Seller badge.</p>
    </div>
  </div>
  <?php elseif ($hasDoc): ?>
  <div class="alert mb-4 d-flex align-items-center gap-3" style="background:rgba(212,175,55,0.1); border:1px solid rgba(212,175,55,0.3); color:#d4af37; border-radius:12px; padding:1rem 1.5rem">
    <span style="font-size:1.5rem">⏳</span>
    <div>
      <strong>Verification Pending Review</strong>
      <p class="mb-0 small" style="color:rgba(212,175,55,0.8)">We'll notify you once approved.</p>
    </div>
  </div>
  <?php else: ?>
  <div class="alert mb-4 d-flex align-items-center gap-3" style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.2); color:#f43f5e; border-radius:12px; padding:1rem 1.5rem">
    <span style="font-size:1.5rem">🛡️</span>
    <div class="d-flex align-items-center justify-content-between flex-grow-1">
      <div>
        <strong>Account Not Verified</strong>
        <p class="mb-0 small" style="color:rgba(244,63,94,0.8)">Verify your identity to get the Trusted Seller badge.</p>
      </div>
      <a href="<?= url('verification.php') ?>" class="btn btn-sm btn-outline-light">Verify Now</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Stats cards -->
  <div class="row g-4 mb-5">
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #111827, #030712); border:1px solid var(--border)">
        <div class="text small fw-700 mb-1">MY ASSETS</div>
        <div class="h3 fw-800 mb-0" style="color:#fff"><?= $myListings ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #111827, #030712); border:1px solid var(--border)">
        <div class="text small fw-700 mb-1">PRODUCTS</div>
        <div class="h3 fw-800 mb-0" style="color:#fff"><?= $myOrders ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #111827, #030712); border:1px solid var(--border)">
        <div class="text small fw-700 mb-1">TOTAL SALES</div>
        <div class="h3 fw-800 mb-0" style="color:#fff"><?= $mySales ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #d4af37, #b8962e); border:1px solid var(--border)">
        <div class="text small fw-700 mb-1" style="color:#000">TOTAL REVENUE</div>
        <div class="h3 fw-800 mb-0" style="color:#000">R <?= number_format($myRevenue, 0) ?></div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Show last 5 orders -->
    <div class="col-lg-7">
        <div class="card overflow-hidden h-100">
            <div class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                <h6 class="fw-700 mb-0">Recent Products</h6>
                <a href="<?= url('orders.php') ?>" class="text-accent small fw-700 text-decoration-none">View All History</a>
            </div>
            <div class="card-body p-0">
                <?php if ($recentOrders && mysqli_num_rows($recentOrders) > 0): ?>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Asset</th><th>Value</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php while ($o = mysqli_fetch_assoc($recentOrders)): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars(substr($o['product_name'],0,30)) ?>...</td>
                            <td class="fw-700" style="color:var(--accent)">R<?= number_format($o['total_amount'],2) ?></td>
                            <td><span class="badge"><?= strtoupper($o['status']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <p class="text small">No recent orders to display.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Show last 5 listings -->
    <div class="col-lg-5">
        <div class="card overflow-hidden h-100">
            <div class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                <h6 class="fw-700 mb-0">Live Inventory</h6>
                <a href="<?= url('create_product.php') ?>" class="btn btn-sm btn-primary py-1">+ New</a>
            </div>
            <div class="card-body p-0">
                <?php if ($recentListings && mysqli_num_rows($recentListings) > 0): ?>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Asset</th><th>Price</th><th>State</th></tr></thead>
                        <tbody>
                        <?php while ($l = mysqli_fetch_assoc($recentListings)): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars(substr($l['product_name'],0,25)) ?>...</td>
                            <td class="fw-700" style="color:var(--accent)">R<?= number_format($l['product_price'],2) ?></td>
                            <td>
                                <?php 
                                $s = $l['status'];
                                $c = '#f59e0b';
                                if ($s == 'approved') {
                                    $c = '#10b981';
                                } elseif ($s == 'rejected') {
                                    $c = '#f43f5e';
                                }
                                ?>
                                <span class="badge" style="background:<?= $c ?>; color:#fff; font-size:.75rem;"><?= strtoupper($s) ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <p class="text small">Your showroom is empty.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
