<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
require __DIR__.'/../config/db.php';
require __DIR__.'/../includes/header.php';

$isAdmin = ($_SESSION['role'] === 'admin');

$products = $pdo->query(
  "SELECT id, name, price, stock_quantity, category FROM products WHERE stock_quantity > 0 ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// Build JS lookup map from DB
$jsLookup = [];
foreach ($products as $p) {
  $jsLookup[$p['id']] = ['name' => $p['name'], 'price' => (float)$p['price'], 'stock' => (int)$p['stock_quantity']];
}
?>

<div class="page-header">
  <h1 style="font-size: 45px;color: black">Sales Point (POS)</h1>
  <p style="font-size: 20px">Record sales transactions</p>
</div>

<?php if (isset($_GET['success'])): ?>
  <div class="alert alert-success">✓ Sale confirmed and inventory updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-error">✗ <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem;">
  ⚠ Admins cannot process sales. This page is view-only for your role.
  Sales can be recorded by Shop Attendants and Business Managers.
</div>
<div class="pos-grid" style="opacity:0.5;pointer-events:none;user-select:none;">
<?php else: ?>
<div class="pos-grid">
<?php endif; ?>
  <!-- LEFT: Add Item -->
  <div>
    <!-- Item Entry Card -->
    <div class="card" style="margin-bottom:1.25rem;">
      <div class="card-header"><h3>Add Items to be Sold</h3></div>
      <div class="card-body">
        <div class="form-group" style="position:relative;">
          <label for="productSearch">Search Product</label>
          <input type="text" id="productSearch" placeholder="Type product name…"
                 autocomplete="off" oninput="filterProducts()" onkeydown="searchKeyNav(event)">
          <!-- Dropdown suggestions -->
          <div id="searchDropdown" style="
            display:none; position:absolute; z-index:100; left:0; right:0;
            background:var(--white); border:2px solid var(--gray-400);
            border-top:none; max-height:180px; overflow-y:auto;">
          </div>
          <p class="hint">Search by name, then press Enter or click to select</p>
        </div>
        <div class="form-group">
          <label for="itemId">Item ID <span style="font-weight:400;color:var(--gray-400);">(auto-filled by search)</span></label>
          <input type="text" id="itemId" placeholder="e.g. 1, 2, 3 …" autocomplete="off">
        </div>
        <div class="form-group">
          <label for="qty">Quantity</label>
          <input type="number" id="qty" placeholder="1" min="1" value="1">
        </div>
        <button class="btn btn-primary btn-full" onclick="addToCart()">＋ Add to Sale</button>

        <!-- Quick reference -->
        <div class="quick-ref">
          <div class="quick-ref-title">Product Reference</div>
          <?php foreach ($products as $p): ?>
          <div class="quick-ref-row">
            <span><?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?>: <?= htmlspecialchars($p['name']) ?></span>
            <span>KES <?= number_format($p['price'], 2) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Calculate Total -->
    <div class="card">
      <div class="card-body">
        <button class="btn btn-outline btn-full" onclick="calcTotal()">Calculate Total</button>
        <div class="pos-total-box" id="totalBox" style="display:none;">
          <div class="pos-total-label">Sale Total</div>
          <div class="pos-total-val" id="totalDisplay">KES 0.00</div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: Cart -->
  <div>
    <div class="card" style="margin-bottom:1.25rem;">
      <div class="card-header">
        <h3>Current Sale(<span id="cartCount">0</span>)</h3>
      </div>
      <div class="card-body-flush" id="cartContainer">
        <div class="empty-state" id="emptyState">
          <div class="empty-icon"></div>
          <p>There are currently no items to be sold </p>
          <p class="empty-sub">Add items using the form on the left</p>
        </div>
        <div class="table-wrap" id="cartTableWrap" style="display:none;">
          <table>
            <thead>
              <tr>
                <th>Item</th>
                <th class="center">Price</th>
                <th class="center">Qty</th>
                <th class="right">Total</th>
                <th class="center" style="width:40px;"></th>
              </tr>
            </thead>
            <tbody id="cartBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Confirm Sale -->
    <form method="POST" action="/inventory_system/actions/sales_actions.php" id="saleForm">
      <input type="hidden" name="cart_data" id="cartDataInput">
      <button type="button" class="confirm-btn" id="confirmBtn" onclick="confirmSale()" disabled>
        Confirm Sale
      </button>
    </form>
  </div>
</div><!-- /pos-grid -->

<script>
const inventory = <?= json_encode($jsLookup) ?>;
let cart = [];

function addToCart() {
  const rawId = document.getElementById('itemId').value.trim();
  const qty   = parseInt(document.getElementById('qty').value) || 0;
  const id    = parseInt(rawId);

  if (!rawId || qty <= 0) { alert('Please enter a valid Item ID and quantity.'); return; }

  const item = inventory[id];
  if (!item) { alert('Item not found. Check the product ID.'); return; }

  // Check stock
  const inCart = cart.filter(c => c.id === id).reduce((s, c) => s + c.qty, 0);
  if (inCart + qty > item.stock) {
    alert(`Only ${item.stock} units in stock (${inCart} already in cart).`); return;
  }

  // Merge if same item already in cart
  const existing = cart.find(c => c.id === id);
  if (existing) {
    existing.qty += qty;
    existing.total = existing.qty * existing.price;
  } else {
    cart.push({ id, name: item.name, price: item.price, qty, total: item.price * qty });
  }

  document.getElementById('productSearch').value = '';
  document.getElementById('itemId').value = '';
  document.getElementById('qty').value = '1';
  document.getElementById('searchDropdown').style.display = 'none';
  document.getElementById('productSearch').focus();
  renderCart();
}

function removeItem(id) {
  cart = cart.filter(c => c.id !== id);
  renderCart();
}

function renderCart() {
  const count = cart.reduce((s, c) => s + c.qty, 0);
  document.getElementById('cartCount').textContent = cart.length;
  document.getElementById('confirmBtn').disabled = cart.length === 0;
  document.getElementById('totalBox').style.display = 'none';

  if (cart.length === 0) {
    document.getElementById('emptyState').style.display = '';
    document.getElementById('cartTableWrap').style.display = 'none';
    return;
  }

  document.getElementById('emptyState').style.display = 'none';
  document.getElementById('cartTableWrap').style.display = '';

  const tbody = document.getElementById('cartBody');
  tbody.innerHTML = cart.map(c => `
    <tr>
      <td>
        <strong>${escHtml(c.name)}</strong>
        <br><span class="mono" style="color:var(--gray-500);">[ID: ${String(c.id).padStart(4,'0')}]</span>
      </td>
      <td class="center">KES ${c.price.toFixed(2)}</td>
      <td class="center"><span class="qty-badge">${c.qty}</span></td>
      <td class="right"><strong>KES ${c.total.toFixed(2)}</strong></td>
      <td class="center">
        <button class="remove-btn" onclick="removeItem(${c.id})">✕</button>
      </td>
    </tr>
  `).join('');
}

function calcTotal() {
  const total = cart.reduce((s, c) => s + c.total, 0);
  document.getElementById('totalDisplay').textContent = 'KES ' + total.toFixed(2);
  document.getElementById('totalBox').style.display = '';
}

function confirmSale() {
  if (cart.length === 0) { alert('Cart is empty!'); return; }
  const total = cart.reduce((s, c) => s + c.total, 0);
  if (!confirm(`Confirm sale of KES ${total.toFixed(2)} for ${cart.length} item(s)?`)) return;
  document.getElementById('cartDataInput').value = JSON.stringify(cart);
  document.getElementById('saleForm').submit();
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Product name search ──
// Build a flat array for searching: [{id, name, price, stock}, ...]
const productList = Object.entries(inventory).map(([id, item]) => ({
  id: parseInt(id), name: item.name, price: item.price, stock: item.stock
}));

let highlightIndex = -1;

function filterProducts() {
  const q = document.getElementById('productSearch').value.trim().toLowerCase();
  const dropdown = document.getElementById('searchDropdown');
  highlightIndex = -1;

  if (!q) { dropdown.style.display = 'none'; return; }

  const matches = productList.filter(p => p.name.toLowerCase().includes(q));

  if (matches.length === 0) {
    dropdown.innerHTML = '<div style="padding:0.6rem 0.875rem;font-size:0.82rem;color:var(--gray-400);">No products found</div>';
    dropdown.style.display = '';
    return;
  }

  dropdown.innerHTML = matches.map((p, i) => `
    <div class="search-option" data-id="${p.id}" data-index="${i}"
         style="padding:0.6rem 0.875rem;cursor:pointer;font-size:0.85rem;
                border-bottom:1px solid var(--gray-200);display:flex;
                justify-content:space-between;align-items:center;"
         onmouseenter="setHighlight(${i})"
         onclick="selectProduct(${p.id}, '${escHtml(p.name)}')">
      <span><strong>${escHtml(p.name)}</strong>
        <span style="color:var(--gray-400);font-size:0.75rem;margin-left:0.5rem;">
          Stock: ${p.stock}
        </span>
      </span>
      <span style="color:var(--gray-600);font-family:'IBM Plex Mono',monospace;font-size:0.78rem;">
        KES ${p.price.toFixed(2)}
      </span>
    </div>
  `).join('');

  dropdown.style.display = '';
}

function setHighlight(index) {
  highlightIndex = index;
  document.querySelectorAll('.search-option').forEach((el, i) => {
    el.style.background = i === index ? 'var(--gray-100)' : '';
  });
}

function searchKeyNav(e) {
  const options = document.querySelectorAll('.search-option');
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    highlightIndex = Math.min(highlightIndex + 1, options.length - 1);
    setHighlight(highlightIndex);
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    highlightIndex = Math.max(highlightIndex - 1, 0);
    setHighlight(highlightIndex);
  } else if (e.key === 'Enter') {
    e.preventDefault();
    if (highlightIndex >= 0 && options[highlightIndex]) {
      const id = parseInt(options[highlightIndex].dataset.id);
      const name = options[highlightIndex].querySelector('strong').textContent;
      selectProduct(id, name);
    }
  } else if (e.key === 'Escape') {
    document.getElementById('searchDropdown').style.display = 'none';
  }
}

function selectProduct(id, name) {
  document.getElementById('productSearch').value = name;
  document.getElementById('itemId').value = id;
  document.getElementById('searchDropdown').style.display = 'none';
  document.getElementById('qty').focus();
}

// Close dropdown when clicking outside
document.addEventListener('click', e => {
  if (!e.target.closest('#productSearch') && !e.target.closest('#searchDropdown')) {
    document.getElementById('searchDropdown').style.display = 'none';
  }
});

// Clear search box after adding to cart (patch addToCart clear section)
const _origAddToCart = addToCart;

// Allow Enter key on itemId to jump to qty
document.getElementById('itemId').addEventListener('keypress', e => {
  if (e.key === 'Enter') document.getElementById('qty').focus();
});
document.getElementById('qty').addEventListener('keypress', e => {
  if (e.key === 'Enter') addToCart();
});
</script>

