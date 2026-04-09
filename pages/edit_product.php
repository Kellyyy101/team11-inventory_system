<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
if (!in_array($_SESSION['role'], ['owner','admin'])) { header("Location: /inventory_system/dashboard.php"); exit(); }
require __DIR__.'/../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: /inventory_system/pages/products.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { header("Location: /inventory_system/pages/products.php"); exit(); }

require __DIR__.'/../includes/header.php';
?>

<div class="page-header">
  <h1>Edit Product</h1>
  <p>Update product details and stock level</p>
</div>

<?php if (isset($_GET['saved'])): ?>
  <div class="alert alert-success">✓ Product updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-error">✗ <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

  <!-- Edit Form -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p class="mono" style="color:var(--gray-500);font-size:0.78rem;">
          Product ID: <?= str_pad($product['id'], 4, '0', STR_PAD_LEFT) ?>
        </p>
      </div>
    </div>
    <div class="card-body">
      <form method="POST" action="/inventory_system/actions/product_actions.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $product['id'] ?>">

        <div class="form-group">
          <label>Product Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>" placeholder="e.g. Grains, Dairy, Beverages">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Price (KES)</label>
            <input type="number" name="price" value="<?= $product['price'] ?>" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" name="stock_quantity" value="<?= $product['stock_quantity'] ?>" min="0" required>
            <p class="hint">Update this to restock the product</p>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <a href="/inventory_system/pages/products.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Stock Info Sidebar -->
  <div>
    <div class="card" style="margin-bottom:1.25rem;">
      <div class="card-header"><h3>Current Stock</h3></div>
      <div class="card-body" style="text-align:center;">
        <div class="stock-box" style="display:inline-block;padding:1.25rem 2rem;">
          <span class="stock-val" style="font-size:2.5rem;"><?= $product['stock_quantity'] ?></span>
          <span class="stock-label" style="font-size:0.8rem;">Units available</span>
        </div>
        <div style="margin-top:1rem;">
          <?php if ($product['stock_quantity'] == 0): ?>
            <span class="badge badge-danger">Out of Stock</span>
          <?php elseif ($product['stock_quantity'] < 10): ?>
            <span class="badge badge-danger">Low Stock</span>
          <?php else: ?>
            <span class="badge badge-success">In Stock</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Sales History</h3></div>
      <div class="card-body">
        <?php
        $history = $pdo->prepare(
          "SELECT SUM(quantity_sold) AS total_sold, SUM(total_price) AS revenue
           FROM sales WHERE product_id = ?"
        );
        $history->execute([$id]);
        $h = $history->fetch(PDO::FETCH_ASSOC);
        ?>
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
          <div>
            <div class="stat-label" style="font-size:0.72rem;">Total Units Sold</div>
            <div style="font-size:1.4rem;font-weight:600;font-family:'IBM Plex Mono',monospace;margin-top:0.2rem;">
              <?= number_format($h['total_sold'] ?? 0) ?>
            </div>
          </div>
          <div>
            <div class="stat-label" style="font-size:0.72rem;">Total Revenue</div>
            <div style="font-size:1.4rem;font-weight:600;font-family:'IBM Plex Mono',monospace;margin-top:0.2rem;">
              KES <?= number_format($h['revenue'] ?? 0, 2) ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

