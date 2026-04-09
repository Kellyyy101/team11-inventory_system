<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
require __DIR__.'/../config/db.php';

if ($_SESSION['role'] === 'admin') {
    header("Location: /inventory_system/pages/sales.php?error=Admins+cannot+process+sales"); exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /inventory_system/pages/sales.php"); exit();
}

$cartJson = $_POST['cart_data'] ?? '';
if (!$cartJson) {
    header("Location: /inventory_system/pages/sales.php?error=Empty+cart"); exit();
}

$cart = json_decode($cartJson, true);
if (!$cart || !is_array($cart)) {
    header("Location: /inventory_system/pages/sales.php?error=Invalid+cart+data"); exit();
}

try {
    $pdo->beginTransaction();

    foreach ($cart as $item) {
        $productId = (int)$item['id'];
        $qty       = (int)$item['qty'];
        $total     = (float)$item['total'];

        // Re-verify stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product || $product['stock_quantity'] < $qty) {
            $pdo->rollBack();
            header("Location: /inventory_system/pages/sales.php?error=Insufficient+stock+for+item+ID+$productId");
            exit();
        }

        // Record sale
        $ins = $pdo->prepare(
            "INSERT INTO sales (product_id, user_id, quantity_sold, total_price) VALUES (?,?,?,?)"
        );
        $ins->execute([$productId, $_SESSION['user_id'], $qty, $total]);

        // Deduct stock
        $upd = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $upd->execute([$qty, $productId]);
    }

    $pdo->commit();
    header("Location: /inventory_system/pages/sales.php?success=1");

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: /inventory_system/pages/sales.php?error=" . urlencode($e->getMessage()));
}
exit();