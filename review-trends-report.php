<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 查詢所有評分
$sql = "SELECT rating FROM review";
$result = mysqli_query($link, $sql);

$ratings = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$total = 0;
$count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $r = (int)$row['rating'];
    if (isset($ratings[$r])) {
        $ratings[$r]++;
        $total += $r;
        $count++;
    }
}

$average = $count > 0 ? round($total / $count, 2) : 0;

echo json_encode([
    'value' => "{$average} / 5",
    'labels' => array_keys($ratings),
    'values' => array_values($ratings)
], JSON_UNESCAPED_UNICODE);
exit;
?>