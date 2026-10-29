<?php
require_once "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // Fetch and sanitize input
  $productId = $_POST['product_id'] ?? '';
  $color = $_POST['color'] ?? '';
  $size = $_POST['size'] ?? '';
  $newStock = $_POST['stock'] ?? null;
  $newPrice = $_POST['price'] ?? null;
  $newCapital = $_POST['capital'] ?? null;
  $newDescription = $_POST['description'] ?? null;

  if (empty($productId) || empty($color) || empty($size)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product_id, color, or size']);
    exit;
  }

  if ($newStock === null && $newPrice === null && $newCapital === null && $newDescription === null) {
    http_response_code(400);
    echo json_encode(['error' => 'At least one field (price, stock, capital, or description) must be provided']);
    exit;
  }
  // Escape and cast inputs
  $productId = mysqli_real_escape_string($link, $productId);
  $color = mysqli_real_escape_string($link, $color);
  $size = mysqli_real_escape_string($link, $size);

  if ($newStock !== null) $newStock = (int)$newStock;
  if ($newPrice !== null) $newPrice = (float)$newPrice;
  if ($newCapital !== null) $newCapital = (float)$newCapital;
  if ($newDescription !== null) $newDescription = mysqli_real_escape_string($link, $newDescription);

  // Get size_id based on size name
  $sizeQuery = "SELECT product_sizes.size FROM product_sizes WHERE size = ?";
  $stmtSize = mysqli_prepare($link, $sizeQuery);
  mysqli_stmt_bind_param($stmtSize, 's', $size);
  mysqli_stmt_execute($stmtSize);
  mysqli_stmt_bind_result($stmtSize, $sizeId);
  mysqli_stmt_fetch($stmtSize);
  mysqli_stmt_close($stmtSize);

  if (!$sizeId) {
    http_response_code(404);
    echo json_encode(['error' => 'Size not found ' . $size . ' (Color: ' . $color . ', Product ID: ' . $productId . ')']);
    exit;
  }
  // Build dynamic update query based on which fields are set
  $detailsFields = [];
  $detailsParams = [];
  $detailsTypes = '';
  
  $productsFields = [];
  $productsParams = [];
  $productsTypes = '';

  // Handle product_details fields
  if ($newStock !== null) {
    $detailsFields[] = 'stock = ?';
    $detailsParams[] = $newStock;
    $detailsTypes .= 'i';
  }

  if ($newPrice !== null) {
    $detailsFields[] = 'price = ?';
    $detailsParams[] = $newPrice;
    $detailsTypes .= 'd';
  }

  if ($newCapital !== null) {
    $detailsFields[] = 'capital = ?';
    $detailsParams[] = $newCapital;
    $detailsTypes .= 'd';
  }

  // Handle products table fields
  if ($newDescription !== null) {
    $productsFields[] = 'description = ?';
    $productsParams[] = $newDescription;
    $productsTypes .= 's';
  }

  // Update product_details table if needed
  if (!empty($detailsFields)) {
    $query = "
      UPDATE product_details 
      INNER JOIN product_sizes ON product_details.size_id = product_sizes.id 
      SET " . implode(', ', $detailsFields) . " 
      WHERE product_details.PRODUCT_ID = ? AND product_details.color = ? AND product_sizes.size = ?
    ";
    $detailsParams[] = $productId;
    $detailsParams[] = $color;
    $detailsParams[] = $size;
    $detailsTypes .= 'sss';

    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, $detailsTypes, ...$detailsParams);

    if (!mysqli_stmt_execute($stmt)) {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to update product details']);
      mysqli_stmt_close($stmt);
      exit;
    }
    mysqli_stmt_close($stmt);
  }

  // Update products table if needed
  if (!empty($productsFields)) {
    $query = "UPDATE products SET " . implode(', ', $productsFields) . " WHERE id = ?";
    $productsParams[] = $productId;
    $productsTypes .= 's';

    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, $productsTypes, ...$productsParams);

    if (!mysqli_stmt_execute($stmt)) {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to update product information']);
      mysqli_stmt_close($stmt);      exit;
    }
    mysqli_stmt_close($stmt);
  }

  echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
  exit;
}
