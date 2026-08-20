<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LiteStock — Smart Inventory for Small Businesses</title>
  <link rel="stylesheet" href="/inventory_system/css/style.css">
  <style>
    /* ── Home page specific styles ── */
    body { background: var(--gray-100); display: block; }

    .home-hero {
      background: var(--gray-900);
      color: var(--white);
      padding: 3.5rem 2rem 3rem;
      text-align: center;
    }
    .home-hero .hero-logo {
      display: flex; align-items: center; justify-content: center;
      gap: 0.75rem; margin-bottom: 1.5rem;
    }
    .home-hero h1 {
      font-size: 2.4rem; font-weight: 700; color: var(--white);
      letter-spacing: -0.02em; margin: 0;
    }
    .home-hero .tagline {
      font-size: 1rem; color: var(--gray-400);
      margin-top: 0.75rem; font-family: 'IBM Plex Mono', monospace;
      letter-spacing: 0.02em;
    }

    .home-body {
      max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem;
      display: grid; grid-template-columns: 1fr 380px; gap: 2.5rem;
      align-items: center;
    }

    /* ── Left: About + Features ── */
    .home-about h2 {
      font-size: 1.3rem; font-weight: 700; color: var(--gray-900);
      margin-bottom: 0.75rem;
    }
    .home-about p {
      font-size: 0.9rem; color: var(--gray-600); line-height: 1.7;
      margin-bottom: 1.5rem;
    }

    .features-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
      margin-bottom: 2rem;
    }
    .feature-card {
      background: var(--white); border: 2px solid var(--gray-300);
      padding: 1.1rem 1.25rem; border-radius: 2px;
    }
    .feature-card .feat-icon {
      font-size: 1.4rem; margin-bottom: 0.5rem; display: block;
    }
    .feature-card h4 {
      font-size: 0.85rem; font-weight: 700; color: var(--gray-900);
      margin-bottom: 0.3rem;
    }
    .feature-card p {
      font-size: 0.78rem; color: var(--gray-500); line-height: 1.5; margin: 0;
    }

    .advantages-list { margin-bottom: 2rem; }
    .advantages-list h2 {
      font-size: 1.1rem; font-weight: 700; color: var(--gray-900);
      margin-bottom: 0.75rem;
    }
    .adv-item {
      display: flex; align-items: flex-start; gap: 0.75rem;
      padding: 0.6rem 0; border-bottom: 1px solid var(--gray-200);
    }
    .adv-item:last-child { border-bottom: none; }
    .adv-check {
      width: 20px; height: 20px; background: var(--gray-800);
      color: var(--white); display: flex; align-items: center;
      justify-content: center; font-size: 0.7rem; flex-shrink: 0;
      margin-top: 0.1rem;
    }
    .adv-item p { font-size: 0.85rem; color: var(--gray-700); margin: 0; line-height: 1.5; }
    .adv-item strong { color: var(--gray-900); }

    /* ── Right: Sign-in card ── */
    .home-signin {
      background: var(--white); border: 2px solid var(--gray-400);
      padding: 2rem; position: sticky; top: 1.5rem;
    }
    .home-signin h3 {
      font-size: 1rem; font-weight: 700; color: var(--gray-900);
      margin-bottom: 0.25rem;
    }
    .home-signin .signin-sub {
      font-size: 0.78rem; color: var(--gray-500);
      font-family: 'IBM Plex Mono', monospace;
      margin-bottom: 1.5rem;
    }
    .home-signin .divider {
      text-align: center; position: relative; margin: 1.25rem 0;
    }
    .home-signin .divider::before {
      content: ''; position: absolute; top: 50%; left: 0; right: 0;
      height: 1px; background: var(--gray-300);
    }
    .home-signin .divider span {
      background: var(--white); padding: 0 0.5rem;
      font-size: 0.75rem; color: var(--gray-500); position: relative;
    }
    .forgot-link {
      display: block; text-align: right; font-size: 0.78rem;
      color: var(--gray-500); text-decoration: none; margin-top: 0.3rem;
    }
    .forgot-link:hover { color: var(--gray-900); text-decoration: underline; }

    .star-row { display: flex; gap: 0.25rem; margin-bottom: 0.75rem; }
    .star-btn {
      background: none; border: none; font-size: 1.4rem; cursor: pointer;
      color: var(--gray-300); padding: 0; transition: color 0.1s;
    }
    .star-btn.active, .star-btn:hover { color: #f59e0b; }

    /* ── Footer ── */
    .home-footer {
      text-align: center; padding: 1.5rem;
      font-size: 0.75rem; color: var(--gray-400);
      border-top: 1px solid var(--gray-300);
      font-family: 'IBM Plex Mono', monospace;
    }

    @media (max-width: 800px) {
      .home-body { grid-template-columns: 1fr; }
      .home-signin { position: static; }
      .features-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ── Hero ── -->
<div class="home-hero">
  <div class="hero-logo">
    
    <h1>LiteStock</h1>
  </div>
  <p class="tagline">Simple inventory &amp; sales management for small retail businesses</p>
</div>

<!-- ── Body ── -->
<div class="home-body">

  <!-- LEFT COLUMN -->
  <div>

    <!-- About -->
    <div class="home-about">
      <h2>What is LiteStock?</h2>
      <p>LiteStock is a lightweight, web-based inventory and sales management system built specifically 
        for small convenience kiosks. It replaces error-prone notebooks and spreadsheets with a fast, 
        easy-to-use digital system that keeps your stock accurate, your sales tracked and your business
        data organised - all without needing an internet subscription or expensive software!</p>
      <p>if your run a kiosk, LiteStock gives you the tools
        to manage your products, record every sale, monitor low stock in real time and generate 
        reports that help you make smarter restocking and pricing decisions.</p>
    </div>

    <!-- Feature cards -->
    <div class="features-grid">
      <div class="feature-card">
        <span class="feat-icon">📦</span>
        <h4>Product Management</h4>
        <p>Add, edit, and organise your products with categories, prices, and stock quantities..</p>
      </div>
      <div class="feature-card">
        <span class="feat-icon">🛒</span>
        <h4>Sales Point (POS)</h4>
        <p>Record sales quickly by searching product names.Totals are automatically calculated and 
          stock is deducted the moment a sale is confirmed.</p>
      </div>
      <div class="feature-card">
        <span class="feat-icon">⚠️</span>
        <h4>Low Stock Alerts</h4>
        <p>The dashboard shows a real-time alert for every product below the stock threshold so you are reminded
           restock before you run out.</p>
      </div>
      <div class="feature-card">
        <span class="feat-icon">📊</span>
        <h4>Sales Reports</h4>
        <p>View weekly revenue charts and a full transaction history. Print reports directly from 
          the browser for record-keeping or review.</p>
      </div>
      <div class="feature-card">
        <span class="feat-icon">👥</span>
        <h4>Role-Based Access</h4>
        <p>Separate accounts for Managers, Shop Attendants and Admins. Each role sees only what 
          they need ensuring no accidental changes to sensitive data.</p>
      </div>
    

    <!-- Advantages -->
    <div class="advantages-list">
      <h2>Why LiteStock?</h2>
      <div class="adv-item">
        <div class="adv-check">✓</div>
        <p><strong>Free to use.</strong> No subscriptions, no licensing fees. Runs entirely on your own computer using XAMPP.</p>
      </div>
      <div class="adv-item">
        <div class="adv-check">✓</div>
        <p><strong>Works offline.</strong> LiteStock runs on your local network - no internet connection required during operation.</p>
      </div>
      <div class="adv-item">
        <div class="adv-check">✓</div>
        <p><strong>Easy to learn.</strong> Designed for shop attendants with no technical background. Clear labels, simple navigation, no training needed.</p>
      </div>
      <div class="adv-item">
        <div class="adv-check">✓</div>
        <p><strong>Data stays safe.</strong> All data is stored on your own machine - not on a third-party cloud server.</p>
      </div>
      <div class="adv-item">
        <div class="adv-check">✓</div>
        <p><strong>Built for small businesses.</strong> LiteStock has no complex features you don't need. It does exactly what a small shop requires.</p>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- RIGHT COLUMN: Sign-in + Feedback -->
  <div>
    <!-- Sign-in card -->
    <div class="home-signin">
      <h3>Sign In to LiteStock</h3>
      <p class="signin-sub">Access your dashboard</p>

      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" style="margin-bottom:1rem;">Invalid username or password.</div>
      <?php endif; ?>
      <?php if (isset($_GET['logout'])): ?>
        <div class="alert alert-success" style="margin-bottom:1rem;">You have been logged out.</div>
      <?php endif; ?>
      <?php if (isset($_GET['reset'])): ?>
        <div class="alert alert-success" style="margin-bottom:1rem;">Password updated successfully. Please sign in.</div>
      <?php endif; ?>

      <form action="/inventory_system/actions/auth.php" method="POST">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus autocomplete="username">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
          <a href="/inventory_system/forgot_password.php" class="forgot-link">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">Sign In</button>
      </form>
      <div class="divider"><span>or</span></div>
      <a href="/inventory_system/pages/signup.php" class="btn btn-outline btn-full">Create an Account</a>
    </div>

    

  </div><!-- /right column -->

</div><!-- /home-body -->



<script>
function setRating(val) {
  document.getElementById('ratingInput').value = val;
  document.querySelectorAll('.star-btn').forEach((btn, i) => {
    btn.classList.toggle('active', i < val);
  });
}
</script>
</body>
</html>