<?php
// includes/header.php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Interlinked Marketplace') ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="<?= url('assets/css/main.css') ?>" as="style">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link href="<?= url('assets/css/main.css') ?>" rel="stylesheet">
    <?= $extraHead ?? '' ?>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg interlinked-nav sticky-top">
  <div class="container">
    <a class="navbar-brand fw-800" href="<?= url('index.php') ?>">
      <span class="brand-text">Interlinked<span class="brand-accent">.</span></span>
    </a>

    <form class="d-none d-lg-flex nav-search mx-4 flex-grow-1" style="max-width:500px" action="<?= url('products.php') ?>" method="GET">
      <div class="input-group">
        <input type="text" class="form-control search-input border-0" name="search" placeholder="Search for items..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="background:rgba(255,255,255,0.05)">
        <button class="btn btn-search px-3" type="submit" style="background:rgba(212,175,55,0.1); color:var(--accent); border:none"><i data-feather="search" style="width:16px;height:16px"></i></button>
      </div>
    </form>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-center gap-3">
        <li class="nav-item"><a class="nav-link fw-600" href="<?= url('products.php') ?>">Products</a></li>

        <?php if (isLoggedIn()): ?>
          <li class="nav-item">
            <a class="nav-link fw-600" href="<?= url('create_product.php') ?>">
              Sell Product
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 ps-3" href="#" data-bs-toggle="dropdown" style="background:rgba(255,255,255,0.03); border-radius:30px; padding: 5px 15px">
              <img src="<?= url('uploads/avatars/' . htmlspecialchars($user['avatar'])) ?>"
                   class="nav-avatar m-0"
                   style="width:28px; height:28px; border:1.5px solid var(--accent)"
                   onerror="this.src='<?= url('assets/images/default-avatar.png') ?>'">
              <span class="d-none d-xl-inline small fw-700"><?= htmlspecialchars($user['username']) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-2 mt-2" style="background:var(--surface); border:1px solid var(--border); border-radius:15px">
              <li><a class="dropdown-item py-2 px-3 rounded" href="<?= url('dashboard.php') ?>"><i data-feather="layout" class="me-2" style="width:14px"></i> Dashboard</a></li>
              <li><a class="dropdown-item py-2 px-3 rounded" href="<?= url('profile.php') ?>"><i data-feather="user" class="me-2" style="width:14px"></i> My Profile</a></li>
              <li><a class="dropdown-item py-2 px-3 rounded" href="<?= url('wishlist.php') ?>"><i data-feather="heart" class="me-2" style="width:14px"></i> My Wishlist</a></li>
              <li><a class="dropdown-item py-2 px-3 rounded" href="<?= url('create_product.php') ?>"><i data-feather="package" class="me-2" style="width:14px"></i> My Listings</a></li>
              <?php if (hasAnyRole(['admin','moderator'])): ?>
              <li><hr class="dropdown-divider opacity-10"></li>
              <li><a class="dropdown-item py-2 px-3 rounded text-accent fw-700" href="<?= url('admin/index.php') ?>"><i data-feather="shield" class="me-2" style="width:14px"></i> Admin Panel</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider opacity-10"></li>
              <li><a class="dropdown-item py-2 px-3 rounded text-danger" href="<?= url('auth/logout.php') ?>"><i data-feather="log-out" class="me-2" style="width:14px"></i> Sign Out</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link small fw-700" href="<?= url('auth/login.php') ?>">Sign In</a></li>
          <li class="nav-item"><a class="btn btn-primary btn-sm px-4" href="<?= url('auth/register.php') ?>">Join Now</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

