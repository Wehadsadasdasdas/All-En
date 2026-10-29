<?php
require_once "connection.php";
session_start();

// Logout logic
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Check if user has items in cart
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
  <title>FAQ - Orders | All-En</title>
  <link rel="stylesheet" href="FAQorders.css" />
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
  <h1>FAQ: Orders</h1>
  <p style="text-align: center; max-width: 800px; margin: 20px auto; font-size: 16px;">
    Here’s everything you need to know about placing and managing orders with All-En.
  </p>

  <div class="accordion-item">
    <button class="accordion-toggle">How do I place an order?</button>
    <div class="accordion-content">
      <p>Simply add items to your cart and proceed to checkout. You will need to create or sign in to your account to complete your purchase.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">Can I cancel or modify my order?</button>
    <div class="accordion-content">
      <p>You may request a cancellation or modification within 1 hour of placing your order by contacting us at <strong>allenluggages@gmail.com</strong>. After that, the order may already be in processing or shipped.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">How do I check my order status?</button>
    <div class="accordion-content">
      <p>You can view your order status and history by visiting your <a href="history.php">Order History</a> page.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">What payment methods do you accept?</button>
    <div class="accordion-content">
      <p>We accept credit cards (Visa, MasterCard), Apple Pay, and other secure payment gateways depending on your region.</p>
    </div>
  </div>

  <div class="accordion-item">
    <button class="accordion-toggle">What if I received the wrong item or something is missing?</button>
    <div class="accordion-content">
      <p>We’re sorry! Please contact our support team within 7 days of receiving your package. Provide your order number and a photo if possible.</p>
    </div>
  </div>
</main>

<?php include "layout/footer.html"; ?>
<script src="FAQorders.js" defer></script>
</body>
</html>
