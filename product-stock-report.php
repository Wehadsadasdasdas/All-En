<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 初始化各類別庫存
$stock = [
    1 => 0, // Luggage
    2 => 0, // Luggage Accessories
    3 => 0  // Travel Accessories
];

// 查詢 product_details 取得 PRODUCT_ID 與 stock
$sql = "SELECT PRODUCT_ID, stock FROM product_details";
$result = mysqli_query($link, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $product_id = $row['PRODUCT_ID'];
    $stock_count = (int)$row['stock'];
    // 查詢 products 取得 category_id
    $product_sql = "SELECT category_id FROM products WHERE id = '" . mysqli_real_escape_string($link, $product_id) . "' LIMIT 1";
    $product_result = mysqli_query($link, $product_sql);
    if ($product_row = mysqli_fetch_assoc($product_result)) {
        $category_id = (int)$product_row['category_id'];
        if (isset($stock[$category_id])) {
            $stock[$category_id] += $stock_count;
        }
    }
}

// 回傳格式
$response = [
    'Luggage' => $stock[1],
    'Luggage Accessories' => $stock[2],
    'Travel Accessories' => $stock[3]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
