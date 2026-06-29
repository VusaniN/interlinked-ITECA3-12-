<?php
$pageTitle = 'Overview — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin','moderator'])) {
    redirect('auth/login.php');
}

$totalUsers    = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM users"))[0]    ?? 0;
$totalProducts = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM products"))[0] ?? 0;
$totalOrders   = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM orders"))[0]   ?? 0;
$totalRevenue  = mysqli_fetch_row(mysqli_query($con,"SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE payment_status='paid'"))[0] ?? 0;
$pendingProds  = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM products WHERE status='pending'"))[0] ?? 0;
$activeUsers   = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM users WHERE is_active=1"))[0] ?? 0;

// Monthly revenue last 6 months
$revenueData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $r = mysqli_fetch_row(mysqli_query($con,
        "SELECT IFNULL(SUM(total_amount),0) FROM orders
         WHERE payment_status='paid' AND DATE_FORMAT(created_at,'%Y-%m')='$m'"))[0] ?? 0;
    $revenueData[] = ['month' => date('M', strtotime($m.'-01')), 'revenue' => (float)$r];
}

$recentOrders = mysqli_query($con,
    "SELECT o.*, p.product_name, u.username AS buyer_name
     FROM orders o
     JOIN products p ON o.product_id = p.product_id
     JOIN users u    ON o.buyer_id   = u.user_id
     ORDER BY o.created_at DESC LIMIT 8");

$pendingList = mysqli_query($con,
    "SELECT p.*, u.username FROM products p
     JOIN users u ON p.seller_id = u.user_id
     WHERE p.status='pending' ORDER BY p.created_at DESC LIMIT 5");
?>
<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<!-- Stats -->
<div class="row g-4 mb-5">
  <?php
  $cards = [
    ['Total Users',    number_format($totalUsers),    'linear-gradient(135deg, #d4af37, #b8962e)', 'users'],
    ['Total Listings', number_format($totalProducts), 'linear-gradient(135deg, #10b981, #059669)', 'package'],
    ['Total Orders',   number_format($totalOrders),   'linear-gradient(135deg, #3b82f6, #2563eb)', 'shopping-bag'],
    ['Revenue (R)',    number_format($totalRevenue,0),'linear-gradient(135deg, #f59e0b, #d97706)', 'dollar-sign'],
  ];
  foreach ($cards as [$label, $val, $bg, $icon]):
  ?>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card" style="background:<?= $bg ?>">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label mb-1"><?= $label ?></div>
          <div class="stat-num"><?= $val ?></div>
        </div>
        <i data-feather="<?= $icon ?>" style="opacity:0.3;width:24px;height:24px"></i>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-4 mb-5">
  <div class="col-lg-8">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h6 class="fw-700 mb-0">Revenue Analytics</h6>
        <select class="form-select form-select-sm w-auto" style="font-size:.7rem">
          <option>Last 6 Months</option>
        </select>
      </div>
      <canvas id="revenueChart" height="100"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-4">
      <h6 class="fw-700 mb-4">Listing Distribution</h6>
      <canvas id="statusChart" height="220"></canvas>
    </div>
  </div>
</div>

<!-- Tables -->
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card overflow-hidden">
      <div class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
        <h6 class="fw-700 mb-0">Recent Activity</h6>
        <a href="orders.php" class="btn btn-sm btn-outline-primary" style="font-size:.7rem">View All Orders</a>
      </div>
      <div class="table-responsive">
        <table class="table admin-table mb-0">
          <thead><tr><th>ID</th><th>Product</th><th>Buyer</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
            <?php while ($o = mysqli_fetch_assoc($recentOrders)): ?>
            <tr>
              <td>#<?= $o['order_id'] ?></td>
              <td><?= htmlspecialchars(substr($o['product_name'],0,25)) ?>...</td>
              <td><?= htmlspecialchars($o['buyer_name']) ?></td>
              <td class="fw-800" style="color:var(--accent)">R<?= number_format($o['total_amount'],2) ?></td>
              <td>
                <span class="status-badge" style="background:rgba(212,175,55,0.1);color:var(--accent)">
                  <?= $o['status'] ?>
                </span>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h6 class="fw-700 mb-0">Pending Approvals</h6>
        <span class="badge rounded-pill bg-danger" style="font-size:.65rem"><?= $pendingProds ?></span>
      </div>
      <div class="pending-list">
        <?php
        $shown = 0;
        while ($pl = mysqli_fetch_assoc($pendingList)):
          $shown++;
        ?>
        <div class="d-flex align-items-center gap-3 mb-4 last-mb-0">
          <img src="<?= url('uploads/products/'.$pl['product_image']) ?>" 
               class="rounded" style="width:40px;height:40px;object-fit:cover"
               onerror="this.src='<?= url('assets/images/placeholder.jpg') ?>'">
          <div class="flex-grow-1" style="min-width:0">
            <div class="fw-700 text-truncate" style="font-size:.85rem"><?= htmlspecialchars($pl['product_name']) ?></div>
            <div class="small text">by <?= htmlspecialchars($pl['username']) ?></div>
          </div>
          <a href="products.php?action=approve&id=<?= $pl['product_id'] ?>" 
             class="btn btn-sm btn-primary py-1 px-3" style="font-size:.7rem">Review</a>
        </div>
        <?php endwhile; ?>
        <?php if (!$shown): ?>
          <div class="text-center py-4">
            <div style="font-size:2rem">✨</div>
            <p class="text small mt-2">All products approved!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$months      = json_encode(array_column($revenueData,'month'));
$revenues    = json_encode(array_column($revenueData,'revenue'));
$approvedCnt = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM products WHERE status='approved'"))[0] ?? 0;
$rejectedCnt = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM products WHERE status='rejected'"))[0] ?? 0;
$soldCnt     = mysqli_fetch_row(mysqli_query($con,"SELECT COUNT(*) FROM products WHERE status='sold'"))[0]    ?? 0;
?>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>

<script>
Chart.defaults.color = 'rgba(255,255,255,0.5)';
Chart.defaults.font.family = "'DM Sans', sans-serif";

new Chart(document.getElementById('revenueChart'), {
  type:'line',
  data:{
    labels:<?= $months ?>,
    datasets:[{
      label:'Revenue (R)',
      data:<?= $revenues ?>,
      borderColor:'#d4af37',
      backgroundColor:'rgba(212, 175, 55, 0.1)',
      borderWidth:3,
      tension:0.4,
      fill:true,
      pointBackgroundColor:'#d4af37',
      pointBorderColor:'#fff',
      pointBorderWidth:2,
      pointRadius:4
    }]
  },
  options:{
    responsive:true,
    plugins:{legend:{display:false}},
    scales:{
      y:{beginAtZero:true, grid:{color:'rgba(255,255,255,0.05)'}},
      x:{grid:{display:false}}
    }
  }
});

new Chart(document.getElementById('statusChart'), {
  type:'doughnut',
  data:{
    labels:['Approved','Pending','Rejected','Sold'],
    datasets:[{
      data:[<?= $approvedCnt ?>,<?= $pendingProds ?>,<?= $rejectedCnt ?>,<?= $soldCnt ?>],
      backgroundColor:['#10b981','#f59e0b','#f43f5e','#3b82f6'],
      borderWidth:0,
      hoverOffset:10
    }]
  },
  options:{
    responsive:true,
    cutout:'75%',
    plugins:{
      legend:{
        position:'bottom',
        labels:{padding:20, usePointStyle:true, font:{size:11}}
      }
    }
  }
});

if (typeof feather !== 'undefined') feather.replace();
</script>

