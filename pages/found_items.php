<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /inventory_system/index.php");
    exit();
}

require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../includes/found_items_client.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../includes/header.php';

$client = new FoundItemsClient();

// Handle direct UUID lookup from query string
$lookupId = trim($_GET['lookup_id'] ?? '');
$lookupResult = null;
if (!empty($lookupId)) {
    $lookupResult = $client->getItemById($lookupId);
}

// Handle search filters
$searchTerm = trim($_GET['search'] ?? '');
$category   = trim($_GET['category'] ?? '');
$status     = trim($_GET['status'] ?? '');

$searchResponse = $client->searchItems($searchTerm, $category, $status);
$itemsList = ($searchResponse['success'] && is_array($searchResponse['data'])) ? $searchResponse['data'] : [];

$currentBaseUrl = FOUND_ITEMS_API_BASE_URL;
$apiKeyConfigured = FOUND_ITEMS_API_KEY !== '';
$foundItemsActionUrl = $base . '/actions/found_items_actions.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-size: 40px; color: black; margin-bottom: 0.3rem;">Found Items Directory</h1>
    <p style="font-size: 16px;">
      Live integration with <code><?= htmlspecialchars($currentBaseUrl) ?></code> via <code>GET /found-items</code> and <code>GET /found-items/{itemId}</code>
    </p>
  </div>
  <div style="display:flex;gap:0.5rem;align-items:center;">
    <button onclick="toggleApiSettings()" class="btn btn-outline btn-sm">
      ⚙ API Key &amp; Settings
    </button>
  </div>
</div>

<!-- API Alerts & Status Messages -->
<?php if (isset($_GET['saved_settings'])): ?>
  <div class="alert alert-success">✓ Upstream API settings updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['reset_settings'])): ?>
  <div class="alert alert-success">✓ API settings reset to defaults.</div>
<?php endif; ?>

<?php if (!$searchResponse['success']): ?>
  <div class="alert alert-error">
    ✗ <strong>Upstream API Notice (Status <?= $searchResponse['status_code'] ?>):</strong> <?= htmlspecialchars($searchResponse['error']) ?>
    <div style="margin-top:0.4rem;font-size:0.8rem;">
      If the Render instance was sleeping, please wait a few seconds and <a href="" style="color:var(--danger);font-weight:600;text-decoration:underline;">refresh the page</a>.
    </div>
  </div>
<?php endif; ?>

<!-- API Settings Drawer / Card (Hidden by default, toggleable) -->
<div id="apiSettingsCard" class="card" style="display:none;margin-bottom:1.5rem;border-color:#6366f1;background:#f8fafc;">
  <div class="card-header" style="background:#e0e7ff;border-bottom-color:#c7d2fe;">
    <h3 style="font-size:1.05rem;color:#3730a3;">Upstream Integration Configuration</h3>
  </div>
  <div class="card-body">
    <p style="font-size:0.85rem;color:var(--gray-600);margin-bottom:1rem;line-height:1.5;">
      Connected to upstream service at <code><?= htmlspecialchars($currentBaseUrl) ?></code>.<br>
      <?php if ($apiKeyConfigured): ?>
        Consumer API Key is configured with <strong><code>items:read</code></strong> scope.
      <?php else: ?>
        <strong>No API key configured.</strong> Enter a key with <code>items:read</code> scope below.
      <?php endif; ?>
    </p>
    <form method="POST" action="<?= htmlspecialchars($foundItemsActionUrl) ?>">
      <input type="hidden" name="action" value="save_settings">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="form-row">
        <div class="form-group">
          <label>Consumer API Key (Scope: <code>items:read</code>)</label>
          <input type="password" name="api_key" placeholder="<?= $apiKeyConfigured ? 'Leave blank to keep the current key' : 'Enter an API key' ?>" <?= $apiKeyConfigured ? '' : 'required' ?> autocomplete="new-password">
          <p class="hint">Sent to the upstream service in the <code>X-API-Key</code> header. Keys entered here are stored in your session.</p>
        </div>
      </div>
      <div class="form-actions" style="margin-top:0.75rem;">
        <button type="submit" class="btn btn-primary btn-sm">Update API Settings</button>
        <button type="button" onclick="toggleApiSettings()" class="btn btn-outline btn-sm">Close</button>
      </div>
    </form>
    <?php if (!empty($_SESSION['found_items_api_key'])): ?>
      <form method="POST" action="<?= htmlspecialchars($foundItemsActionUrl) ?>" style="margin-top:0.75rem;">
        <input type="hidden" name="action" value="reset_settings">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <button type="submit" class="btn btn-outline btn-sm">Reset to Environment Key</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- ========================================== -->
