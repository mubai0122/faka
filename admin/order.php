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

$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;
if ($status >= 0) {
    $stmt = $db->prepare("SELECT * FROM faka_order WHERE status = ? ORDER BY id DESC");
    $stmt->execute([$status]);
} else {
    $stmt = $db->query("SELECT * FROM faka_order ORDER BY id DESC");
}
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>订单管理 - 管理后台</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-toggle">◀</div>
            <h3><span>发卡网后台</span></h3>
            <nav>
                <a href="index.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 3h18v18H3zM3 9h18M9 21V9"/></svg></span><span>仪表盘</span></a>
                <a href="product.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></span><span>商品管理</span></a>
                <a href="stock.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span><span>库存管理</span></a>
                <a href="order.php" class="active"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span><span>订单管理</span></a>
                <a href="category.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span><span>分类管理</span></a>
                <a href="payment.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span><span>支付配置</span></a>
                <a href="setting.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H5.78a1.65 1.65 0 0 0-1.51 1 1.65 1.65 0 0 0 .33 1.82l.07.08A10 10 0 0 0 12 17.66a10 10 0 0 0 6.18-2.58z"/></svg></span><span>网站设置</span></a>
                <a href="logout.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span>退出登录</span></a>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn" id="mobileMenuBtn">☰ 菜单</button>
                <h2>订单管理</h2>
                <div class="filter-bar"><a href="?status=-1" class="filter-link">全部</a><a href="?status=0" class="filter-link">待支付</a><a href="?status=1" class="filter-link">已完成</a></div>
            </div>
            <div class="card">
                <table class="data-table"><thead><tr><th>订单号</th><th>商品</th><th>金额</th><th>买家邮箱</th><th>状态</th><th>支付方式</th><th>创建时间</th><th>操作</th></tr></thead>
                <tbody><?php foreach ($orders as $o): ?><tr><td><?php echo $o['order_no']; ?></td><td><?php echo htmlspecialchars($o['product_name']); ?></td><td>¥<?php echo $o['price']; ?></td><td><?php echo htmlspecialchars($o['buyer_email']); ?></td><td><span class="badge <?php echo $o['status'] ? 'success' : 'pending'; ?>"><?php echo $o['status'] ? '已完成' : '待支付'; ?></span></td><td><?php echo $o['pay_type'] == 'alipay' ? '支付宝' : '微信支付'; ?></td><td><?php echo $o['create_time']; ?></td><td><button class="btn-sm" onclick="showOrder(<?php echo htmlspecialchars(json_encode($o)); ?>)">详情</button></td></tr><?php endforeach; ?></tbody></table>
            </div>
        </main>
    </div>
    <div id="orderModal" class="modal" style="display:none"><div class="modal-overlay"></div><div class="modal-container"><div class="modal-header"><h3>订单详情</h3><button class="modal-close" onclick="closeModal()">&times;</button></div><div class="modal-body" id="orderDetail"></div></div></div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script>
        function showOrder(order) {
            document.getElementById('orderDetail').innerHTML = `<div class="info-row"><span class="info-label">订单号：</span><span>${order.order_no}</span></div><div class="info-row"><span class="info-label">商品名称：</span><span>${order.product_name}</span></div><div class="info-row"><span class="info-label">金额：</span><span>¥${order.price}</span></div><div class="info-row"><span class="info-label">买家姓名：</span><span>${order.buyer_name || '-'}</span></div><div class="info-row"><span class="info-label">买家QQ：</span><span>${order.buyer_qq || '-'}</span></div><div class="info-row"><span class="info-label">买家邮箱：</span><span>${order.buyer_email || '-'}</span></div><div class="info-row"><span class="info-label">卡密信息：</span><pre style="margin-top:8px">${order.card_info || '未生成'}</pre></div><div class="info-row"><span class="info-label">创建时间：</span><span>${order.create_time}</span></div><div class="info-row"><span class="info-label">支付时间：</span><span>${order.pay_time || '-'}</span></div>`;
            document.getElementById('orderModal').style.display = 'flex';
        }
        function closeModal() { document.getElementById('orderModal').style.display = 'none'; }
        (function() {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const overlay = document.getElementById('sidebarOverlay');
            if (toggleBtn) toggleBtn.onclick = () => sidebar.classList.toggle('collapsed');
            if (mobileMenuBtn) mobileMenuBtn.onclick = () => { sidebar.classList.add('open'); if(overlay) overlay.classList.add('active'); };
            if (overlay) overlay.onclick = () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); };
            window.onresize = () => { if(window.innerWidth > 768) { sidebar.classList.remove('open'); if(overlay) overlay.classList.remove('active'); } };
        })();
    </script>
</body>
</html>
