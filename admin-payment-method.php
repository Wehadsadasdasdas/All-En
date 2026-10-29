<?php
require_once 'connection.php'; // 請確認路徑正確

// 啟用錯誤報告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 設定 JSON 標頭
header('Content-Type: application/json');

// 查詢所有訂單的支付方式數量
$sql = "SELECT payment_method, COUNT(*) as count FROM orders GROUP BY payment_method";
$result = mysqli_query($link, $sql);

if ($result) {
    $labels = [];
    $values = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $labels[] = $row['payment_method'];
        $values[] = (int)$row['count'];
    }
    // 回傳 JSON 給前端（圓餅圖格式）
    echo json_encode(['labels' => $labels, 'values' => $values]);
} else {
    echo json_encode(['error' => 'Database query failed']);
}
exit; // 確保 PHP 腳本在輸出 JSON 後停止執行
?>