<!-- 1. DIRECT UUID ITEM LOOKUP SECTION (GET /found-items/{itemId}) -->
<!-- ========================================== -->
<div class="card" style="margin-bottom: 1.75rem; border-color: var(--gray-700);">
  <div class="card-header" style="background: var(--gray-800); color: var(--white); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="color: var(--white); font-size: 1.15rem;">🔍 Direct Item Retrieval: <code>GET /found-items/{itemId}</code></h3>
      <p style="color: var(--gray-300); font-size: 0.8rem; margin-top: 0.2rem;">
        Retrieve a single found-item record from the live system by its UUID.
      </p>
    </div>
    <span class="badge" style="background: #374151; color: #e5e7eb; border-color: #4b5563;">Endpoint: /found-items/{itemId}</span>
  </div>
  <div class="card-body">
    <form method="GET" action="" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
      <div style="flex:1;min-width:280px;">
        <label for="lookup_id" style="font-size:0.8rem;font-weight:600;">Item ID (UUID) *</label>
        <input type="text" id="lookup_id" name="lookup_id"
               value="<?= htmlspecialchars($lookupId) ?>"
               placeholder="Paste exact found item UUID…"
               required
               style="font-family:'IBM Plex Mono', monospace;">
      </div>
      <button type="submit" class="btn btn-primary" style="height:40px;">
        Retrieve Item from Upstream
      </button>
      <?php if (!empty($lookupId)): ?>
        <a href="found_items.php" class="btn btn-outline" style="height:40px;">Clear</a>
      <?php endif; ?>
    </form>

    <!-- Direct Lookup Results Panel -->
    <?php if ($lookupResult !== null): ?>
      <div style="margin-top:1.25rem;border-top:2px solid var(--gray-300);padding-top:1.25rem;">
        <?php if ($lookupResult['success'] && !empty($lookupResult['data'])):
          $item = $lookupResult['data'];
          $id   = $item['id'] ?? $item['itemId'] ?? $lookupId;
          $title = $item['title'] ?? $item['name'] ?? 'Untitled Item';
          $cat   = $item['category'] ?? 'other';
          $desc  = $item['description'] ?? 'No description provided';
          $loc   = $item['location'] ?? 'Unknown location';
          $storage = $item['storageLocation'] ?? $loc;
          $pickup  = $item['pickupOffice'] ?? 'Main Security Office';
          $hours   = $item['pickupHours'] ?? 'Business hours';
          $date    = !empty($item['dateFound']) ? date('d M Y, H:i', strtotime($item['dateFound'])) : 'N/A';
          $status  = strtolower($item['status'] ?? 'unclaimed');
          $images  = $item['images'] ?? [];
        ?>
          <div style="background:#f0fdf4;border:2px solid #86efac;padding:1.25rem;border-radius:2px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:0.75rem;">
              <div>
                <span class="badge" style="background:#16a34a;color:#fff;margin-bottom:0.4rem;">200 OK — Live Upstream Record Retrieved</span>
                <h3 style="font-size:1.3rem;color:var(--gray-900);margin:0.25rem 0;"><?= htmlspecialchars($title) ?></h3>
                <p class="mono" style="color:var(--gray-600);font-size:0.85rem;">
                  UUID: <strong><?= htmlspecialchars($id) ?></strong>
                </p>
              </div>
              <div>
                <?= renderStatusBadge($status) ?>
                <span class="badge" style="margin-left:0.3rem;"><?= htmlspecialchars($cat) ?></span>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-top:1rem;">
              <div>
                <p style="font-size:0.9rem;color:var(--gray-800);line-height:1.6;margin-bottom:0.75rem;">
                  <strong>Description:</strong><br><?= nl2br(htmlspecialchars($desc)) ?>
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;background:#fff;padding:0.875rem;border:1px solid #d1d5db;">
                  <div>
                    <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Discovery Location:</span>
                    <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;"><?= htmlspecialchars($loc) ?></p>
                  </div>
                  <div>
                    <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Date Logged:</span>
                    <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;"><?= htmlspecialchars($date) ?></p>
                  </div>
                  <div>
                    <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Storage / Locker:</span>
                    <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;"><?= htmlspecialchars($storage) ?></p>
                  </div>
                  <div>
                    <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Pickup Point:</span>
                    <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;"><?= htmlspecialchars($pickup) ?> (<?= htmlspecialchars($hours) ?>)</p>
                  </div>
                </div>
              </div>

              <div>
                <?php if (
                    !empty($images)
                    && is_array($images)
                    && isset($images[0])
                    && is_string($images[0])
                    && filter_var($images[0], FILTER_VALIDATE_URL)
                    && in_array(strtolower((string)parse_url($images[0], PHP_URL_SCHEME)), ['http', 'https'], true)
                ): ?>
                  <div style="margin-bottom:0.75rem;">
                    <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;display:block;margin-bottom:0.3rem;">Item Image:</span>
                    <img src="<?= htmlspecialchars($images[0]) ?>" alt="Found Item" style="max-width:100%;height:140px;object-fit:cover;border:1px solid #d1d5db;border-radius:2px;">
                  </div>
                <?php else: ?>
                  <div style="background:#fff;border:1px dashed #d1d5db;height:140px;display:flex;align-items:center;justify-content:center;color:var(--gray-400);font-size:0.8rem;">
                    No photo uploaded
                  </div>
                <?php endif; ?>
                <button type="button" onclick="showRawJson(<?= htmlspecialchars(json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-outline btn-sm btn-full" style="margin-top:0.5rem;">
                  View Raw JSON Payload
                </button>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="alert alert-error">
            ✗ <strong>Lookup Failed (Status <?= $lookupResult['status_code'] ?>):</strong> <?= htmlspecialchars($lookupResult['error']) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. SEARCH & DIRECTORY BROWSING SECTION -->
