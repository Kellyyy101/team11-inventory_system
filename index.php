<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login – Inventory System</title>
  <link rel="stylesheet" href="/inventory_system/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <h1 style="font-size: 45px;">LiteStock</h1>
    <p class="login-sub">Sign in to your account</p>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-error">Invalid username or password.</div>
    <?php endif; ?>
    <?php if (isset($_GET['logout'])): ?>
      <div class="alert alert-success">You have been logged out.</div>
    <?php endif; ?>

    <form action="actions/auth.php" method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter username" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.75rem;">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>