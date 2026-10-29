<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';

// 狀態名稱
$statuses = ['Available', 'Expired'];
$statusCounts = array_fill_keys($statuses, 0);

// 查詢 warranty_status 資料表的 name 欄位，並分組計算
$sql = "SELECT name, COUNT(*) as count FROM warranty_status WHERE name IN ('Available', 'Expired') GROUP BY name";
$result = mysqli_query($link, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
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
