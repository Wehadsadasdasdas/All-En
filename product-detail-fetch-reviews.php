<?php
require_once "connection.php"; // Ensure your connection file is correctly set up

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'error' => ''];

$input = json_decode(file_get_contents('php://input'), true);

$product_id = $input['product_id'] ?? null;
$size_name = $input['size_name'] ?? null;
$color_name = $input['color_name'] ?? null;
$sort_order = $input['sort_order'] ?? 'latest'; // Default to 'latest'

if (!$product_id) {
  $response['error'] = 'Product ID is required.';
  echo json_encode($response);
  exit();
}

// Determine sort order
$orderBy = '';
if ($sort_order === 'earliest') {
  $orderBy = 'ORDER BY r.Submit_date ASC';
} else { // default or 'latest'
  $orderBy = 'ORDER BY r.Submit_date DESC';
}

try {
  $stmt_params = [];
  $stmt_types = "";

  // Base query
  $query = "SELECT r.id, r.product_id, r.size_id, s.size, r.color, r.Content, r.rating, r.Submit_date, r.admin_id, c.name  
              FROM review r, customers c, product_sizes s
              WHERE r.size_id = s.id 
              AND r.product_id = ?
              AND c.id = r.customer_id
              AND admin_id";
  $stmt_params[] = $product_id;
  $stmt_types .= "s";

  $query .= " " . $orderBy;

  $stmt = $link->prepare($query);

  if ($stmt === false) {
    throw new Exception("Failed to prepare statement: " . $link->error);
  }

  if (!empty($stmt_params)) {
    $stmt->bind_param($stmt_types, ...$stmt_params);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  $reviews = [];
  while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
  }

  $response['success'] = true;
  $response['data'] = $reviews;

  $stmt->close();
} catch (Exception $e) {
  $response['error'] = $e->getMessage();
} finally {
  $link->close();
}

echo json_encode($response);
