<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
if (!in_array($_SESSION['role'], ['owner','admin'])) { header("Location: /inventory_system/dashboard.php"); exit(); }
require __DIR__.'/../config/db.php';
require __DIR__.'/../includes/header.php';

$users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1 style="font-size: 45px;color: black">Users</h1>
  <p style="font-size: 20px;">Manage staff accounts and access roles</p>
</div>

<?php if (isset($_GET['registered'])): ?>
  <div class="alert alert-success">✓ New user registered successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success">✓ User deleted.</div>
<?php endif; ?>

<div style="margin-bottom:1.25rem;">
  <a href="/inventory_system/pages/register_user.php" class="btn btn-primary" style="font-size: 15px;">＋ Register New User</a>
</div>

<div style="margin-bottom:0.75rem;">
  <input type="text" data-filter-table="usersTable" placeholder="Search users…" style="max-width:320px;">
</div>

<div class="card">
  <div class="card-header"><h3>All Users (<?= count($users) ?>)</h3></div>
  <div class="card-body-flush">
    <div class="table-wrap">
      <table id="usersTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Registered</th>
            <th class="center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><span class="mono"><?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
            <td>
              <?php $roleLabel = ['owner'=>'Manager','attendant'=>'Attendant','admin'=>'Admin'][$u['role']] ?? ucfirst($u['role']); ?>
              <span class="badge role-<?= $u['role'] ?>"><?= $roleLabel ?></span>
            </td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td class="center">
              <?php if ($u['id'] !== $_SESSION['user_id']): ?>
              <form method="POST" action="/inventory_system/actions/user_actions.php" style="display:inline;"
                    onsubmit="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
              <?php else: ?>
                <span style="font-size:0.75rem;color:var(--gray-400);">You</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

