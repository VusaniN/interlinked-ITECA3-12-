<?php
// Page for the main home screen
$pageTitle = 'Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';

// Get featured products from the database
$featuredRes = mysqli_query($con,
    "SELECT p.*, u.username, c.name AS cat_name
     FROM products p
     JOIN users u ON p.seller_id = u.user_id
     JOIN categories c ON p.category_id = c.category_id
     WHERE p.status = 'approved' AND p.is_active = 1
     ORDER BY p.is_featured DESC, p.created_at DESC
     LIMIT 8");

// Get main categories
$catRes = mysqli_query($con,
    "SELECT * FROM categories WHERE is_active=1 AND parent_id IS NULL ORDER BY sort_order");

// Get some stats for the home page
$statsUsers_query = mysqli_query($con, "SELECT COUNT(*) FROM users WHERE is_active=1");
$statsUsers_row = mysqli_fetch_row($statsUsers_query);
$statsUsers = $statsUsers_row[0];

$statsProducts_query = mysqli_query($con, "SELECT COUNT(*) FROM products WHERE status='approved'");
$statsProducts_row = mysqli_fetch_row($statsProducts_query);
$statsProducts = $statsProducts_row[0];

$statsOrders_query = mysqli_query($con, "SELECT COUNT(*) FROM orders");
$statsOrders_row = mysqli_fetch_row($statsOrders_query);
$statsOrders = $statsOrders_row[0];

?>
<?php require_once 'includes/header.php'; ?>

<!-- Verification alert for users -->
<?php if (isLoggedIn()): 
    $uId = $_SESSION['user_id'];
    $vCheck_query = mysqli_query($con, "SELECT is_verified, verification_doc FROM users WHERE user_id = $uId");
    $vCheck = mysqli_fetch_assoc($vCheck_query);
    if (!($vCheck['is_verified'] ?? 0)):
?>
<div class="py-2" style="background:rgba(212, 175, 55, 0.1); border-bottom: 1px solid var(--border)">
  <div class="container d-flex justify-content-between align-items-center">
    <div class="small fw-600" style="color:var(--accent)">
      <i data-feather="shield" style="width:16px;margin-right:5px"></i>
      <?php
        if ($vCheck['verification_doc'] ?? '') {
            echo 'Verification pending review.';
        } else {
            echo 'Verify your account for the "Trusted Seller" badge.';
        }
      ?>
    </div>
    <?php if (!($vCheck['verification_doc'] ?? '')): ?>
    <a href="<?= url('verification.php') ?>" class="text-accent fw-800 small text-decoration-none">Verify Now →</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; endif; ?>

<!-- Main Hero Section -->
<section class="hero-section">
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1 class="hero-title">
          Buy, sell or trade<br>
          products safe<br>
          and securely
        </h1>
        <p class="hero-subtitle mt-4" style="">
          Join and start selling your products securely. Trade electronics, vehicles, and more in South Africa.
        </p>
        <div class="d-flex gap-3 mt-5 flex-wrap">
          <a href="<?= url('products.php') ?>" class="btn btn-primary btn-lg px-5">
            Browse Products
          </a>
          <a href="<?= url('create_product.php') ?>" class="btn btn-outline-primary btn-lg px-5">
            Sell Item
          </a>
        </div>
        <div class="hero-stats mt-5 pt-3">
          <div class="hero-stat"><strong><?= number_format($statsUsers) ?>+</strong><span>Users</span></div>
          <div class="hero-stat mx-lg-5"><strong><?= number_format($statsProducts) ?>+</strong><span>Listings</span></div>
          <div class="hero-stat"><strong><?= number_format($statsOrders) ?>+</strong><span>Sales</span></div>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <div id="model-viewer-container" class="animate-float" style="height:380px; border-radius:32px; background:radial-gradient(circle, rgba(212,175,55,0.05) 0%, transparent 70%)"></div>
      </div>
    </div>
  </div>
</section>

<!-- Show categories -->
<section class="py-5 mt-4">
  <div class="container">
    <div class="d-flex align-items-center gap-3 mb-5">
      <div style="width:50px; height:2px; background:var(--accent)"></div>
      <h2 class="section-title mb-0" style="font-size:1.2rem; text-transform:uppercase; letter-spacing:2px; color:var(--accent) !important">Curated Categories</h2>
    </div>
    <div class="row g-2">
      <?php
      $catIcons = [
        'electronics'   => ['📱', '#60a5fa'],
        'clothing'      => ['👕', '#f472b6'],
        'home-garden'   => ['🏡', '#34d399'],
        'sports'        => ['⚽', '#fb923c'],
        'books'         => ['📚', '#a78bfa'],
        'vehicles'      => ['🚗', '#f87171'],
        'collectibles'  => ['🎁', '#fbbf24'],
        'other'         => ['📦', '#94a3b8']
      ];
      while ($cat = mysqli_fetch_assoc($catRes)):
        $slug = $cat['slug'];
        $icon = $catIcons[$slug][0] ?? '📦';
        $color = $catIcons[$slug][1] ?? '#94a3b8';
      ?>
      <div class="col-6 col-md-3">
        <a href="<?= url('products.php?category=' . $cat['category_id']) ?>" class="text-decoration-none d-flex align-items-center justify-content-center gap-2 px-2 py-2 rounded-3" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); min-height:56px; transition:all 0.2s" onmouseover="this.style.borderColor='<?= $color ?>';this.style.background='<?= $color ?>15'" onmouseout="this.style.borderColor='rgba(255,255,255,0.08)';this.style.background='rgba(255,255,255,0.03)'">
          <span style="font-size:1.1rem; line-height:1"><?= $icon ?></span>
          <span style="color:#fff; font-weight:600; font-size:.75rem; white-space:nowrap"><?= htmlspecialchars($cat['name']) ?></span>
        </a>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- Latest products -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-5">
      <div>
        <div style="width:50px; height:2px; background:var(--accent); mb-3"></div>
        <h2 class="section-title mt-3">Latest Acquisitions</h2>
      </div>
      <a href="<?= url('products.php') ?>" class="text-accent fw-700 text-decoration-none small">View Full Catalog <i data-feather="arrow-right" style="width:16px"></i></a>
    </div>

    <div class="row g-4">
      <?php
      $hasProducts = false;
      while ($p = mysqli_fetch_assoc($featuredRes)):
        $hasProducts = true;
      ?>
      <div class="col-sm-6 col-md-4 col-xl-3">
        <div class="product-card h-100">
          <div class="position-relative overflow-hidden">
            <?php if ($p['is_featured']): ?>
            <span class="badge" style="position:absolute;top:15px;left:15px;z-index:1;background:var(--accent);color:#000;font-weight:800;border-radius:4px;font-size:.65rem">FEATURED</span>
            <?php endif; ?>
            <button class="wishlist-btn" data-product-id="<?= $p['product_id'] ?>" style="background:rgba(0,0,0,0.3); color:#fff; border:1px solid rgba(255,255,255,0.1)">
              <i data-feather="heart" style="width:14px"></i>
            </button>
            <a href="<?= url('product.php?id=' . $p['product_id']) ?>">
              <img src="<?= url('uploads/products/' . htmlspecialchars($p['product_image'])) ?>"
                   class="card-img-top" style="height:240px; object-fit:cover"
                   onerror="this.src='<?= url('assets/images/placeholder.jpg') ?>'"
                   alt="<?= htmlspecialchars($p['product_name']) ?>">
            </a>
          </div>
          <div class="card-body p-4 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-accent small fw-700" style="letter-spacing:1px;font-size:.65rem;text-transform:uppercase"><?= htmlspecialchars($p['cat_name']) ?></span>
                <span class="" style="font-size:.7rem"><i data-feather="map-pin" style="width:10px"></i> 
                <?php 
                    if ($p['location']) {
                        echo htmlspecialchars($p['location']);
                    } else {
                        echo 'SA';
                    }
                ?>
                </span>
            </div>
            <a href="<?= url('product.php?id=' . $p['product_id']) ?>" class="product-name mb-3 h5 text-decoration-none">
              <?= htmlspecialchars($p['product_name']) ?>
            </a>
            <div class="mt-auto pt-3 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                <div class="product-price h5 mb-0" style="color:var(--accent); font-weight:800">R <?= number_format($p['product_price'], 2) ?></div>
                <div class="small"><i data-feather="user" style="width:12px"></i> <?= htmlspecialchars($p['username']) ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>

      <?php if (!$hasProducts): ?>
      <div class="col-12 text-center py-5">
        <div class="">
          <i data-feather="package" style="width:48px;height:48px;opacity:.1" class="mb-3"></i>
          <p>Our catalog is currently being updated. Check back soon!</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof init3DProductViewer === 'function') {
    init3DProductViewer('model-viewer-container', 0xd4af37);
  }
  if (typeof feather !== 'undefined') feather.replace();
});
</script>