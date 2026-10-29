<?php
require_once "connection.php";
session_start();

// jika logout di klik
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Check cart status
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
  <title>FAQ - Manage Deliveries | All-En</title>
  <link rel="stylesheet" href="FAQmanagedeliveries.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet" />
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
      <a href="#" class="nav-item">Shop <img src="images/shop-dropdown.png" alt="Dropdown" /></a>
      <div class="dropdown-menu">
        <a href="luggage-category.php"><h3>Luggage</h3></a>
        <a href="luggage-accessory-category.php"><h3>Luggage Accessories</h3></a>
        <a href="travel-accessory-category.php"><h3>Travel Accessories</h3></a>
      </div>
    </div>
    <a href="index.php#bestseller" class="nav-item">Best Seller</a>
    <a href="index.php#newarrival" class="nav-item">New Arrivals</a>
  </nav>

  <div class="search-bar" style="position: relative;">
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
      <p><a href="signin.php" style="color: blue; text-decoration: underline">Sign in</a> to see saved items.</p>
    <?php else: ?>
      <?php if (!$hasItems): ?>
        <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">Your Cart is empty.</h2>
      <?php else: ?>
        <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">Your items are waiting — checkout now!</h2>
      <?php endif; ?>
      <div style="margin-top: 30px">
        <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px">My Profile</h3>
        <ul style="list-style: none; padding-left: 0; line-height: 2;">
          <li><a href="cart.php" style="color: black; text-decoration: none">🛒 Cart</a></li>
          <li><a href="history.php" style="color: black; text-decoration: none">📦 Order History</a></li>
          <form method="POST" action="">
            <button class="logout-btn" type="submit" name="logout" style="background: none; border: none; color: black; text-decoration: none; cursor: pointer;">🔒 Logout</button>
          </form>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="divider"></div>

<main class="about-container">
  <h1>FAQ: Managing Deliveries</h1>
  <p style="text-align: center; max-width: 800px; margin: 20px auto; font-size: 16px;">
    Have questions about your delivery? We’ve got answers.
  </p>

  <div class="accordion-item">
    <button class="accordion-toggle">How do I track my order?</button>
    <div class="accordion-content">
      <p>You can track your order by visiting your <a href="history.php">Order History</a> page after logging in. The latest status will be shown beside each order.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">Can I change my delivery address?</button>
    <div class="accordion-content">
      <p>If your order hasn’t shipped yet, contact us at <strong>allenluggages@gmail.com</strong> as soon as possible. Once shipped, we can no longer modify the address.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">What if I'm not home when my delivery arrives?</button>
    <div class="accordion-content">
      <p>The courier will usually attempt redelivery or leave a notice for pickup instructions. Contact the courier for more details using your tracking number.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">What should I do if my package is late?</button>
    <div class="accordion-content">
      <p>Please allow a few extra business days beyond the estimated date. If it still hasn't arrived, contact our support team for help.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">Can I schedule a specific delivery time?</button>
    <div class="accordion-content">
      <p>We currently do not support delivery time scheduling, but many couriers provide tracking updates and estimated arrival windows.</p>
    </div>
  </div>
</main>

<?php include "layout/footer.html"; ?>
<script src="FAQmanagedeliveries.js" defer></script>
</body>
</html>
