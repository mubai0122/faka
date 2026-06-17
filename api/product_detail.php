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

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['code' => 0, 'msg' => '参数错误']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $stmt = $db->prepare("SELECT * FROM faka_product WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['code' => 0, 'msg' => '商品不存在']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM faka_stock WHERE product_id = ? AND status = 0");
    $stmt->execute([$product_id]);
    $stock = $stmt->fetchColumn();
    
    $detail = $product['detail'] ?? '';
    $detail = str_replace(['\n', '\\n'], "\n", $detail);
    $detail = nl2br(htmlspecialchars($detail));
    
    echo json_encode([
        'code' => 1,
        'data' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'description' => nl2br(htmlspecialchars($product['description'] ?? '')),
            'detail' => $detail,
            'image' => $product['image'] ?? '',
            'stock' => (int)$stock
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['code' => 0, 'msg' => '数据库错误']);
}
?>
