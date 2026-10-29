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
$action = $_POST['action'] ?? null; // 'increase' or 'decrease'

if (!$product_id || !$size_id || !$color || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data',$customer_id, 'product_id' => $product_id, 'size_id' => $size_id, 'color' => $color, 'action' => $action]);
    exit;
}

// Ambil quantity saat ini
$query = "SELECT quantity FROM cart WHERE customer_id=? AND product_id=? AND size_id=? AND color=?";
$stmt = mysqli_prepare($link, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: prepare failed']);
    exit;
}
// Gunakan 's' untuk product_id jika id produk bertipe string (misal: 'P23')
mysqli_stmt_bind_param($stmt, "isis", $customer_id, $product_id, $size_id, $color);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

$current_quantity = (int)$row['quantity'];

// Ambil stock dari product_details
$stock_query = "SELECT stock FROM product_details WHERE product_id=? AND size_id=? AND color=?";
$stock_stmt = mysqli_prepare($link, $stock_query);
mysqli_stmt_bind_param($stock_stmt, "sis", $product_id, $size_id, $color);
mysqli_stmt_execute($stock_stmt);
$stock_result = mysqli_stmt_get_result($stock_stmt);
$stock_row = mysqli_fetch_assoc($stock_result);
$stock = $stock_row ? (int)$stock_row['stock'] : 0;

if ($action === 'increase') {
    $new_quantity = $current_quantity + 1;
    if ($new_quantity > $stock) {
        echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock', 'stock' => $stock]);
        exit;
    }
} elseif ($action === 'decrease') {
    $new_quantity = max(1, $current_quantity - 1); // jangan sampai kurang dari 1
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Update quantity
$update = "UPDATE cart SET quantity=? WHERE customer_id=? AND product_id=? AND size_id=? AND color=?";
$stmt = mysqli_prepare($link, $update);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: prepare failed (update)']);
    exit;
}
// Gunakan 'siis' untuk product_id string
mysqli_stmt_bind_param($stmt, "iisis", $new_quantity, $customer_id, $product_id, $size_id, $color);
$success = mysqli_stmt_execute($stmt);

if (!$success || mysqli_stmt_affected_rows($stmt) === 0) {
    echo json_encode(['success' => false, 'message' => 'Failed to update quantity or no row updated', 'debug' => [
        'new_quantity' => $new_quantity,
        'customer_id' => $customer_id,
        'product_id' => $product_id,
        'size_id' => $size_id,
        'color' => $color
    ]]);
    exit;
}

// Ambil harga satuan dari product_details
$price_query = "SELECT price FROM product_details WHERE product_id=? AND size_id=? AND color=?";
$price_stmt = mysqli_prepare($link, $price_query);
mysqli_stmt_bind_param($price_stmt, "sis", $product_id, $size_id, $color);
mysqli_stmt_execute($price_stmt);
$price_result = mysqli_stmt_get_result($price_stmt);
$price_row = mysqli_fetch_assoc($price_result);
$unit_price = $price_row ? (float)$price_row['price'] : 0;
$item_total_price = $unit_price * $new_quantity;

// Hitung ulang subtotal, discount, total setelah update quantity
$subtotal_query = "SELECT SUM(pd.price * c.quantity) AS subtotal FROM cart c JOIN product_details pd ON c.product_id = pd.product_id AND c.size_id = pd.size_id AND c.color = pd.color WHERE c.customer_id = ? GROUP BY c.customer_id";
$subtotal_stmt = mysqli_prepare($link, $subtotal_query);
mysqli_stmt_bind_param($subtotal_stmt, "i", $customer_id);
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
    'new_quantity' => $new_quantity,
    'item_total_price' => $item_total_price,
    'subtotal' => $subtotal,
    'discount' => $discountAmount,
    'delivery' => $deliveryFee,
    'total' => $total
]);
exit;
?>
