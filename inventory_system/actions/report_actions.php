<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: /inventory_system/index.php"); 
    exit(); 
}
if (!in_array($_SESSION['role'], ['owner','admin'])) { 
    header("Location: /inventory_system/dashboard.php"); 
    exit(); 
}
require __DIR__.'/../config/db.php';

// Only process if there's an action parameter
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $export_type = $_GET['export_type'] ?? 'full';
    
    // Set filename with timestamp
    $filename = "inventory_report_" . date('Y-m-d_H-i-s') . ".csv";
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    switch ($export_type) {
        case 'sales_summary':
            // Export sales summary
            fputcsv($output, ['Sales Summary Report']);
            fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            fputcsv($output, ['Period:', 'Last 30 days']);
            fputcsv($output, []);
            
            $sql = "SELECT 
                        DATE(sale_date) as date,
                        COUNT(*) as transactions,
                        SUM(quantity_sold) as items_sold,
                        SUM(total_price) as revenue
                    FROM sales 
                    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(sale_date)
                    ORDER BY date DESC";
            
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['Date', 'Transactions', 'Items Sold', 'Revenue (KES)']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['transactions'],
                    $row['items_sold'],
                    number_format($row['revenue'], 2)
                ]);
            }
            break;
            
        case 'products_report':
            // Export products report
            fputcsv($output, ['Products Inventory Report']);
            fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            $sql = "SELECT 
                        id, name, category, price, stock_quantity,
                        CASE 
                            WHEN stock_quantity = 0 THEN 'Out of Stock'
                            WHEN stock_quantity < 10 THEN 'Low Stock'
                            ELSE 'In Stock'
                        END as status
                    FROM products 
                    ORDER BY name";
            
            $stmt = $pdo->query($sql);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['Product ID', 'Name', 'Category', 'Price (KES)', 'Stock', 'Status']);
            foreach ($products as $product) {
                fputcsv($output, [
                    str_pad($product['id'], 4, '0', STR_PAD_LEFT),
                    $product['name'],
                    $product['category'] ?? 'General',
                    number_format($product['price'], 2),
                    $product['stock_quantity'],
                    $product['status']
                ]);
            }
            break;
            
        case 'top_products':
            // Export top selling products
            fputcsv($output, ['Top Selling Products Report']);
            fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            $sql = "SELECT 
                        p.name,
                        p.category,
                        COUNT(s.id) as times_sold,
                        SUM(s.quantity_sold) as total_sold,
                        SUM(s.total_price) as revenue
                    FROM sales s
                    JOIN products p ON s.product_id = p.id
                    GROUP BY p.id, p.name, p.category
                    ORDER BY revenue DESC
                    LIMIT 20";
            
            $stmt = $pdo->query($sql);
            $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['Product Name', 'Category', 'Times Sold', 'Total Quantity', 'Revenue (KES)']);
            foreach ($top_products as $product) {
                fputcsv($output, [
                    $product['name'],
                    $product['category'] ?? 'General',
                    $product['times_sold'],
                    $product['total_sold'],
                    number_format($product['revenue'], 2)
                ]);
            }
            break;
            
        case 'low_stock':
            // Export low stock products
            fputcsv($output, ['Low Stock Alert Report']);
            fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            $sql = "SELECT 
                        id, name, category, price, stock_quantity,
                        CASE 
                            WHEN stock_quantity = 0 THEN 'Out of Stock'
                            ELSE 'Low Stock'
                        END as status
                    FROM products 
                    WHERE stock_quantity < 10
                    ORDER BY stock_quantity ASC";
            
            $stmt = $pdo->query($sql);
            $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['Product ID', 'Name', 'Category', 'Price (KES)', 'Current Stock', 'Status']);
            foreach ($low_stock as $product) {
                fputcsv($output, [
                    str_pad($product['id'], 4, '0', STR_PAD_LEFT),
                    $product['name'],
                    $product['category'] ?? 'General',
                    number_format($product['price'], 2),
                    $product['stock_quantity'],
                    $product['status']
                ]);
            }
            break;
            
        case 'attendant_performance':
            // Export attendant performance
            fputcsv($output, ['Attendant Performance Report']);
            fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            $sql = "SELECT 
                        u.username,
                        COUNT(s.id) as transactions,
                        SUM(s.quantity_sold) as items_sold,
                        SUM(s.total_price) as revenue,
                        AVG(s.total_price) as avg_transaction
                    FROM sales s
                    JOIN users u ON s.user_id = u.id
                    GROUP BY u.id, u.username
                    ORDER BY revenue DESC";
            
            $stmt = $pdo->query($sql);
            $attendants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['Attendant', 'Transactions', 'Items Sold', 'Total Revenue (KES)', 'Avg Transaction (KES)']);
            foreach ($attendants as $attendant) {
                fputcsv($output, [
                    $attendant['username'],
                    $attendant['transactions'],
                    $attendant['items_sold'],
                    number_format($attendant['revenue'], 2),
                    number_format($attendant['avg_transaction'], 2)
                ]);
            }
            break;
            
        case 'full':
        default:
            // Export full report with all data
            fputcsv($output, ['COMPREHENSIVE INVENTORY & SALES REPORT']);
            fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
            fputcsv($output, ['Generated by:', $_SESSION['username'] ?? 'System']);
            fputcsv($output, []);
            
            // Add summary statistics
            $sql_summary = "SELECT 
                                COUNT(DISTINCT DATE(sale_date)) as active_days,
                                COUNT(*) as total_transactions,
                                SUM(quantity_sold) as total_items_sold,
                                SUM(total_price) as total_revenue,
                                AVG(total_price) as avg_transaction
                            FROM sales";
            
            $stmt = $pdo->query($sql_summary);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['SYSTEM SUMMARY STATISTICS']);
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Active Days', $summary['active_days'] ?? 0]);
            fputcsv($output, ['Total Transactions', $summary['total_transactions'] ?? 0]);
            fputcsv($output, ['Total Items Sold', $summary['total_items_sold'] ?? 0]);
            fputcsv($output, ['Total Revenue', 'KES ' . number_format($summary['total_revenue'] ?? 0, 2)]);
            fputcsv($output, ['Average Transaction Value', 'KES ' . number_format($summary['avg_transaction'] ?? 0, 2)]);
            fputcsv($output, []);
            
            // Add inventory summary
            $sql_inventory = "SELECT 
                                COUNT(*) as total_products,
                                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                                SUM(CASE WHEN stock_quantity > 0 AND stock_quantity < 10 THEN 1 ELSE 0 END) as low_stock,
                                SUM(CASE WHEN stock_quantity >= 10 THEN 1 ELSE 0 END) as well_stocked,
                                SUM(stock_quantity) as total_items_in_stock
                            FROM products";
            
            $stmt = $pdo->query($sql_inventory);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            fputcsv($output, ['INVENTORY SUMMARY']);
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Total Products', $inventory['total_products']]);
            fputcsv($output, ['Out of Stock', $inventory['out_of_stock']]);
            fputcsv($output, ['Low Stock (<10)', $inventory['low_stock']]);
            fputcsv($output, ['Well Stocked', $inventory['well_stocked']]);
            fputcsv($output, ['Total Items in Stock', $inventory['total_items_in_stock']]);
            fputcsv($output, []);
            
            // Add recent transactions
            fputcsv($output, ['RECENT TRANSACTIONS (Last 20)']);
            fputcsv($output, ['Date', 'Product', 'Quantity', 'Total (KES)', 'Attendant']);
            
            $sql_recent = "SELECT 
                                s.sale_date,
                                p.name as product_name,
                                s.quantity_sold,
                                s.total_price,
                                u.username
                            FROM sales s
                            JOIN products p ON s.product_id = p.id
                            JOIN users u ON s.user_id = u.id
                            ORDER BY s.sale_date DESC
                            LIMIT 20";
            
            $stmt = $pdo->query($sql_recent);
            $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($recent as $transaction) {
                fputcsv($output, [
                    date('Y-m-d H:i', strtotime($transaction['sale_date'])),
                    $transaction['product_name'],
                    $transaction['quantity_sold'],
                    number_format($transaction['total_price'], 2),
                    $transaction['username']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit();
}

// If no action parameter or invalid action, redirect to reports page
header("Location: /inventory_system/pages/reports.php");
exit();
?>