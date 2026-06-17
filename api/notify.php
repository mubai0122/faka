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
require_once __DIR__ . '/../includes/payment.php';

// Log
function logNotify($msg) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logDir . 'notify_' . date('Y-m-d') . '.log', 
        date('[H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

logNotify("收到回调请求！参数：" . json_encode($_REQUEST));

try {
    $db = Database::getInstance();
    $payment = new Payment($db);
    
    $data = $_REQUEST;
    
    $out_trade_no = $data['out_trade_no'] ?? '';
    $trade_status = $data['trade_status'] ?? '';
    $trade_no = $data['trade_no'] ?? '';
    $total_amount = $data['money'] ?? $data['total_amount'] ?? '0';
    
    // 验证
    if (!$payment->verifySign($data)) {
        logNotify("签名验证失败");
        die("fail");
    }
    
    if ($trade_status !== 'TRADE_SUCCESS') {
        logNotify("交易状态未成功: " . $trade_status);
        die("fail");
    }
    
    if (empty($out_trade_no)) {
        logNotify("订单号为空");
        die("fail");
    }
    
    $db->beginTransaction();
    
    // 查询
    $stmt = $db->prepare("SELECT * FROM faka_order WHERE order_no = ? FOR UPDATE");
    $stmt->execute([$out_trade_no]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        logNotify("订单不存在: " . $out_trade_no);
        $db->rollBack();
        die("fail");
    }
    
    // 跳过
    if ($order['status'] == 1) {
        logNotify("订单已处理过: " . $out_trade_no);
        $db->commit();
        die("success");
    }
    
    // 获取
    $stmt = $db->prepare("SELECT id, card_info FROM faka_stock WHERE product_id = ? AND status = 0 LIMIT 1 FOR UPDATE");
    $stmt->execute([$order['product_id']]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stock) {
        logNotify("库存不足，商品ID: " . $order['product_id']);
        $db->rollBack();
        die("fail");
    }
    
    // 更新
    $stmt = $db->prepare("UPDATE faka_stock SET status = 1, order_id = ?, sell_time = NOW() WHERE id = ?");
    $stmt->execute([$order['id'], $stock['id']]);
    
    // 更新P
    $stmt = $db->prepare("UPDATE faka_order SET status = 1, pay_time = NOW(), trade_no = ?, card_info = ? WHERE order_no = ?");
    $stmt->execute([$trade_no, $stock['card_info'], $out_trade_no]);
    
    // 更新S
    $stmt = $db->prepare("UPDATE faka_product SET sales = sales + 1, stock = stock - 1 WHERE id = ?");
    $stmt->execute([$order['product_id']]);
    
    $db->commit();
    
    logNotify("订单处理成功: " . $out_trade_no . "，卡密ID: " . $stock['id']);
    
    // TODO: 发送邮件通知用户（可选）
    // 我有点不想写了喵，推荐用phpmailer喵。    
    echo "success";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    logNotify("异常错误: " . $e->getMessage());
    echo "fail";
}
