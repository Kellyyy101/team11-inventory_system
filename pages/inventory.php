<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
require __DIR__.'/../config/db.php';
require __DIR__.'/../includes/header.php';

$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1 style="font-size: 45px;color: black">Inventory</h1>
  <p style="font-size: 20px;">View all products and current stock levels</p>
</div>

<div class="card">
  <div class="card-header">
    <h3>Stock Overview (<?= count($products) ?> products)</h3>
  </div>
  <div class="card-body-flush">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th class="right">Price (KES)</th>
            <th class="center">Stock</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:2rem;">No products in the system yet.</td></tr>
          <?php else: ?>
          <?php foreach ($products as $p): ?>
          <tr class="<?= $p['stock_quantity'] < 10 ? 'low-stock-row' : '' ?>">
            <td><span class="mono"><?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
            <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
            <td class="right"><?= number_format($p['price'], 2) ?></td>
            <td class="center"><span class="qty-badge"><?= $p['stock_quantity'] ?></span></td>
            <td>
              <?php if ($p['stock_quantity'] == 0): ?>
                <span class="badge badge-danger">Out of Stock</span>
              <?php elseif ($p['stock_quantity'] < 10): ?>
                <span class="badge badge-danger">Low Stock</span>
              <?php else: ?>
                <span class="badge badge-success">In Stock</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

