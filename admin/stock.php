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


if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    
    // 先获取要删除的卡密信息
    $stmt = $db->prepare("SELECT product_id FROM faka_stock WHERE id = ?");
    $stmt->execute([$id]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($card) {
        $product_id = $card['product_id'];
        
        // 删除卡密
        $db->prepare("DELETE FROM faka_stock WHERE id = ?")->execute([$id]);
        
        // 更新商品表的stock字段（使用实时统计）
        $stmt = $db->prepare("SELECT COUNT(*) FROM faka_stock WHERE product_id = ? AND status = 0");
        $stmt->execute([$product_id]);
        $newStock = $stmt->fetchColumn();
        
        $db->prepare("UPDATE faka_product SET stock = ? WHERE id = ?")->execute([$newStock, $product_id]);
    }
    
    header('Location: stock.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_add'])) {
    $product_id = (int)$_POST['product_id'];
    $card_list = trim($_POST['card_list']);
    $lines = explode("\n", $card_list);
    $count = 0;
    $stmt = $db->prepare("INSERT INTO faka_stock (product_id, card_info, status, create_time) VALUES (?, ?, 0, NOW())");
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) { 
            $stmt->execute([$product_id, $line]); 
            $count++; 
        }
    }
    
    // 改成实时统计，而不是 stock + count
    $stmt = $db->prepare("SELECT COUNT(*) FROM faka_stock WHERE product_id = ? AND status = 0");
    $stmt->execute([$product_id]);
    $realStock = $stmt->fetchColumn();
    $db->prepare("UPDATE faka_product SET stock = ? WHERE id = ?")->execute([$realStock, $product_id]);
    
    header('Location: stock.php?msg=添加了 ' . $count . ' 个卡密');
    exit;
}
$products = $db->query("SELECT id, name FROM faka_product ORDER BY id DESC")->fetchAll();
$product_id = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
if ($product_id > 0) {
    $stmt = $db->prepare("SELECT s.*, p.name as product_name FROM faka_stock s LEFT JOIN faka_product p ON s.product_id = p.id WHERE s.product_id = ? ORDER BY s.id DESC");
    $stmt->execute([$product_id]);
} else {
    $stmt = $db->query("SELECT s.*, p.name as product_name FROM faka_stock s LEFT JOIN faka_product p ON s.product_id = p.id ORDER BY s.id DESC LIMIT 100");
}
$stocks = $stmt->fetchAll();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>库存管理 - 管理后台</title>
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
                <a href="stock.php" class="active"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span><span>库存管理</span></a>
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
                <h2>库存管理</h2>
            </div>
            <?php if ($msg): ?><div class="alert-success"><?php echo $msg; ?></div><?php endif; ?>
            <div class="card"><h3>批量添加卡密</h3>
                <form method="POST">
                    <div class="form-group"><label>选择商品</label><select name="product_id" required><option value="">请选择商品</option><?php foreach ($products as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>卡密列表（一行一个）</label><textarea name="card_list" rows="5" placeholder="账号:密码&#10;卡号:卡密&#10;ABC123-EFG456" required></textarea></div>
                    <button type="submit" name="batch_add" class="btn btn-primary">批量导入</button>
                </form>
            </div>
            <div class="card">
                <h3>卡密列表</h3>
                <div class="filter-bar"><form method="GET"><select name="pid" onchange="this.form.submit()"><option value="0">全部商品</option><?php foreach ($products as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo $product_id == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?></select></form></div>
                <table class="data-table"><thead><tr><th>ID</th><th>商品</th><th>卡密内容</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead>
                <tbody><?php foreach ($stocks as $s): ?><tr><td><?php echo $s['id']; ?></td><td><?php echo htmlspecialchars($s['product_name']); ?></td><td style="max-width:300px; word-break:break-all;"><?php echo htmlspecialchars(mb_substr($s['card_info'], 0, 50)); ?></td><td><span class="badge <?php echo $s['status'] ? 'pending' : 'success'; ?>"><?php echo $s['status'] ? '已售' : '未售'; ?></span></td><td><?php echo $s['create_time']; ?></td><td><a href="?del=<?php echo $s['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('确定删除？')">删除</a></td></tr><?php endforeach; ?></tbody></table>
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
