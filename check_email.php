<?php
require_once "connection.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$email = mysqli_real_escape_string($link, $data["email"]);

$query = "SELECT * FROM customers WHERE email = '$email'";
$result = mysqli_query($link, $query);

echo json_encode(["exists" => mysqli_num_rows($result) > 0]);
?>
