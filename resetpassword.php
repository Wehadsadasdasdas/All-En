<?php
require_once "connection.php";
date_default_timezone_set('Asia/Taipei');
$resetStatus = "";

// Get token from URL
$token = $_GET['token'] ?? '';

// If the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $newPassword = mysqli_real_escape_string($link, $_POST["new_password"]);
  $confirmPassword = mysqli_real_escape_string($link, $_POST["confirm_password"]);
  $token = mysqli_real_escape_string($link, $_POST["token"]);

  if ($newPassword !== $confirmPassword) {
    $resetStatus = "mismatch";
  } else {
    // Check token in password_resets and join with customers table
    $query = "SELECT pr.email FROM password_resets pr
          INNER JOIN customers c ON pr.email = c.email
          WHERE pr.token = '$token'
          AND pr.expires_at > NOW()";

    $result = mysqli_query($link, $query);

    if (mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_assoc($result);
      $email = $row['email'];

      // Update password in customers table
      $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
      $updateQuery = "UPDATE customers SET password = '$hashedPassword' WHERE email = '$email'";
      mysqli_query($link, $updateQuery);

      // Remove used token
      mysqli_query($link, "DELETE FROM password_resets WHERE email = '$email'");

      $resetStatus = "success";
    } else {
      $resetStatus = "invalid";
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All-En Sign-In</title>
  <link rel="stylesheet" href="signin.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>

  <!-- Header -->
  <header class="header" id="mainHeader">
    <div class="logo">All-En</div>

    <nav class="nav">

      <div class="dropdown">
        <a href="#" class="nav-item">Shop <img src="images/shop-dropdown.png" alt="Dropdown"></a>
        <div class="dropdown-menu">
          <a href="men.html">
            <h3>Luggage</h3>
          </a>

          <a href="women.html">
            <h3>Luggage Accessories</h3>
          </a>

          <a href="accessories.html">
            <h3>Acessories</h3>
          </a>
        </div>
      </div>
      <a href="#" class="nav-item">On Sale</a>
      <a href="#" class="nav-item">New Arrivals</a>
    </nav>

    <div class="search-bar">
      <img src="images/search.png" alt="Search">
      <input type="text" placeholder="Search for products..." class="search-input">
    </div>
    <div class="cart-icons">
      <img src="images/Cart.png" alt="Wishlist" class="cart-icon" style="cursor: pointer;">
    </div>
  </header>

  <div class="cart-content" id="cartContent">
    <div class="cart-inner">
      <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px;">Your Bag is empty.</h2>
      <p><a href="signin.php" style="color: blue; text-decoration: underline;">Sign in</a> to see if you have any saved items</p>
      <div style="margin-top: 30px;">
        <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">My Profile</h3>
        <ul style="list-style: none; padding-left: 0; line-height: 2;">
          <li><a href="cart.php" style="color: black; text-decoration: none">🧾 Wishlist</a></li>
          <li><a href="#" style="color: black; text-decoration: none">👤 History</a></li>
          <li><a href="signin.php" style="color: black; text-decoration: none">🔐 Sign in</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="index.php" class="breadcrumb-item">Home</a>
    <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
    <a href="#" class="breadcrumb-item active">Login</a>
  </div>

  <!-- Main Content -->
  <main class="main-content">
  <h1 class="signup-title">Reset Password</h1>

  <form class="form-container" method="POST">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
    <input type="password" name="new_password" class="input-field" placeholder="New Password" required />
    <input type="password" name="confirm_password" class="input-field" placeholder="Confirm New Password" required />

    <button type="submit" class="create-account-btn">Reset Password</button>
  </form>

  <a href="signin.php" class="signin-link">Back to Sign In</a>
</main>

  <?php include "layout/footer.html" ?>

  <div class="custom-alert" id="customAlert" style="display: none;">
    <div class="custom-alert-box">
      <p class="custom-alert-message"></p>
      <button class="custom-alert-btn" onclick="window.location.href='index.php'">Go to homepage</button>
    </div>
  </div>


  <script src="signin.js"></script>

  <script>
    const resetStatus = "<?php echo $resetStatus; ?>";
    const alertBox = document.getElementById("customAlert");
    const alertMessage = document.querySelector(".custom-alert-message");
    const alertButton = document.querySelector(".custom-alert-btn");
    if (resetStatus === "success") {
      alertMessage.textContent = "✅ Your password has been reset successfully!";
      alertButton.textContent = "Go to Sign In";
      alertButton.onclick = () => window.location.href = "signin.php";
      alertBox.style.display = "flex";
    } else if (resetStatus === "mismatch") {
      alertMessage.textContent = "❌ Passwords do not match!";
      alertButton.textContent = "Try Again";
      alertButton.onclick = () => window.location.href = window.location.href;
      alertBox.style.display = "flex";
    } else if (resetStatus === "invalid") {
      alertMessage.textContent = "❌ Invalid or expired token.";
      alertButton.textContent = "Back to Forgot Password";
      alertButton.onclick = () => window.location.href = "forgotpassword.php";
      alertBox.style.display = "flex";
    }
  </script>


</body>

</html>