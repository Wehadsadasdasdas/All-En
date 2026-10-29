<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 1. 取得每個 payment_method_id 的訂單數量
$sql = "SELECT payment_method_id, COUNT(*) AS total FROM orders GROUP BY payment_method_id";
$result = mysqli_query($link, $sql);

$method_counts = [];
$total_count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $method_counts[$row['payment_method_id']] = (int)$row['total'];
    $total_count += (int)$row['total'];
}   

// 2. 取得 payment_method_id 對應的 name
$method_names = [];
if (!empty($method_counts)) {
    $ids = array_map('intval', array_keys($method_counts));
    $sql2 = "SELECT id, name FROM order_payment_methods WHERE id IN (" . implode(',', $ids) . ")";
    $result2 = mysqli_query($link, $sql2);
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $method_names[$row2['id']] = $row2['name'];
    }
}

// 3. 組合 name 與數量，並過濾數量>0
$display = [];
foreach ($method_counts as $id => $count) {
    if ($count > 0) {
        $name = isset($method_names[$id]) ? $method_names[$id] : 'Unknown';
        $display[$name] = $count;
    }
}

// 4. 準備 labels 與 values
$labels = array_keys($display);
$values = array_values($display);

// 5. 回傳
echo json_encode([
    'labels' => $labels,
    'values' => $values
], JSON_UNESCAPED_UNICODE);
exit;
?>
