<?php
require_once "connection.php";

// Get the order ID from the request
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
$response = ['success' => false, 'payment_method_id' => null];

if ($order_id) {
    // Get the payment_method_id for the specified order
    $query = "SELECT payment_method_id FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response = [
            'success' => true,
            'payment_method_id' => $row['payment_method_id']
        ];
    }
    mysqli_stmt_close($stmt);
}

// Return the payment method information
header('Content-Type: application/json');
echo json_encode($response);
?>



