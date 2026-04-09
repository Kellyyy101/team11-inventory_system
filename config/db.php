<?php
$host   = 'localhost';
$dbname = 'inventory_db';
$user   = 'root';
$pass   = ''; // XAMPP default – no password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div style='font-family:monospace;padding:2rem;background:#fef2f2;color:#dc2626;border:2px solid #fca5a5;margin:2rem;'>
         <strong>Database connection failed:</strong><br>" . htmlspecialchars($e->getMessage()) .
         "<br><br>Make sure XAMPP MySQL is running and the database <code>inventory_db</code> exists.
         </div>");
}