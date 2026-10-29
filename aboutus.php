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
  <title>About Us - All-En</title>
  <link rel="stylesheet" href="aboutus.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

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
            <img
                src="images/Cart.png"
                alt="Wishlist"
                class="cart-icon"
                style="cursor: pointer" />
        </div>
    </header>

        <div class="cart-content" id="cartContent">
        <div class="cart-inner">

            <?php if (isset($_SESSION['is_login']) == false): ?>
                <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
                    Your Cart is empty.
                </h2>
                <p>
                    <a
                        href="/~D1229568/allen/signin.php"
                        style="color: blue; text-decoration: underline">Sign in</a>
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
  <h1>About Us</h1>

  <div class="about-section">
    <h2>Better Travel Since 2000</h2>
    <p>Founded with a vision to redefine travel, All-En crafts quality luggage and accessories for the modern explorer. Trusted, innovative, and always evolving.</p>
  </div>

  <div class="about-section">
    <h2>Strength of a Product</h2>
    <p>Our luggage is designed to last. Strong, durable, and stylish—All-En supports every adventure, whether across the world or across town.</p>
  </div>

  <div class="about-section">
    <h2>Timelessness of a Brand</h2>
    <p>All-En is inspired by heritage and committed to innovation. Our products blend timeless aesthetics with modern functionality.</p>
  </div>

  <div class="about-section">
    <h2>The Power of Travel</h2>
    <p>We believe in the transformative power of travel and the confidence it brings. Every journey is an opportunity—and All-En is your trusted companion.</p>
  </div>
</main>


  <?php include "layout/footer.html" ?>
  <script src="aboutus.js" defer></script>
</body>
</html>
