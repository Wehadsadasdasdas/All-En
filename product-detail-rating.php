<?php
require_once "connection.php"; // Make sure this path is correct

header('Content-Type: application/json');

$response = [
  'success' => false,
  'average_rating' => null,
  'error' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  $product_id = $data['product_id'] ?? null;

  if ($product_id === null) {
    $response['error'] = 'Product ID not provided.';
    echo json_encode($response);
    exit();
  }

  // Prepare the SQL query to get the average rating
  // Using AVG(rating) from the review table
  // It's important to prevent SQL injection by using prepared statements.
  $stmt = $link->prepare("SELECT AVG(rating) AS average_rating FROM review WHERE product_id = ?");

  if ($stmt) {
    $stmt->bind_param("s", $product_id); // 's' for string (assuming product_id is a string/VARCHAR)
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $averageRating = $row['average_rating'];

      // If there are no reviews, AVG will return null. Handle this.
      if ($averageRating !== null) {
        $response['success'] = true;
        $response['average_rating'] = round($averageRating * 2) / 2; // Round to nearest 0.5
      } else {
        $response['success'] = true; // Still success, but no rating yet
        $response['average_rating'] = 0; // No reviews, so 0 average rating
      }
    } else {
      $response['success'] = true; // No reviews found for this product
      $response['average_rating'] = 0;
    }
    $stmt->close();
  } else {
    $response['error'] = 'Database query preparation failed: ' . $link->error;
  }
} else {
  $response['error'] = 'Invalid request method.';
}

echo json_encode($response);
$link->close();
