<?php
require_once "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Fetch and sanitize input
  // Using filter_input for better security and validation
  // For product_id as string, we use FILTER_SANITIZE_STRING
  $product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_STRING);
  $size = filter_input(INPUT_POST, 'size', FILTER_SANITIZE_STRING);
  $color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING);

  if (empty($product_id) || empty($size) || empty($color)) { // Check for empty string after sanitization
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing product_id, size, or color.']);
    exit;
  }

  // Start a transaction for atomicity (optional but good practice for multi-step operations)
  $link->begin_transaction();

  try {
    // Get size_id from product_sizes
    $sizeQuery = "SELECT id FROM product_sizes WHERE size = ?";
    $stmtSize = $link->prepare($sizeQuery);
    if (!$stmtSize) {
      throw new Exception('Failed to prepare size query: ' . $link->error);
    }
    $stmtSize->bind_param("s", $size);
    $stmtSize->execute();
    $stmtSize->bind_result($size_id);
    $stmtSize->fetch();
    $stmtSize->close();

    if (!$size_id) {
      http_response_code(404);
      echo json_encode(['success' => false, 'error' => "Size '$size' not found."]);
      $link->rollback(); // Rollback if size not found
      exit;
    }

    // Prepare DELETE statement for product_details
    $sqlDeleteDetails = "DELETE FROM product_details WHERE PRODUCT_ID = ? AND size_id = ? AND color = ?";
    $stmtDeleteDetails = $link->prepare($sqlDeleteDetails);
    if (!$stmtDeleteDetails) {
      throw new Exception('Failed to prepare delete product_details statement: ' . $link->error);
    }

    // Changed 'i' for product_id to 's' (string)
    $stmtDeleteDetails->bind_param("sis", $product_id, $size_id, $color);

    if (!$stmtDeleteDetails->execute()) {
      throw new Exception('Failed to delete product_details: ' . $stmtDeleteDetails->error);
    }

    $affectedRowsDetails = $stmtDeleteDetails->affected_rows;
    $stmtDeleteDetails->close();

    // Check if there are any remaining product_details for this product_id
    $checkDetailsQuery = "SELECT 1 FROM product_details WHERE PRODUCT_ID = ? LIMIT 1";
    $stmtCheckDetails = $link->prepare($checkDetailsQuery);
    if (!$stmtCheckDetails) {
      throw new Exception('Failed to prepare check product_details query: ' . $link->error);
    }
    // Changed 'i' for product_id to 's' (string)
    $stmtCheckDetails->bind_param("s", $product_id);
    $stmtCheckDetails->execute();
    $stmtCheckDetails->store_result(); // Store result to check number of rows
    $hasRemainingDetails = $stmtCheckDetails->num_rows > 0;
    $stmtCheckDetails->close();

    if (!$hasRemainingDetails) {
      // No remaining product_details for this product_id, so delete the product
      $sqlDeleteProduct = "DELETE FROM products WHERE id = ?";
      $stmtDeleteProduct = $link->prepare($sqlDeleteProduct);
      if (!$stmtDeleteProduct) {
        throw new Exception('Failed to prepare delete product statement: ' . $link->error);
      }
      // Changed 'i' for product_id to 's' (string)
      $stmtDeleteProduct->bind_param("s", $product_id);

      if (!$stmtDeleteProduct->execute()) {
        throw new Exception('Failed to delete product: ' . $stmtDeleteProduct->error);
      }
      $affectedRowsProduct = $stmtDeleteProduct->affected_rows;
      $stmtDeleteProduct->close();

      if ($affectedRowsDetails > 0 || $affectedRowsProduct > 0) {
        $link->commit(); // Commit the transaction
        echo json_encode(['success' => true, 'message' => 'Product detail and possibly product deleted successfully.']);
      } else {
        $link->rollback(); // Nothing was deleted, rollback
        echo json_encode(['success' => false, 'error' => 'No product detail found matching the criteria, and product was not deleted as it might not exist or had other details.']);
      }
    } else {
      // There are still product_details, so only report on the product_details deletion
      if ($affectedRowsDetails > 0) {
        $link->commit(); // Commit the transaction
        echo json_encode(['success' => true, 'message' => 'Product detail deleted successfully. Product retained as other details exist.']);
      } else {
        $link->rollback(); // No details were deleted, rollback
        echo json_encode(['success' => false, 'error' => 'No product detail found matching the criteria.']);
      }
    }
  } catch (Exception $e) {
    $link->rollback(); // Rollback on any exception
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database operation failed: ' . $e->getMessage()]);
  } finally {
    $link->close();
  }
} else {
  http_response_code(405); // Method Not Allowed
  echo json_encode(['success' => false, 'error' => 'Only POST requests are allowed.']);
}
