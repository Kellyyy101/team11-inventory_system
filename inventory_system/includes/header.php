<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Determine base URL dynamically so it works from any subfolder depth
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$base = '';
if (preg_match('#^(/inventory_system)#', $scriptPath, $m)) {
    $base = $m[1]; // always '/inventory_system'
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LiteStock</title>
  <link rel="stylesheet" href="<?= $base ?>/css/style.css">
  <script src="<?= $base ?>/js/main.js" defer></script>
</head>
<body>
<div class="app-shell">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h2 style="font-size: 30px;">LiteStock</h2>
      <p style="font-size: 15px;">Inventory Made Easier.</p>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= $base ?>/pages/home.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">
        <span class="nav-icon" style="font-size: 20px;">⌂</span> Home
      </a>
      <a href="<?= $base ?>/dashboard.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon" style="font-size: 20px;">▦</span> Dashboard
      </a>
      <a href="<?= $base ?>/pages/inventory.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : '' ?>">
        <span class="nav-icon" style="font-size: 20px;">☰</span> Inventory
      </a>
      <a href="<?= $base ?>/pages/sales.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'active' : '' ?>">
        <span class="nav-icon" style="font-size: 20px;">◈</span> Sales Point
      </a>
      <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['owner','admin'])): ?>
      <a href="<?= $base ?>/pages/products.php"
         class="<?= in_array(basename($_SERVER['PHP_SELF']), ['products.php','edit_product.php']) ? 'active' : '' ?>">
        <span class="nav-icon" style="font-size: 20px;">⊞</span> Products
      </a>
      <a href="<?= $base ?>/pages/reports.php"
         class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
        <span class="nav-icon" style="font-size: 20px;">◫</span> Reports
      </a>
      <a href="<?= $base ?>/pages/users.php"
         class="<?= in_array(basename($_SERVER['PHP_SELF']), ['users.php','register_user.php']) ? 'active' : '' ?>">
        <span class="nav-icon" style="font-size: 20px;">◉</span> Users
      </a>
      <?php endif; ?>
      
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></strong>
        <?php $sidebarRoleLabel = ['owner'=>'Manager','attendant'=>'Attendant','admin'=>'Admin'][$_SESSION['role'] ?? ''] ?? ucfirst($_SESSION['role'] ?? ''); echo $sidebarRoleLabel; ?>
      </div>
      <a href="<?= $base ?>/logout.php" class="btn btn-outline btn-sm btn-full">Logout</a>
    </div>
  </aside>
  <!-- Main -->
  <main class="main-content">
    <div class="page-inner">