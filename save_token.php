<?php
require_once "connection.php";
header('Content-Type: application/json');

date_default_timezone_set('Asia/Taipei');

$data = json_decode(file_get_contents("php://input"), true);
$email = mysqli_real_escape_string($link, $data["email"]);
$token = mysqli_real_escape_string($link, $data["token"]);
$expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // 1 hour expiry

// Insert or update the token
$query = "INSERT INTO password_resets (email, token, expires_at)
          VALUES ('$email', '$token', '$expires')
          ON DUPLICATE KEY UPDATE token='$token', expires_at='$expires'";

if (mysqli_query($link, $query)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => mysqli_error($link)]);
}
?>
