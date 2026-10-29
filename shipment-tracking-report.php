<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';

// 狀態名稱
$statuses = ['Pending', 'Processing', 'Out for Delivery', 'Delivered'];
$statusCounts = array_fill_keys($statuses, 0);

// 查詢 delivery 表的 status_id 對應 delivery_status 資料表的 name
$sql = "SELECT ds.name, COUNT(*) as count FROM delivery d LEFT JOIN delivery_status ds ON d.status_id = ds.id GROUP BY ds.name";
$result = mysqli_query($link, $sql);
$debug = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
        $debug[] = $row; // 收集每一筆查詢結果
        if (in_array($name, $statuses, true)) {
            $statusCounts[$name] = (int)$row['count'];
        }
    }
}

$output = [];
foreach ($statuses as $s) {
    $output[] = $statusCounts[$s];
}

echo json_encode([
    'value' => implode(' / ', $output)
], JSON_UNESCAPED_UNICODE);
exit;
// 若要 debug，請將上面一行註解，改用下方這行：
// echo json_encode(['value' => implode(' : ', $output), 'debug' => $debug], JSON_UNESCAPED_UNICODE);
