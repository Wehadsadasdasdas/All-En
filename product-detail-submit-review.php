<?php
require_once "connection.php"; // Ensure your connection file is correctly set up

header('Content-Type: application/json');
session_start(); // Start session to check for user login

$response = ['success' => false, 'message' => ''];

// Retrieve customer_id from session
$customer_id = $_SESSION['user_id'] ?? null;

// Check if user is logged in (example: check if user_id is in session)
// You might need to adjust 'user_id' based on your actual session variable
if ($customer_id === null) {
  $response['message'] = 'You need to login to submit a review.';
  echo json_encode($response);
  exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$product_id = $input['product_id'] ?? null;
$size_id = $input['size_id'] ?? null; // Ideally passed directly from frontend if known
$size_name = $input['size_name'] ?? null; // Fallback to size_name if size_id not directly passed
$color = $input['color'] ?? null;
$content = $input['content'] ?? null;
$rating = $input['rating'] ?? null;
$submit_date = $input['submit_date'] ?? null;

// Basic validation
if (!$product_id || !$color || !$content || $rating === null || !$submit_date) {
  $response['message'] = 'Missing required review data.';
  echo json_encode($response);
  exit();
}

// Convert rating to integer and validate
$rating = (int)$rating;
if ($rating < 1 || $rating > 5) {
  $response['message'] = 'Rating must be between 1 and 5.';
  echo json_encode($response);
  exit();
}

try {
  // If size_id is not directly passed from frontend, try to resolve it from size_name
  if ($size_id === null && $size_name !== null) {
    $stmt_size = $link->prepare("SELECT id FROM product_sizes WHERE size = ?");
    if ($stmt_size === false) {
      throw new Exception("Failed to prepare size statement: " . $link->error);
    }
    $stmt_size->bind_param("s", $size_name);
    $stmt_size->execute();
    $result_size = $stmt_size->get_result();
    if ($row_size = $result_size->fetch_assoc()) {
      $size_id = $row_size['id'];
    } else {
      // Handle case where size_name doesn't match an existing size
      // For now, we'll proceed with NULL size_id, but you might want to return an error
      error_log("Warning: Size name '$size_name' not found for product_id $product_id.");
    }
    $stmt_size->close();
  }

  // Modified INSERT statement to include customer_id
  $stmt = $link->prepare("INSERT INTO review (product_id, size_id, color, Content, rating, Submit_date, customer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

  if ($stmt === false) {
    throw new Exception("Failed to prepare statement: " . $link->error);
  }

  // Corrected bind_param:
  // 's' for product_id (string)
  // 'i' for size_id (integer, can be NULL)
  // 's' for color (string)
  // 's' for Content (string, as you confirmed)
  // 'i' for rating (integer, as it's cast to int)
  // 's' for Submit_date (string)
  // 'i' for customer_id (integer, assuming user_id in session is int)
  $stmt->bind_param("sissssi", $product_id, $size_id, $color, $content, $rating, $submit_date, $customer_id);

  if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Review added successfully.';
  } else {
    throw new Exception("Failed to insert review: " . $stmt->error);
  }

  $stmt->close();
} catch (Exception $e) {
  $response['message'] = $e->getMessage();
} finally {
  $link->close();
}

echo json_encode($response);
