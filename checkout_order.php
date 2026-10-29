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

// Cek apakah user sudah pernah pakai discount_code_id ini
if ($discount_code_id) {
    $checkUsed = "SELECT COUNT(*) as cnt FROM orders WHERE customer_id = ? AND discount_code_id = ?";
    $stmtUsed = mysqli_prepare($link, $checkUsed);
    mysqli_stmt_bind_param($stmtUsed, "ii", $user_id, $discount_code_id);
    mysqli_stmt_execute($stmtUsed);
    $resultUsed = mysqli_stmt_get_result($stmtUsed);
    $rowUsed = mysqli_fetch_assoc($resultUsed);
    if ($rowUsed && $rowUsed['cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already used this discount code.']);
        exit;
    }
}

$total = $subtotal - $discountAmount + $deliveryFee;

// Insert ke tabel orders
$recipientAddress = isset($_POST['recipientAddress']) ? trim($_POST['recipientAddress']) : '';
if (!$recipientAddress) {
    error_log("Recipient address is required");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Recipient address required']);
    exit;
}
// Insert into delivery table
$insertDelivery = "INSERT INTO delivery (Shipping_Address, status_id) VALUES (?, 1)"; // status_id 1 = default (e.g. 'Processing')
$deliveryStmt = $link->prepare($insertDelivery);
$deliveryStmt->bind_param("s", $recipientAddress);
$deliveryStmt->execute();
$delivery_id = $deliveryStmt->insert_id;
$deliveryStmt->close();

if ($discount_code_id) {
    $insertOrder = "INSERT INTO orders (customer_id, order_date, total_price, status_id, delivery_id, payment_method_id, discount_code_id) VALUES (?, NOW(), ?, 1, ?, ?, ?)";
    $stmtOrder = mysqli_prepare($link, $insertOrder);
    // Corrected: 6 params, so 6 types: idiiii
    mysqli_stmt_bind_param($stmtOrder, "idiii", $user_id, $total, $delivery_id, $payment_method_id, $discount_code_id);
} else {
    $insertOrder = "INSERT INTO orders (customer_id, order_date, total_price, status_id, delivery_id, payment_method_id) VALUES (?, NOW(), ?, 1, ?, ?)";
    $stmtOrder = mysqli_prepare($link, $insertOrder);
    // Corrected: 4 params, so 4 types: idii
    mysqli_stmt_bind_param($stmtOrder, "idii", $user_id, $total, $delivery_id, $payment_method_id);
}
$success = mysqli_stmt_execute($stmtOrder);

if ($success) {
    $order_id = mysqli_insert_id($link);
    // Ambil semua item cart user
    $cartQuery = "SELECT product_id, color, size_id, quantity FROM cart WHERE customer_id = ?";
    $cartStmt = mysqli_prepare($link, $cartQuery);
    mysqli_stmt_bind_param($cartStmt, "i", $user_id);
    mysqli_stmt_execute($cartStmt);
    $cartResult = mysqli_stmt_get_result($cartStmt);
    while ($cartItem = mysqli_fetch_assoc($cartResult)) {
        $insertDetail = "INSERT INTO order_detail (order_id, product_id, color, size_id, quantity) VALUES (?, ?, ?, ?, ?)";
        $detailStmt = mysqli_prepare($link, $insertDetail);
        mysqli_stmt_bind_param($detailStmt, "issii", $order_id, $cartItem['product_id'], $cartItem['color'], $cartItem['size_id'], $cartItem['quantity']);
        mysqli_stmt_execute($detailStmt);
    }    // Save a copy of cart items in session before deleting them
    $cartBackupQuery = "SELECT product_id, color, size_id, quantity FROM cart WHERE customer_id = ?";
    $cartBackupStmt = mysqli_prepare($link, $cartBackupQuery);
    mysqli_stmt_bind_param($cartBackupStmt, "i", $user_id);
    mysqli_stmt_execute($cartBackupStmt);
    $cartBackupResult = mysqli_stmt_get_result($cartBackupStmt);

    $cartBackup = [];
    while ($item = mysqli_fetch_assoc($cartBackupResult)) {
        $cartBackup[] = $item;
    }

    // Store in session for recovery if needed
    $_SESSION['cart_backup'] = $cartBackup;
    $_SESSION['order_id'] = $order_id;

    // Setelah insert order_detail, kosongkan cart user
    $deleteCart = "DELETE FROM cart WHERE customer_id = ?";
    $deleteCartStmt = mysqli_prepare($link, $deleteCart);
    mysqli_stmt_bind_param($deleteCartStmt, "i", $user_id);
    mysqli_stmt_execute($deleteCartStmt);

    // Setelah order sukses, hapus session discount code
    unset($_SESSION['applied_discount_code']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order failed: ' . mysqli_error($link)]);
}