<!-- ========================================== -->
<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-header">
    <h3>🔎 Live Found Items Search</h3>
  </div>
  <div class="card-body">
    <form method="GET" action="">
      <div class="form-row">
        <div class="form-group">
          <label>Search Keyword</label>
          <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search by title, description, or location…">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <option value="">— All Categories —</option>
            <option value="electronics" <?= $category === 'electronics' ? 'selected' : '' ?>>Electronics</option>
            <option value="personal-items" <?= $category === 'personal-items' ? 'selected' : '' ?>>Personal Items</option>
            <option value="documents" <?= $category === 'documents' ? 'selected' : '' ?>>Documents / Student IDs</option>
            <option value="clothing" <?= $category === 'clothing' ? 'selected' : '' ?>>Clothing &amp; Apparel</option>
            <option value="keys" <?= $category === 'keys' ? 'selected' : '' ?>>Keys</option>
            <option value="other" <?= $category === 'other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="">— All Statuses —</option>
            <option value="unclaimed" <?= $status === 'unclaimed' ? 'selected' : '' ?>>Unclaimed</option>
            <option value="under_review" <?= $status === 'under_review' ? 'selected' : '' ?>>Under Review</option>
            <option value="claimed" <?= $status === 'claimed' ? 'selected' : '' ?>>Claimed</option>
            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
          </select>
        </div>
        <div style="display:flex;align-items:flex-end;gap:0.75rem;padding-bottom:1.25rem;">
          <button type="submit" class="btn btn-primary" style="flex:1;">Search Upstream Items</button>
          <?php if (!empty($searchTerm) || !empty($category) || !empty($status)): ?>
            <a href="found_items.php" class="btn btn-outline">Reset Filter</a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- 3. LIVE FOUND ITEMS CATALOGUE TABLE -->
