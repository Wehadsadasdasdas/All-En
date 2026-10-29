<?php
require_once "connection.php";
session_start();

// If logout is clicked
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // If user is not logged in, assume cart is empty
    $hasItems = false;
} else {
    // Check if there are items in the cart for this user
    $query = "SELECT COUNT(*) AS cnt FROM cart WHERE customer_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $hasItems = ($row['cnt'] > 0);
    mysqli_stmt_close($stmt);
}

if (isset($_GET['ajax_search'])) {
    $term = '%' . mysqli_real_escape_string($link, $_GET['ajax_search']) . '%';

    $query = "
        SELECT p.id, p.name ,p.image_link
        FROM products p
        WHERE p.name LIKE ?
    ";

    // Using prepared statement for search to prevent SQL injection
    $stmt_search = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt_search, "s", $term);
    mysqli_stmt_execute($stmt_search);
    $result_search = mysqli_stmt_get_result($stmt_search);

    $products = [];
    while ($row = mysqli_fetch_assoc($result_search)) {
        $products[] = $row;
    }
    echo json_encode($products);
    mysqli_stmt_close($stmt_search);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Collection QR Code</title>
    <link rel="stylesheet" href="qr.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .order-row {
            border-bottom: 15px solid transparent;
        }

        .order-row td {
            padding-right: 15px;
            padding-bottom: 10px;
        }
    </style>
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

    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
        <a href="cart.php">Cart</a>
        <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
        <span>Checkout</span>
    </div>


    <div class="main-content">
        <?php
        // Get payment method from URL
        $payment_method = isset($_GET['payment_method']) ? (int)$_GET['payment_method'] : 0;

        // Retrieve order_id from session and then unset it
        $order_id_from_session = $_SESSION['order_id'] ?? null;
        // if ($order_id_from_session) {
        //     unset($_SESSION['order_id']); // Unset the order_id after retrieving it
        // }

        // // Prioritize order_id from session, fallback to URL if session is empty (though it should be from session after checkout)
        $display_order_id = $order_id_from_session ?? (isset($_GET['order_id']) ? (int)$_GET['order_id'] : null);

        $order_details = []; // Initialize as empty array
        $total_amount = 0;

        if ($display_order_id) { // Only query if order_id is available
            $query_order = "
                SELECT
                    o.id AS order_id,
                    o.order_date,
                    oi.quantity,
                    p.name AS product_name,
                    oi.color,
                    s.size AS size_name,
                    p.image_link,
                    pd.price
                FROM
                    orders o
                JOIN
                    order_detail oi ON o.id = oi.order_id
                JOIN
                    products p ON oi.product_id = p.id
                JOIN
                    product_details pd ON oi.product_id = pd.PRODUCT_ID
                                          AND oi.color = pd.color
                                          AND oi.size_id = pd.size_id
                JOIN
                    product_sizes s ON oi.size_id = s.id
                WHERE
                    o.id = ?
                LIMIT 100;
            ";

            $stmt_order = mysqli_prepare($link, $query_order);
            mysqli_stmt_bind_param($stmt_order, "i", $display_order_id);
            mysqli_stmt_execute($stmt_order);
            $result_order = mysqli_stmt_get_result($stmt_order);

            while ($row_order = mysqli_fetch_assoc($result_order)) {
                $order_details[] = $row_order;
                $total_amount += ($row_order['quantity'] * $row_order['price']);
            }
            mysqli_stmt_close($stmt_order);
        }
        ?>

        <div class="payment-info">
            <h2>Thank you for shopping with us!</h2>
            <p>Your order (ID: <?php echo htmlspecialchars($display_order_id ?? 'N/A'); ?>) has been placed.</p>
            <p>Please complete your payment using the chosen method below:</p>

            <?php // Show different content based on payment method
            if ($payment_method == 2) { // Line Pay (ID 2)
            ?>
                <div class="payment-section taiwan-pay-details">
                    <h3>Line Pay</h3>
                    <p>Please scan the QR code below to complete your payment:</p>
                    <img src="qr/lineqr.png" alt="Line Pay QR Code" class="qr-code">
                </div>
            <?php
            } elseif ($payment_method == 3) { // PayPal (ID 3)
            ?>
                <div class="payment-section paypal-details">
                    <h3>PayPal Payment</h3>
                    <p>Click the link below to complete your payment via PayPal:</p>
                    <p>
                        <a href="https://www.paypal.me/WilbertHeulwen" target="_blank" class="paypal-link">
                            Pay with PayPal
                        </a>
                    </p>
                    <p>Alternatively, you can scan the QR code below:</p>
                    <img src="qr/paypal_qr.png" alt="PayPal QR Code" class="qr-code">
                </div>
            <?php
            } else { // Taiwan Pay (ID 1) or any other value not explicitly handled
            ?>
                <div class="payment-section taiwan-pay-details">
                    <h3>Taiwan Pay</h3>
                    <p>Please scan the QR code below to complete your payment:</p>
                    <img src="qr/taiwanpay.png" alt="Taiwan Pay QR Code" class="qr-code">
                </div>
            <?php
            }
            ?>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php if (!empty($order_details)): ?>
                    <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($order_details[0]['order_date']))); ?></p>
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th style="padding-left: 10px;">Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_details as $item): ?>
                                <tr class="order-row">
                                    <td>
                                        <?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['color']); ?>, <?php echo htmlspecialchars($item['size_name']); ?>)
                                    </td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td style="padding-left: 10px;">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="total-amount">
                        <?php
                        $stmt_total = mysqli_prepare($link, "SELECT total_price FROM orders WHERE id = ?");
                        mysqli_stmt_bind_param($stmt_total, "i", $display_order_id);
                        mysqli_stmt_execute($stmt_total);
                        $result_total = mysqli_stmt_get_result($stmt_total);
                        $item = mysqli_fetch_assoc($result_total);

                        if ($item) {
                            echo "<div class='total-amount'><strong>Total Amount: $" . htmlspecialchars(number_format($item['total_price'], 2)) . "</strong></div>";
                        } else {
                            echo "<div class='total-amount'><strong>Order not found.</strong></div>";
                        }

                        mysqli_stmt_close($stmt_total);
                        ?>

                    </div>
                <?php else: ?>
                    <p>No order details found for this transaction. Please ensure you were redirected from a successful checkout.</p>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <a href="cart.php" class="back-btn">Back to Cart</a>
                <a href="index.php" class="continue-btn">Continue Shopping</a>
            </div>
        </div>
    </div>

    <?php include "layout/footer.html" ?>
    <script src="qr.js"></script>
</body>

</html>