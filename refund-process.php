<?php
session_start();
require_once "connection.php";

header('Content-Type: application/json');

// Check if all required data is present
if (!isset($_POST['order_id']) || !isset($_POST['refund_reason']) || !isset($_POST['proof_link'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$order_id = $_POST['order_id'];
$reason = trim($_POST['refund_reason']);
$proof_link = trim($_POST['proof_link']);

// Validate input data
if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Refund reason cannot be empty']);
    exit;
}

if (empty($proof_link)) {
    echo json_encode(['success' => false, 'message' => 'Proof link cannot be empty']);
    exit;
}

// Validate proof link is a URL
if (!filter_var($proof_link, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid URL for the proof link']);
    exit;
}

try {
    // Begin transaction
    $link->begin_transaction();

    // Insert into refund table with explicit column names
    $query = "INSERT INTO refund (reason, refund_proof, status_id, date) VALUES (?, ?, 1, CURRENT_TIMESTAMP)";
    $stmt = $link->prepare($query);
    $stmt->bind_param("ss", $reason, $proof_link);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert refund record: " . $stmt->error);
    }
    
    $refund_id = $link->insert_id;

    // Update orders table
    $query = "UPDATE orders SET refund_id = ? WHERE id = ?";
    $stmt = $link->prepare($query);
    $stmt->bind_param("ii", $refund_id, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update order: " . $stmt->error);
    }

    // If everything is successful, commit the transaction
    $link->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // On error, rollback changes
    $link->rollback();
    
    // Log the error for debugging
    error_log("Refund Process Error: " . $e->getMessage());    // Log the full error for debugging
    error_log("Detailed error: " . $e->getMessage());
    
    // Send a user-friendly error message
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to process refund request. Please try again or contact support if the problem persists.'
    ]);
}
?>
