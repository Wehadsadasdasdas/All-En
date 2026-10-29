<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 查詢累積銷售數量最多的產品
$sql = "
    SELECT od.product_id, SUM(od.quantity) AS total_sold
    FROM order_detail od
    GROUP BY od.product_id
    ORDER BY total_sold DESC
    LIMIT 1
";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);

if ($row) {
    $product_id = $row['product_id'];
    $total_sold = $row['total_sold'];

    // 查詢產品名稱
    $sql_name = "SELECT name FROM products WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql_name);
    mysqli_stmt_bind_param($stmt, "s", $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $product_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($product_name) {
        $output = $product_name . ' / ' . $total_sold;
    } else {
        $output = $product_id . ' / ' . $total_sold;
    }
} else {
    $output = '-';
}

echo json_encode([
    'value' => $output
], JSON_UNESCAPED_UNICODE);
exit;
?>