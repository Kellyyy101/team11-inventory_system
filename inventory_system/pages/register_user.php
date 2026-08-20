<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
if (!in_array($_SESSION['role'], ['owner','admin'])) { header("Location: /inventory_system/dashboard.php"); exit(); }
require __DIR__.'/../includes/header.php';
?>

<div class="page-header">
  <h1>Register New User</h1>
  <p>Create a staff account with an assigned role</p>
</div>

<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-error">✗ <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

  <div class="card">
    <div class="card-header"><h3>Account Details</h3></div>
    <div class="card-body">
      <form method="POST" action="/inventory_system/actions/user_actions.php">
        <input type="hidden" name="action" value="register">

        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" placeholder="e.g. jane_attendant" required
                 pattern="[a-zA-Z0-9_]+" title="Letters, numbers and underscores only">
          <p class="hint">Letters, numbers, and underscores only. No spaces.</p>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" required>
            <option value="">— Select a role —</option>
            <option value="attendant">Shop Attendant — Can record sales and view inventory</option>
            <option value="owner">Business Manager — Full access including products and reports</option>
            <option value="admin">Admin — System administration</option>
          </select>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Min. 6 characters" minlength="6" required>
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Repeat password" required>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Register User</button>
          <a href="/inventory_system/pages/users.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Role guide -->
  <div>
    <div class="card">
      <div class="card-header"><h3>Role Permissions</h3></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
          <div>
            <span class="badge role-attendant" style="margin-bottom:0.5rem;display:inline-block;">Attendant</span>
            <ul style="font-size:0.8rem;color:var(--gray-600);padding-left:1rem;line-height:1.8;">
              <li>Record sales (POS)</li>
              <li>View inventory levels</li>
              <li>Login / logout</li>
            </ul>
          </div>
          <div style="border-top:1px solid var(--gray-200);padding-top:1.25rem;">
            <span class="badge role-owner" style="margin-bottom:0.5rem;display:inline-block;">Manager</span>
            <ul style="font-size:0.8rem;color:var(--gray-600);padding-left:1rem;line-height:1.8;">
              <li>Everything above</li>
              <li>Add / edit products</li>
              <li>View sales reports</li>
              <li>Manage users</li>
            </ul>
          </div>
          <div style="border-top:1px solid var(--gray-200);padding-top:1.25rem;">
            <span class="badge role-admin" style="margin-bottom:0.5rem;display:inline-block;">Admin</span>
            <ul style="font-size:0.8rem;color:var(--gray-600);padding-left:1rem;line-height:1.8;">
              <li>Full system access</li>
              <li>Delete users &amp; products</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

