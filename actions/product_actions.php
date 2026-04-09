<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
if (!in_array($_SESSION['role'], ['owner','admin'])) { header("Location: /inventory_system/dashboard.php"); exit(); }
require __DIR__.'/../config/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock_quantity'] ?? 0);

    if (!$name || $price < 0 || $stock < 0) {
        header("Location: /inventory_system/pages/products.php"); exit();
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock_quantity) VALUES (?,?,?,?)");
    $stmt->execute([$name, $category, $price, $stock]);
    header("Location: /inventory_system/pages/products.php?added=1");

} elseif ($action === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock_quantity'] ?? 0);

    if (!$id || !$name) {
        header("Location: /inventory_system/pages/products.php"); exit();
    }

    $stmt = $pdo->prepare(
        "UPDATE products SET name=?, category=?, price=?, stock_quantity=? WHERE id=?"
    );
    $stmt->execute([$name, $category, $price, $stock, $id]);
    header("Location: /inventory_system/pages/edit_product.php?id=$id&saved=1");

} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    }
    header("Location: /inventory_system/pages/products.php?deleted=1");

} else {
    header("Location: /inventory_system/pages/products.php");
}
exit();