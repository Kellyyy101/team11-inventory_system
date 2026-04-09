<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory_system/index.php"); exit(); }
if (!in_array($_SESSION['role'], ['owner','admin'])) { header("Location: /inventory_system/dashboard.php"); exit(); }
require __DIR__.'/../config/db.php';
require __DIR__.'/../includes/header.php';

// Weekly sales per day
$weeklySales = $pdo->query("
  SELECT DAYNAME(sale_date) AS day_name,
         DAYOFWEEK(sale_date) AS day_num,
         SUM(total_price) AS total
  FROM sales
  WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
  GROUP BY DAYNAME(sale_date), DAYOFWEEK(sale_date)
  ORDER BY day_num
")->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$weeklyTotal = array_sum(array_column($weeklySales, 'total'));
$dailyAvg    = count($weeklySales) > 0 ? $weeklyTotal / count($weeklySales) : 0;
$bestDay     = count($weeklySales) > 0
               ? array_reduce($weeklySales, fn($carry, $d) => (!$carry || $d['total'] > $carry['total']) ? $d : $carry)
               : null;

// Recent transactions
$recentTxns = $pdo->query("
  SELECT s.id, s.sale_date, s.total_price, s.quantity_sold,
         p.name AS product, u.username AS attendant
  FROM sales s
  JOIN products p ON s.product_id = p.id
  JOIN users u ON s.user_id = u.id
  ORDER BY s.sale_date DESC
  LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Chart max for scaling
$maxSales = count($weeklySales) > 0 ? max(array_column($weeklySales, 'total')) : 1;
if ($maxSales == 0) $maxSales = 1;
?>

<div class="page-header">
  <h1 style="font-size: 45px;color: black">Sales Reports</h1>
  <p style="font-size: 20px;">Weekly overview and recent transaction history</p>
</div>
<button onclick="printReport()" class="btn btn-outline no-print" style="margin-top:0.25rem;">
    &#128438; Print Report
  </button> 
  <style>
@media print {
  .no-print, .sidebar, .sidebar-footer, .btn { display: none !important; }
  .main-content { margin-left: 0 !important; }
  .page-inner { padding: 0 !important; }
  .card { border: 1px solid #ccc !important; box-shadow: none !important; page-break-inside: avoid; }
  body { background: white !important; }
  .chart-bar { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .stats-grid { grid-template-columns: repeat(3,1fr) !important; }
}
</style>

<!-- Summary Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label" style="font-size: 20px;">Weekly Total</div>
    <div class="stat-value">KES <?= number_format($weeklyTotal, 0) ?></div>
    <div class="stat-sub" style="font-size: 15px;">Last 7 days</div>
  </div>
  <div class="stat-card">
    <div class="stat-label" style="font-size: 20px;">Daily Average</div>
    <div class="stat-value">KES <?= number_format($dailyAvg, 0) ?></div>
    <div class="stat-sub" style="font-size: 15px;">Per active day</div>
  </div>
  <div class="stat-card">
    <div class="stat-label" style="font-size: 20px;">Best Day</div>
    <div class="stat-value"><?= $bestDay ? substr($bestDay['day_name'], 0, 3) : '—' ?></div>
    <div class="stat-sub" style="font-size: 20px;"><?= $bestDay ? 'KES ' . number_format($bestDay['total'], 0) : 'No data yet' ?></div>
  </div>
</div>

<!-- Daily Sales Chart -->
<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-header">
    <div>
      <h3 style="font-size: 30px;">Daily Sales Totals</h3>
      <p style="font-size: 15px;">Revenue breakdown for the past 7 days</p>
    </div>
  </div>
  <div class="card-body">
    <div class="chart-wrap">
      <?php if (empty($weeklySales)): ?>
        <div class="empty-state"><p>No sales data for the last 7 days</p></div>
      <?php else: ?>
        <div class="chart-bars">
          <?php foreach ($weeklySales as $day):
            $heightPct = ($day['total'] / $maxSales) * 100;
          ?>
          <div class="chart-bar-col">
            <div class="chart-bar-val">KES <?= number_format($day['total'] / 1000, 1) ?>k</div>
            <div class="chart-bar" style="height:<?= $heightPct ?>%;" title="<?= $day['day_name'] ?>: KES <?= number_format($day['total'],2) ?>"></div>
            <div class="chart-bar-label"><?= substr($day['day_name'], 0, 3) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="chart-axis"></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Recent Transactions -->
<div class="card">
  <div class="card-header">
    <div>
      <h3 style="font-size: 30px;">Recent Transactions</h3>
      <p style="font-size: 15px;">Last 20 sales recorded in the system</p>
    </div>
  </div>
  <div class="card-body-flush">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Product</th>
            <th>Date</th>
            <th>Time</th>
            <th class="center">Qty</th>
            <th class="right">Amount</th>
            <th>Attendant</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentTxns)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">No transactions yet</td></tr>
          <?php else: ?>
          <?php foreach ($recentTxns as $t): ?>
          <tr>
            <td><span class="mono">TXN<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
            <td><?= htmlspecialchars($t['product']) ?></td>
            <td><?= date('d M Y', strtotime($t['sale_date'])) ?></td>
            <td style="color:var(--gray-500);"><?= date('H:i', strtotime($t['sale_date'])) ?></td>
            <td class="center"><span class="qty-badge"><?= $t['quantity_sold'] ?></span></td>
            <td class="right"><strong>KES <?= number_format($t['total_price'], 2) ?></strong></td>
            <td><?= htmlspecialchars($t['attendant']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

 <script>

function printReport() {
  window.print();
}
</script>
