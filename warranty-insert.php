<?php
session_start();
require_once "connection.php";

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_SESSION['user_id'];
  $data = json_decode(file_get_contents('php://input'), true);
  $warranty_token = trim($data['warranty_id'] ?? '');
  $warranty_reason = trim($data['warranty_reason'] ?? '');
  $sql = "SELECT product_id, size_id, color FROM product_details WHERE warranty_token = ? LIMIT 1";
  $stmt = $link->prepare($sql);
  $stmt->bind_param("s", $warranty_token);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $product_id = $row['product_id'];
    $size_id = $row['size_id'];
    $color = $row['color'];

    $stmt->close(); // Close first statement

    $sql2 = "SELECT od.*
                 FROM order_detail od
                 JOIN orders o ON od.order_id = o.id
                 WHERE o.customer_id = ? AND od.product_id = ? AND od.size_id = ? AND od.color = ?
                 LIMIT 1";
    $stmt2 = $link->prepare($sql2);
    $stmt2->bind_param("isis", $user_id, $product_id, $size_id, $color);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->fetch_assoc()) {
      $stmt2->close();

      $claim_date = date("Y-m-d");
      $status_id = 1;
      $insert_sql = "INSERT INTO warranty (claim_date, status_id, description, customer_id, warranty_token) VALUES (?, ?, ?, ?, ?)";
      $insert_stmt = $link->prepare($insert_sql);
      $insert_stmt->bind_param("sisis", $claim_date, $status_id, $warranty_reason, $user_id, $warranty_token);

      if ($insert_stmt->execute()) {
        $insert_stmt->close();
        $link->close();
        echo json_encode(['success' => true, 'redirect' => 'warrant-process.php']);
        exit();
      } else {
        $insert_stmt->close();
        $link->close();
        echo json_encode(['success' => false, 'message' => 'Failed to submit warranty claim.']);
        exit();
      }
    } else {
      $stmt2->close();
      $link->close();
      echo json_encode(['success' => false, 'message' => 'You have not purchased this product.']);
      exit();
    }
  } else {
    $stmt->close();
    $link->close();
    echo json_encode(['success' => false, 'message' => 'Warranty token not found.']);
    exit();
  }
}
