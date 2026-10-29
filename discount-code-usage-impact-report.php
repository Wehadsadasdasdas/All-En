<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 查詢 orders 資料表 discount_code_id 與 total_price
$sql = "SELECT discount_code_id, total_price FROM orders";
$result = mysqli_query($link, $sql);

$total_orders = 0;
$used_discount_orders = 0;
$total_discount_amount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total_orders++;
    $discount_code_id = $row['discount_code_id'];
    $total_price = (float)$row['total_price'];
    if (!is_null($discount_code_id)) {
        $used_discount_orders++;
        // 查詢 discount_percent
        $code_sql = "SELECT discount_percent FROM discount_code WHERE id = '" . mysqli_real_escape_string($link, $discount_code_id) . "' LIMIT 1";
        $code_result = mysqli_query($link, $code_sql);
        if ($code_row = mysqli_fetch_assoc($code_result)) {
            $discount_percent = (float)$code_row['discount_percent'];
            // 折抵金額 = total_price * (discount_percent / 100)
            $total_discount_amount += $total_price * ($discount_percent / 100);
        }
    }
}

// 計算使用率
$usage_rate = $total_orders > 0 ? round($used_discount_orders / $total_orders * 100, 1) : 0;
// 折抵金額四捨五入到小數點一位
$total_discount_amount = round($total_discount_amount, 1);

echo json_encode([
    'usage_rate' => $usage_rate, // %
    'total_discount_amount' => $total_discount_amount // 金額
], JSON_UNESCAPED_UNICODE);
exit;
?>
