<?php
$pageTitle = 'Reports — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin','moderator'])) {
    redirect('auth/login.php');
}

// Gather report data
$totalRevenue  = mysqli_fetch_row(mysqli_query($con, "SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE payment_status='paid'"))[0] ?? 0;
$totalOrders   = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM orders"))[0] ?? 0;
$paidOrders    = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM orders WHERE payment_status='paid'"))[0] ?? 0;
$pendingOrders = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM orders WHERE status='pending'"))[0] ?? 0;

// Top sellers
$topSellers = mysqli_query($con,
    "SELECT u.username, u.full_name, u.revenue, u.total_sales
     FROM users u
     WHERE u.total_sales > 0
     ORDER BY u.revenue DESC
     LIMIT 10");

// Orders by status
$ordersByStatus = mysqli_query($con,
    "SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");

// Users by role
$usersByRole = mysqli_query($con,
    "SELECT r.role_name, COUNT(u.user_id) as cnt
     FROM roles r
     LEFT JOIN users u ON r.role_id = u.role_id
     GROUP BY r.role_name");

// Monthly signups
$monthlySignups = mysqli_query($con,
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt
     FROM users
     GROUP BY month
     ORDER BY month DESC
     LIMIT 12");
?>
<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h4 class="fw-800 mb-0" style="font-family:'Sora',sans-serif">📊 Marketplace Reports</h4>
    <div class="small">Analytics & insights.</div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-5">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg, #d4af37, #b8962e)">
            <div class="stat-label mb-1">Total Revenue</div>
            <div class="stat-num">R <?= number_format($totalRevenue, 2) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg, #3b82f6, #2563eb)">
            <div class="stat-label mb-1">Total Orders</div>
            <div class="stat-num"><?= number_format($totalOrders) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg, #10b981, #059669)">
            <div class="stat-label mb-1">Paid Orders</div>
            <div class="stat-num"><?= number_format($paidOrders) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg, #f59e0b, #d97706)">
            <div class="stat-label mb-1">Pending Orders</div>
            <div class="stat-num"><?= number_format($pendingOrders) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Top Sellers -->
    <div class="col-lg-6">
        <div class="card p-4">
            <h6 class="fw-700 mb-4" style="color:var(--accent)">Top Sellers</h6>
            <?php if ($topSellers && mysqli_num_rows($topSellers) > 0): ?>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Seller</th><th class="text-end">Sales</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                        <?php while ($s = mysqli_fetch_assoc($topSellers)): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?></td>
                            <td class="text-end"><?= $s['total_sales'] ?></td>
                            <td class="text-end fw-700" style="color:var(--accent)">R <?= number_format($s['revenue'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-center py-3">No sales data yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Orders by Status -->
    <div class="col-lg-3">
        <div class="card p-4">
            <h6 class="fw-700 mb-4" style="color:var(--accent)">Orders by Status</h6>
            <?php if ($ordersByStatus && mysqli_num_rows($ordersByStatus) > 0): ?>
                <?php while ($os = mysqli_fetch_assoc($ordersByStatus)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-secondary"><?= ucfirst($os['status']) ?></span>
                    <span class="fw-800"><?= $os['cnt'] ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center py-3 small">No data.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Users by Role -->
    <div class="col-lg-3">
        <div class="card p-4">
            <h6 class="fw-700 mb-4" style="color:var(--accent)">Users by Role</h6>
            <?php if ($usersByRole && mysqli_num_rows($usersByRole) > 0): ?>
                <?php while ($ur = mysqli_fetch_assoc($usersByRole)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-secondary"><?= ucfirst($ur['role_name']) ?></span>
                    <span class="fw-800"><?= $ur['cnt'] ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center py-3 small">No data.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Monthly Signups -->
    <div class="col-lg-6">
        <div class="card p-4">
            <h6 class="fw-700 mb-4" style="color:var(--accent)">Monthly Signups</h6>
            <?php if ($monthlySignups && mysqli_num_rows($monthlySignups) > 0): ?>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Month</th><th class="text-end">New Users</th></tr></thead>
                    <tbody>
                        <?php while ($ms = mysqli_fetch_assoc($monthlySignups)): ?>
                        <tr>
                            <td class="fw-600"><?= date('F Y', strtotime($ms['month'] . '-01')) ?></td>
                            <td class="text-end fw-700"><?= $ms['cnt'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-center py-3 small">No signup data.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>
