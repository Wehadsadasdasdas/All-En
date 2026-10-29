<?php
require_once "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Fetch and sanitize input
  $product_id = $_POST['product_id'] ?? '';
  $size = $_POST['size'] ?? '';
  $color = $_POST['color'] ?? '';

  if (empty($product_id) || empty($size) || empty($color)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing product_id, size, or color']);
    exit;
  }

  // Get size_id from product_sizes
  $sizeQuery = "SELECT id FROM product_sizes WHERE size = ?";
  $stmtSize = $link->prepare($sizeQuery);
  $stmtSize->bind_param("s", $size);
  $stmtSize->execute();
  $stmtSize->bind_result($size_id);
  $stmtSize->fetch();
  $stmtSize->close();

  if (!$size_id) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "Size '$size' not found."]);
    exit;
  }

  // Prepare DELETE statement
  $sql = "DELETE FROM product_details WHERE PRODUCT_ID = ? AND size_id = ? AND color = ?";
  $stmt = $link->prepare($sql);
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare delete statement: ' . $link->error]);
    exit;
  }

  $stmt->bind_param("sis", $product_id, $size_id, $color); // Assuming size_id is integer

  if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
      echo json_encode(['success' => true, 'message' => 'Product detail deleted successfully.']);
    } else {
      echo json_encode(['success' => false, 'error' => 'No product detail found matching the criteria.']);
    }
  } else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
  }

  $stmt->close();
  $link->close();
}
