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
  <title>Terms & Conditions - All-En</title>
  <link rel="stylesheet" href="terms.css">
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
        <img src="images/Cart.png" alt="Cart" class="cart-icon" style="cursor: pointer" />
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
  <h1>Terms & Conditions</h1>
  <p style="text-align: center; max-width: 800px; margin: 20px auto; font-size: 16px;">
    By accessing or using All-En's website and services, you agree to the following terms and conditions.
  </p>

<div class="about-section">
  <div class="section-title">
    <h2>Use of Website</h2>
  </div>
  <div class="section-content">
    <p>All content on this site is for personal and non-commercial use only. You agree not to misuse or copy any part of the website without written consent.</p>
  </div>
</div>

<div class="about-section">
  <div class="section-title">
    <h2>Orders & Payment</h2>
  </div>
  <div class="section-content">
    <p>Orders placed on this site are subject to product availability and confirmation of payment. We reserve the right to cancel any order at our discretion.</p>
  </div>
</div>

<div class="about-section">
  <div class="section-title">
    <h2>Pricing & Accuracy</h2>
  </div>
  <div class="section-content">
    <p>All prices are listed in NTD (New Taiwan Dollar). While we strive for accuracy, pricing or availability errors may occur. We reserve the right to correct such errors at any time.</p>
  </div>
</div>

<div class="about-section">
  <div class="section-title">
    <h2>Returns & Refunds</h2>
  </div>
  <div class="section-content">
    <p>All-En offers a 30-day return window. Please refer to our <a href="returns.php">Returns Policy</a> for more information.</p>
  </div>
</div>

<div class="about-section">
  <div class="section-title">
    <h2>Intellectual Property</h2>
  </div>
  <div class="section-content">
    <p>All content, logos, images, and designs are the property of All-En and are protected by copyright laws.</p>
  </div>
</div>

<div class="about-section">
  <div class="section-title">
    <h2>Privacy Policy</h2>
  </div>
  <div class="section-content">
    <p>Your data is handled with care. See our <a href="privacy.php">Privacy Policy</a> for more details on how we collect and use information.</p>
  </div>
</div>

<div class="about-section">
  <div class="section-title">
    <h2>Changes to Terms</h2>
  </div>
  <div class="section-content">
    <p>We reserve the right to modify these terms at any time. Continued use of the site implies acceptance of the updated terms.</p>
  </div>
</div>

</main>

<?php include "layout/footer.html" ?>
<script src="terms.js" defer></script>
</body>
</html>
