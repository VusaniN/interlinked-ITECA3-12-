<?php
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';
requireLogin('auth/login.php');
$uid = (int)$_SESSION['user_id'];
$pageTitle = 'Portal — Interlinked SA';

$myListings = 0; $myOrders = 0; $mySales = 0; $myRevenue = 0;
$stmt = $con->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
$stmt->bind_param("i", $uid); $stmt->execute(); $stmt->bind_result($myListings); $stmt->fetch(); $stmt->close();
$stmt = $con->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ?");
$stmt->bind_param("i", $uid); $stmt->execute(); $stmt->bind_result($myOrders); $stmt->fetch(); $stmt->close();
$stmt = $con->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ?");
$stmt->bind_param("i", $uid); $stmt->execute(); $stmt->bind_result($mySales); $stmt->fetch(); $stmt->close();
$stmt = $con->prepare("SELECT revenue FROM users WHERE user_id = ?");
$stmt->bind_param("i", $uid); $stmt->execute(); $stmt->bind_result($myRevenue); $stmt->fetch(); $stmt->close();
if (!$myRevenue) $myRevenue = 0;

$recentOrders = [];
$stmt = $con->prepare("SELECT o.*, p.product_name FROM orders o JOIN products p ON o.product_id = p.product_id WHERE o.buyer_id = ? ORDER BY o.created_at DESC LIMIT 5");
$stmt->bind_param("i", $uid); $stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $recentOrders[] = $row;
$stmt->close();

$recentListings = [];
$stmt = $con->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $uid); $stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $recentListings[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="<?= url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= url('assets/css/fonts.css') ?>" rel="stylesheet">
    <link href="<?= url('assets/css/main.css') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background:var(--primary);border-bottom:1px solid var(--border)">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>" style="font-family:var(--font-main);color:var(--accent)">Interlinked.</a>
        <div class="d-flex align-items-center gap-3">
            <a href="<?= url('products.php') ?>" class="text-decoration-none" style="color:rgba(255,255,255,0.8)">Products</a>
            <a href="<?= url('auth/logout.php') ?>" class="btn btn-sm" style="border:1px solid var(--border);color:var(--accent)">Sign Out</a>
        </div>
    </div>
</nav>

<div class="container py-5">

  <div class="d-flex align-items-center gap-4 mb-5 flex-wrap">
    <div class="position-relative">
        <?php $avatar = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'default.png'; ?>
        <img src="<?= url('uploads/avatars/' . $avatar) ?>"
             style="width:80px;height:80px;border-radius:24px;object-fit:cover;border:2px solid var(--accent); box-shadow: 0 10px 20px rgba(0,0,0,0.3)">
        <div style="position:absolute; bottom:-5px; right:-5px; background:var(--success); width:20px; height:20px; border-radius:50%; border:3px solid var(--surface)"></div>
    </div>
    <div>
      <h2 class="fw-800 mb-1" style="font-family:'Sora',sans-serif;color:#fff">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></h2>
      <div class="d-flex align-items-center gap-2">
          <span class="badge" style="background:rgba(212,175,55,0.1); color:var(--accent); text-transform:uppercase; font-size:.65rem; letter-spacing:1px; border:1px solid var(--border)"><?= htmlspecialchars($_SESSION['role'] ?? 'MEMBER') ?></span>
          <span class="small" style="color:rgba(255,255,255,0.7)">ID: #<?= str_pad($uid, 6, '0', STR_PAD_LEFT) ?></span>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= url('create_product.php') ?>" class="btn btn-primary px-4">+ Create Listing</a>
    </div>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #111827, #030712); border:1px solid var(--border)">
        <div class="small fw-700 mb-1" style="color:rgba(255,255,255,0.7)">MY ASSETS</div>
        <div class="h3 fw-800 mb-0" style="color:#fff"><?= $myListings ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #111827, #030712); border:1px solid var(--border)">
        <div class="small fw-700 mb-1" style="color:rgba(255,255,255,0.7)">PRODUCTS</div>
        <div class="h3 fw-800 mb-0" style="color:#fff"><?= $myOrders ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #111827, #030712); border:1px solid var(--border)">
        <div class="small fw-700 mb-1" style="color:rgba(255,255,255,0.7)">TOTAL SALES</div>
        <div class="h3 fw-800 mb-0" style="color:#fff"><?= $mySales ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-4 h-100" style="background:linear-gradient(135deg, #d4af37, #b8962e); border:1px solid var(--border)">
        <div class="small fw-700 mb-1" style="color:#fff">TOTAL REVENUE</div>
        <div class="h3 fw-800 mb-0" style="color:#fff">R <?= number_format($myRevenue, 0) ?></div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card overflow-hidden h-100" style="background:var(--card-bg);border:1px solid var(--border)">
        <div class="p-4 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(212,175,55,0.2)">
          <h6 class="fw-700 mb-0" style="color:#fff">Recent Orders</h6>
          <a href="<?= url('orders.php') ?>" class="small fw-700 text-decoration-none" style="color:var(--accent)">View All</a>
        </div>
        <div class="card-body p-0">
          <?php if (count($recentOrders) > 0): ?>
          <div class="table-responsive">
            <table class="table mb-0" style="color:#fff">
              <thead><tr><th>Product</th><th>Amount</th><th>Status</th></tr></thead>
              <tbody>
              <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars(substr($o['product_name'],0,30)) ?>...</td>
                <td class="fw-700" style="color:var(--accent)">R<?= number_format($o['total_amount'],2) ?></td>
                <td><span class="badge" style="background:rgba(212,175,55,0.2);color:var(--accent)"><?= strtoupper($o['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="text-center py-5"><p class="small" style="color:rgba(255,255,255,0.7)">No recent orders to display.</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card overflow-hidden h-100" style="background:var(--card-bg);border:1px solid var(--border)">
        <div class="p-4 d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(212,175,55,0.2)">
          <h6 class="fw-700 mb-0" style="color:#fff">My Listings</h6>
          <a href="<?= url('create_product.php') ?>" class="btn btn-sm btn-primary py-1">+ New</a>
        </div>
        <div class="card-body p-0">
          <?php if (count($recentListings) > 0): ?>
          <div class="table-responsive">
            <table class="table mb-0" style="color:#fff">
              <thead><tr><th>Product</th><th>Price</th><th>Status</th></tr></thead>
              <tbody>
              <?php foreach ($recentListings as $l): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars(substr($l['product_name'],0,25)) ?>...</td>
                <td class="fw-700" style="color:var(--accent)">R<?= number_format($l['product_price'],2) ?></td>
                <td>
                  <?php
                  $s = $l['status']; $c = '#f59e0b';
                  if ($s == 'approved') $c = '#10b981';
                  elseif ($s == 'rejected') $c = '#f43f5e';
                  ?>
                  <span class="badge" style="background:<?= $c ?>; color:#fff; font-size:.75rem;"><?= strtoupper($s) ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="text-center py-5"><p class="small" style="color:rgba(255,255,255,0.7)">Your showroom is empty.</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
