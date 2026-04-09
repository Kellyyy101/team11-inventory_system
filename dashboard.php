<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
require __DIR__.'/config/db.php';
require __DIR__.'/includes/header.php';

// Stats
$totalSales   = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM sales")->fetchColumn();
$totalItems   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Low stock (below threshold of 10)
$lowStock = $pdo->query(
  "SELECT id, name, category, stock_quantity, 10 AS threshold
   FROM products WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="page-header">
  <h1 style="font-size: 45px;color: black">Dashboard</h1>
  <p style="font-size: 20px;">Overview of sales, inventory, and alerts</p>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label" style="font-size: 20px;">Total Sales</div>
    <div class="stat-value">KES <?= number_format($totalSales, 0) ?></div>
    <div class="stat-sub" style="font-size: 15px;">All time revenue</div>
  </div>
  <div class="stat-card">
    <div class="stat-label" style="font-size: 20px;" >Total Products</div>
    <div class="stat-value"><?= $totalItems ?></div>
    <div class="stat-sub" style="font-size: 15px;">Items in catalogue</div>
  </div>
  <div class="stat-card" style="<?= count($lowStock) > 0 ? 'background:#fef2f2;border-left:4px solid #dc2626;border-color:#AD3232;' : '' ?>">
    <div class="stat-label" style="<?= count($lowStock) > 0 ? 'color:#991b1b;font-size: 20px;' : '' ?>">Low Stock Alerts</div>
    <div class="stat-value" style="<?= count($lowStock) > 0 ? 'color:#dc2626;' : '' ?>"><?= count($lowStock) ?></div>
    <div class="stat-sub" style="<?= count($lowStock) > 0 ? 'color:#ef4444;font-size: 15px' : '' ?>">Items below threshold</div>
  </div>
</div>

<!-- Low Stock Widget -->
<div class="card" style="border-color:
#AD3232;">
  <div class="card-header" style="background:
#FFD6D6;border-bottom-color:
#AD3232;">
    <div class="alert-icon" style="border-color:#f87171;background:#fee2e2;color:#dc2626;font-size: 35px;">!</div>
    <div>
      <h3 style="color:#991b1b;font-size: 22px;">Low Stock Alerts</h3>
      <p style="color:#dc2626; font-size: 15px">Products with fewer than 10 units remaining</p>
    </div>
  </div>
  <div class="card-body-flush">
    <?php if (empty($lowStock)): ?>
      <div class="empty-state">
        <div class="empty-icon"></div>
        <p>All products are well stocked</p>
        <p class="empty-sub">No items below the threshold right now</p>
      </div>
    <?php else: ?>
      <?php foreach ($lowStock as $item): ?>
      <div class="stock-list-item" style="background:#fff5f5;">
        <div style="flex:1;">
          <div style="display:flex;align-items:flex-start;gap:1rem;">
            <div style="flex:1;">
              <p style="font-weight:600;color:var(--gray-900);"><?= htmlspecialchars($item['name']) ?></p>
              <div style="display:flex;align-items:center;gap:0.75rem;margin-top:0.3rem;">
                <span class="mono" style="color:var(--gray-500);">ID: <?= $item['id'] ?></span>
                <span class="badge"><?= htmlspecialchars($item['category'] ?? 'General') ?></span>
              </div>
            </div>
            <div class="stock-box" style="border-color:#fca5a5;background:#fef2f2;">
              <span class="stock-val" style="color:#dc2626;text-align: center"><?= $item['stock_quantity'] ?></span>
              
              <span class="stock-label">Stock level</span>
            </div>
          </div>
        </div>
        <div style="margin-left:1.5rem;">
          <?php if (in_array($_SESSION['role'], ['owner','admin'])): ?>
            <a href="/inventory_system/pages/edit_product.php?id=<?= $item['id'] ?>" class="btn btn-primary btn-sm">Restock</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