<!-- ========================================== -->
<div class="card">
  <div class="card-header" style="justify-content:space-between;">
    <div>
      <h3 style="font-size:1.1rem;">Upstream Catalogue (<?= count($itemsList) ?> items returned)</h3>
    </div>
    <span class="mono" style="font-size:0.78rem;color:var(--gray-500);">
      Host: <?= htmlspecialchars(parse_url($currentBaseUrl, PHP_URL_HOST)) ?>
    </span>
  </div>
  <div class="card-body-flush">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Item Details</th>
            <th>Category</th>
            <th>Found Location</th>
            <th>Date Found</th>
            <th class="center">Status</th>
            <th class="center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($itemsList)): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:2.5rem;color:var(--gray-400);">
                <div style="font-size:2rem;margin-bottom:0.5rem;">🔍</div>
                <?php if ($searchResponse['success']): ?>
                  <p>No found items match the search query on the upstream system.</p>
                <?php else: ?>
                  <p>No records could be retrieved from the upstream service.</p>
                  <p style="font-size:0.8rem;margin-top:0.3rem;">Please check the API status notice above.</p>
                <?php endif; ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($itemsList as $row):
              $rowId    = $row['id'] ?? $row['itemId'] ?? '';
              $rowTitle = $row['title'] ?? $row['name'] ?? 'Untitled';
              $rowCat   = $row['category'] ?? 'other';
              $rowDesc  = $row['description'] ?? '';
              $rowLoc   = $row['location'] ?? '—';
              $rowDate  = !empty($row['dateFound']) ? date('d M Y', strtotime($row['dateFound'])) : '—';
              $rowStat  = strtolower($row['status'] ?? 'unclaimed');
            ?>
              <tr>
                <td style="max-width:320px;">
                  <strong style="display:block;font-size:0.92rem;color:var(--gray-900);">
                    <?= htmlspecialchars($rowTitle) ?>
                  </strong>
                  <span class="mono" style="font-size:0.75rem;color:var(--gray-500);cursor:pointer;"
                        title="Click to copy UUID" onclick="copyToClipboard(<?= htmlspecialchars(json_encode($rowId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)">
                    UUID: <?= htmlspecialchars($rowId) ?> 📋
                  </span>
                  <?php if (!empty($rowDesc)): ?>
                    <p style="font-size:0.78rem;color:var(--gray-600);margin-top:0.2rem;white-space:normal;">
                      <?= htmlspecialchars(substr($rowDesc, 0, 90)) . (strlen($rowDesc) > 90 ? '…' : '') ?>
                    </p>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge"><?= htmlspecialchars($rowCat) ?></span>
                </td>
                <td>
                  <span style="font-size:0.85rem;color:var(--gray-800);"><?= htmlspecialchars($rowLoc) ?></span>
                </td>
                <td style="font-size:0.85rem;color:var(--gray-600);">
                  <?= htmlspecialchars($rowDate) ?>
                </td>
                <td class="center">
                  <?= renderStatusBadge($rowStat) ?>
                </td>
                <td class="center" style="display:flex;gap:0.4rem;justify-content:center;padding:1rem 0.75rem;">
                  <button type="button" class="btn btn-outline btn-sm" onclick="fetchItemDetails(<?= htmlspecialchars(json_encode($rowId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)">
                    Inspect
                  </button>
                  <a href="?lookup_id=<?= urlencode($rowId) ?>" class="btn btn-primary btn-sm">
                    Direct Link
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 4. MODAL FOR INSPECTING ITEM DETAILS (AJAX) -->
<!-- ========================================== -->
<div id="itemDetailModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;padding:1rem;">
  <div style="background:#fff;border-radius:4px;max-width:680px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 25px -5px rgba(0,0,0,0.3);border:2px solid var(--gray-700);">
    <div style="padding:1.25rem 1.5rem;background:var(--gray-900);color:#fff;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <h3 id="modalTitle" style="color:#fff;font-size:1.15rem;margin:0;">Item Inspection</h3>
        <p class="mono" id="modalEndpoint" style="color:var(--gray-400);font-size:0.75rem;margin-top:0.2rem;">GET /found-items/{itemId}</p>
      </div>
      <button onclick="closeModal()" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;">✕</button>
    </div>
    <div id="modalBody" style="padding:1.5rem;">
      <div style="text-align:center;padding:2rem;color:var(--gray-500);">Loading record from upstream live service...</div>
    </div>
    <div style="padding:1rem 1.5rem;background:var(--gray-100);border-top:1px solid var(--gray-300);display:flex;justify-content:space-between;align-items:center;">
      <button type="button" class="btn btn-outline btn-sm" id="modalRawJsonBtn" onclick="toggleModalJson()">Toggle Raw JSON</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="closeModal()">Close</button>
    </div>
  </div>
