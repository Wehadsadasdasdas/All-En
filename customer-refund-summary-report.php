<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 定義所有要統計的狀態
$statuses = [
    'Refund Requested',
    'Refund Approved',
    'Refund Rejected',
    'Refund Processed',
    'Refund Completed',
    'Refund Cancelled'
];

// 初始化每個狀態的計數
$statusCounts = array_fill_keys($statuses, 0);

// 查詢 refund_status 資料表，統計各 Description 數量
$sql = "SELECT Description, COUNT(*) as count FROM refund_status WHERE Description IN ('" . implode("','", $statuses) . "') GROUP BY Description";
$result = mysqli_query($link, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $desc = $row['Description'];
        if (isset($statusCounts[$desc])) {
            $statusCounts[$desc] = (int)$row['count'];
        }
    }
}

// 依照指定順序組成 n/n/n/... 格式
$output = [];
foreach ($statuses as $s) {
    $output[] = $statusCounts[$s];
}

echo json_encode([
    'value' => implode(' / ', $output)
], JSON_UNESCAPED_UNICODE);
exit;
?>