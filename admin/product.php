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
    // 获取图片路径并删除文件
    $stmt = $db->prepare("SELECT image FROM faka_product WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if ($product && $product['image'] && file_exists(__DIR__ . '/..' . $product['image'])) {
        unlink(__DIR__ . '/..' . $product['image']);
    }
    $db->prepare("DELETE FROM faka_product WHERE id = ?")->execute([$id]);
    header('Location: product.php');
    exit;
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE faka_product SET status = 1 - status WHERE id = ?")->execute([$id]);
    header('Location: product.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $category_id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $detail = trim($_POST['detail']);
    $price = (float)$_POST['price'];
    $status = isset($_POST['status']) ? 1 : 0;
    $image = $_POST['old_image'] ?? '';
    
    // 处理图片上传
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = date('YmdHis') . '_' . rand(1000, 9999) . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image = '/uploads/' . $filename;
                // 删除旧图片
                if (!empty($_POST['old_image']) && file_exists(__DIR__ . '/..' . $_POST['old_image'])) {
                    unlink(__DIR__ . '/..' . $_POST['old_image']);
                }
            }
        }
    }
    
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE faka_product SET category_id=?, name=?, description=?, detail=?, price=?, status=?, image=?, update_time=NOW() WHERE id=?");
        $stmt->execute([$category_id, $name, $description, $detail, $price, $status, $image, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO faka_product (category_id, name, description, detail, price, status, image, create_time) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$category_id, $name, $description, $detail, $price, $status, $image]);
    }
    header('Location: product.php');
    exit;
}

$products = $db->query("SELECT p.*, c.name as cate_name FROM faka_product p LEFT JOIN faka_category c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
$categories = $db->query("SELECT * FROM faka_category ORDER BY sort ASC")->fetchAll();

$editProduct = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM faka_product WHERE id = ?");
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>商品管理 - 管理后台</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .image-preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 8px;
            border: 1px solid #e5e7eb;
        }
        .current-image-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-toggle">◀</div>
            <h3><span>发卡网后台</span></h3>
            <nav>
                <a href="index.php"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 3h18v18H3zM3 9h18M9 21V9"/></svg></span><span>仪表盘</span></a>
                <a href="product.php" class="active"><span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></span><span>商品管理</span></a>
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
                <h2>商品管理</h2>
                <button class="btn btn-primary" onclick="showAddForm()">+ 添加商品</button>
            </div>
            <div class="card">
                <table class="data-table">
                    <thead><tr><th>ID</th><th>图片</th><th>分类</th><th>商品名称</th><th>价格</th><th>销量</th><th>状态</th><th>操作</th></tr></thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php if($p['image']): ?><img src="<?php echo $p['image']; ?>" class="current-image-img"><?php else: ?>-<?php endif; ?></td>
                            <td><?php echo htmlspecialchars($p['cate_name'] ?? '未分类'); ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td>¥<?php echo $p['price']; ?></td>
                            <td><?php echo $p['sales']; ?></td>
                            <td><span class="badge <?php echo $p['status'] ? 'success' : 'pending'; ?>"><?php echo $p['status'] ? '上架' : '下架'; ?></span></td>
                            <td><a href="?edit=<?php echo $p['id']; ?>" class="btn-sm">编辑</a><a href="?toggle=<?php echo $p['id']; ?>" class="btn-sm"><?php echo $p['status'] ? '下架' : '上架'; ?></a><a href="?del=<?php echo $p['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('确定删除？')">删除</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <div id="productModal" class="modal" style="display:none">
        <div class="modal-overlay"></div>
        <div class="modal-container" style="max-width: 600px;">
            <div class="modal-header"><h3 id="modalTitle">添加商品</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="productId">
                <input type="hidden" name="old_image" id="oldImage">
                <div class="modal-body">
                    <div class="form-group"><label>商品分类</label><select name="category_id" id="categoryId" required><option value="0">请选择分类</option><?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>商品名称</label><input type="text" name="name" id="productName" required></div>
                    <div class="form-group"><label>商品简介</label><textarea name="description" id="productDesc" rows="2" placeholder="显示在首页商品卡片中，支持换行"></textarea></div>
                    <div class="form-group"><label>商品详情</label><textarea name="detail" id="productDetail" rows="4" placeholder="显示在点击商品后的详情弹窗中"></textarea></div>
                    <div class="form-group"><label>价格 (¥)</label><input type="number" name="price" id="productPrice" step="0.01" required></div>
                    <div class="form-group"><label>商品图片</label><input type="file" name="image" id="productImage" accept="image/jpeg,image/png,image/gif,image/webp"><div id="imagePreview" class="current-image"></div></div>
                    <div class="checkbox-group"><input type="checkbox" name="status" id="productStatus" value="1" checked><label>上架</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button><button type="submit" class="btn btn-primary">保存</button></div>
            </form>
        </div>
    </div>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        const modal = document.getElementById('productModal');
        
        function showAddForm() {
            document.getElementById('modalTitle').innerText = '添加商品';
            document.getElementById('productId').value = '';
            document.getElementById('categoryId').value = '0';
            document.getElementById('productName').value = '';
            document.getElementById('productDesc').value = '';
            document.getElementById('productDetail').value = '';
            document.getElementById('productPrice').value = '';
            document.getElementById('productStatus').checked = true;
            document.getElementById('oldImage').value = '';
            document.getElementById('imagePreview').innerHTML = '';
            document.getElementById('productImage').value = '';
            modal.style.display = 'flex';
        }
        
        <?php if ($editProduct): ?>
        function showEditForm() {
            document.getElementById('modalTitle').innerText = '编辑商品';
            document.getElementById('productId').value = '<?php echo $editProduct['id']; ?>';
            document.getElementById('categoryId').value = '<?php echo $editProduct['category_id']; ?>';
            document.getElementById('productName').value = '<?php echo htmlspecialchars($editProduct['name']); ?>';
            document.getElementById('productDesc').value = '<?php echo htmlspecialchars(str_replace(["\r\n", "\n"], '\n', $editProduct['description'] ?? '')); ?>';
            document.getElementById('productDetail').value = '<?php echo htmlspecialchars(str_replace(["\r\n", "\n"], '\n', $editProduct['detail'] ?? '')); ?>';
            document.getElementById('productPrice').value = '<?php echo $editProduct['price']; ?>';
            document.getElementById('productStatus').checked = <?php echo $editProduct['status'] ? 'true' : 'false'; ?>;
            document.getElementById('oldImage').value = '<?php echo $editProduct['image']; ?>';
            if ('<?php echo $editProduct['image']; ?>') {
                document.getElementById('imagePreview').innerHTML = '<img src="<?php echo $editProduct['image']; ?>" class="image-preview">';
            }
            modal.style.display = 'flex';
        }
        showEditForm();
        <?php endif; ?>
        
        function closeModal() { modal.style.display = 'none'; window.location.href = 'product.php'; }
        
        // 图片预览
        document.getElementById('productImage')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('imagePreview').innerHTML = '<img src="' + ev.target.result + '" class="image-preview">';
                };
                reader.readAsDataURL(file);
            }
        });
        
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
