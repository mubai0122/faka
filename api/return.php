<?php
/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';

$orderNo = $_GET['out_trade_no'] ?? '';

if (empty($orderNo)) {
    header('Location: ' . SITE_URL . '?msg=error');
    exit;
}

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM faka_order WHERE order_no = ?");
    $stmt->execute([$orderNo]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order && $order['status'] == 1) {
        // 成功
        header('Location: ' . SITE_URL . '?action=result&order_no=' . $orderNo);
    } else {
        // 未支付&失败
        header('Location: ' . SITE_URL . '?action=order&order_no=' . $orderNo);
    }
} catch (Exception $e) {
    header('Location: ' . SITE_URL . '?msg=error');
}
