<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 查詢最高利潤產品
$sql = "
    SELECT 
        od.product_id, 
        pd.PRODUCT_ID,
        SUM((pd.price - pd.capital) * od.quantity) AS total_profit
    FROM order_detail od
    JOIN product_details pd ON od.product_id = pd.PRODUCT_ID
    GROUP BY od.product_id, pd.PRODUCT_ID
    ORDER BY total_profit DESC
    LIMIT 1
";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);

if ($row) {
    // 取得產品名稱
    $product_id = $row['PRODUCT_ID'];
    $name_sql = "SELECT name FROM products WHERE id = '" . mysqli_real_escape_string($link, $product_id) . "' LIMIT 1";
    $name_result = mysqli_query($link, $name_sql);
    $name_row = mysqli_fetch_assoc($name_result);
    $product_name = $name_row ? $name_row['name'] : $product_id;
    // 取得該產品所有 order_detail 的 quantity 累加
    $qty_sql = "SELECT SUM(quantity) AS total_qty FROM order_detail WHERE PRODUCT_ID = '" . mysqli_real_escape_string($link, $product_id) . "'";
    $qty_result = mysqli_query($link, $qty_sql);
    $qty_row = mysqli_fetch_assoc($qty_result);
    $total_qty = $qty_row ? (int)$qty_row['total_qty'] : 0;
    // 取得該產品所有 product_details 的 price 與 capital（假設同一產品 id 價格與成本一致，取第一筆）
    $price_sql = "SELECT price, capital FROM product_details WHERE PRODUCT_ID = '" . mysqli_real_escape_string($link, $product_id) . "' LIMIT 1";
    $price_result = mysqli_query($link, $price_sql);
    $price_row = mysqli_fetch_assoc($price_result);
    $price = $price_row ? (float)$price_row['price'] : 0;
    $capital = $price_row ? (float)$price_row['capital'] : 0;
    $total_profit = ($price - $capital) * $total_qty;
    $output = $product_name . ' / ' . $total_profit . ' / ' . $total_qty;
} else {
    $output = '-';
}

echo json_encode([
    'value' => $output
], JSON_UNESCAPED_UNICODE);
exit;
?>