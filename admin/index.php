<?php
/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';

$db = Database::getInstance();

// 核心数据
$result = $db->query("SELECT COUNT(*) FROM faka_product");
$productCount = $result->fetchColumn();

$result = $db->query("SELECT COUNT(*) FROM faka_order");
$orderCount = $result->fetchColumn();

$result = $db->query("SELECT SUM(price) FROM faka_order WHERE status = 1");
$totalAmount = $result->fetchColumn() ?: 0;

$result = $db->query("SELECT COUNT(*) FROM faka_stock WHERE status = 0");
$stockCount = $result->fetchColumn();

// 今日数据
$result = $db->query("SELECT COUNT(*) FROM faka_order WHERE DATE(create_time) = CURDATE()");
$todayOrders = $result->fetchColumn();

$result = $db->query("SELECT SUM(price) FROM faka_order WHERE status = 1 AND DATE(pay_time) = CURDATE()");
$todaySales = $result->fetchColumn() ?: 0;

// 昨日数据
$result = $db->query("SELECT COUNT(*) FROM faka_order WHERE DATE(create_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$yesterdayOrders = $result->fetchColumn();

$result = $db->query("SELECT SUM(price) FROM faka_order WHERE status = 1 AND DATE(pay_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$yesterdaySales = $result->fetchColumn() ?: 0;

// 环比增长率
$orderGrowth = $yesterdayOrders > 0 ? round(($todayOrders - $yesterdayOrders) / $yesterdayOrders * 100, 1) : ($todayOrders > 0 ? 100 : 0);
$salesGrowth = $yesterdaySales > 0 ? round(($todaySales - $yesterdaySales) / $yesterdaySales * 100, 1) : ($todaySales > 0 ? 100 : 0);

// 待处理
$result = $db->query("SELECT COUNT(*) FROM faka_order WHERE status = 0");
$pendingOrders = $result->fetchColumn();

$result = $db->query("SELECT COUNT(*) FROM faka_stock WHERE status = 0 AND product_id IN (SELECT id FROM faka_product WHERE stock < 10)");
$lowStock = $result->fetchColumn();

// 近7天销售额
$result = $db->query("
    SELECT DATE(pay_time) as date, SUM(price) as total 
    FROM faka_order 
    WHERE status = 1 AND pay_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(pay_time)
    ORDER BY date ASC
");
$chartData = $result->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$amounts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('m/d', strtotime($date));
    $found = false;
    foreach ($chartData as $row) {
        if ($row['date'] == $date) {
            $amounts[] = (float)$row['total'];
            $found = true;
            break;
        }
    }
    if (!$found) $amounts[] = 0;
}

// 最近7天订单量
$result = $db->query("
    SELECT DATE(create_time) as date, COUNT(*) as total 
    FROM faka_order 
    WHERE create_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(create_time)
    ORDER BY date ASC
");
$orderChartData = $result->fetchAll(PDO::FETCH_ASSOC);

$orderCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $found = false;
    foreach ($orderChartData as $row) {
        if ($row['date'] == $date) {
            $orderCounts[] = (int)$row['total'];
            $found = true;
            break;
        }
    }
    if (!$found) $orderCounts[] = 0;
}

// 支付方式占比
$result = $db->query("
    SELECT pay_type, COUNT(*) as count 
    FROM faka_order 
    WHERE status = 1 AND pay_type IS NOT NULL
    GROUP BY pay_type
");
$paymentStats = $result->fetchAll(PDO::FETCH_ASSOC);
$paymentLabels = [];
$paymentData = [];
foreach ($paymentStats as $stat) {
    $paymentLabels[] = $stat['pay_type'] == 'alipay' ? '支付宝' : '微信支付';
    $paymentData[] = $stat['count'];
}

// 热门商品TOP5
$result = $db->query("
    SELECT product_name, SUM(price) as total_sales, COUNT(*) as order_count
    FROM faka_order 
    WHERE status = 1
    GROUP BY product_id, product_name
    ORDER BY total_sales DESC
    LIMIT 5
");
$hotProducts = $result->fetchAll(PDO::FETCH_ASSOC);

// 最近10条订单
$result = $db->query("SELECT * FROM faka_order ORDER BY id DESC LIMIT 10");
$recentOrders = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>仪表盘 - 管理后台</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-toggle">◀</div>
            <h3><span>发卡网后台</span></h3>
            <nav>
                <a href="index.php" class="active"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 3h18v18H3zM3 9h18M9 21V9"/></svg></span><span>仪表盘</span></a>
                <a href="product.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></span><span>商品管理</span></a>
                <a href="stock.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span><span>库存管理</span></a>
                <a href="order.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span><span>订单管理</span></a>
                <a href="category.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span><span>分类管理</span></a>
                <a href="payment.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span><span>支付配置</span></a>
                <a href="setting.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H5.78a1.65 1.65 0 0 0-1.51 1 1.65 1.65 0 0 0 .33 1.82l.07.08A10 10 0 0 0 12 17.66a10 10 0 0 0 6.18-2.58z"/></svg></span><span>网站设置</span></a>
                <a href="logout.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span>退出登录</span></a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn" id="mobileMenuBtn">☰ 菜单</button>
                <h2>仪表盘</h2>
                <div class="admin-info">
                    <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                    <span class="admin-time"><?php echo date('Y-m-d H:i'); ?></span>
                </div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-value">¥<?php echo number_format($totalAmount, 2); ?></div>
                        <div class="stat-label">总销售额</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $orderCount; ?></div>
                        <div class="stat-label">总订单数</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎫</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $productCount; ?></div>
                        <div class="stat-label">商品总数</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stockCount; ?></div>
                        <div class="stat-label">剩余库存</div>
                    </div>
                </div>
            </div>
            
            <!-- 今日数据卡片 -->
            <div class="stats-grid-small">
                <div class="stat-card-small">
                    <div class="stat-small-label">今日订单</div>
                    <div class="stat-small-value"><?php echo $todayOrders; ?></div>
                    <div class="stat-small-trend <?php echo $orderGrowth >= 0 ? 'up' : 'down'; ?>">
                        <?php echo $orderGrowth >= 0 ? '↑' : '↓'; ?> <?php echo abs($orderGrowth); ?>%
                        <span class="trend-text">较昨日</span>
                    </div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-small-label">今日销售额</div>
                    <div class="stat-small-value">¥<?php echo number_format($todaySales, 2); ?></div>
                    <div class="stat-small-trend <?php echo $salesGrowth >= 0 ? 'up' : 'down'; ?>">
                        <?php echo $salesGrowth >= 0 ? '↑' : '↓'; ?> <?php echo abs($salesGrowth); ?>%
                        <span class="trend-text">较昨日</span>
                    </div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-small-label">待处理订单</div>
                    <div class="stat-small-value"><?php echo $pendingOrders; ?></div>
                    <div class="stat-small-note">需及时处理</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-small-label">库存预警</div>
                    <div class="stat-small-value"><?php echo $lowStock; ?></div>
                    <div class="stat-small-note">低于10件</div>
                </div>
            </div>
            
            <!-- 双图表 -->
            <div class="charts-row">
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>近7天销售额趋势</h3>
                        <span class="card-sub">单位：元</span>
                    </div>
                    <canvas id="salesChart" height="220"></canvas>
                </div>
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>近7天订单量趋势</h3>
                        <span class="card-sub">单位：笔</span>
                    </div>
                    <canvas id="ordersChart" height="220"></canvas>
                </div>
            </div>
            
            <!-- 第二行图表 -->
            <div class="charts-row">
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>支付方式占比</h3>
                    </div>
                    <canvas id="paymentChart" height="200"></canvas>
                </div>
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>热销商品 TOP5</h3>
                    </div>
                    <div class="hot-products">
                        <?php if (empty($hotProducts)): ?>
                            <div class="empty-text">暂无销售数据</div>
                        <?php else: ?>
                            <?php foreach ($hotProducts as $index => $product): ?>
                            <div class="hot-item">
                                <div class="hot-rank rank-<?php echo $index + 1; ?>"><?php echo $index + 1; ?></div>
                                <div class="hot-info">
                                    <div class="hot-name"><?php echo htmlspecialchars(mb_substr($product['product_name'], 0, 20)); ?></div>
                                    <div class="hot-stats"><?php echo $product['order_count']; ?> 笔订单</div>
                                </div>
                                <div class="hot-sales">¥<?php echo number_format($product['total_sales'], 2); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 最近订单 -->
            <div class="card">
                <div class="card-header">
                    <h3>最近订单</h3>
                    <a href="order.php" class="view-all">查看全部 →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>订单号</th><th>商品</th><th>金额</th><th>买家</th><th>状态</th><th>时间</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><code><?php echo $order['order_no']; ?></code></td>
                            <td><?php echo htmlspecialchars(mb_substr($order['product_name'], 0, 20)); ?></td>
                            <td><span class="price">¥<?php echo $order['price']; ?></span></td>
                            <td><?php echo htmlspecialchars($order['buyer_email'] ?? '-'); ?></td>
                            <td><span class="badge <?php echo $order['status'] ? 'success' : 'pending'; ?>"><?php echo $order['status'] ? '已完成' : '待支付'; ?></span></td>
                            <td><?php echo date('m-d H:i', strtotime($order['create_time'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6" class="text-center">暂无订单</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        // 侧边栏功能
        (function() {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                });
            }
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    if (overlay) overlay.classList.add('active');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                });
            }
            
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('open');
                    if (overlay) overlay.classList.remove('active');
                }
            });
        })();

        // 销售额曲线图
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: '销售额',
                    data: <?php echo json_encode($amounts); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.05)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return '¥' + ctx.parsed.y.toFixed(2); } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { callback: function(v) { return '¥' + v; } } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 订单量柱状图
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: '订单量',
                    data: <?php echo json_encode($orderCounts); ?>,
                    backgroundColor: 'rgba(75,85,99,0.7)',
                    borderRadius: 6,
                    barPercentage: 0.65
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.parsed.y + ' 笔订单'; } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 支付方式占比饼图
        <?php if (!empty($paymentData)): ?>
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($paymentLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($paymentData); ?>,
                    backgroundColor: ['#3b82f6', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }
                }
            }
        });
        <?php else: ?>
        document.getElementById('paymentChart').style.display = 'none';
        <?php endif; ?>
    </script>
</body>
</html>
