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

<?php

if (isset($_GET['ajax_search'])) {
    $term = mysqli_real_escape_string($link, $_GET['ajax_search']);

    $query = "
        SELECT p.id, p.name ,p.image_link
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
    <title>All-En Checkout</title>
    <link rel="stylesheet" href="checkout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>

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
                id="searchInput" />

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
                        href="/~D1229568/allen/signup.php"
                        style="color: blue; text-decoration: underline">Sign in</a>
                    to see if you have any saved items
                </p>
            <?php else: ?>


                <?php if (!$hasItems): ?>
                    <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
                        Your Bag is empty.
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

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
        <a href="cart.php">Cart</a>
        <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
        <span>Checkout</span>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <h1 class="page-title">Checkout</h1>

        <!-- Recipient Information -->
        <section class="form-section">
            <h2 class="section-title">Delivery information</h2>
            <!-- <div class="form-row">
            <div class="form-group">
                <label for="recipientUsername" class="form-label">Name</label>
                <input type="text" id="recipientUsername" class="form-control" placeholder="Username" required>
            </div>
            <div class="form-group">
                <label for="recipientPhone" class="form-label">Phone Number</label>
                <input type="tel" id="recipientPhone" class="form-control" placeholder="Phone Number" required>
            </div>
            <div class="form-group">
                <label for="recipientEmail" class="form-label">Email</label>
                <input type="email" id="recipientEmail" class="form-control" placeholder="Email" required>
            </div>
        </div> -->
            <div class="form-row">
                <div class="form-group">
                    <label for="recipientAddress" class="form-label">Address</label>
                    <input type="text" id="recipientAddress" class="form-control" name="recipientAddress" placeholder="Address" required>
                </div>
            </div>
        </section>

        <!-- Payment Method -->
        <section class="form-section">
            <h2 class="section-title">Payment Method</h2>
            <div class="payment-options">
                <div class="payment-option">
                    <!-- Changed values according to your new scheme -->
                    <input type="radio" id="taiwanPay" name="paymentMethod" class="payment-radio" value="1" checked>
                    <label for="taiwanPay" class="payment-label">Taiwan Pay</label>

                    <input type="radio" id="linePay" name="paymentMethod" class="payment-radio" value="2">
                    <label for="linePay" class="payment-label">Line Pay</label>

                    <input type="radio" id="paypalPay" name="paymentMethod" class="payment-radio" value="3">
                    <label for="paypalPay" class="payment-label">PayPal</label>
                </div>
            </div>
        </section>

        <!-- Checkout Button -->
        <div class="checkout-btn-container">
            <button type="button" class="checkout-btn" id="checkoutBtn">Checkout</button>
        </div>
    </main>

    <?php include "layout/footer.html" ?>

    <script src="checkout.js"></script>
</body>

</html>