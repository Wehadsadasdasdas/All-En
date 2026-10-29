<?php
require_once "connection.php";
session_start();
$loginStatus = ""; // buat variabel status

// Cek apakah user sudah login
if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true) {
  header("Location: index.php");
  exit();
}

if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $username = mysqli_real_escape_string($link, $username);
  $password = mysqli_real_escape_string($link, $password);

  $query = "SELECT * FROM customers WHERE username='$username'";
  $result = mysqli_query($link, $query);

  if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    if (password_verify($password, $row['password'])) {
      $_SESSION['is_login'] = true;
      $_SESSION['username'] = $username;
      $_SESSION['user_id'] = $row['id'];
      $loginStatus = "success";
    } else {
      $loginStatus = "fail";
    }
  } else {
    $loginStatus = "fail";
  }

}


// Handle Ajax Search Request
if (isset($_GET['ajax_search'])) {
    $term = mysqli_real_escape_string($link, $_GET['ajax_search']);
    $query = "
        SELECT p.id, p.name, p.image_link
        FROM products p 
        WHERE p.name LIKE '%$term%' 
    ";
    $result = mysqli_query($link, $query);
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    echo json_encode($products);
    exit;
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
          <a href="luggage-category.php">
            <h3>Luggage</h3>
          </a>

          <a href="luggage-accessory-category.php">
            <h3>Luggage Accessories</h3>
          </a>

          <a href="travel-accessory-category.php">
            <h3>Acessories</h3>
          </a>
        </div>
      </div>
        <a href="index.php#bestseller" class="nav-item">Best Seller</a>
        <a href="index.php#newarrival" class="nav-item">New Arrivals</a>
    </nav>

    <div class="search-bar" style="position: relative;">
            <img src="images/search.png" alt="Search" />
            <input
                type="text"
                placeholder="Search for products..."
                class="search-input"
                id="searchInput"
            />

            <ul id="search-suggestions"></ul>
        </div>


    <div class="cart-icons">
      <img src="images/Cart.png" alt="Wishlist" class="cart-icon" style="cursor: pointer;">
    </div>
  </header>

  <div class="cart-content" id="cartContent">
    <div class="cart-inner">
        <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
            Your Bag is empty.
        </h2>
        <p>
            <a
                href="/~D1229568/allen/signin.php"
                style="color: blue; text-decoration: underline">Sign in</a>
            to see if you have any saved items
        </p>
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
    <h1 class="signup-title">Sign-In</h1>

    <form class="form-container" method="POST">
      <input type="text" name="username" class="input-field" placeholder="Username" required>
      <input type="password" name="password" class="input-field" placeholder="Password" autocomplete="new-password" required>

      <div style="text-align: center;">
        <a href="forgotpassword.php" class="forgot-link">Forgot Password?</a>
      <!-- <a href="forgotpassword.php" style="font-size: 14px; color: blue; text-decoration: underline;">Forgot Password?</a> -->
      </div>


      <button type="submit" class="create-account-btn" name="login">Login</button>
    </form>

    <a href="signup.php" class="signin-link">Don't have account? Click here</a>
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
    const loginStatus = "<?php echo $loginStatus; ?>";
    const alertBox = document.getElementById("customAlert");
    const alertMessage = document.querySelector(".custom-alert-message");
    const alertButton = document.querySelector(".custom-alert-btn");

    if (loginStatus === "success") {
      alertMessage.textContent = "You have successfully signed in";
      alertButton.textContent = "Go to homepage";
      alertButton.onclick = () => window.location.href = "index.php";
      alertBox.style.display = "flex";
    } else if (loginStatus === "fail") {
      alertMessage.textContent = "Your username or password is wrong";
      alertButton.textContent = "Try again";
      alertButton.onclick = () => window.location.href = "signin.php";
      alertBox.style.display = "flex";
    }
  </script>


</body>

</html>