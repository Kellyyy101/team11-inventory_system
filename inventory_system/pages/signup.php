<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — LiteStock</title>
  <link rel="stylesheet" href="/inventory_system/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box" style="width:420px;">

    <!-- Brand -->
    <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:1rem;">
      
      <span style="font-size:1.3rem;font-weight:700;color:var(--gray-900);">LiteStock</span>
    </div>

    <h1 style="font-size:1.1rem;font-weight:700;color:var(--gray-900);margin-bottom:0.2rem;">Create an Account</h1>
    <p class="login-sub">New accounts are set up as Business Manager by default.</p>

    <?php
    require __DIR__ . '/../config/db.php';
    $error   = '';
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $username = trim($_POST['username'] ?? '');
      $password = $_POST['password'] ?? '';
      $confirm  = $_POST['confirm_password'] ?? '';

      if (!$username || !$password || !$confirm) {
        $error = 'All fields are required.';
      } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores. No spaces.';
      } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
      } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
      } else {
        // Check username is not taken
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
          $error = 'That username is already taken. Please choose another.';
        } else {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          // New sign-ups are always Business Manager (role = 'owner' in DB)
          $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'owner')")
              ->execute([$username, $hash]);
          $success = true;
        }
      }
    }
    ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        ✓ Account created successfully! You can now sign in.
      </div>
      <a href="/inventory_system/pages/home.php" class="btn btn-primary btn-full" style="margin-top:0.75rem;">
        Sign In
      </a>

    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="e.g. jane_manager"
                 pattern="[a-zA-Z0-9_]+"
                 title="Letters, numbers and underscores only"
                 required autofocus autocomplete="username">
          <p class="hint">Letters, numbers, and underscores only. No spaces.</p>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Min. 6 characters" minlength="6" required autocomplete="new-password">
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 placeholder="Repeat your password" required autocomplete="new-password">
        </div>

        <!-- Role info box -->
        <div style="background:var(--gray-50);border:1px solid var(--gray-300);padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.8rem;color:var(--gray-600);line-height:1.6;">
          <strong style="color:var(--gray-800);">Account type: Business Manager</strong><br>
          You will have full access to manage products, view reports, register new staff accounts, and monitor inventory. To create a Shop Attendant account, ask your system Admin to register one for you after you sign in.
        </div>

        <button type="submit" class="btn btn-primary btn-full">Create Account</button>
      </form>

      <p style="margin-top:1rem;text-align:center;font-size:0.8rem;">
        Already have an account?
        <a href="/inventory_system/pages/home.php" style="color:var(--gray-700);font-weight:600;">Sign In</a>
      </p>
      <p style="margin-top:0.5rem;text-align:center;font-size:0.8rem;">
        <a href="/inventory_system/pages/home.php" style="color:var(--gray-400);">← Back to Home</a>
      </p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>