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
require_once __DIR__ . '/../includes/payment.php';

$db = Database::getInstance();
$payment = new Payment($db);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $pid = trim($_POST['pid'] ?? '');
    $key = trim($_POST['key'] ?? '');
    $gateway = trim($_POST['gateway'] ?? 'https://pay.liohg.top/submit.php');
    $status = isset($_POST['status']) ? 1 : 0;
    if (!empty($type)) {
        Payment::updateConfig($db, $type, ['pid' => $pid, 'key' => $key, 'gateway' => $gateway]);
        Payment::toggleStatus($db, $type, $status);
        $message = '保存成功';
        $payment = new Payment($db);
    }
}

$alipayConfig = $payment->getPaymentConfig('alipay');
$wxpayConfig = $payment->getPaymentConfig('wxpay');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>支付配置 - 管理后台</title>
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
                <a href="order.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span><span>订单管理</span></a>
                <a href="category.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span><span>分类管理</span></a>
                <a href="payment.php" class="active"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span><span>支付配置</span></a>
                <a href="setting.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H5.78a1.65 1.65 0 0 0-1.51 1 1.65 1.65 0 0 0 .33 1.82l.07.08A10 10 0 0 0 12 17.66a10 10 0 0 0 6.18-2.58z"/></svg></span><span>网站设置</span></a>
                <a href="logout.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span>退出登录</span></a>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn" id="mobileMenuBtn">☰ 菜单</button>
                <h2>支付配置</h2>
            </div>
            <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>
            <div class="card"><h3>支付宝支付</h3>
                <form method="POST"><input type="hidden" name="type" value="alipay"><div class="form-group"><label>商户ID (PID)</label><input type="text" name="pid" value="<?php echo htmlspecialchars($alipayConfig['pid'] ?? ''); ?>"></div><div class="form-group"><label>商户密钥 (KEY)</label><input type="text" name="key" value="<?php echo htmlspecialchars($alipayConfig['key'] ?? ''); ?>"></div><div class="form-group"><label>支付网关</label><input type="text" name="gateway" value="<?php echo htmlspecialchars($alipayConfig['gateway'] ?? 'https://pay.liohg.top/submit.php'); ?>"></div><div class="checkbox-group"><input type="checkbox" name="status" id="alipay_status" value="1" <?php echo ($alipayConfig['status'] ?? 0) == 1 ? 'checked' : ''; ?>><label>启用支付宝支付</label></div><button type="submit" class="btn btn-primary">保存</button></form>
            </div>
            <div class="card"><h3>微信支付</h3>
                <form method="POST"><input type="hidden" name="type" value="wxpay"><div class="form-group"><label>商户ID (PID)</label><input type="text" name="pid" value="<?php echo htmlspecialchars($wxpayConfig['pid'] ?? ''); ?>"></div><div class="form-group"><label>商户密钥 (KEY)</label><input type="text" name="key" value="<?php echo htmlspecialchars($wxpayConfig['key'] ?? ''); ?>"></div><div class="form-group"><label>支付网关</label><input type="text" name="gateway" value="<?php echo htmlspecialchars($wxpayConfig['gateway'] ?? 'https://pay.liohg.top/submit.php'); ?>"></div><div class="checkbox-group"><input type="checkbox" name="status" id="wxpay_status" value="1" <?php echo ($wxpayConfig['status'] ?? 0) == 1 ? 'checked' : ''; ?>><label>启用微信支付</label></div><button type="submit" class="btn btn-primary">保存</button></form>
            </div>
            <div class="card"><h3>回调地址</h3><p>异步通知地址：<code><?php echo SITE_URL; ?>api/notify.php</code></p><p>同步跳转地址：<code><?php echo SITE_URL; ?>api/return.php</code></p></div>
        </main>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script>
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
