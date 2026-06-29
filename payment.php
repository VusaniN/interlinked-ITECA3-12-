<?php
// ============================================================
// PAYMENT PAGE text
// Shows payment options after placing an order
// Student Project — ITECA-12
// ============================================================
$pageTitle = 'Payment — Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

$orderId = 0;
if (isset($_GET['order_id'])) {
    $orderId = intval($_GET['order_id']);
}

$uid = $_SESSION['user_id'];

// get the order details from database
$stmt = $con->prepare("
    SELECT o.*, p.product_name, p.product_image, u.username AS seller_name
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON o.seller_id = u.user_id
    WHERE o.order_id = ? AND o.buyer_id = ?
");
$stmt->bind_param("ii", $orderId, $uid);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// if already paid, go to orders
if ($order['payment_status'] === 'paid') {
    header('Location: orders.php');
    exit;
}

// handle payment confirmation
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired.';
    } else {
        $method = $_POST['payment_method'] ?? 'qr';
        $stmt = $con->prepare("UPDATE orders SET payment_status = 'paid', status = 'paid', payment_method = ? WHERE order_id = ? AND buyer_id = ?");
        $stmt->bind_param("sii", $method, $orderId, $uid);
        if ($stmt->execute()) {
            $success = 'Payment confirmed! Your order is now being processed.';
            header('Refresh: 3; URL=orders.php');
        } else {
            $error = 'Payment update failed. Please try again.';
        }
    }
}

