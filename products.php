<?php
// Page to show all products with filtering
$pageTitle = 'Browse Listings — Interlinked Marketplace';
$rootPath  = '';
require_once 'includes/session.php';
require_once 'config/database.php';

// Get filter values from the URL
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$category = 0;
if (isset($_GET['category'])) {
    $category = intval($_GET['category']);
}

$sortKey = 'newest';
if (isset($_GET['sort'])) {
    $sortKey = trim($_GET['sort']);
}

$minPrice = 0;
if (isset($_GET['min_price'])) {
    $minPrice = floatval($_GET['min_price']);
}

$maxPrice = 999999;
if (isset($_GET['max_price'])) {
    $maxPrice = floatval($_GET['max_price']);
}

$condition = '';
if (isset($_GET['condition'])) {
    $condition = trim($_GET['condition']);
}

$page = 1;
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
    if ($page < 1) {
        $page = 1;
    }
}

$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build the SQL query part for filtering
$whereParts = ["p.status = 'approved'", "p.is_active = 1"];
$types = '';
$params = [];

if ($minPrice > 0) {
    $whereParts[] = "p.product_price >= ?";
    $types .= 'd';
    $params[] = $minPrice;
}
if ($maxPrice < 999999) {
    $whereParts[] = "p.product_price <= ?";
    $types .= 'd';
    $params[] = $maxPrice;
}
if ($search !== '') {
    $whereParts[] = "(p.product_name LIKE ? OR p.product_description LIKE ? OR c.name LIKE ?)";
    $types .= 'sss';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}
if ($category > 0) {
    $whereParts[] = "p.category_id = ?";
    $types .= 'i';
    $params[] = $category;
}
if ($condition !== '') {
    $allowedConditions = ['new', 'like_new', 'good', 'fair', 'poor'];
    if (in_array($condition, $allowedConditions)) {
        $whereParts[] = "p.condition_type = ?";
        $types .= 's';
        $params[] = $condition;
    }
}

$whereStr = implode(' AND ', $whereParts);

// Choose the order for the products
$allowedSorts = [
    'price_asc'  => 'p.product_price ASC',
    'price_desc' => 'p.product_price DESC',
    'popular'    => 'p.views DESC',
    'newest'     => 'p.created_at DESC',
];

if (isset($allowedSorts[$sortKey])) {
    $orderBy = $allowedSorts[$sortKey];
} else {
    $orderBy = 'p.created_at DESC';
}

// First, count how many products we have
$countSql = "SELECT COUNT(*) FROM products p WHERE $whereStr";
$countStmt = $con->prepare($countSql);
if ($types) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows_res = $countStmt->get_result();
$totalRows_row = $totalRows_res->fetch_row();
$totalRows = $totalRows_row[0];
$totalPages = (int)ceil($totalRows / $perPage);

// Now get the actual products
$sql = "SELECT p.*, u.username, c.name AS cat_name
        FROM products p
        JOIN users u  ON p.seller_id   = u.user_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE $whereStr ORDER BY $orderBy
        LIMIT ? OFFSET ?";

$mainTypes = $types . 'ii';
$mainParams = array_merge($params, [$perPage, $offset]);

$stmt = $con->prepare($sql);
$stmt->bind_param($mainTypes, ...$mainParams);
$stmt->execute();
$products = $stmt->get_result();