</div>

<?php
/**
 * Helper to render color-coded status badges
 */
function renderStatusBadge(string $status): string {
    switch ($status) {
        case 'unclaimed':
            return '<span class="badge" style="background:#fef3c7;border-color:#fde047;color:#854d0e;">Unclaimed</span>';
        case 'under_review':
            return '<span class="badge" style="background:#e0e7ff;border-color:#a5b4fc;color:#3730a3;">Under Review</span>';
        case 'claimed':
            return '<span class="badge badge-success">Claimed</span>';
        case 'closed':
            return '<span class="badge" style="background:#e5e7eb;border-color:#9ca3af;color:#374151;">Closed</span>';
        default:
            return '<span class="badge">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}
?>

<script>
function toggleApiSettings() {
  const card = document.getElementById('apiSettingsCard');
  card.style.display = (card.style.display === 'none' || card.style.display === '') ? 'block' : 'none';
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    alert('Copied Item UUID: ' + text);
  }).catch(() => {
    prompt('Item UUID:', text);
  });
}

let currentModalItem = null;
let isShowingJson = false;

function fetchItemDetails(itemId) {
  const modal = document.getElementById('itemDetailModal');
  const body = document.getElementById('modalBody');
  const title = document.getElementById('modalTitle');
  const endpoint = document.getElementById('modalEndpoint');

  endpoint.textContent = 'GET ' + <?= json_encode($currentBaseUrl) ?> + '/found-items/' + itemId;
  title.textContent = 'Retrieving ' + itemId + '...';
  body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--gray-500);"><p>Querying upstream API with items:read scope...</p></div>';
  modal.style.display = 'flex';
  isShowingJson = false;

  fetch(<?= json_encode($foundItemsActionUrl) ?> + '?action=get_item&itemId=' + encodeURIComponent(itemId))
    .then(res => res.json())
    .then(res => {
      if (!res.success) {
        body.innerHTML = `
          <div class="alert alert-error">
            <strong>Upstream Request Failed (Status ${res.status_code || 500}):</strong><br>
            ${esc(res.error || 'Unknown error occurred')}
          </div>
        `;
        return;
      }
      currentModalItem = res.data;
      renderModalItem(res.data);
    })
    .catch(err => {
      body.innerHTML = `<div class="alert alert-error">Network error connecting to API handler: ${esc(err.message)}</div>`;
    });
}

