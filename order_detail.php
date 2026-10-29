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
    <title>All-En Product List</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="order_detail.css">
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
        <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
        <span>Cart</span>
    </div>

    <!-- Page Title -->
    <h1 class="page-title">Product List</h1>

    <!-- Content -->
    <div class="main-content">
        <!-- Product List -->
        <div class="product-list">
            <?php
            require_once "connection.php";
            // Get order_id from GET or fallback to 1 for demo
            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 1;

            // Fetch all products in this order
            $sql = "
            SELECT 
                od.product_id,
                p.name AS product_name,
                p.image_link,
                ps.size AS size,
                od.color,
                od.quantity,
                pd.price
            FROM order_detail od
            JOIN products p ON od.product_id = p.id
            JOIN product_sizes ps ON od.size_id = ps.id
            JOIN product_details pd ON od.product_id = pd.product_id AND od.size_id = pd.size_id AND od.color = pd.color
            WHERE od.order_id = ?
        ";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order_items = [];
            while ($row = $result->fetch_assoc()) {
                $order_items[] = $row;
            }
            $stmt->close();
            ?>

            <?php if (count($order_items) === 0): ?>
                <div style="padding: 32px; font-size: 18px; color: #888;">No products found for this order.</div>
            <?php else: ?>
                <?php foreach ($order_items as $item): ?>
                    <div class="product-item">
                        <div class="product-image">
                            <img src="Product_images/<?= htmlspecialchars($item['image_link']) ?>" alt="Product Image" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                        </div>
                        <div class="product-details">
                            <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="product-size">Size: <?= htmlspecialchars($item['size']) ?></div>
                            <div class="product-color">Color: <?= htmlspecialchars($item['color']) ?></div>
                            <div class="product-quantity">Quantity: <?= (int)$item['quantity'] ?></div>
                            <div class="product-price">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                        </div>
                    </div>
                    <div class="item-divider"></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>


        <?php
        $orderInfo = [];
        $sqlOrder = "
            SELECT 
                o.id AS order_id,
                o.order_date,
                o.total_price,
                ds.name AS delivery_status_name,
                o.discount_code_id,
                d.discount_percent AS discount_persen
            FROM orders o
            LEFT JOIN delivery ON o.delivery_id = delivery.id
            LEFT JOIN delivery_status ds ON delivery.status_id = ds.id
            LEFT JOIN discount_code d ON o.discount_code_id = d.id
            WHERE o.id = ?
        ";
        $stmtOrder = $link->prepare($sqlOrder);
        $stmtOrder->bind_param("i", $order_id);
        $stmtOrder->execute();
        $resultOrder = $stmtOrder->get_result();
        if ($rowOrder = $resultOrder->fetch_assoc()) {
            $orderInfo = $rowOrder;
        }
        $stmtOrder->close();
        ?>


        <!-- Order Status -->
        <div class="order-status">
            <div class="order-title">Order Status</div>

            <div class="delivery-status">
                <div class="status-label">Delivery Status</div>
                <div class="status-value">
                    <?= htmlspecialchars($orderInfo['delivery_status_name'] ?? '-') ?>
                </div>

            </div>


            <div class="delivery-status">
                <?php if (!empty($orderInfo['discount_code_id']) && isset($orderInfo['discount_persen'])): ?>
                    <div class="status-label">Discount</div>
                <?php endif; ?>

                <div class="status-value">
                    <?php if (!empty($orderInfo['discount_code_id']) && isset($orderInfo['discount_persen'])): ?>
                        <span><?= (int)$orderInfo['discount_persen'] ?>%</span>
                    <?php endif; ?>
                </div>
            </div>




            <div class="order-divider"></div>

            <div class="total">
                <div class="status-label">Total</div>
                <div class="status-value">
                    $<?= isset($orderInfo['total_price']) ? number_format($orderInfo['total_price'], 2) : '0.00' ?>
                </div>
            </div>
        </div>
    </div>

    <?php include "layout/footer.html" ?>

    <script src="order_detail.js"></script>
</body>

</html>

<!-- <?php
        // DEBUG: Show orderInfo for troubleshooting
        // Remove or comment out after debugging
        if (!empty($orderInfo)) {
            echo '<pre style="background:#eee;padding:8px;">$orderInfo: ' . print_r($orderInfo, true) . '</pre>';
        } else {
            echo '<pre style="background:#fee;padding:8px;">$orderInfo is EMPTY or NULL</pre>';
        }
        ?> -->