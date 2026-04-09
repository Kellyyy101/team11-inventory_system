<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
if (!in_array($_SESSION['role'], ['owner','admin'])) { header("Location: /inventory_system/dashboard.php"); exit(); }
require __DIR__.'/../config/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username  = trim($_POST['username'] ?? '');
    $role      = $_POST['role'] ?? '';
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validate
    if (!$username || !$role || !$password) {
        header("Location: /inventory_system/pages/register_user.php?error=All+fields+are+required"); exit();
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        header("Location: /inventory_system/pages/register_user.php?error=Username+can+only+contain+letters%2C+numbers+and+underscores"); exit();
    }
    if (!in_array($role, ['owner','attendant','admin'])) {
        header("Location: /inventory_system/pages/register_user.php?error=Invalid+role"); exit();
    }
    if (strlen($password) < 6) {
        header("Location: /inventory_system/pages/register_user.php?error=Password+must+be+at+least+6+characters"); exit();
    }
    if ($password !== $confirm) {
        header("Location: /inventory_system/pages/register_user.php?error=Passwords+do+not+match"); exit();
    }

    // Check username is unique
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        header("Location: /inventory_system/pages/register_user.php?error=Username+already+exists"); exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
    $stmt->execute([$username, $hash, $role]);

    header("Location: /inventory_system/pages/users.php?registered=1");

} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    // Cannot delete yourself
    if ($id && $id !== $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    }
    header("Location: /inventory_system/pages/users.php?deleted=1");

} else {
    header("Location: /inventory_system/pages/users.php");
}
exit();