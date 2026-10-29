<?php
require_once "connection.php";
session_start();

//jika logout di klik
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<?php
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // Jika user belum login, langsung anggap keranjang kosong
    $hasItems = false;
} else {
    // Cek apakah ada item di cart untuk user ini
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Support - All-En</title>
  <link rel="stylesheet" href="customersupport.css">
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
                <a href="luggage-category.php">
                    <h3>Luggage</h3>
                </a>
                <a href="luggage-accessory-category.php">
                    <h3>Luggage Accessories</h3>
                </a>
                <a href="travel-accessory-category.php">
                    <h3>Travel Accessories</h3>
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
        <img src="images/Cart.png" alt="Wishlist" class="cart-icon" style="cursor: pointer" />
    </div>
</header>

<div class="cart-content" id="cartContent">
    <div class="cart-inner">
        <?php if (!isset($_SESSION['is_login'])): ?>
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">Your Cart is empty.</h2>
            <p>
                <a href="/~D1229568/allen/signin.php" style="color: blue; text-decoration: underline">Sign in</a>
                to see if you have any saved items
            </p>
        <?php else: ?>
                <?php if (!$hasItems): ?>
                    <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
                        Your Cart is empty.
                    </h2>
                 <?php else: ?>
                    <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
                        Your items are waiting — checkout now!
                    </h2>
                 <?php endif; ?>
                <div style="margin-top: 30px">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px">
                        My Profile
                    </h3>
                    <ul style="list-style: none; padding-left: 0; line-height: 2">
                        <li>
                            <a
                                href="/~D1229568/allen/cart.php"
                                style="color: black; text-decoration: none">🛒 Cart</a>
                        </li>
                        <li>
                            <a
                                href="/~D1229568/allen/history.php"
                                style="color: black; text-decoration: none">📦 History</a>
                        </li>
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
  <h1>Customer Support</h1>
  <p style="text-align: center; max-width: 800px; margin: 20px auto; font-size: 16px;">
    We're here to help you! Whether you have questions about your order, returns, or just need guidance, our support team is always ready.
  </p>

  <div class="about-section">
    <div class="section-title"><h2>How Can We Help?</h2></div>
    <div class="section-content">
      <p>Browse our frequently asked questions or contact our team directly below.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Contact Us</h2></div>
    <div class="section-content">
      <p>Email: allenluggages@gmail.com<br>Phone: +886 912 345 678<br>Hours: Mon - Fri, 9 AM - 6 PM (Taiwan Time)</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Order Tracking</h2></div>
    <div class="section-content">
      <p>Check the status of your order by visiting the <a href="history.php">Order Tracking</a></p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Refunds</h2></div>
    <div class="section-content">
      <p>All-En offers a 30-day refund policy. Visit our <a href="return.php">Refund Policy</a> page for step-by-step instructions on refunding your item.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Shipping Information</h2></div>
    <div class="section-content">
      <p>Learn about our domestic and international shipping options, delivery times, and fees on our <a href="shippinginfo.php">Shipping Info</a> page.</p>
    </div>
  </div>
</main>

<?php include "layout/footer.html" ?>
<script src="customersupport.js" defer></script>
</body>
</html>
