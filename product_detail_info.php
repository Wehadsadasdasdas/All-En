<?php
// get_product_details_by_size.php

require_once "connection.php"; // Your existing database connection file (should establish $link)

header('Content-Type: application/json');
$response = ['success' => false, 'data' => [], 'error' => null];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get the JSON input sent from JavaScript
  $inputJSON = file_get_contents('php://input');
  $input = json_decode($inputJSON, TRUE); // Convert JSON into an associative array

  $productId = null;
  $sizeName = null;

  // Check if product_id and size_name are provided in the JSON payload
  if (isset($input['product_id'])) {
    $productId = $input['product_id'];
  }
  if (isset($input['size_name'])) { // JavaScript sends the textContent of the size option
    $sizeName = $input['size_name'];
  }

  if ($productId !== null && $sizeName !== null) {
    // Your SQL query using explicit JOIN
    // We select pz.size and alias it to size_name for consistency in the output if desired
    $query = "SELECT pd.PRODUCT_ID, pd.price, pd.stock, pd.color, pz.size AS size_name
                  FROM `product_details` pd
                  INNER JOIN `product_sizes` pz ON pd.size_id = pz.id
                  WHERE pd.PRODUCT_ID = ? AND pz.size = ?";

    // Use prepared statements to prevent SQL injection
    if ($stmt = mysqli_prepare($link, $query)) {
      // Bind parameters: 'i' for integer (productId), 's' for string (sizeName)
      mysqli_stmt_bind_param($stmt, "ss", $productId, $sizeName);

      // Execute the statement
      if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $details = [];
        while ($row = mysqli_fetch_assoc($result)) {
          // Ensure numeric types are correct if necessary
          $row['PRODUCT_ID'] = (int) $row['PRODUCT_ID'];
          $row['price'] = (float) $row['price']; // Assuming price is decimal/float
          $row['stock'] = (int) $row['stock'];
          $details[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $details;
      } else {
        $response['error'] = "Query execution failed: " . mysqli_stmt_error($stmt);
      }
      // Close the statement
      mysqli_stmt_close($stmt);
    } else {
      $response['error'] = "Failed to prepare statement: " . mysqli_error($link);
    }
  } else {
    $response['error'] = "Product ID or Size Name not provided in the request.";
  }
} else {
  $response['error'] = "Invalid request method. Only POST is accepted for this endpoint.";
}

// Close the database connection (if your connection.php doesn't do it on script end)
if (isset($link)) {
  mysqli_close($link);
}

// Send the JSON response back to JavaScript
echo json_encode($response);
exit;
