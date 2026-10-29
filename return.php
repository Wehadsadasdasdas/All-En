<?php
require_once "connection.php";
session_start();

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Refund Policy - All-En</title>
  <link rel="stylesheet" href="return.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Announcement Bar -->
<!-- Header -->
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
        <img src="images/Cart.png" alt="Wishlist" class="cart-icon" style="cursor: pointer" />
    </div>
</header>

<div class="cart-content" id="cartContent">
    <div class="cart-inner">
        <?php if (!isset($_SESSION['is_login'])): ?>
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">Your Cart is empty.</h2>
            <p><a href="signin.php" style="color: blue; text-decoration: underline">Sign in</a> to see your items.</p>
        <?php else: ?>
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">Your items are waiting — checkout now!</h2>
            <div style="margin-top: 30px">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px">My Profile</h3>
                <ul style="list-style: none; padding-left: 0; line-height: 2">
                    <li><a href="cart.php" style="color: black; text-decoration: none">🛒 Cart</a></li>
                    <li><a href="history.php" style="color: black; text-decoration: none">📦 History</a></li>
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
  <h1>Refund Policy</h1>
  <p style="text-align: center; max-width: 800px; margin: 20px auto; font-size: 16px;">
    We want you to love what you ordered! If you're not satisfied, here’s how to refund it.
  </p>

  <div class="about-section">
    <div class="section-title"><h2>14-Day <br> Refund Window</h2></div>
    <div class="section-content">
      <p>You have 14 calendar days from the date of delivery to refund your item(s) for a refund or exchange.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Eligibility</h2></div>
    <div class="section-content">
      <p>Refunded items must be unused, in original packaging, and with all tags attached. Items showing signs of wear will not be accepted.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>How to Refund</h2></div>
    <div class="section-content">
      <p>To request a refund, go to the <a href="history.php">Order History</a> page and click the <strong>Request Refund</strong> button under the order you wish to return.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Refunds</h2></div>
    <div class="section-content">
      <p>Once we receive and inspect your refund, we will process your refund to your original payment method within 3-5 business days.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Shipping</h2></div>
    <div class="section-content">
      <p>You will be responsible for paying for your own shipping costs for returning your item. Shipping costs are non-refundable.</p>
    </div>
  </div>
</main>

  

<?php include "layout/footer.html" ?>
<script src="return.js" defer></script>
</body>
</html>
