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
    <title>All-En - Your Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cart.css">

</head>
<body>

<!-- Header -->
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
                        href="/~D1229568/allen/signup.php"
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

<div class="breadcrumb">
    <a href="index.php">Home</a>
    <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
    <span>Cart</span>
</div>


<?php
// Ambil user_id dari session
$user_id = $_SESSION['user_id'] ?? null;

$cart_items = [];

if ($user_id) {
    $query = "
        SELECT 
            c.product_id AS product_id,
            p.name AS product_name,
            p.image_link,
            ps.size,
            c.color,
            pd.size_id,
            pd.price,
            SUM(c.quantity) AS total_quantity,
            SUM(pd.price * c.quantity) AS total_price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        JOIN product_sizes ps ON c.size_id = ps.id
        JOIN product_details pd 
            ON c.product_id = pd.product_id 
            AND c.size_id = pd.size_id 
            AND c.color = pd.color
        WHERE c.customer_id = ?
        GROUP BY c.product_id, c.size_id, c.customer_id, c.color
    ";


    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
}
?>



<main class="main-content">
    <?php if (empty($cart_items)): ?>
    <h1 class="page-title" style="text-align: center">No items here yet — let's go shopping!</h1>
    <div class="empty-cart-message" style="text-align: center; margin: 30px 0;">
        <a href="index.php" class="shop-now-link" style="display: inline-block; padding: 10px 20px; background-color: #333; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500;">Continue Shopping</a>
    </div>
    <?php else: ?>
    <h1 class="page-title">Your cart</h1>

    <div class="cart-container">
        <div class="cart-items">
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <img src="Product_images/<?php echo htmlspecialchars($item['image_link']); ?>" alt="Product Image" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                    </div>

                    <div class="item-details">
                        <h3 class="item-name"><?= htmlspecialchars($item['product_name']) ?></h3>
                        <p class="item-size">Size: <?= htmlspecialchars($item['size']) ?></p>
                        <p class="item-color">Color: <?= htmlspecialchars($item['color']) ?></p>
                        <p class="item-price">$<?= number_format($item['total_price'], 2) ?></p>
                    </div>
                    <div class="item-quantity">
                    <button class="quantity-btn decrease"
                        data-product-id="<?= $item['product_id'] ?>"
                        data-size-id="<?= $item['size_id'] ?>"
                        data-color="<?= $item['color'] ?>"
                        data-action="decrease">
                        <img src="cart/decrease.png" alt="Decrease">
                    </button>

                    <span class="quantity-value"><?= $item['total_quantity'] ?></span>

                    <button class="quantity-btn increase"
                        data-product-id="<?= $item['product_id'] ?>"
                        data-size-id="<?= $item['size_id'] ?>"
                        data-color="<?= $item['color'] ?>"
                        data-action="increase">
                        <img src="cart/increase.png" alt="Increase">
                    </button>

                    </div>
                    <img src="cart/remove.png" alt="Remove" class="remove-item">
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php
        session_start();

        $user_id = $_SESSION['user_id'] ?? 0;
        $subtotal = 0;
        $discountAmount = 0;
        $discountCodeInput = $_POST['promo_code'] ?? '';
        $deliveryFee = 15;
        $total = 0;

        // Hitung subtotal
        $querySubtotal = "
            SELECT SUM(pd.price * c.quantity) AS subtotal
            FROM cart c
            JOIN product_details pd 
                ON c.product_id = pd.product_id 
                AND c.size_id = pd.size_id 
                AND c.color = pd.color
            WHERE c.customer_id = ?
            GROUP BY c.customer_id
        ";

        $stmt = mysqli_prepare($link, $querySubtotal);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $subtotal = $row['subtotal'];
        }

        // Cek kode diskon
        $discountAmount = 0; // Default: tidak ada diskon
        if (isset($_SESSION['applied_discount_code'])) {
            $appliedCode = $_SESSION['applied_discount_code'];
            $checkDiscount = "SELECT discount_percent FROM discount_code WHERE code = ?";
            $stmt = mysqli_prepare($link, $checkDiscount);
            mysqli_stmt_bind_param($stmt, "s", $appliedCode);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $discountAmount = $subtotal * ((float)$row['discount_percent']) / 100;
            }
        }

        $total = $subtotal - $discountAmount + $deliveryFee;
        ?>




        <div class="order-summary">
            <h2 class="summary-title">Order Summary</h2>

        <div class="summary-row">
            <span class="summary-label">Subtotal</span>
            <span class="summary-value">$<?php echo number_format($subtotal, 2); ?></span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Discount</span>
            <span class="summary-value discount-value">-$<?php echo number_format($discountAmount, 2); ?></span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Delivery Fee</span>
            <span class="summary-value">$<?php echo number_format($deliveryFee, 2); ?></span>
        </div>

        <div class="summary-divider"></div>

        <div class="summary-row total-row">
            <span class="summary-label">Total</span>
            <span class="summary-value">$<?php echo number_format($total, 2); ?></span>
        </div>


            <div class="promo-code">
                <div class="promo-input">
                    <img src="cart/promo.png" alt="Promo">
                    <input type="text" placeholder="Add promo code">
                </div>
                <button class="apply-btn">Apply</button>
                <button class="apply-btn" id="rmv">Remove</button>
            </div>            
                <button class="checkout-btn">
                Go to Checkout
                <img src="cart/go-to-co.png" alt="Arrow">
            </button>
        </div>


    </div>
    <?php endif; ?>
</main>

<?php include "layout/footer.html" ?>

<script src="cart.js"></script>
</body>
</html>