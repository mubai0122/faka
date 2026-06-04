<?php
/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */

// 检测安装状态
if (!file_exists(__DIR__ . '/install.lock')) {
    header('Location: install.php');
    exit;
}

// 加载配置文件
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment.php';

// 获取请求参数
$action = $_GET['action'] ?? 'index';
$category_id = isset($_GET['cate']) ? (int)$_GET['cate'] : 0;
$order_no = $_GET['order_no'] ?? '';

// 初始化数据库
$db = Database::getInstance();
$payment = new Payment($db);

// 获取网站设置
$settings = getSettings($db);
$categories = getCategories($db);
$products = getProducts($db, $category_id);

$notice = $settings['notice'] ?? '欢迎光临本站，请放心购买！';
$availablePayments = $payment->getAvailablePayments();

// 处理订单查询
$orderInfo = null;
if ($action === 'query' && !empty($_POST['order_no'])) {
    $orderInfo = getOrderByNo($db, trim($_POST['order_no']));
} elseif ($action === 'result' && !empty($order_no)) {
    $orderInfo = getOrderByNo($db, $order_no);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="keywords" content="<?php echo htmlspecialchars($settings['site_keywords'] ?? '发卡网,自动发卡'); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_description'] ?? '专业的自动发卡平台'); ?>">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? '个人发卡网'); ?> - 自动发卡平台</title>
    <link rel="stylesheet" href="/assets/css/index.css">
