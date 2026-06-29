<?php
// ============================================================
// CHECKOUT PAGE
// Handles the purchase form and saves order to database
// Student Project — ITECA-12 Web Development
// ============================================================
$pageTitle = 'Checkout — Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

$productId = 0;
if (isset($_GET['product_id'])) {
    $productId = intval($_GET['product_id']);
}

$error = '';

// get product info from db
$stmt = $con->prepare("SELECT p.*, u.username, u.user_id as seller_id FROM products p JOIN users u ON p.seller_id = u.user_id WHERE p.product_id = ? AND p.status = 'approved' AND p.is_active = 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();

// redirect if product not found
if (!$product) {
    header('Location: products.php');
    exit;
}

// dont let user buy their own stuff
if ($product['seller_id'] == $_SESSION['user_id']) {
    $error = 'You cannot purchase your own listing.';
}

// when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error == '') {
    // check csrf token
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired.';
    } else {
        $qty = 1;
        if (isset($_POST['quantity'])) {
            $qty = intval($_POST['quantity']);
        }
        if ($qty < 1) $qty = 1;

        $total = $product['product_price'] * $qty;
        $buyerId = $_SESSION['user_id'];

        // get shipping details from form
        $name = $_SESSION['full_name'];
        if (isset($_POST['name']) && $_POST['name'] != '') {
            $name = trim($_POST['name']);
        }

        $email = $_SESSION['email'];
        if (isset($_POST['email']) && $_POST['email'] != '') {
            $email = trim($_POST['email']);
        }

        $phone = '';
        if (isset($_POST['phone'])) {
            $phone = trim($_POST['phone']);
        }

        $address = '';
        if (isset($_POST['address'])) {
            $address = trim($_POST['address']);
        }

        // save order to database
        $stmt = $con->prepare("INSERT INTO orders (buyer_id, seller_id, product_id, quantity, unit_price, total_amount, status, payment_method, shipping_name, shipping_email, shipping_phone, shipping_address) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'qr_payment', ?, ?, ?, ?)");
        $stmt->bind_param("iiiiidssss", $buyerId, $product['seller_id'], $productId, $qty, $product['product_price'], $total, $name, $email, $phone, $address);

        if ($stmt->execute()) {
            $newOrderId = $stmt->insert_id;
            // Mark product as sold
            $upd = $con->prepare("UPDATE products SET status = 'sold', quantity = 0 WHERE product_id = ?");
            $upd->bind_param("i", $productId);
            $upd->execute();
            // redirect to payment page
            header('Location: payment.php?order_id=' . $newOrderId);
            exit;
        } else {
            $error = 'Order processing failed. Please try again.';
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5" style="max-width:700px">
  <h2 class="fw-800 mb-4" style="font-family:'Sora',sans-serif">Secure Checkout</h2>

  <?php if ($error != ''): ?>
    <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php if ($error == ''): ?>
  <!-- product being purchased -->
  <div class="card p-4 mb-4">
    <div class="d-flex align-items-center gap-3">
      <img src="<?= url('uploads/products/' . htmlspecialchars($product['product_image'])) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:12px" onerror="this.src='<?= url('assets/images/placeholder.jpg') ?>'">
      <div>
        <div class="fw-700"><?= htmlspecialchars($product['product_name']) ?></div>
        <div class="small">Seller: <?= htmlspecialchars($product['username']) ?></div>
        <div class="fw-800" style="color:var(--accent)">R <?= number_format($product['product_price'], 2) ?></div>
      </div>
    </div>
  </div>

  <!-- shipping details form -->
  <div class="card p-4 p-lg-5">
    <form method="POST">
      <?= csrfField() ?>

      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label small fw-700">FULL NAME</label>
          <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-700">EMAIL</label>
          <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-700">PHONE</label>
          <input type="tel" class="form-control" name="phone" placeholder="+27 xx xxx xxxx">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-700">QUANTITY</label>
          <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?= intval($product['quantity']) ?>">
        </div>
        <div class="col-12">
          <label class="form-label small fw-700">SHIPPING ADDRESS</label>
          <textarea class="form-control" name="address" rows="3" placeholder="Street address, city, postal code"></textarea>
        </div>
      </div>

      <div class="alert alert-info mt-4 py-3" style="background:rgba(212,175,55,0.05);border:1px solid var(--border)">
        <div class="d-flex justify-content-between">
          <span class="fw-700">Total Payable:</span>
          <span class="fw-800" style="color:var(--accent);font-size:1.3rem">R <?= number_format($product['product_price'], 2) ?></span>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-3 mt-3">Place Order</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
