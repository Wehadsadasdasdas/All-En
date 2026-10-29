<?php
session_start();
require_once "connection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_method_id = isset($_POST['payment_method_id']) ? intval($_POST['payment_method_id']) : null;

if (!$payment_method_id) {
    echo json_encode(['success' => false, 'message' => 'Payment method required']);
    exit;
}

// Ambil total, discount_code_id dari session/user
$subtotal = 0;
$discountAmount = 0;
$discount_code_id = null;
$deliveryFee = 15;
$total = 0;

// Hitung subtotal
$querySubtotal = "SELECT SUM(pd.price * c.quantity) AS subtotal FROM cart c JOIN product_details pd ON c.product_id = pd.product_id AND c.size_id = pd.size_id AND c.color = pd.color WHERE c.customer_id = ? GROUP BY c.customer_id";
$stmt = mysqli_prepare($link, $querySubtotal);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $subtotal = (float)$row['subtotal'];
}

// Cek discount_code_id dari session
if (isset($_SESSION['applied_discount_code'])) {
    $appliedCode = $_SESSION['applied_discount_code'];
    $checkDiscount = "SELECT id, discount_percent FROM discount_code WHERE code = ?";
    $stmtDisc = mysqli_prepare($link, $checkDiscount);
    mysqli_stmt_bind_param($stmtDisc, "s", $appliedCode);
    mysqli_stmt_execute($stmtDisc);
    $resultDisc = mysqli_stmt_get_result($stmtDisc);
    if ($rowDisc = mysqli_fetch_assoc($resultDisc)) {
        $discount_code_id = $rowDisc['id'];
        $discountPercent = (float)$rowDisc['discount_percent'];
        $discountAmount = $subtotal * $discountPercent / 100;
    }
}

$total = $subtotal - $discountAmount + $deliveryFee;

// Insert ke tabel orders
$insertOrder = "INSERT INTO orders (customer_id, total_price, payment_method_id, discount_code_id, delivery_id) VALUES (?, ?, ?, ?, NULL)";
$stmtOrder = mysqli_prepare($link, $insertOrder);
if ($discount_code_id) {
    mysqli_stmt_bind_param($stmtOrder, "idii", $user_id, $total, $payment_method_id, $discount_code_id);
} else {
    // discount_code_id NULL
    mysqli_stmt_bind_param($stmtOrder, "idi", $user_id, $total, $payment_method_id);
}
$success = mysqli_stmt_execute($stmtOrder);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order failed: ' . mysqli_error($link)]);
}
