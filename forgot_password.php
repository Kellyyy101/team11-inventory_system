<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password — LiteStock</title>
  <link rel="stylesheet" href="/inventory_system/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <h1>Reset Password</h1>
    <p class="login-sub">Enter your username and new password</p>

    <?php
    require __DIR__ . '/config/db.php';
    $error   = '';
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $username  = trim($_POST['username'] ?? '');
      $newPass   = $_POST['new_password'] ?? '';
      $confirm   = $_POST['confirm_password'] ?? '';

      if (!$username || !$newPass || !$confirm) {
        $error = 'All fields are required.';
      } elseif (strlen($newPass) < 6) {
        $error = 'Password must be at least 6 characters.';
      } elseif ($newPass !== $confirm) {
        $error = 'Passwords do not match.';
      } else {
        // Check user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
          $error = 'No account found with that username.';
        } else {
          $hash = password_hash($newPass, PASSWORD_DEFAULT);
          $pdo->prepare("UPDATE users SET password = ? WHERE username = ?")
              ->execute([$hash, $username]);
          $success = true;
        }
      }
    }
    ?>

    <?php if ($success): ?>
      <div class="alert alert-success">✓ Password updated successfully.</div>
      <a href="/inventory_system/pages/home.php?reset=1" class="btn btn-primary btn-full" style="margin-top:0.5rem;">Back to Sign In</a>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
        </div>
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" placeholder="Min. 6 characters" minlength="6" required>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">Update Password</button>
      </form>
      <p style="margin-top:1rem;text-align:center;font-size:0.8rem;">
        <a href="/inventory_system/pages/home.php" style="color:var(--gray-500);">← Back to Home</a>
      </p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>