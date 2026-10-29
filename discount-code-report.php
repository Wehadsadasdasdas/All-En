<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 查詢 discount_code 資料表的總數量
$sql = "SELECT COUNT(*) as total FROM discount_code";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
$total = $row ? (int)$row['total'] : 0;

echo json_encode([
    'value' => $total
], JSON_UNESCAPED_UNICODE);
exit;
?>