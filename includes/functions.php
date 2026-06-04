<?php
/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */

function getSettings($db) {
    $settings = [];
    $stmt = $db->prepare("SELECT `key`, `value` FROM faka_setting");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }
    return $settings;
}

function getCategories($db) {
    $stmt = $db->prepare("SELECT * FROM faka_category WHERE status = 1 ORDER BY sort ASC, id ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProducts($db, $category_id = 0) {
    if ($category_id > 0) {
        $stmt = $db->prepare("SELECT * FROM faka_product WHERE status = 1 AND category_id = ? ORDER BY id DESC");
        $stmt->execute([$category_id]);
    } else {
        $stmt = $db->prepare("SELECT * FROM faka_product WHERE status = 1 ORDER BY id DESC");
        $stmt->execute();
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as &$product) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM faka_stock WHERE product_id = ? AND status = 0");
        $stmt->execute([$product['id']]);
        $product['stock'] = $stmt->fetchColumn();
        $product['description'] = nl2br(htmlspecialchars($product['description'] ?? ''));
    }
    
    return $products;
}

function getProductDetail($db, $product_id) {
    $stmt = $db->prepare("SELECT * FROM faka_product WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM faka_stock WHERE product_id = ? AND status = 0");
        $stmt->execute([$product_id]);
        $product['stock'] = $stmt->fetchColumn();
    }
    
    return $product;
}

function getOrderByNo($db, $order_no) {
    $stmt = $db->prepare("SELECT * FROM faka_order WHERE order_no = ?");
    $stmt->execute([$order_no]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
