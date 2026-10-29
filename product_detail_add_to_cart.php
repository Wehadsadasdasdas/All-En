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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data',
        'user_id' => $user_id,
        'quantity' => $quantity,
        'product_id' => $product_id,
        'size' => $size,
        'color' => $color
    ]);
    exit;
}

// Find the size_id for the given size name
$sizeQuery = "SELECT id FROM product_sizes WHERE size = '$size'";
$sizeResult = mysqli_query($link, $sizeQuery);
$sizeRow = mysqli_fetch_assoc($sizeResult);
$size_id = $sizeRow['id'] ?? null;

if (!$size_id) {
    echo json_encode(['success' => false, 'message' => 'Size not found']);
    exit;
}
$stock = "SELECT stock FROM product_details WHERE product_id = '$product_id' AND size_id = '$size_id'";
$stockResult = mysqli_query($link, $stock); // Check stock for the product and size
$stockRow = mysqli_fetch_assoc($stockResult); //

// Cek stock yang sudah ada di cart user
$cartQtyQuery = "SELECT quantity FROM cart WHERE customer_id = '$user_id' AND product_id = '$product_id' AND size_id = '$size_id' AND color = '$color'";
$cartQtyResult = mysqli_query($link, $cartQtyQuery);
$cartQtyRow = mysqli_fetch_assoc($cartQtyResult);
$cartQty = $cartQtyRow ? (int)$cartQtyRow['quantity'] : 0;

// Jika user sudah punya di cart, cek total quantity (cart + request)
if ($cartQty > 0) {
    $totalQty = $cartQty + $quantity;
    if ($totalQty > $stockRow['stock']) {
        echo json_encode(['stock' => false, 'message' => 'Insufficient stock (cart + request exceeds stock)']);
        exit;
    }
    // Update quantity di cart
    $updateQuery = "UPDATE cart SET quantity = quantity + $quantity WHERE customer_id = '$user_id' AND product_id = '$product_id' AND size_id = '$size_id' AND color = '$color'";
    if (mysqli_query($link, $updateQuery)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . mysqli_error($link)]);
    }
    exit;
} else {
    // Jika belum ada di cart, cek request quantity <= stock
    if ($quantity > $stockRow['stock']) {
        echo json_encode(['stock' => false, 'message' => 'Insufficient stock']);
        exit;
    }
    // Insert ke cart
    $insertQuery = "INSERT INTO cart (customer_id, product_id, size_id, color, quantity) VALUES ('$user_id', '$product_id', '$size_id', '$color', '$quantity')";
    if (mysqli_query($link, $insertQuery)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: ' . mysqli_error($link)]);
    }
    exit;
}
