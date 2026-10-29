<?php
header('Content-Type: application/json');
session_start();

// Hapus session discount HANYA jika file ini dipanggil secara eksplisit (misal: klik tombol Remove),
// JANGAN include/require file ini di cart.php atau proses lain!
if (isset($_SESSION['applied_discount_code'])) {
    unset($_SESSION['applied_discount_code']);
}

require_once "connection.php";

$user_id = $_SESSION['user_id'] ?? null;
$subtotal = 0;
$discountAmount = 0;
$deliveryFee = 15;
$total = 0;

if ($user_id) {
    $querySubtotal = "SELECT SUM(pd.price * c.quantity) AS subtotal FROM cart c JOIN product_details pd ON c.product_id = pd.product_id AND c.size_id = pd.size_id AND c.color = pd.color WHERE c.customer_id = ? GROUP BY c.customer_id";
    $stmt = mysqli_prepare($link, $querySubtotal);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $subtotal = (float)$row['subtotal'];
    }
}
$total = $subtotal + $deliveryFee;
echo json_encode([
    'success' => true,
    'subtotal' => $subtotal,
    'discount' => 0,
    'delivery' => $deliveryFee,
    'total' => $total
]);
