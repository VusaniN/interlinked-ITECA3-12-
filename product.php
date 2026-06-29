<?php
// ============================================================
// PRODUCT DETAIL PAGE
// Shows full product info, seller details, and buy button
// Uses prepared statements for database queries
// ============================================================
$pageTitle = 'Product Details — Interlinked Marketplace';
$rootPath  = '';
require_once 'includes/session.php';
require_once 'config/database.php';

// get product id from URL parameter
$productId = 0;
if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
}

if ($productId == 0) {
    header('Location: products.php');
    exit;
}

// Update the view count for this product
$viewStmt = $con->prepare("UPDATE products SET views = views + 1 WHERE product_id = ?");
$viewStmt->bind_param("i", $productId);
$viewStmt->execute();

// Get the product data
$stmt = $con->prepare("SELECT p.*, u.username, u.full_name AS seller_name, u.avatar AS seller_avatar, c.name AS cat_name
        FROM products p
        JOIN users u ON p.seller_id = u.user_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.product_id = ? AND p.is_active = 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$res = $stmt->get_result();
$p = $res->fetch_assoc();

// Redirect if product not found
if (!$p) {
    header('Location: products.php');
    exit;
}

$pageTitle = $p['product_name'] . ' — Interlinked Marketplace';

// Check if this product is already in the user's wishlist
$inWishlist = false;
if (isLoggedIn()) {
    $wCheck = $con->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $wCheck->bind_param("ii", $_SESSION['user_id'], $productId);
    $wCheck->execute();
    $inWishlist = $wCheck->get_result()->num_rows > 0;
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?= intval($p['category_id']) ?>"><?= htmlspecialchars($p['cat_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($p['product_name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Image of the product -->
        <div class="col-lg-7">
            <div class="card p-2 mb-4">
                <img src="uploads/products/<?= htmlspecialchars($p['product_image']) ?>" 
                     class="img-fluid rounded shadow-sm w-100" 
                     style="max-height: 500px; object-fit: contain; background: #f8f9fa;"
                     onerror="this.src='<?= url('assets/images/placeholder.jpg') ?>'"
                     alt="<?= htmlspecialchars($p['product_name']) ?>">
            </div>
            
            <div class="card p-4">
                <h5 class="fw-700 mb-3" style="color:#fff">Product Description</h5>
                <div class="product-description" style="color:#fff">
                    <?= nl2br(htmlspecialchars($p['product_description'])) ?>
                </div>
            </div>
        </div>

        <!-- Buying part -->
        <div class="col-lg-5">
            <div class="sticky-top" style="top: 100px;">
                <div class="card p-4 mb-4">
                    <span class="badge bg-secondary mb-2" style="width:fit-content" ><?= htmlspecialchars($p['cat_name']) ?></span>
                    <h1 class="fw-800 h2 mb-2" style="color:#fff"><?= htmlspecialchars($p['product_name']) ?></h1>
                    
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="h3 fw-800 mb-0" style="color:var(--accent)">R <?= number_format($p['product_price'], 2) ?></div>
                        <?php if ($p['quantity'] > 0): ?>
                            <span class="badge bg-success-soft text-success">In Stock (<?= intval($p['quantity']) ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-danger-soft text-danger">Sold Out</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 mb-4">
                        <?php if ($p['quantity'] > 0 && $p['status'] === 'approved'): ?>
                            <a href="checkout.php?product_id=<?= intval($p['product_id']) ?>" class="btn btn-primary btn-lg fw-700">
                                <i data-feather="shopping-cart" style="width:18px"></i> Buy Now
                            </a>
                        <?php elseif ($p['quantity'] <= 0): ?>
                            <button class="btn btn-secondary btn-lg" disabled>Out of Stock</button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg" disabled>Unavailable</button>
                        <?php endif; ?>
                        <button class="btn wishlist-btn <?= $inWishlist ? 'wishlisted' : 'btn-outline-light' ?>" data-product-id="<?= intval($p['product_id']) ?>" <?= $inWishlist ? 'style="color:#f43f5e;border-color:#f43f5e"' : '' ?>>
                            <i data-feather="heart" style="width:18px"></i> <?= $inWishlist ? 'In Wishlist' : 'Add to Wishlist' ?>
                        </button>
                    </div>

                    <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.04); border:1px solid var(--border)">
                        <div class="row g-3">
                            <div class="col-6">
                                <small class="d-block" style="color:#fff">Condition</small>
                                <strong class="text-capitalize" style="color:#fff"><?= htmlspecialchars(str_replace('_', ' ', $p['condition_type'])) ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="d-block" style="color:#fff">Location</small>
                                <strong style="color:#fff"><?php 
                                    if ($p['location']) {
                                        echo htmlspecialchars($p['location']);
                                    } else {
                                        echo 'Not specified';
                                    }
                                ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info about seller -->
                <div class="card p-4 mb-4">
                    <h6 class="fw-700 mb-3">Seller Information</h6>
                    <div class="d-flex align-items-center gap-3"style="color:#fff">
                        <?php
                            $avatar = 'default.png';
                            if ($p['seller_avatar']) {
                                $avatar = $p['seller_avatar'];
                            }
                        ?>
                        <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" 
                             style="width:48px; height:48px; border-radius:50%; object-fit:cover"
                             onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'">
                        <div>
                            <div class="fw-700"><?= htmlspecialchars($p['username']) ?></div>
                            <small style="color:#fff">Member since <?= date('M Y', strtotime($p['created_at']))  ?></small>
                        </div>
                    </div>
                    <hr>
                    <a href="messages.php?to=<?= intval($p['seller_id']) ?>" class="btn btn-outline-secondary w-100 btn-sm">
                        <i data-feather="message-circle" style="width:14px"></i> Contact Seller
                    </a>
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script>

document.addEventListener('DOMContentLoaded', () => {
  if (typeof init3DProductViewer === 'function') {
    init3DProductViewer('model-viewer-container', 0xe94560);
  }
});
</script>
