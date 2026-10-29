<?php
require_once 'connection.php';
header('Content-Type: application/json');
$sql = "SELECT SUM(total_price) as total FROM orders";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
$total = $row ? (float)$row['total'] : 0;
echo json_encode(['value' => $total]);
exit;
?>