// generate a payment reference number
$payRef = 'INT-' . str_pad($order['order_id'], 5, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($order['created_at']), 0, 4));
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5" style="max-width:600px">
  <h2 class="fw-800 mb-2" style="font-family:'Sora',sans-serif">Complete Payment</h2>
  <p class="text mb-4">Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($order['product_name']) ?></p>

  <!-- WIP: Payment gateway integration in progress. Currently using simulated payment flow. -->

  <?php if ($error != ''): ?>
    <div class="alert mb-4" style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.2); color:#f43f5e; border-radius:12px; padding:1rem 1.5rem"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success != ''): ?>
    <div class="alert mb-4" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.2); color:#10b981; border-radius:12px; padding:1rem 1.5rem"><?= htmlspecialchars($success) ?><br><small style="color:rgba(16,185,129,0.7)">Redirecting to orders...</small></div>
  <?php endif; ?>

  <!-- order summary -->
  <div class="card p-4 mb-4">
    <div class="d-flex align-items-center gap-3">
      <img src="<?= url('uploads/products/' . htmlspecialchars($order['product_image'])) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:10px" onerror="this.src='<?= url('assets/images/placeholder.jpg') ?>'">
      <div class="flex-grow-1">
        <div class="fw-700" style ="color:#fff"><?= htmlspecialchars($order['product_name']) ?></div>
        <div class="text small">Seller: <?= htmlspecialchars($order['seller_name']) ?></div>
      </div>
      <div class="fw-800" style="color:var(--accent);font-size:1.2rem">R <?= number_format($order['total_amount'], 2) ?></div>
    </div>
  </div>

  <!-- payment method selection -->
  <div class="card p-4 mb-4">
    <h5 class="fw-700 mb-3">Select Payment Method</h5>

    <div class="d-grid gap-2 mb-4">
      <button class="btn btn-outline-primary py-3 payment-option active" data-method="qr" onclick="showPayment('qr')">
        <span class="fw-700">Scan QR Code</span>
        <small class="d-block text">SnapScan / Zapper / Banking App</small>
      </button>
      <button class="btn btn-outline-primary py-3 payment-option" data-method="eft" onclick="showPayment('eft')">
        <span class="fw-700">Instant EFT</span>
        <small class="d-block text">Pay directly from your bank</small>
      </button>
      <button class="btn btn-outline-primary py-3 payment-option" data-method="card" onclick="showPayment('card')">
        <span class="fw-700">Credit / Debit Card</span>
        <small class="d-block text">Visa, Mastercard</small>
      </button>
      <button class="btn btn-outline-primary py-3 payment-option" data-method="cod" onclick="showPayment('cod')">
        <span class="fw-700">Cash on Collection</span>
        <small class="d-block text">Pay when you collect the item</small>
      </button>
    </div>

    <!-- QR Code payment section -->
    <div id="payment-qr" class="payment-section">
      <div class="text-center">
        <p class="text small mb-3">Scan the QR code below with your banking app to pay:</p>
        <!-- static QR code image -->
        <div class="d-inline-block p-3" style="background:#fff;border-radius:12px">
          <img src="<?= url('assets/images/qr_payment.png') ?>" alt="Scan to pay" style="width:180px;height:180px;object-fit:contain" onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<div style=\'width:180px;height:180px;display:flex;align-items:center;justify-content:center;color:#666;font-size:0.8rem\'>QR Code<br>Ref: <?= $payRef ?></div>')">
        </div>
        <div class="mt-3 p-2 rounded" style="color:#fff">
          Payment Reference: <strong style="color:var(--accent)"><?= $payRef ?></strong>
        </div>
        <p class="text small mt-2">Amount: <strong>R <?= number_format($order['total_amount'], 2) ?></strong></p>
        <p class="text small">* QR code is for demonstration.</p>
      </div>
    </div>

    <!-- EFT section -->
    <div id="payment-eft" class="payment-section" style="display:none">
      <div class="p-3 rounded" style="background:rgba(255,255,255,0.05)">
        <h6 class="fw-700 mb-2">Bank Details for EFT Payment</h6>
        <table class="table table-sm mb-0" style="color:rgba(255,255,255,0.8)">
          <tr><td style="width:120px" class="text">Bank:</td><td><strong>First National Bank</strong></td></tr>
          <tr class="text"><td>Account Type:</td><td><strong>Cheque Account</strong></td></tr>
          <tr><td class="text">Account No:</td><td><strong>6289 **** **** 1234</strong></td></tr>
          <tr class="text"><td>Branch Code:</td><td><strong>250655</strong></td></tr>
          <tr><td class="text">Reference:</td><td><strong style="color:var(--accent)"><?= $payRef ?></strong></td></tr>
        </table>
        <div class="alert alert-warning mt-3 mb-0 small">
          Important: Use the reference number above when making payment so we can match it to your order.
        </div>
      </div>
    </div>

    <!-- Card section -->
    <div id="payment-card" class="payment-section" style="display:none">
      <form onsubmit="return false">
        <div class="mb-3">
          <label class="form-label small fw-700">CARD NUMBER</label>
          <input type="text" class="form-control" placeholder="4242 4242 4242 4242" maxlength="19">
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label small fw-700">EXPIRY DATE</label>
            <input type="text" class="form-control" placeholder="MM/YY" maxlength="5">
          </div>
          <div class="col-6">
            <label class="form-label small fw-700">CVV</label>
            <input type="text" class="form-control" placeholder="123" maxlength="4">
          </div>
        </div>
        <div class="alert alert-info mt-3 mb-0 small" style="background:rgba(212,175,55,0.05);border:1px solid var(--border)">
          Card payments are processed securely. Your details are encrypted.
        </div>
      </form>
    </div>

    <!-- Cash on collection -->
    <div id="payment-cod" class="payment-section" style="display:none">
      <div class="p-3 rounded" style="background:rgba(255,255,255,0.05)">
        <h6 class="fw-700 mb-2">Cash on Collection</h6>
        <p class="text small mb-2">Pay the seller directly when you collect the item. Make sure to meet in a safe, public place.</p>
        <div class="alert alert-warning mb-0 small">
          Only pay after inspecting the item. The seller will contact you to arrange collection.
        </div>
      </div>
    </div>

    <!-- confirm payment button -->
    <form method="POST" class="mt-4">
      <?= csrfField() ?>
      <input type="hidden" name="payment_method" id="selected-method" value="qr">
      <button type="submit" class="btn btn-primary w-100 py-3 fw-700">Confirm Payment</button>
    </form>

    <a href="orders.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
  </div>
</div>

<script>
// switch between payment method sections
function showPayment(method) {
  document.querySelectorAll('.payment-section').forEach(function(s) { s.style.display = 'none'; });
  document.querySelectorAll('.payment-option').forEach(function(b) { b.classList.remove('active'); });
  document.getElementById('payment-' + method).style.display = 'block';
  document.querySelector('[data-method="' + method + '"]').classList.add('active');
  document.getElementById('selected-method').value = method;
}
</script>

<?php require_once 'includes/footer.php'; ?>
