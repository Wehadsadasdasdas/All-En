<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 查詢 orders 資料表所有訂單
$sql = "SELECT status_id FROM orders";
$result = mysqli_query($link, $sql);

$total = 0;
$pending = 0;
$cancelled = 0;
$successed = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total++;
    if ($row['status_id'] == 1) {
        $pending++;
    } elseif ($row['status_id'] == 5) {
        $cancelled++;
    } else {
        $successed++;
    }
}

// 避免除以0
if ($total > 0) {
    $pending_percent = round($pending / $total * 100, 1);
    $cancelled_percent = round($cancelled / $total * 100, 1);
    $successed_percent = round($successed / $total * 100, 1);
} else {
    $pending_percent = $cancelled_percent = $successed_percent = 0;
}

echo json_encode([
    'successed' => $successed_percent,
    'pending' => $pending_percent,
    'cancelled' => $cancelled_percent
], JSON_UNESCAPED_UNICODE);
exit;
?>
