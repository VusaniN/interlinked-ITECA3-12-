<?php
require_once dirname(__FILE__) . '/../../includes/session.php';
require_once dirname(__FILE__) . '/../../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin','moderator'])) {
    redirect('auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin Panel — Interlinked') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link href="<?= url('assets/css/main.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/admin.css') ?>" rel="stylesheet">
  <script src="https://unpkg.com/feather-icons"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- Admin Sidebar -->
<div class="admin-sidebar">
  <div class="sidebar-brand">
    <div class="d-flex align-items-center gap-2">
      <span style="color:var(--accent)">&#9679;</span> Interlinked <span style="color:var(--accent);font-weight:400">Admin</span>
    </div>
    <div style="font-size:.65rem;opacity:.5;font-weight:400;margin-top:.4rem;text-transform:uppercase;letter-spacing:1px">Management System</div>
  </div>

  <?php $currentFile = basename($_SERVER['PHP_SELF']); ?>
  <nav class="mt-4 flex-grow-1">
    <?php
    $navItems = [
      ['file'=>'index.php',              'icon'=>'grid',         'label'=>'Dashboard'],
      ['file'=>'products.php',           'icon'=>'package',      'label'=>'Products'],
      ['file'=>'verification_queue.php', 'icon'=>'shield',       'label'=>'Verifications'],
      ['file'=>'orders.php',             'icon'=>'shopping-bag', 'label'=>'Orders'],
      ['file'=>'users.php',              'icon'=>'users',        'label'=>'Users', 'roles'=>['admin']],
      ['file'=>'settings.php',           'icon'=>'settings',     'label'=>'Settings', 'roles'=>['admin']],
    ];
    foreach ($navItems as $item):
      if (isset($item['roles']) && !hasAnyRole($item['roles'])) continue;
    ?>
    <a class="nav-link <?= $currentFile === $item['file'] ? 'active' : '' ?>"
       href="<?= url('admin/' . $item['file']) ?>">
      <i data-feather="<?= $item['icon'] ?>" style="width:18px;height:18px"></i>
      <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <div style="padding:1.5rem;border-top:1px solid var(--border)">
    <a href="<?= url('index.php') ?>" class="nav-link mb-2" style="font-size:.8rem;padding:0.5rem 0">
      <i data-feather="external-link" style="width:14px"></i> View Marketplace
    </a>
    <a href="<?= url('auth/logout.php') ?>" class="nav-link" style="font-size:.8rem;padding:0.5rem 0;color:var(--danger)">
      <i data-feather="log-out" style="width:14px"></i> Sign Out
    </a>
  </div>
</div>

<!-- Admin Main Content -->
<div class="admin-main">
  <div class="admin-topbar">
    <div class="fw-700" style="font-family:'Sora',sans-serif;font-size:1.1rem"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
    <div class="d-flex align-items-center gap-3">
      <div class="text-end d-none d-sm-block">
        <div class="fw-700" style="font-size:.85rem;line-height:1"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
        <div style="font-size:.7rem;color:var(--accent);text-transform:uppercase;letter-spacing:1px;font-weight:700"><?= $_SESSION['role'] ?? '' ?></div>
      </div>
      <img src="<?= url('uploads/avatars/' . ($_SESSION['avatar'] ?? 'default.png')) ?>" 
           class="rounded-circle" style="width:40px;height:40px;object-fit:cover;border:2px solid var(--accent)"
           onerror="this.src='<?= url('assets/images/placeholder.jpg') ?>'">
    </div>
  </div>
  <div class="p-4 p-lg-5">
