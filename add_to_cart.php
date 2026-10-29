<?php
session_start();
require_once "connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$product_id = isset($data['product_id']) ? mysqli_real_escape_string($link, $data['product_id']) : null;
$size = isset($data['size']) ? mysqli_real_escape_string($link, $data['size']) : null;
$color = isset($data['color']) ? mysqli_real_escape_string($link, $data['color']) : null;
$quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id']) ? $_SESSION['id'] : null);

if (!$product_id || !$size || !$color || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Find the size_id for the given size name
$sizeQuery = "SELECT id FROM size WHERE size = '$size'";
$sizeResult = mysqli_query($link, $sizeQuery);
$sizeRow = mysqli_fetch_assoc($sizeResult);
$size_id = $sizeRow['id'] ?? null;

if (!$size_id) {
    echo json_encode(['success' => false, 'message' => 'Size not found']);
    exit;
}

// Insert into cart
$insertQuery = "INSERT INTO cart (user_id, product_id, size_id, color, quantity) 
                VALUES ('$user_id', '$product_id', '$size_id', '$color', '$quantity')";

if (mysqli_query($link, $insertQuery)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . mysqli_error($link)]);
}
?>
