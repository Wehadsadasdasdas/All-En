<?php
require_once "connection.php"; // Your database connection file

header('Content-Type: application/json'); // Set header for JSON response

$response = ['success' => false, 'error' => '', 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $response['error'] = 'Invalid request method.';
  echo json_encode($response);
  exit();
}

// Get non-file data from $_POST
$id = trim($_POST['id'] ?? '');
$name = trim($_POST['name'] ?? '');
$category_id = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
$description = trim($_POST['description'] ?? '');

// Decode JSON data for sizes/colors, which was stringified and appended to FormData
$sizes_json = $_POST['sizes'] ?? '';
$sizes = json_decode($sizes_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  $response['error'] = 'Invalid JSON for sizes: ' . json_last_error_msg();
  echo json_encode($response);
  exit();
}

// Basic input validation
if (empty($id) || empty($name) || $category_id === false || empty($sizes)) {
  $response['error'] = 'Missing required product data (ID, Name, Category, Sizes).';
  echo json_encode($response);
  exit();
}

// --- Image Upload Handling ---
$image_link = null;
// CORRECTED: Ensure this path matches where your frontend expects images
// Based on your frontend: <img src="Product_images/${product.image}"
// This means the images are expected in a folder named 'Product_images'
// that is in the same directory as this PHP script (or accessible from the web root).
$upload_dir = __DIR__ . '/Product_images/';


// Create the directory if it doesn't exist
if (!is_dir($upload_dir)) {
  // Attempt to create the directory with read/write/execute permissions for owner,
  // and read/execute for group/others. True for recursive creation.
  if (!mkdir($upload_dir, 0755, true)) {
    $response['error'] = 'Failed to create upload directory.';
    echo json_encode($response);
    exit();
  }
}

// Check if a file was uploaded and there's no upload error
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
  $file_tmp_path = $_FILES['product_image']['tmp_name'];
  $file_name = $_FILES['product_image']['name'];
  $file_size = $_FILES['product_image']['size'];
  $file_type = $_FILES['product_image']['type'];
  $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

  $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
  $max_file_size = 5 * 1024 * 1024; // 5 MB

  if (!in_array($file_ext, $allowed_extensions)) {
    $response['error'] = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
    echo json_encode($response);
    exit();
  }

  if ($file_size > $max_file_size) {
    $response['error'] = 'File size exceeds the limit of 5MB.';
    echo json_encode($response);
    exit();
  }

  // Generate a unique filename using product ID and a timestamp to avoid conflicts
  $new_file_name = $id . '_' . time() . '.' . $file_ext;
  $dest_path = $upload_dir . $new_file_name;

  // Move the uploaded file
  if (move_uploaded_file($file_tmp_path, $dest_path)) {
    $image_link = $new_file_name; // This is the filename to save in the database
  } else {
    $response['error'] = 'Failed to move uploaded file to destination.';
    echo json_encode($response);
    exit();
  }
} else if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
  // Handle other upload errors (e.g., file too large as per php.ini, partial upload, etc.)
  $response['error'] = 'File upload error: ' . $_FILES['product_image']['error'];
  echo json_encode($response);
  exit();
}
// If no file was uploaded (UPLOAD_ERR_NO_FILE), $image_link remains null, which is fine if image is optional.

// Start database transaction for atomicity
mysqli_begin_transaction($link);

try {
  // 1. Insert into 'products' table
  $stmt_product = mysqli_prepare($link, "INSERT INTO products (id, name, category_id, image_link, description) VALUES (?, ?, ?, ?, ?)");
  if (!$stmt_product) {
    throw new Exception('Prepare failed for products table: ' . mysqli_error($link));
  }
  mysqli_stmt_bind_param($stmt_product, "ssiss", $id, $name, $category_id, $image_link, $description);

  if (!mysqli_stmt_execute($stmt_product)) {
    // Check for duplicate entry error specifically for the 'products' table
    if (mysqli_errno($link) == 1062) { // MySQL error code for duplicate entry
      throw new Exception('Product ID "' . htmlspecialchars($id) . '" already exists. If you want to add variants, use the dedicated function or a different Product ID.');
    }
    throw new Exception('Execute failed for products table: ' . mysqli_stmt_error($stmt_product));
  }
  mysqli_stmt_close($stmt_product);

  // 2. Insert into 'product_details' for each size and color
  $stmt_details = mysqli_prepare($link, "INSERT INTO product_details (PRODUCT_ID, size_id, price, capital, stock, color, warranty_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
  if (!$stmt_details) {
    throw new Exception('Prepare failed for product_details: ' . mysqli_error($link));
  }

  foreach ($sizes as $size_entry) {
    $size_id = filter_var($size_entry['size_id'], FILTER_VALIDATE_INT);
    if ($size_id === false || $size_id <= 0) { // Ensure size_id is valid
      throw new Exception('Invalid size ID encountered: ' . htmlspecialchars($size_entry['size_id']));
    }

    foreach ($size_entry['colors'] as $color_entry) {
      $color = trim($color_entry['color'] ?? '');
      $stock = filter_var($color_entry['stock'] ?? '', FILTER_VALIDATE_INT);
      $price = filter_var($color_entry['price'] ?? '', FILTER_VALIDATE_FLOAT);
      $capital = filter_var($color_entry['capital'] ?? '', FILTER_VALIDATE_FLOAT);
      $warrantyToken = trim($color_entry['warrantyToken'] ?? '');

      if (empty($color) || $stock === false || $price === false || $capital === false) {
        throw new Exception('Missing or invalid detail data for a color/size variant.');
      }

      // Bind parameters: s (string) for PRODUCT_ID, i (int) for size_id,
      // d (double) for price, d (double) for capital,
      // i (int) for stock, s (string) for color, s (string) for warranty_token
      mysqli_stmt_bind_param($stmt_details, 'siddiss', $id, $size_id, $price, $capital, $stock, $color, $warrantyToken);

      if (!mysqli_stmt_execute($stmt_details)) {
        // Check for duplicate entry on product_details (PRODUCT_ID, size_id, color should be unique)
        if (mysqli_errno($link) == 1062) {
          throw new Exception("Duplicate entry for product ID '{$id}' with size '{$size_id}' and color '{$color}'. This variant already exists.");
        }
        throw new Exception('Execute failed for product details (variant): ' . mysqli_stmt_error($stmt_details));
      }
    }
  }
  mysqli_stmt_close($stmt_details);

  mysqli_commit($link); // Commit the transaction if all operations are successful
  $response['success'] = true;
  $response['message'] = 'Product and its details inserted successfully.';
} catch (Exception $e) {
  mysqli_rollback($link); // Rollback the transaction on error
  $response['error'] = 'Database error: ' . $e->getMessage();

  // If an image was uploaded but the DB transaction failed, delete the uploaded image
  // Ensure $dest_path is defined before attempting to use it in this scope.
  // It is defined in the 'if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK)' block.
  // So, we use a check here.
  if (isset($dest_path) && $image_link && file_exists($dest_path)) {
    unlink($dest_path);
    $response['error'] .= ' Uploaded image "' . htmlspecialchars($new_file_name ?? '') . '" removed due to database transaction error.';
  }
} finally {
  mysqli_close($link); // Close the database connection
}

echo json_encode($response);
