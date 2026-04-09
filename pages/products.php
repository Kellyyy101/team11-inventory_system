<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
if (!in_array($_SESSION['role'], ['owner','admin'])) { header("Location: /inventory_system/dashboard.php"); exit(); }
require __DIR__.'/../config/db.php';
require __DIR__.'/../includes/header.php';

$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1 style="font-size: 45px;color: black">Products</h1>
  <p style="font-size: 20px;">Manage your product catalogue and stock levels</p>
</div>

<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success">✓ Product deleted successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['added'])): ?>
  <div class="alert alert-success">✓ Product added successfully.</div>
<?php endif; ?>

<!-- Add Product Form -->
<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-header"><h3>Add New Product</h3></div>
  <div class="card-body">
    <form method="POST" action="/inventory_system/actions/product_actions.php">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group">
          <label>Product Name</label>
          <input type="text" name="name" placeholder="e.g. Maize Flour 2kg" required>
        </div>
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" placeholder="e.g. Grains">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Price (KES)</label>
          <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label>Initial Stock Qty</label>
          <input type="number" name="stock_quantity" placeholder="0" min="0" required>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Product</button>
      </div>
    </form>
  </div>
</div>

<!-- Search Filter -->
<div style="margin-bottom:0.75rem;">
  <input type="text" data-filter-table="productsTable" placeholder="Search products…" style="max-width:320px;">
</div>

<!-- Products Table -->
<div class="card">
  <div class="card-header"><h3>All Products (<?= count($products) ?>)</h3></div>
  <div class="card-body-flush">
    <div class="table-wrap">
      <table id="productsTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th class="right">Price (KES)</th>
            <th class="center">Stock</th>
            <th>Status</th>
            <th class="center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">No products yet. Add one above.</td></tr>
          <?php else: ?>
          <?php foreach ($products as $p): ?>
          <tr class="<?= $p['stock_quantity'] < 10 ? 'low-stock-row' : '' ?>">
            <td><span class="mono"><?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
            <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
            <td class="right"><?= number_format($p['price'], 2) ?></td>
            <td class="center">
              <span class="qty-badge"><?= $p['stock_quantity'] ?></span>
            </td>
            <td>
              <?php if ($p['stock_quantity'] == 0): ?>
                <span class="badge badge-danger">Out of Stock</span>
              <?php elseif ($p['stock_quantity'] < 10): ?>
                <span class="badge badge-danger">Low Stock</span>
              <?php else: ?>
                <span class="badge badge-success">In Stock</span>
              <?php endif; ?>
            </td>
            <td class="center" style="display:flex;gap:0.5rem;justify-content:center;padding:0.875rem 1.25rem;">
              <a href="/inventory_system/pages/edit_product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
              <form method="POST" action="/inventory_system/actions/product_actions.php" style="display:inline;"
                    onsubmit="return confirm('Delete <?= htmlspecialchars($p['name']) ?>? This cannot be undone.');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

