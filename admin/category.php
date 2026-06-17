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
    $db->prepare("DELETE FROM faka_category WHERE id = ?")->execute([$id]);
    header('Location: category.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name']);
    $sort = (int)$_POST['sort'];
    $status = isset($_POST['status']) ? 1 : 0;
    if ($id > 0) {
        $db->prepare("UPDATE faka_category SET name=?, sort=?, status=? WHERE id=?")->execute([$name, $sort, $status, $id]);
    } else {
        $db->prepare("INSERT INTO faka_category (name, sort, status) VALUES (?, ?, ?)")->execute([$name, $sort, $status]);
    }
    header('Location: category.php');
    exit;
}

$categories = $db->query("SELECT * FROM faka_category ORDER BY sort ASC, id ASC")->fetchAll();
$editCate = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM faka_category WHERE id = ?");
    $stmt->execute([$id]);
    $editCate = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>分类管理 - 管理后台</title>
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
                <a href="category.php" class="active"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span><span>分类管理</span></a>
                <a href="payment.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span><span>支付配置</span></a>
                <a href="setting.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H5.78a1.65 1.65 0 0 0-1.51 1 1.65 1.65 0 0 0 .33 1.82l.07.08A10 10 0 0 0 12 17.66a10 10 0 0 0 6.18-2.58z"/></svg></span><span>网站设置</span></a>
                <a href="logout.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span>退出登录</span></a>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <button class="mobile-menu-btn" id="mobileMenuBtn">☰ 菜单</button>
                <h2>分类管理</h2>
                <button class="btn btn-primary" onclick="showAddForm()">+ 添加分类</button>
            </div>
            <div class="card">
                <table class="data-table"><thead><tr><th>ID</th><th>分类名称</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
                <tbody><?php foreach ($categories as $c): ?><tr><td><?php echo $c['id']; ?></td><td><?php echo htmlspecialchars($c['name']); ?></td><td><?php echo $c['sort']; ?></td><td><span class="badge <?php echo $c['status'] ? 'success' : 'pending'; ?>"><?php echo $c['status'] ? '启用' : '禁用'; ?></span></td><td><a href="?edit=<?php echo $c['id']; ?>" class="btn-sm">编辑</a><a href="?del=<?php echo $c['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('确定删除？')">删除</a></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </main>
    </div>
    <div id="cateModal" class="modal" style="display:none"><div class="modal-overlay"></div><div class="modal-container"><div class="modal-header"><h3 id="modalTitle">添加分类</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form method="POST"><input type="hidden" name="id" id="cateId"><div class="modal-body"><div class="form-group"><label>分类名称</label><input type="text" name="name" id="cateName" required></div><div class="form-group"><label>排序（数字越小越靠前）</label><input type="number" name="sort" id="cateSort" value="0"></div><div class="checkbox-group"><input type="checkbox" name="status" id="cateStatus" value="1" checked><label>启用</label></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button><button type="submit" class="btn btn-primary">保存</button></div></form></div></div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script>
        const modal = document.getElementById('cateModal');
        function showAddForm() { document.getElementById('modalTitle').innerText = '添加分类'; document.getElementById('cateId').value = ''; document.getElementById('cateName').value = ''; document.getElementById('cateSort').value = '0'; document.getElementById('cateStatus').checked = true; modal.style.display = 'flex'; }
        <?php if ($editCate): ?>
        function showEditForm() { document.getElementById('modalTitle').innerText = '编辑分类'; document.getElementById('cateId').value = '<?php echo $editCate['id']; ?>'; document.getElementById('cateName').value = '<?php echo htmlspecialchars($editCate['name']); ?>'; document.getElementById('cateSort').value = '<?php echo $editCate['sort']; ?>'; document.getElementById('cateStatus').checked = <?php echo $editCate['status'] ? 'true' : 'false'; ?>; modal.style.display = 'flex'; }
        showEditForm();
        <?php endif; ?>
        function closeModal() { modal.style.display = 'none'; window.location.href = 'category.php'; }
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