function renderModalItem(item) {
  const title = document.getElementById('modalTitle');
  title.textContent = item.title || item.name || 'Found Item';

  const dateStr = item.dateFound ? new Date(item.dateFound).toLocaleString() : 'N/A';
  let imgHtml = '';
  if (Array.isArray(item.images) && item.images.length > 0) {
    const imageUrl = String(item.images[0]);
    if (/^https?:\/\//i.test(imageUrl)) {
      imgHtml = `<div style="margin-top:1rem;"><strong style="font-size:0.8rem;text-transform:uppercase;color:var(--gray-600);">Attached Photo:</strong><br><img src="${esc(imageUrl)}" style="max-width:100%;max-height:220px;object-fit:cover;border:1px solid #d1d5db;margin-top:0.3rem;"></div>`;
    }
  }

  document.getElementById('modalBody').innerHTML = `
    <div id="modalFormattedView">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;">
        <div>
          <span class="mono" style="font-size:0.82rem;color:var(--gray-600);background:var(--gray-100);padding:0.2rem 0.5rem;display:inline-block;">
            UUID: ${esc(item.id || item.itemId || '')}
          </span>
        </div>
        <div>
          <span class="badge" style="margin-right:0.3rem;">${esc(item.category || 'General')}</span>
          <span class="badge" style="background:#16a34a;color:#fff;">${esc(item.status || 'unclaimed')}</span>
        </div>
      </div>

      <div style="margin-bottom:1rem;">
        <strong style="font-size:0.8rem;text-transform:uppercase;color:var(--gray-600);">Description:</strong>
        <p style="font-size:0.95rem;color:var(--gray-800);line-height:1.6;margin-top:0.3rem;">
          ${esc(item.description || 'No description provided')}
        </p>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;background:var(--gray-50);border:1px solid var(--gray-300);padding:1rem;">
        <div>
          <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Discovery Location</span>
          <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;">${esc(item.location || 'Unknown')}</p>
        </div>
        <div>
          <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Date &amp; Time Logged</span>
          <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;">${dateStr}</p>
        </div>
        <div>
          <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Storage Location</span>
          <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;">${esc(item.storageLocation || item.location || 'Security Office')}</p>
        </div>
        <div>
          <span style="font-size:0.75rem;color:var(--gray-500);text-transform:uppercase;">Pickup Point</span>
          <p style="font-size:0.85rem;font-weight:600;margin-top:0.2rem;">${esc(item.pickupOffice || 'Central Security Desk')}</p>
        </div>
      </div>

      ${imgHtml}
    </div>

    <div id="modalJsonView" style="display:none;margin-top:1rem;">
      <pre style="background:#1f2937;color:#f9fafb;padding:1rem;border-radius:2px;font-family:'IBM Plex Mono',monospace;font-size:0.8rem;overflow-x:auto;">${esc(JSON.stringify(item, null, 2))}</pre>
    </div>
  `;
}

function toggleModalJson() {
  const jsonView = document.getElementById('modalJsonView');
  const fmtView  = document.getElementById('modalFormattedView');
  if (!jsonView || !fmtView) return;
  isShowingJson = !isShowingJson;
  jsonView.style.display = isShowingJson ? 'block' : 'none';
  fmtView.style.display  = isShowingJson ? 'none' : 'block';
}

function showRawJson(data) {
  currentModalItem = data;
  document.getElementById('modalTitle').textContent = data.title || data.name || 'Found Item';
  document.getElementById('modalEndpoint').textContent = 'Direct Lookup Payload';
  renderModalItem(data);
  document.getElementById('itemDetailModal').style.display = 'flex';
  toggleModalJson();
}

function closeModal() {
  document.getElementById('itemDetailModal').style.display = 'none';
}

function esc(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

window.onclick = function(e) {
  const modal = document.getElementById('itemDetailModal');
  if (e.target === modal) {
    closeModal();
  }
};
</script>