// Get categories for the filter sidebar
$cats = mysqli_query($con, "SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-4">
  <div class="row g-4">

    <!-- Sidebar with filters -->
    <div class="col-lg-3">
      <div class="card p-3 sticky-top" style="top:80px">
        <h6 class="fw-700 mb-3">🔍 Filter Listings</h6>
        <form method="GET" action="products.php">
          <?php if ($search): ?>
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select form-select-sm" name="category" onchange="this.form.submit()">
              <option value="">All Categories</option>
              <?php while ($c = mysqli_fetch_assoc($cats)): ?>
              <option value="<?= intval($c['category_id']) ?>" <?php if ($category == $c['category_id']) echo 'selected'; ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Price Range (R)</label>
            <div class="d-flex gap-2">
              <input type="number" class="form-control form-control-sm" name="min_price"
                     placeholder="Min" value="<?php if ($minPrice > 0) echo $minPrice; ?>" min="0" step="0.01">
              <input type="number" class="form-control form-control-sm" name="max_price"
                     placeholder="Max" value="<?php if ($maxPrice < 999999) echo $maxPrice; ?>" min="0" step="0.01">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Condition</label>
            <select class="form-select form-select-sm" name="condition">
              <option value="">Any</option>
              <?php foreach(['new'=>'New','like_new'=>'Like New','good'=>'Good','fair'=>'Fair','poor'=>'Poor'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?php if ($condition === $v) echo 'selected'; ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn btn-primary btn-sm w-100">Apply Filters</button>
          <a href="products.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Clear All</a>
        </form>
      </div>
    </div>

    <!-- Product Grid -->
    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <?php if ($search): ?>
          <h5 class="fw-700 mb-0">Results for "<span style="color:var(--accent)"><?= htmlspecialchars($search) ?></span>"</h5>
          <?php else: ?>
          <h5 class="fw-700 mb-0">All Listings</h5>
          <?php endif; ?>
          <small class="text"><?= number_format($totalRows) ?> item<?php if ($totalRows != 1) echo 's'; ?> found</small>
        </div>
        <select class="form-select form-select-sm" style="width:auto"
                onchange="const p=new URLSearchParams(window.location.search);p.set('sort',this.value);window.location.search=p.toString()">
          <option value="newest"     <?php if ($sortKey === 'newest') echo 'selected'; ?>>Newest First</option>
          <option value="price_asc"  <?php if ($sortKey === 'price_asc') echo 'selected'; ?>>Price: Low → High</option>
          <option value="price_desc" <?php if ($sortKey === 'price_desc') echo 'selected'; ?>>Price: High → Low</option>
          <option value="popular"    <?php if ($sortKey === 'popular') echo 'selected'; ?>>Most Popular</option>
        </select>
      </div>

      <?php if (mysqli_num_rows($products) > 0): ?>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4">
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <div class="col">
          <div class="product-card position-relative h-100">
            <button class="wishlist-btn" data-product-id="<?= intval($p['product_id']) ?>">
              <i data-feather="heart" style="width:15px"></i>
            </button>
            <a href="product.php?id=<?= intval($p['product_id']) ?>">
              <div style="overflow:hidden;height:190px">
                <img src="uploads/products/<?= htmlspecialchars($p['product_image']) ?>"
                     class="card-img-top" style="height:190px;object-fit:cover"
                     onerror="this.src='assets/images/placeholder.jpg'"
                     alt="<?= htmlspecialchars($p['product_name']) ?>">
              </div>
            </a>
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span class="badge bg-secondary" style="font-size:.65rem"><?= htmlspecialchars($p['cat_name']) ?></span>
                <span class="badge bg-light text-dark" style="font-size:.65rem;text-transform:capitalize"><?= htmlspecialchars(str_replace('_',' ',$p['condition_type'])) ?></span>
              </div>
              <a href="product.php?id=<?= intval($p['product_id']) ?>" class="product-name text-dark">
                <?= htmlspecialchars($p['product_name']) ?>
              </a>
              <div class="product-price mt-auto">R <?= number_format($p['product_price'], 2) ?></div>
              <div class="product-meta d-flex justify-content-between mt-1 mb-2">
                <span><i data-feather="user" style="width:11px"></i> <?= htmlspecialchars($p['username']) ?></span>
                <span><i data-feather="eye" style="width:11px"></i> <?= intval($p['views']) ?></span>
              </div>
              <a href="product.php?id=<?= intval($p['product_id']) ?>" class="btn btn-primary btn-sm w-100">View</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>

      <?php if ($totalPages > 1): ?>
      <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++):
            $q = array_merge($_GET, ['page' => $i]);
          ?>
          <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
            <a class="page-link" href="?<?= http_build_query($q) ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>

      <?php else: ?>
      <div class="text-center py-5">
        <i data-feather="search" style="width:64px;height:64px;opacity:.2"></i>
        <h5 class="mt-3 text">No listings found</h5>
        <p class="text">Try adjusting your filters or <a href="products.php">browse everything</a></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
