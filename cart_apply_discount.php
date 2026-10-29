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

$user_id = $_SESSION['user_id'];
$promo_code = $_POST['promo_code'] ?? '';

if (!$promo_code) {
    echo json_encode(['success' => false, 'message' => 'Promo code required']);
    exit;
}

// Cek discount_code_id dari session
if (isset($_POST['promo_code'])) {
    $appliedCode = $_POST['promo_code'];
    $checkDiscount = "SELECT id, discount_percent FROM discount_code WHERE code = ?";
    $stmtDisc = mysqli_prepare($link, $checkDiscount);
    mysqli_stmt_bind_param($stmtDisc, "s", $appliedCode);
    mysqli_stmt_execute($stmtDisc);
    $resultDisc = mysqli_stmt_get_result($stmtDisc);
    if ($rowDisc = mysqli_fetch_assoc($resultDisc)) {
        $discount_code_id = $rowDisc['id'];
        // Cek apakah user sudah pernah pakai discount_code_id ini
        
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
        $discountPercent = (float)$rowDisc['discount_percent'];
        // Simpan promo code ke session
        $_SESSION['applied_discount_code'] = $promo_code;
        // Hitung subtotal
        $querySubtotal = "SELECT SUM(pd.price * c.quantity) AS subtotal FROM cart c JOIN product_details pd ON c.product_id = pd.product_id AND c.size_id = pd.size_id AND c.color = pd.color WHERE c.customer_id = ? GROUP BY c.customer_id";
        $stmt2 = mysqli_prepare($link, $querySubtotal);
        mysqli_stmt_bind_param($stmt2, "s", $user_id);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);
        $row = mysqli_fetch_assoc($result2);
        $subtotal = $row ? (float)$row['subtotal'] : 0;
        $discountAmount = $subtotal * $discountPercent / 100;
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
    } else {
        echo json_encode(['success' => false, 'message' => 'Promo code not found']);
        exit;
    }
}
