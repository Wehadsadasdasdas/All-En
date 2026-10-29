<?php
require_once "connection.php";
session_start();

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $hasItems = false;
} else {
    $query = "SELECT COUNT(*) AS cnt FROM cart WHERE customer_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $hasItems = ($row['cnt'] > 0);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FAQ - Account | All-En</title>
  <link rel="stylesheet" href="FAQaccount.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<?php if (!isset($_SESSION['is_login'])): ?>
  <div class="announcement-bar">
    <p class="announcement-text">
      New here? Sign up and start your travel journey with us.
        <span style="text-decoration: underline; cursor: pointer">Sign Up Now!</span>
    </p>
    <img src="images/X.png" alt="Close" class="close-btn" />
  </div>
<?php endif; ?>

<header class="header" id="mainHeader">
  <div class="logo">All-En</div>
  <nav class="nav">
    <div class="dropdown">
      <a href="#" class="nav-item">Shop <img src="images/shop-dropdown.png" alt="Dropdown"/></a>
      <div class="dropdown-menu">
        <a href="luggage-category.php"><h3>Luggage</h3></a>
        <a href="luggage-accessory-category.php"><h3>Luggage Accessories</h3></a>
        <a href="travel-accessory-category.php"><h3>Travel Accessories</h3></a>
      </div>
    </div>
    <a href="index.php#bestseller" class="nav-item">Best Seller</a>
    <a href="index.php#newarrival" class="nav-item">New Arrivals</a>
  </nav>
  <div class="search-bar">
    <img src="images/search.png" alt="Search" />
    <input type="text" placeholder="Search for products..." class="search-input" id="searchInput" />
    <ul id="search-suggestions"></ul>
  </div>
  <div class="cart-icons">
    <img src="images/Cart.png" alt="Cart" class="cart-icon" style="cursor: pointer" />
  </div>
</header>

<div class="cart-content" id="cartContent">
  <div class="cart-inner">
    <?php if (!isset($_SESSION['is_login'])): ?>
      <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">Your Cart is empty.</h2>
      <p><a href="signin.php" style="color: blue; text-decoration: underline">Sign in</a> to view your saved items.</p>
    <?php else: ?>
      <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
        <?= $hasItems ? "Your items are waiting — checkout now!" : "Your Cart is empty." ?>
      </h2>
      <div style="margin-top: 30px">
        <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px">My Profile</h3>
        <ul style="list-style: none; padding-left: 0; line-height: 2">
          <li><a href="cart.php" style="color: black; text-decoration: none">🛒 Cart</a></li>
          <li><a href="history.php" style="color: black; text-decoration: none">📦 History</a></li>
          <form method="POST" action=""><button class="logout-btn" name="logout" style="background: none; border: none; color: black; cursor: pointer;">🔒 Logout</button></form>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="divider"></div>

<main class="about-container">
  <h1>Account FAQs</h1>
  <p style="text-align: center; max-width: 800px; margin: 20px auto; font-size: 16px;">
    Find answers to common questions about your All-En account.
  </p>

  <div class="accordion-item">
    <button class="accordion-toggle">How do I create an account?</button>
    <div class="accordion-content">
      <p>Click on <a href="signup.php" style="text-decoration: underline;">Sign Up</a> in the black announcement bar at the very top of the page to create your account.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">I forgot my password. What should I do?</button>
    <div class="accordion-content">
      <p>Go to the <a href="forgotpassword.php">Forgot Password</a> page and enter your registered email to reset your password.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">Can I delete my account?</button>
    <div class="accordion-content">
      <p>Yes, please contact our <a href="customersupport.php">Customer Support</a> and we’ll assist you in closing your account.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">Is my account information secure?</button>
    <div class="accordion-content">
      <p>Absolutely. We use secure encryption and comply with data protection laws. For more, read our <a href="privacypolicy.php">Privacy Policy</a>.</p>
    </div>
  </div>
</main>



<?php include "layout/footer.html"; ?>
<script src="FAQaccount.js" defer></script>
</body>
</html>
