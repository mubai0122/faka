<?php
/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$buyer_name = trim($_POST['buyer_name'] ?? '');
$buyer_qq = trim($_POST['buyer_qq'] ?? '');
$buyer_email = trim($_POST['buyer_email'] ?? '');
$pay_type = trim($_POST['pay_type'] ?? 'alipay');

if ($product_id <= 0) {
    echo json_encode(['code' => 0, 'msg' => '商品参数错误']);
    exit;
}

if (empty($buyer_email) || !filter_var($buyer_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['code' => 0, 'msg' => '请填写正确的邮箱地址']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $stmt = $db->prepare("SELECT * FROM faka_product WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['code' => 0, 'msg' => '商品不存在或已下架']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM faka_stock WHERE product_id = ? AND status = 0");
    $stmt->execute([$product_id]);
    $stockCount = $stmt->fetchColumn();
    
    if ($stockCount <= 0) {
        echo json_encode(['code' => 0, 'msg' => '库存不足，请稍后再试']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM faka_payment WHERE type = ? AND status = 1");
    $stmt->execute([$pay_type]);
    $paymentConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paymentConfig) {
        echo json_encode(['code' => 0, 'msg' => '当前支付方式不可用，请联系管理员']);
        exit;
    }
    
    $config = json_decode($paymentConfig['config'], true);
    $pid = $config['pid'] ?? '';
    $key = $config['key'] ?? '';
    $gateway = $config['gateway'] ?? '';
    
    if (empty($pid) || empty($key) || empty($gateway)) {
        echo json_encode(['code' => 0, 'msg' => '支付配置不完整，请联系管理员']);
        exit;
    }
    
    $order_no = date('YmdHis') . rand(1000, 9999);
    
    $stmt = $db->prepare("INSERT INTO faka_order (order_no, product_id, product_name, price, buyer_name, buyer_qq, buyer_email, status, create_time, ip_address, pay_type) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?, ?)");
    $stmt->execute([$order_no, $product_id, $product['name'], $product['price'], $buyer_name, $buyer_qq, $buyer_email, $_SERVER['REMOTE_ADDR'], $pay_type]);
    
    // 构建
    $params = [
        'pid' => $pid,
        'type' => $pay_type,
        'out_trade_no' => $order_no,
        'notify_url' => rtrim(SITE_URL, '/') . '/api/notify.php',
        'return_url' => rtrim(SITE_URL, '/') . '/api/return.php',
        'name' => $product['name'],
        'money' => $product['price'],
    ];
    
    // 签名
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        if ($v != '') {
            $signStr .= $k . '=' . $v . '&';
        }
    }
    $signStr = rtrim($signStr, '&');
    $sign = md5($signStr . $key);
    
    $payUrl = $gateway . '?' . http_build_query($params) . '&sign=' . $sign . '&sign_type=MD5';
    
    // 返回
    echo json_encode([
        'code' => 1,
        'msg' => '创建订单成功',
        'data' => [
            'order_no' => $order_no,
            'pay_url' => $payUrl
        ]
    ]);
    exit;
    
} catch (PDOException $e) {
    echo json_encode(['code' => 0, 'msg' => '数据库错误: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['code' => 0, 'msg' => '系统错误: ' . $e->getMessage()]);
    exit;
}
?>
