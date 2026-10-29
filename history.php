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
    <title>All-En - Your Order History</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="history.css">
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

<div class="breadcrumb">
    <a href="index.php">Home</a>
    <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
    <span>Order History</span>
</div>

<?php
    session_start();
    require_once "connection.php";

    $user_id = $_SESSION['user_id'] ?? null;
    $order_history = [];    if ($user_id) {
        $query = "
            SELECT o.id, o.order_date, o.total_price, os.name AS status_name, o.admin_id,
                (
                    SELECT p.image_link
                    FROM order_detail od
                    JOIN products p ON od.product_id = p.id
                    WHERE od.order_id = o.id
                    LIMIT 1
                ) AS image_link,
                o.delivery_id,
                d.status_id AS delivery_status_id
            FROM orders o
            JOIN order_status os ON o.status_id = os.id
            LEFT JOIN delivery d ON o.delivery_id = d.id
            WHERE o.customer_id = ?
            ORDER BY o.order_date DESC
        ";
        $stmt = $link->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
       
        while ($row = $result->fetch_assoc()) {
            $order_history[] = $row;
        }
    }
?>
    <div class="main-content">


        <?php if (empty($order_history)): ?>
            <h1 class="page-title" style="text-align: center">You haven't placed any orders yet.</h1>
            <h1 class="page-title" style="text-align: center">Explore our products and make your first purchase!</h1>
            <div class="empty-cart-message" style="text-align: center; margin: 30px 0;">
                <a href="index.php" class="shop-now-link" style="display: inline-block; padding: 10px 20px; background-color: #333; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500;">Continue Shopping</a>
            </div>
        <?php else: ?>


            <h1 class="page-title">Your History</h1>
            <div class="order-history">
                <?php if (empty($order_history)): ?>
                    <div class="no-orders-message" style="text-align: center; padding: 30px;">
                        <h3>You haven't placed any orders yet.</h3>
                        <p>Explore our products and make your first purchase!</p>
                        <a href="index.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #333; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500;">Shop Now</a>
                    </div>
                <?php else: ?>
                <?php $no = 1; foreach ($order_history as $order): ?>
                    <div class="order-card" data-order-id="<?= $order['id'] ?>">
                        <div class="order-image">
                            <img src="Product_images/<?= htmlspecialchars($order['image_link']) ?>" alt="Order Image" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" >
                        </div>
                        <div class="order-details">
                            <h2 class="order-title">Order #<?= $no ?></h2>
                            <p class="order-status">Status: <?= htmlspecialchars($order['status_name']) ?></p>                        
                            <p class="order-date">Date: <?= htmlspecialchars($order['order_date']) ?></p>
                            <p class="order-price">$<?= number_format($order['total_price'], 2) ?></p>                            <!-- ask for Refund -->
                            </div>
                          </div>

                    <?php 
                    if (isset($order['delivery_status_id']) && $order['delivery_status_id'] == 5):
                        // Calculate the difference between current date and order date
                        $orderDate = new DateTime($order['order_date']);
                        $currentDate = new DateTime();
                        $daysDifference = $currentDate->diff($orderDate)->days;
                        
                        // Only show refund button if order is less than 14 days old
                        if ($daysDifference <= 14):
                    ?>
                    <button class="refund-button" onclick="handleRefund(<?= $order['id'] ?>)">Request Refund</button>
                    <?php 
                        endif;
                    endif; 
                    ?>
                      <?php if (isset($order['status_name']) && is_null($order['admin_id'])): ?>
                    <button class="payment-button" onclick="handlePayment(<?= $order['id'] ?>)">Payment</button>
                    <?php endif; ?>
                    <div class="order-divider"></div>
                
                <?php $no++; endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
    
 <?php include "layout/footer.html"?>

    <script src="history.js"></script>
    <script>
// Add click event to each order-card to save order_id and redirect
    document.querySelectorAll('.order-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.classList.contains('refund-button')) {
                e.stopPropagation();
                return;
            }
            var orderId = this.getAttribute('data-order-id');
            localStorage.setItem('selected_order_id', orderId);
            window.location.href = 'order_detail.php?order_id=' + orderId;
        });
    });

    function handleRefund(orderId) {
        localStorage.setItem('refund_order_id', orderId);
        window.location.href = 'refund.php';
    }      
      function handlePayment(orderId) {
        // Store the order ID in localStorage
        localStorage.setItem('payment_order_id', orderId);
        
        // Fetch the payment method for this order
        fetch('get_payment_method.php?order_id=' + orderId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Direct to the appropriate payment display based on payment_method_id
                    if (data.payment_method_id == 1) {
                        // For BCA (payment_method_id = 1)
                        window.location.href = 'qr.php?order_id=' + orderId + '&payment_method=1';
                    } else if (data.payment_method_id == 2) {
                        // For QR (payment_method_id = 2)
                        window.location.href = 'qr.php?order_id=' + orderId + '&payment_method=2';
                    } else {
                        // Default case
                        window.location.href = 'qr.php?order_id=' + orderId;
                    }
                } else {
                    // Fall back to default qr.php
                    window.location.href = 'qr.php?order_id=' + orderId;
                }
            })
            .catch(error => {
                console.error('Error fetching payment method:', error);
                // Fall back to just passing the order_id
                window.location.href = 'qr.php?order_id=' + orderId;
            });
    }
</script>
</body>
</html>