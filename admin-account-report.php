<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'connection.php';

// 設定 JSON 標頭
header('Content-Type: application/json');

$sql = "SELECT COUNT(*) as total FROM admin_role";
$result = mysqli_query($link, $sql);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total = $row ? (int)$row['total'] : 0;
    echo json_encode(['value' => $total]);
} else {
    echo json_encode(['error' => 'Database query failed']);
}
?>
