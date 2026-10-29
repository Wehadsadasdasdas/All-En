<?php
require_once "connection.php";
session_start();
$loginStatus = ""; // Initialize status variable

// Cek apakah user sudah login, jika sudah, redirect ke dashboard
if (isset($_SESSION['admin_is_login']) && $_SESSION['admin_is_login'] === true) {
  header("Location: admin-dashboard.php");
  exit();
}

if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['password']; // This is the plain-text password entered by the user

  // Prepare a statement to retrieve the admin's data, including the hashed password
  // Use prepared statements for security against SQL injection
  $query = "SELECT id, Name, Password, role_id FROM admins WHERE Name = ?";
  $stmt = mysqli_prepare($link, $query);

  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $username); // Bind username parameter
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
      $row = mysqli_fetch_assoc($result);
      $stored_hashed_password = $row['Password']; // Get the hashed password from the database

      // Verify the entered plain-text password against the stored hashed password
      if (password_verify($password, $stored_hashed_password)) {
        // Login successful!
        $_SESSION['admin_is_login'] = true;
        $_SESSION['admin_username'] = $row['Name'];
        $_SESSION['admin_user_id'] = $row['id'];
        $_SESSION['admin_role_id'] = $row['role_id']; // Store role_id if needed elsewhere

        $loginStatus = "success";
      } else {
        // Password incorrect
        $loginStatus = "fail";
      }
    } else {
      // Username not found
      $loginStatus = "fail";
    }
    mysqli_stmt_close($stmt);
  } else {
    // Error preparing the statement
    $loginStatus = "fail"; // Or a more specific error message
    error_log("Failed to prepare statement for login: " . mysqli_error($link));
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All-En Admin Sign-In</title>
  <link rel="stylesheet" href="admin-signin.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
  <main class="main-content">
    <h1 class="signup-title">Sign-In</h1>

    <form class="form-container" method="POST">
      <input type="text" name="username" class="input-field" placeholder="Username" required>
      <input type="password" name="password" class="input-field" placeholder="Password" autocomplete="current-password" required>

      <button type="submit" class="create-account-btn" name="login">Login</button>
    </form>
  </main>
  <div class="custom-alert" id="customAlert" style="display: none;">
    <div class="custom-alert-box">
      <p class="custom-alert-message"></p>
      <button class="custom-alert-btn" onclick="window.location.href='index.php'">Go to homepage</button>
    </div>
  </div>


  <script src="admin-signin.js"></script>

  <script>
    const loginStatus = "<?php echo $loginStatus; ?>";
    const alertBox = document.getElementById("customAlert");
    const alertMessage = document.querySelector(".custom-alert-message");
    const alertButton = document.querySelector(".custom-alert-btn");

    if (loginStatus === "success") {
      alertMessage.textContent = "You have successfully signed in!";
      alertButton.textContent = "Go to Dashboard"; // Changed text to be more specific
      alertButton.onclick = () => window.location.href = "admin-lobby.php";
      alertBox.style.display = "flex";
    } else if (loginStatus === "fail") {
      alertMessage.textContent = "Invalid username or password. Please try again."; // More specific error message
      alertButton.textContent = "Try again";
      alertButton.onclick = () => window.location.href = "admin-signin.php"; // Redirect to refresh form
      alertBox.style.display = "flex";
    }
  </script>
</body>

</html>