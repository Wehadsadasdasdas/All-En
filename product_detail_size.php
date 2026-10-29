<?php
// get_product_sizes.php

require_once "connection.php"; // Your existing connection file, $link should be available here

header('Content-Type: application/json');
$response = ['success' => false, 'data' => [], 'error' => null];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get the JSON input sent from JavaScript
  $inputJSON = file_get_contents('php://input');
  $input = json_decode($inputJSON, TRUE); // Convert JSON into an associative array

  $productId = null;
  if (isset($input['product_id'])) {
    $productId = $input['product_id'];
  }
  // As a fallback, you might also check $_POST if not sending JSON
  // elseif (isset($_POST['product_id'])) {
  // $productId = $_POST['product_id'];
  // }


  if ($productId !== null) {

    // Your specific SQL query
    // Using an alias 'pd' for product_details for brevity
    $query = "SELECT pd.PRODUCT_ID, pz.size as size
                  FROM `product_details` pd, `product_sizes` pz
                  WHERE pd.PRODUCT_ID = ?
                  AND pz.id = pd.size_id
                  GROUP BY pd.size_id";

    // Use prepared statements to prevent SQL injection
    if ($stmt = mysqli_prepare($link, $query)) {
      // Bind the integer product ID to the placeholder
      mysqli_stmt_bind_param($stmt, "s", $productId); // "i" for integer type

      // Execute the statement
      if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $sizes = [];
        while ($row = mysqli_fetch_assoc($result)) {
          $row['size'] = $row['size'];
          $sizes[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $sizes;
      } else {
        $response['error'] = "Query execution failed: " . mysqli_stmt_error($stmt);
      }
      // Close the statement
      mysqli_stmt_close($stmt);
    } else {
      $response['error'] = "Failed to prepare statement: " . mysqli_error($link);
    }
  } else {
    $response['error'] = "Product ID not provided in the request.";
  }
} else {
  $response['error'] = "Invalid request method. Only POST is accepted for this endpoint.";
}

// Close the database connection (if your connection.php doesn't do it on script end)
mysqli_close($link);

// Send the JSON response back to JavaScript
echo json_encode($response);
exit;
