<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 查詢最多 member 居住的城市
$sql = "SELECT city, COUNT(*) as count FROM customers WHERE city IS NOT NULL AND city != '' GROUP BY city ORDER BY count DESC LIMIT 1";
$result = mysqli_query($link, $sql);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $city = $row['city'];
    $count = $row['count'];
    $value = $city . "(" . $count . " members)";
    echo json_encode([
        'city' => $city,
        'count' => $count,
        'value' => $value
    ]);
    exit;
} else {
    echo json_encode([
        'city' => null,
        'count' => 0,
        'value' => 'No Data'
    ]);
    exit;
}
