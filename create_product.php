
<?php
$pageTitle = 'List Your Asset — Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

// Block buyers from listing items
if (hasRole('buyer')) {
    $_SESSION['error'] = "You're registered as a buyer you cant list items";
    header("Location: " . url('dashboard.php'));
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $name      = trim($_POST['product_name']        ?? '');
        $desc      = trim($_POST['product_description'] ?? '');
        $price     = floatval($_POST['product_price']  ?? 0);
        $qty       = intval($_POST['quantity']          ?? 1);
        $cat       = intval($_POST['category_id']       ?? 8);
        $condition = trim($_POST['condition_type'] ?? 'good');
        $location  = trim($_POST['location']        ?? '');
        $sellerId  = $_SESSION['user_id'];

        if (!$name || $price <= 0) {
            $error = 'Please provide a valid name and price.';
        } else {
            $imageName = 'placeholder.jpg';

            if (!empty($_FILES['product_image']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp','gif'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Unsupported image format.';
                } elseif ($_FILES['product_image']['size'] > 5 * 1024 * 1024) {
                    $error = 'Image must be under 5MB.';
                } else {
                    $imageName = uniqid('prod_') . '.' . $ext;
                    $dest      = __DIR__ . '/uploads/products/' . $imageName;
                    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $dest)) {
                        $error     = 'Failed to process image.';
                        $imageName = 'placeholder.jpg';
                    }
                }
            }

            if (!$error) {
                $stmt = $con->prepare("INSERT INTO products (seller_id, category_id, product_name, product_description, product_price, quantity, condition_type, product_image, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iissdisss", $sellerId, $cat, $name, $desc, $price, $qty, $condition, $imageName, $location);
                
                if ($stmt->execute()) {
                    $success = 'Listing established! It will be live after our audit.';
                } else {
                    $error = 'Failed to archive listing. Database error.';
                }
            }
        }
    }
}

$cats = mysqli_query($con, "SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5" style="max-width:800px">
  <div class="mb-5 text-center">
    <h2 class="fw-800" style="font-family:'Sora',sans-serif">Add New Product</h2>
    <p class="text">Fill in the details below to list your item.</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger mb-4" style="background:rgba(244, 63, 94, 0.1); border:1px solid rgba(244, 63, 94, 0.2); color:#f43f5e"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="alert alert-success mb-4" style="background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.2); color:#10b981"><?= $success ?></div>
  <?php endif; ?>

  <div class="card p-4 p-lg-5">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>

      <div class="mb-4">
        <label class="form-label text small fw-700">PRODUCT NAME</label>
        <input type="text" class="form-control" name="product_name" maxlength="200"
               value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>"
               placeholder="e.g. Rolex Submariner" required>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <label class="form-label text small fw-700">CATEGORY</label>
          <select class="form-select" name="category_id" required>
            <?php while ($c = mysqli_fetch_assoc($cats)): ?>
            <option value="<?= $c['category_id'] ?>"
              <?= ($_POST['category_id'] ?? '') == $c['category_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label text small fw-700">CONDITION</label>
          <select class="form-select" name="condition_type">
            <?php foreach(['new'=>'Brand New','like_new'=>'Like New','good'=>'Good','fair'=>'Fair','poor'=>'Poor'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($_POST['condition_type'] ?? 'good') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label text small fw-700">DESCRIPTION</label>
        <textarea class="form-control" name="product_description" rows="5"
                  placeholder="Tell us about the product..."><?= htmlspecialchars($_POST['product_description'] ?? '') ?></textarea>
      </div>

      <div class="row g-4 mb-5">
        <div class="col-md-4">
          <label class="form-label text small fw-700">PRICE (R)</label>
          <div class="input-group">
            <span class="input-group-text fw-700 border-0" style="background:rgba(255,255,255,0.05); color:var(--accent)">R</span>
            <input type="number" class="form-control" name="product_price"
                   step="0.01" min="1" value="<?= htmlspecialchars($_POST['product_price'] ?? '') ?>"
                   placeholder="0.00" required>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label text small fw-700">QUANTITY</label>
          <input type="number" class="form-control" name="quantity" min="1"
                 value="<?= intval($_POST['quantity'] ?? 1) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label text small fw-700">LOCATION</label>
          <input type="text" class="form-control" name="location"
                 value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
                 placeholder="e.g. Cape Town">
        </div>
      </div>

      <!-- Image Upload -->
      <div class="mb-5">
        <label class="form-label text small fw-700">PRODUCT IMAGE</label>
        <div class="p-5 text-center rounded-4" id="dropzone"
             style="cursor:pointer; border:2px dashed var(--border); background:rgba(255,255,255,0.02); transition:all 0.3s">
          <img id="image-preview" src="#" style="max-height:250px; display:none; border-radius:12px; margin: 0 auto 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.3)">
          <div id="drop-placeholder">
            <i data-feather="camera" style="width:40px; height:40px; color:var(--accent); opacity:0.6" class="mb-3"></i>
            <p class="mb-1 fw-600">Click to upload image</p>
            <small class="text">JPG, PNG, or WebP</small>
          </div>
          <input type="file" name="product_image" id="product_image" accept="image/*" class="d-none">
        </div>
      </div>

      <div class="d-flex gap-3 justify-content-end border-top border-secondary border-opacity-10 pt-5">
        <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-light px-4">Withdraw</a>
        <button type="submit" class="btn btn-primary px-5 py-2">
          Establish Listing
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script>
document.getElementById('dropzone').addEventListener('click', () => document.getElementById('product_image').click());

const dz = document.getElementById('dropzone');
dz.addEventListener('dragover',  e => { e.preventDefault(); dz.style.borderColor = 'var(--accent)'; dz.style.background = 'rgba(212, 175, 55, 0.05)'; });
dz.addEventListener('dragleave', () => { dz.style.borderColor = 'var(--border)'; dz.style.background = 'rgba(255,255,255,0.02)'; });
dz.addEventListener('drop', e => {
  e.preventDefault();
  dz.style.borderColor = 'var(--border)';
  dz.style.background = 'rgba(255,255,255,0.02)';
  if (e.dataTransfer.files[0]) {
    document.getElementById('product_image').files = e.dataTransfer.files;
    previewImage(e.dataTransfer.files[0]);
  }
});

function previewImage(file) {
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('image-preview').src = e.target.result;
    document.getElementById('image-preview').style.display = 'block';
    document.getElementById('drop-placeholder').style.display = 'none';
  };
  reader.readAsDataURL(file);
}
document.getElementById('product_image').addEventListener('change', function() {
  if (this.files[0]) previewImage(this.files[0]);
});
</script>

