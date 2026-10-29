<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "connection.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;
$size_id = $_POST['size_id'] ?? null;
$color = $_POST['color'] ?? null;

if (!$product_id || !$size_id || !$color) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$query = "DELETE FROM cart WHERE customer_id=? AND product_id=? AND size_id=? AND color=?";
$stmt = mysqli_prepare($link, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: prepare failed']);
    exit;
}
// Gunakan 'siis' jika product_id string
mysqli_stmt_bind_param($stmt, "siis", $customer_id, $product_id, $size_id, $color);
$success = mysqli_stmt_execute($stmt);

if (!$success || mysqli_stmt_affected_rows($stmt) === 0) {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item or item not found']);
    exit;
}

// Hitung ulang subtotal, discount, total setelah remove
$subtotal_query = "SELECT SUM(pd.price * c.quantity) AS subtotal FROM cart c JOIN product_details pd ON c.product_id = pd.product_id AND c.size_id = pd.size_id AND c.color = pd.color WHERE c.customer_id = ? GROUP BY c.customer_id";
$subtotal_stmt = mysqli_prepare($link, $subtotal_query);
mysqli_stmt_bind_param($subtotal_stmt, "s", $customer_id);
mysqli_stmt_execute($subtotal_stmt);
$subtotal_result = mysqli_stmt_get_result($subtotal_stmt);
$subtotal_row = mysqli_fetch_assoc($subtotal_result);
$subtotal = $subtotal_row ? (float)$subtotal_row['subtotal'] : 0;

// Cek apakah user sudah pernah apply promo code valid di session
$discountAmount = 0;
$discountPercent = 0;
if (isset($_SESSION['applied_discount_code'])) {
    $appliedCode = $_SESSION['applied_discount_code'];
    $checkDiscount = "SELECT discount_percent FROM discount_code WHERE code = ?";
    $stmtDisc = mysqli_prepare($link, $checkDiscount);
    if ($stmtDisc) {
        mysqli_stmt_bind_param($stmtDisc, "s", $appliedCode);
        mysqli_stmt_execute($stmtDisc);
        $resultDisc = mysqli_stmt_get_result($stmtDisc);
        if ($rowDisc = mysqli_fetch_assoc($resultDisc)) {
            $discountPercent = (float)$rowDisc['discount_percent'];
            $discountAmount = $subtotal * $discountPercent / 100;
        }
    }
}
$deliveryFee = 15;
$total = $subtotal - $discountAmount + $deliveryFee;

echo json_encode([
    'success' => true,
    'subtotal' => $subtotal,
    'discount' => $discountAmount,
    'delivery' => $deliveryFee,
    'total' => $total
]);
exit;
