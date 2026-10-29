<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';
header('Content-Type: application/json');
// 查詢訂單總金額與訂單數
$sql = "SELECT SUM(total_price) as total, COUNT(*) as count FROM orders";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
$total = $row ? (float)$row['total'] : 0;
$count = $row ? (int)$row['count'] : 0;
$avg = $count > 0 ? round($total / $count, 2) : 0;
// 進度條百分比（可根據實際需求調整，這裡假設以 10000 為滿分）
$progress = $avg > 0 ? min(100, round($avg / 10000 * 100)) : 0;
echo json_encode([
    'value' => $avg,
    'progress' => $progress
]);
exit;
?>