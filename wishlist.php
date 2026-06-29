<?php
// Page to show user's wishlist
$pageTitle = 'My Wishlist — Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

$uid = $_SESSION['user_id'];

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $removeId = intval($_POST['remove_id']);
    $stmt = $con->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $uid, $removeId);
    $stmt->execute();
    header('Location: wishlist.php');
    exit;
}

// Get wishlist items
$wishlist = mysqli_query($con, "
    SELECT w.wishlist_id, p.product_id, p.product_name, p.product_price, p.product_image,
           p.condition_type, p.quantity, p.status, u.username AS seller_name
    FROM wishlist w
    JOIN products p ON w.product_id = p.product_id
    JOIN users u ON p.seller_id = u.user_id
    WHERE w.user_id = $uid AND p.is_active = 1
    ORDER BY w.created_at DESC
");
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
  <h2 class="fw-800 mb-4" style="font-family:'Sora',sans-serif">❤️ My Wishlist</h2>

  <?php if ($wishlist && mysqli_num_rows($wishlist) > 0): ?>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
    <?php while ($item = mysqli_fetch_assoc($wishlist)): ?>
    <div class="col">
      <div class="product-card position-relative h-100">
        <a href="product.php?id=<?= intval($item['product_id']) ?>">
          <div style="overflow:hidden;height:190px">
            <img src="uploads/products/<?= htmlspecialchars($item['product_image']) ?>"
                 class="card-img-top" style="height:190px;object-fit:cover"
                 onerror="this.src='assets/images/placeholder.jpg'"
                 alt="<?= htmlspecialchars($item['product_name']) ?>">
          </div>
        </a>
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <span class="badge" style="background:rgba(255,255,255,0.1); color:#fff; font-size:.65rem;text-transform:capitalize"><?= htmlspecialchars(str_replace('_',' ',$item['condition_type'])) ?></span>
            <?php if ($item['quantity'] > 0): ?>
              <span class="badge bg-success-soft text-success" style="font-size:.65rem">In Stock</span>
            <?php else: ?>
              <span class="badge bg-danger-soft text-danger" style="font-size:.65rem">Sold Out</span>
            <?php endif; ?>
          </div>
          <a href="product.php?id=<?= intval($item['product_id']) ?>" class="product-name" style="color:#fff; text-decoration:none">
            <?= htmlspecialchars($item['product_name']) ?>
          </a>
          <div class="product-price mt-auto">R <?= number_format($item['product_price'], 2) ?></div>
          <div class="product-meta d-flex justify-content-between mt-1 mb-2">
            <span><i data-feather="user" style="width:11px"></i> <?= htmlspecialchars($item['seller_name']) ?></span>
          </div>
          <div class="d-flex gap-2">
            <?php if ($item['quantity'] > 0 && $item['status'] === 'approved'): ?>
            <a href="checkout.php?product_id=<?= intval($item['product_id']) ?>" class="btn btn-primary btn-sm flex-grow-1">Buy Now</a>
            <?php else: ?>
            <button class="btn btn-secondary btn-sm flex-grow-1" disabled>Unavailable</button>
            <?php endif; ?>
            <form method="POST" class="d-inline">
              <input type="hidden" name="remove_id" value="<?= intval($item['product_id']) ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm" title="Remove from wishlist">
                <i data-feather="trash-2" style="width:14px"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="text-center py-5">
    <i data-feather="heart" style="width:64px;height:64px;opacity:.2"></i>
    <h5 class="mt-3" style="color:#fff; opacity:0.7">Your wishlist is empty</h5>
    <p style="color:#fff; opacity:0.5">Browse products and click the heart icon to save items here.</p>
    <a href="products.php" class="btn btn-primary mt-2">Browse Listings</a>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
<script>const rootPath='';const csrfToken='<?= csrfToken() ?>';</script>
