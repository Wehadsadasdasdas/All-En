<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'connection.php';

// 狀態名稱與對應值
$statusMap = [
    0 => 'Inactive',
    1 => 'Active',
    2 => 'Suspended',
    3 => 'Banned'
];
$statusCounts = [0, 0, 0, 0];

// 查詢 customers 各狀態數量
$sql = "SELECT status_id, COUNT(*) as count FROM customers WHERE status_id IN (0,1,2,3) GROUP BY status_id ORDER BY status_id";
$result = mysqli_query($link, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sid = (int)$row['status_id'];
        if (isset($statusCounts[$sid])) {
            $statusCounts[$sid] = (int)$row['count'];
        }
    }
}

// 回傳格式 n/n/n/n
$value = implode(' / ', $statusCounts);
echo json_encode([
    'labels' => array_values($statusMap),
    'values' => $statusCounts,
    'value' => $value
], JSON_UNESCAPED_UNICODE);
exit;
?>
