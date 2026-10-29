<?php
require_once "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');

  // Decode JSON input for all data
  $id = trim($_POST['id'] ?? '');
  $sizes = json_decode($_POST['sizes'] ?? '[]', true);

  // Validate required fields
  if (empty($id) || empty($sizes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid required data (Product ID or Sizes).']);
    exit;
  }


  // Start transaction for atomicity
  mysqli_begin_transaction($link);

  try {
    // Prepare product_details insert/update statement
    $detailsStmt = mysqli_prepare($link, "
            INSERT INTO product_details (PRODUCT_ID, size_id, price, capital, stock, color, warranty_token)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                price = VALUES(price),
                capital = VALUES(capital),
                stock = VALUES(stock),
                warranty_token = VALUES(warranty_token)
        ");

    if (!$detailsStmt) {
      throw new Exception('Failed to prepare product details statement: ' . mysqli_error($link));
    }

    foreach ($sizes as $size) {
      $size_id = filter_var($size['size_id'] ?? null, FILTER_VALIDATE_INT);
      if ($size_id === false || $size_id <= 0) {
        throw new Exception('Invalid size ID encountered: ' . htmlspecialchars($size['size_id'] ?? 'N/A'));
      }

      $colors = $size['colors'] ?? [];
      if (!is_array($colors) || empty($colors)) {
        throw new Exception('No colors provided for size ID: ' . $size_id);
      }

      foreach ($colors as $colorInfo) {
        // Apply trim and validation
        $color = trim($colorInfo['color'] ?? '');
        $stock = filter_var($colorInfo['stock'] ?? null, FILTER_VALIDATE_INT);
        $price = filter_var($colorInfo['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $capital = filter_var($colorInfo['capital'] ?? null, FILTER_VALIDATE_FLOAT);
        $warrantyToken = trim($colorInfo['warrantyToken'] ?? '');

        if (empty($color) || $stock === false || $price === false || $capital === false) {
          throw new Exception('Missing or invalid detail data for a color/size variant.');
        }

        mysqli_stmt_bind_param($detailsStmt, 'siddiss', $id, $size_id, $price, $capital, $stock, $color, $warrantyToken);

        if (!mysqli_stmt_execute($detailsStmt)) {
          // Check for specific duplicate key error on product_details if needed
          // (though ON DUPLICATE KEY UPDATE handles this gracefully by updating)
          throw new Exception('Failed to insert/update product details for variant (Product ID: ' . $id . ', Size ID: ' . $size_id . ', Color: ' . $color . '): ' . mysqli_stmt_error($detailsStmt));
        }
      }
    }

    mysqli_stmt_close($detailsStmt);
    mysqli_commit($link); // Commit the transaction

    echo json_encode(['success' => true, 'message' => 'Product details inserted/updated successfully']);
  } catch (Exception $e) {
    mysqli_rollback($link); // Rollback on error
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database operation failed: ' . $e->getMessage()]);
  } finally {
    mysqli_close($link);
  }
  exit;
}

// If not a POST request, return an error
http_response_code(405); // Method Not Allowed
echo json_encode(['error' => 'Invalid request method.']);
