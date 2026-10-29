<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';
header('Content-Type: application/json');

$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($type === 'maa') {
    // Member Account Activity Report
    $sql = "SELECT customer_id, COUNT(*) as order_count FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id ORDER BY order_count DESC, customer_id ASC LIMIT 1";
    $result = mysqli_query($link, $sql);
    $row = mysqli_fetch_assoc($result);
    $cid = $row ? $row['customer_id'] : '';
    $count = $row ? (int)$row['order_count'] : 0;
    echo json_encode(['value' => $cid ? $cid . ' ($' . $count . ')' : '無資料']);
    exit;
} else {
    // Customer Lifetime Value Report
    $sql = "SELECT customer_id, SUM(total_price) as total_spent FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id ORDER BY total_spent DESC, customer_id ASC LIMIT 1";
    $result = mysqli_query($link, $sql);
    $row = mysqli_fetch_assoc($result);
    $cid = $row ? $row['customer_id'] : '';
    $total = $row ? (float)$row['total_spent'] : 0;
    echo json_encode(['value' => $cid ? $cid . ' ($' . $total . ')' : '\u7121\u8cc7\u6599']);
    exit;
}
?>
