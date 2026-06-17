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
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_keywords' => trim($_POST['site_keywords'] ?? ''),
        'site_description' => trim($_POST['site_description'] ?? ''),
        'kf_wechat' => trim($_POST['kf_wechat'] ?? ''),
        'kf_qq' => trim($_POST['kf_qq'] ?? ''),
        'kf_workwx' => trim($_POST['kf_workwx'] ?? ''),
        'kf_dingtalk' => trim($_POST['kf_dingtalk'] ?? ''),
        'kf_feishu' => trim($_POST['kf_feishu'] ?? ''),
        'kf_telegram' => trim($_POST['kf_telegram'] ?? ''),
        'kf_whatsapp' => trim($_POST['kf_whatsapp'] ?? ''),
        'kf_twitter' => trim($_POST['kf_twitter'] ?? ''),
        'kf_instagram' => trim($_POST['kf_instagram'] ?? ''),
        'kf_facebook' => trim($_POST['kf_facebook'] ?? ''),
        'kf_phone' => trim($_POST['kf_phone'] ?? ''),
        'notice' => trim($_POST['notice'] ?? ''),
        'kf_email' => trim($_POST['kf_email'] ?? '')
    ];
    
    $stmt = $db->prepare("REPLACE INTO faka_setting (`key`, `value`) VALUES (?, ?)");
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    $message = '保存成功';
}

$stmt = $db->query("SELECT `key`, `value` FROM faka_setting");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>网站设置 - 管理后台</title>
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
                <a href="payment.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span><span>支付配置</span></a>
                <a href="setting.php" class="active"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H5.78a1.65 1.65 0 0 0-1.51 1 1.65 1.65 0 0 0 .33 1.82l.07.08A10 10 0 0 0 12 17.66a10 10 0 0 0 6.18-2.58z"/></svg></span><span>网站设置</span></a>
                <a href="logout.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span>退出登录</span></a>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn" id="mobileMenuBtn">☰ 菜单</button>
                <h2>网站设置</h2>
            </div>
            <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>
            <div class="card">
                <form method="POST">
                    <h3>基本设置</h3>
                    <div class="form-group"><label>网站名称</label><input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? '个人发卡网'); ?>"></div>
                    <div class="form-group"><label>网站关键词 (SEO)</label><input type="text" name="site_keywords" value="<?php echo htmlspecialchars($settings['site_keywords'] ?? ''); ?>"></div>
                    <div class="form-group"><label>网站描述 (SEO)</label><textarea name="site_description" rows="2"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea></div>
                    <div class="form-group"><label>网站公告</label><textarea name="notice" rows="3"><?php echo htmlspecialchars($settings['notice'] ?? ''); ?></textarea></div>
                    
                    <h3>联系方式</h3>
                    <div class="form-group"><label>微信</label><input type="text" name="kf_wechat" value="<?php echo htmlspecialchars($settings['kf_wechat'] ?? ''); ?>" placeholder="微信号"></div>
                    <div class="form-group"><label>QQ</label><input type="text" name="kf_qq" value="<?php echo htmlspecialchars($settings['kf_qq'] ?? ''); ?>" placeholder="QQ号码"></div>
                    <div class="form-group"><label>企业微信</label><input type="text" name="kf_workwx" value="<?php echo htmlspecialchars($settings['kf_workwx'] ?? ''); ?>" placeholder="企业微信ID"></div>
                    <div class="form-group"><label>钉钉</label><input type="text" name="kf_dingtalk" value="<?php echo htmlspecialchars($settings['kf_dingtalk'] ?? ''); ?>" placeholder="钉钉号"></div>
                    <div class="form-group"><label>飞书</label><input type="text" name="kf_feishu" value="<?php echo htmlspecialchars($settings['kf_feishu'] ?? ''); ?>" placeholder="飞书账号"></div>
                    <div class="form-group"><label>Telegram</label><input type="text" name="kf_telegram" value="<?php echo htmlspecialchars($settings['kf_telegram'] ?? ''); ?>" placeholder="@username"></div>
                    <div class="form-group"><label>WhatsApp</label><input type="text" name="kf_whatsapp" value="<?php echo htmlspecialchars($settings['kf_whatsapp'] ?? ''); ?>" placeholder="+86 1234567890"></div>
                    <div class="form-group"><label>Twitter / X</label><input type="text" name="kf_twitter" value="<?php echo htmlspecialchars($settings['kf_twitter'] ?? ''); ?>" placeholder="@username"></div>
                    <div class="form-group"><label>Instagram</label><input type="text" name="kf_instagram" value="<?php echo htmlspecialchars($settings['kf_instagram'] ?? ''); ?>" placeholder="用户名"></div>
                    <div class="form-group"><label>Facebook</label><input type="text" name="kf_facebook" value="<?php echo htmlspecialchars($settings['kf_facebook'] ?? ''); ?>" placeholder="主页ID或链接"></div>
                    <div class="form-group"><label>电话</label><input type="text" name="kf_phone" value="<?php echo htmlspecialchars($settings['kf_phone'] ?? ''); ?>" placeholder="客服电话"></div>
                    <div class="form-group"><label>邮箱</label><input type="email" name="kf_email" value="<?php echo htmlspecialchars($settings['kf_email'] ?? ''); ?>" placeholder="客服邮箱"></div>
                    
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
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