</head>
<body>
    <div class="app">
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <h1><?php echo htmlspecialchars($settings['site_name'] ?? '发卡网'); ?></h1>
                    </div>
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <nav class="nav" id="mainNav">
                        <a href="?action=index" class="<?php echo $action === 'index' ? 'active' : ''; ?>">首页</a>
                        <a href="?action=query-page" class="<?php echo $action === 'query-page' ? 'active' : ''; ?>">订单查询</a>
                        <a href="?action=contact" class="<?php echo $action === 'contact' ? 'active' : ''; ?>">联系客服</a>
                    </nav>
                </div>
            </div>
        </header>

        <?php if ($notice): ?>
        <div class="notice-bar">
            <div class="container">
                <div class="notice-content">
                    <svg class="notice-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notice-text"><?php echo htmlspecialchars($notice); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <main class="main">
            <div class="container">
                <?php if ($action === 'query-page' || $action === 'query'): ?>
                <div class="page-card">
                    <div class="page-title">
                        <h2>订单查询</h2>
                        <p>输入订单号查询您的卡密信息</p>
                    </div>
                    <form method="POST" action="?action=query" class="query-form" id="queryForm">
                        <div class="form-group">
                            <label>订单号</label>
                            <input type="text" name="order_no" placeholder="请输入订单号" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">查询订单</button>
                    </form>
                    <?php if ($orderInfo): ?>
                    <div class="order-result">
                        <h3>订单详情</h3>
                        <div class="order-info">
                            <div class="info-row"><span class="info-label">订单号：</span><span class="info-value"><?php echo htmlspecialchars($orderInfo['order_no']); ?></span></div>
                            <div class="info-row"><span class="info-label">商品名称：</span><span class="info-value"><?php echo htmlspecialchars($orderInfo['product_name']); ?></span></div>
                            <div class="info-row"><span class="info-label">购买金额：</span><span class="info-value">¥ <?php echo number_format($orderInfo['price'], 2); ?></span></div>
                            <div class="info-row"><span class="info-label">订单状态：</span><span class="info-value status-badge status-<?php echo $orderInfo['status'] == 1 ? 'success' : 'pending'; ?>"><?php echo $orderInfo['status'] == 1 ? '已完成' : '待支付'; ?></span></div>
                            <?php if ($orderInfo['status'] == 1 && $orderInfo['card_info']): ?>
                            <div class="info-row"><span class="info-label">卡密信息：</span><div class="card-info-box"><pre><?php echo htmlspecialchars($orderInfo['card_info']); ?></pre><button class="btn-copy" data-clipboard="<?php echo htmlspecialchars($orderInfo['card_info']); ?>">复制卡密</button></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($action === 'query' && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="empty-state"><p>未查询到订单，请检查订单号是否正确</p></div>
                    <?php endif; ?>
                </div>
                <?php elseif ($action === 'result' && $orderInfo): ?>
                <div class="page-card success-card">
                    <div class="success-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <h2>支付成功！</h2>
                    <p>您的卡密已生成，请妥善保管</p>
                    <div class="card-info-box"><div class="card-info-title">卡密信息</div><pre class="card-content"><?php echo htmlspecialchars($orderInfo['card_info']); ?></pre><button class="btn-copy" data-clipboard="<?php echo htmlspecialchars($orderInfo['card_info']); ?>">复制卡密</button></div>
                    <div class="order-detail"><p><strong>订单号：</strong><?php echo htmlspecialchars($orderInfo['order_no']); ?></p><p><strong>商品：</strong><?php echo htmlspecialchars($orderInfo['product_name']); ?></p><p><strong>金额：</strong>¥ <?php echo number_format($orderInfo['price'], 2); ?></p></div>
                    <div class="action-buttons"><a href="?action=index" class="btn btn-secondary">继续购买</a><a href="?action=query-page" class="btn btn-outline">查询其他订单</a></div>
                </div>
                <?php elseif ($action === 'contact'): ?>
                <div class="page-card">
                    <div class="page-title">
                        <h2>联系客服</h2>
                        <p>欢迎联系我们，我们将竭诚为您服务</p>
                    </div>
                    <div class="contact-list">
                        <?php
                        $contacts = [
                            'kf_wechat' => ['name' => '微信', 'img' => '1.png'],
                            'kf_qq' => ['name' => 'QQ', 'img' => '2.png'],
                            'kf_workwx' => ['name' => '企业微信', 'img' => '3.png'],
                            'kf_dingtalk' => ['name' => '钉钉', 'img' => '4.png'],
                            'kf_feishu' => ['name' => '飞书', 'img' => '5.png'],
                            'kf_telegram' => ['name' => 'Telegram', 'img' => '6.png'],
                            'kf_whatsapp' => ['name' => 'WhatsApp', 'img' => '7.png'],
                            'kf_twitter' => ['name' => 'Twitter / X', 'img' => '8.png'],
                            'kf_instagram' => ['name' => 'Instagram', 'img' => '9.png'],
                            'kf_facebook' => ['name' => 'Facebook', 'img' => '10.png'],
                            'kf_phone' => ['name' => '电话', 'img' => '11.png'],
                            'kf_email' => ['name' => '邮箱', 'img' => '12.png']
                        ];
                        foreach ($contacts as $key => $contact):
                            $value = $settings[$key] ?? '';
                            if (empty($value)) continue;
                        ?>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <img src="/assets/images/<?php echo $contact['img']; ?>" alt="<?php echo $contact['name']; ?>">
                            </div>
                            <div class="contact-content">
                                <div class="contact-name"><?php echo $contact['name']; ?></div>
                                <div class="contact-value"><?php echo nl2br(htmlspecialchars($value)); ?></div>
                                <button class="contact-copy" data-clipboard="<?php echo htmlspecialchars($value); ?>" onclick="copyContact(this)">复制</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="category-bar">
                    <div class="category-list">
                        <a href="?" class="cate-item <?php echo $category_id == 0 ? 'active' : ''; ?>">全部商品</a>
                        <?php foreach ($categories as $cate): ?>
                        <a href="?cate=<?php echo $cate['id']; ?>" class="cate-item <?php echo $category_id == $cate['id'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($cate['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="product-grid">
                    <?php if (empty($products)): ?>
                    <div class="empty-state"><p>暂无商品，请稍后再来</p></div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <div class="product-card" data-id="<?php echo $product['id']; ?>" onclick="showProductDetail(<?php echo $product['id']; ?>)">
                            <div class="product-header">
                                <?php if ($product['image']): ?>
                                <img src="<?php echo $product['image']; ?>" class="product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php endif; ?>
                                <div class="product-title-wrap">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <?php if ($product['stock'] <= 0): ?>
                                    <span class="product-badge out-stock">售罄</span>
                                    <?php elseif ($product['stock'] < 10): ?>
                                    <span class="product-badge low-stock">仅剩<?php echo $product['stock']; ?>件</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="product-desc"><?php echo $product['description']; ?></p>
                            <div class="product-footer">
                                <div class="product-price">
                                    <span class="price-symbol">¥</span>
                                    <span class="price-value"><?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                <button class="btn-buy <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>" 
                                        data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-price="<?php echo $product['price']; ?>"
                                        onclick="event.stopPropagation()"
                                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                    立即购买
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? '发卡网'); ?> 版权所有</p>
            </div>
        </footer>
    </div>

    <div class="modal" id="buyModal">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header"><h3>购买商品</h3><button class="modal-close">&times;</button></div>
            <div class="modal-body">
                <div class="product-info"><span class="product-name" id="modalProductName"></span><span class="product-price" id="modalProductPrice"></span></div>
                <form id="buyForm">
                    <input type="hidden" name="product_id" id="productId">
                    <div class="form-group"><label>邮箱 <span class="required">*</span></label><input type="email" name="buyer_email" placeholder="用于接收卡密" required></div>
                    <div class="form-group"><label>QQ</label><input type="text" name="buyer_qq" placeholder="选填，便于联系"></div>
                    <div class="form-group"><label>姓名</label><input type="text" name="buyer_name" placeholder="选填"></div>
                    <div class="form-group">
                        <label>支付方式</label>
                        <div class="pay-cards">
                            <?php if (empty($availablePayments)): ?>
                            <span class="text-muted">暂无可用支付方式，请联系管理员</span>
                            <?php else: ?>
                                <?php foreach ($availablePayments as $pay): ?>
                                <label class="pay-card">
                                    <input type="radio" name="pay_type" value="<?php echo $pay['type']; ?>" <?php echo $pay['type'] == 'alipay' ? 'checked' : ''; ?>>
                                    <div class="pay-card-content">
                                        <div class="pay-card-icon">
                                            <img src="/assets/images/<?php echo $pay['type'] == 'alipay' ? '13.png' : '1.png'; ?>" alt="<?php echo htmlspecialchars($pay['name']); ?>">
                                        </div>
                                        <div class="pay-card-info">
                                            <div class="pay-card-name"><?php echo htmlspecialchars($pay['name']); ?></div>
                                            <div class="pay-card-desc">安全支付，极速到账</div>
                                        </div>
                                        <div class="pay-card-check">
                                            <span class="check-mark">✓</span>
                                        </div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary modal-cancel">取消</button><button class="btn btn-primary" id="submitBuy">确认购买</button></div>
        </div>
    </div>

    <div class="modal" id="detailModal">
        <div class="modal-overlay"></div>
        <div class="modal-container" style="max-width: 450px;">
            <div class="modal-header">
                <h3>商品详情</h3>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailContent">
                <div style="text-align:center; padding:20px;">加载中...</div>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p>处理中...</p>
    </div>

    <script src="/assets/js/index.js"></script>
</body>
</html>
