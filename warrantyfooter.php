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
  <title>Warranty Policy - All-En</title>
  <link rel="stylesheet" href="warrantyfooter.css" />
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
          <form method="POST" action="">
            <button class="logout-btn" name="logout" style="background: none; border: none; color: black; cursor: pointer;">🔒 Logout</button>
          </form>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="divider"></div>

<main class="about-container">
  <h1>Warranty Policy</h1>
  <p style="text-align: center; max-width: 800px; margin: 20px auto; font-size: 16px;">
    We stand behind the quality of every All-En product. If something goes wrong, we’re here to make it right.
  </p>

<div class="about-section">
  <div class="section-title"><h2>What Our<br> Warranty Covers</h2></div>
  <div class="section-content">
    <ul>
      <li><span class="icon">✅</span>Broken or malfunctioning zippers</li>
      <li><span class="icon">✅</span>Cracked or broken handles</li>
      <li><span class="icon">✅</span>Damaged wheels</li>
      <li><span class="icon">✅</span>Faulty stitching or seams</li>
    </ul>
  </div>
</div>

<div class="about-section">
  <div class="section-title"><h2>What’s Not Covered</h2></div>
  <div class="section-content">
    <ul>
      <li><span class="icon">❌</span>Cosmetic damage (scratches, dents, scuffs, stains)</li>
      <li><span class="icon">❌</span>Damage due to misuse, neglect, or airline handling</li>
      <li><span class="icon">❌</span>Normal wear and tear</li>
      <li><span class="icon">❌</span>Unauthorized modifications or repairs</li>
    </ul>
  </div>
</div>

  <div class="about-section">
    <div class="section-title"><h2>Warranty Period</h2></div>
    <div class="section-content">
      <p>All-En offers a limited warranty of 2 years from the date of purchase. Please retain your proof of purchase for warranty claims.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>How to Claim</h2></div>
    <div class="section-content">
      <p>
        To apply for a warranty claim, please go to our 
        <a href="warranty.php" style="text-decoration: underline; color: blue;">Warranty Application</a> page and follow the instructions. 
        You may also contact our support team at <strong>allenluggages@gmail.com</strong> with your order number, a description of the issue, and relevant photos.
      </p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Return Shipping<br> Responsibility</h2></div>
    <div class="section-content">
      <p>You are responsible for paying the shipping cost to return your item to us. Shipping fees are non-refundable unless otherwise approved by our support team.</p>
    </div>
  </div>

  <div class="about-section">
    <div class="section-title"><h2>Important Note</h2></div>
    <div class="section-content">
      <p>This warranty is only valid for purchases made through our official website or authorized retailers. Warranty is non-transferable.</p>
    </div>
  </div>
</main>

<?php include "layout/footer.html"; ?>
<script src="warrantyfooter.js" defer></script>
</body>
</html>
