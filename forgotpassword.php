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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="forgotpassword.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<body>
    <!-- Announcement Bar -->
    <!-- Header -->    <!-- Announcement Bar -->
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
            <a href="#bestseller" class="nav-item">Best Seller</a>
            <a href="#newarrival" class="nav-item">New Arrivals</a>
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
                    Your Bag is empty.
                </h2>
                <p>
                    <a
                        href="/~D1229568/allen/signin.php"
                        style="color: blue; text-decoration: underline">Sign in</a>
                    to see if you have any saved items
                </p>
            <?php else: ?>
                <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
                    Your Bag is empty.
                </h2>
                <div style="margin-top: 30px">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px">
                        My Profile
                    </h3>
                    <ul style="list-style: none; padding-left: 0; line-height: 2">
                        <li>
                            <a
                                href="/~D1229568/allen/cart.php"
                                style="color: black; text-decoration: none">🧾 Wishlist</a>
                        </li>
                        <li>
                            <a
                                href="/~D1229568/allen/history.php"
                                style="color: black; text-decoration: none">👤 History</a>
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
<main class="main-content">
    <h1 class="signup-title">Forgot Password</h1>

    <form class="form-container" id="resetForm">
      <input type="email" id="email" class="input-field" placeholder="Enter your registered email" required />
      <button type="submit" class="create-account-btn">Send Reset Link</button>
    </form>

    <?php if ($status === "not_found"): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            showPopup("❌ Email not found in our records.");
        });
    </script>
    <?php endif; ?>

    <a href="signin.php" class="signin-link">Back to Sign In</a>
</main>
<div id="popup" class="custom-alert">
  <div class="custom-alert-box">
    <p id="popupMessage" class="custom-alert-message">[message]</p>
    <button class="custom-alert-btn" onclick="closePopup()">OK</button>
  </div>
</div>
  <script src="forgotpassword.js" defer></script>
<?php include "layout/footer.html" ?>

<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script>
emailjs.init("hHd5rmHuzCOmPNsF1"); // Replace with your public key

const form = document.getElementById("resetForm");

form.addEventListener("submit", function (e) {
    e.preventDefault(); // Stop form from submitting normally

    const email = document.getElementById("email").value;

    // Step 1: Check if email exists in database
    fetch("check_email.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email })
    })
    .then(res => res.json())
    .then(data => {
        if (data.exists) {
            // Step 2: Send email using EmailJS
            const token = Math.random().toString(36).substring(2, 15);
            const resetLink = `http://140.134.53.57/~D1229568/allen/resetpassword.php?token=${token}`;

            emailjs.send("service_ugfducl", "template_x8n8r4g", {
                email: email,
                link: resetLink
            }).then(() => {
                showPopup("✅ Reset link sent! Please check your email.");
            }).catch(() => {
                showPopup("❌ Failed to send email.");
            });

            // Step 3: Save token to database (optional)
            fetch("save_token.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email: email, token: token })
            });
        } else {
            showPopup("❌ Email not found in our records.");
        }
    });
});

function showPopup(message) {
    document.getElementById("popupMessage").textContent = message;
    document.getElementById("popup").style.display = "flex";
}

function closePopup() {
    document.getElementById("popup").style.display = "none";
}
</script>
</script>
</body>
</html>
