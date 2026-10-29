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

<?php
session_start();
require_once "connection.php";

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['warranty_id'])) {
    $user_id = $_SESSION['user_id'];
    $warranty_token = trim($_POST['warranty_id']);
    $warranty_reason = trim($_POST['warranty_reason']); // Get the warranty reason

    // Cari product detail berdasarkan warranty_token
    $sql = "SELECT product_id, size_id, color FROM product_details WHERE warranty_token = ? LIMIT 1";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $warranty_token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $product_id = $row['product_id'];
        $size_id = $row['size_id'];
        $color = $row['color'];
        // Cek apakah user pernah membeli produk ini (di order_detail dan orders)
        $sql2 = "SELECT od.*
                 FROM order_detail od
                 JOIN orders o ON od.order_id = o.id WHERE o.customer_id = ? AND od.product_id = ? AND od.size_id = ? AND od.color = ?
                 LIMIT 1";
        $stmt2 = $link->prepare($sql2);
        $stmt2->bind_param("isis", $user_id, $product_id, $size_id, $color);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2->fetch_assoc()) {
            // Pernah beli, proceed to insert into warranty table
            // Insert into warranty table
            $claim_date = date("Y-m-d"); // Current date
            $status_id = 1; // Default status ID
            $insert_sql = "INSERT INTO warranty (claim_date, status_id, description, customer_id, warranty_token) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $link->prepare($insert_sql);
            $insert_stmt->bind_param("sisis", $claim_date, $status_id, $warranty_reason, $user_id, $warranty_token);

            if ($insert_stmt->execute()) {
                // If successful, redirect to warrant-process.php
                http_response_code(500);
                echo json_encode(['success' => true, 'redirect' => 'warrant-process.php']);
                exit();
            } else {
                // If insertion fails
                http_response_code(400);
                echo "<script>alert('Failed to submit warranty claim. Please try again.');window.history.back();</script>";
                exit();
            }
        } else {
            http_response_code(300);
            // If warranty token found, but user has not purchased this specific product
            echo "<script>alert('Please check your warranty_token or ensure you have purchased this product.');window.history.back();</script>";
            exit();
        }
    } else {
        http_response_code(200);
        // If warranty token is not found in product_details
        echo json_encode(['success' => false, 'message' => 'Please check your warranty_token']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Warranty Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="warranty.css">
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


    <?php if (isset($_SESSION['is_login'])): ?>
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
            <span>Cart</span>
        </div>

        <h1 class="page-title">Warranty</h1>

        <div class="content-container">
            <div class="warranty-terms">
                <h2 class="terms-title">Warranty Terms</h2>
                <div class="term-divider"></div>
                <div class="terms-content">
                    <p>This Warranty covers:</p>
                    <ul>
                        <li>Broken zippers</li>
                        <li>Cracked or broken handles</li>
                        <li>Damaged wheels</li>
                        <li>Faulty stitching or seams</li>
                    </ul>
                    <p>This warranty does not cover:</p>
                    <ul>
                        <li>Cosmetic damage (scratches, dents, scuffs, stains)</li>
                        <li>Damage caused by misuse, neglect, or accidents (including airline damage)</li>
                        <li>Normal wear and tear</li>
                        <li>Modifications or repairs by unauthorized parties</li>
                    </ul>
                </div>
                <h2 class="shipping-title">Shipping</h2>
                <div class="shipping-content">
                    <p>You will be responsible for paying for your own shipping costs for returning your item. Shipping costs are non-refundable.</p>
                </div>
            </div>
            <div class="form-section-wrapper">
                <div class="form-container">
                    <div class="form-group full-width">
                        <label for="warranty-id" class="form-label">Warranty ID</label>
                        <input type="text" id="warranty-id" class="form-input" placeholder="Warranty ID">
                    </div>
                    <div class="form-group full-width">
                        <label for="warranty-reason" class="form-label">Warranty Reason</label>
                        <textarea id="warranty-reason" class="form-input" placeholder="Describe your warranty reason" rows="10" style="height: 120px; padding-top: 12px; resize: none;""></textarea>
                    </div>
                </div>

                <div class="button-container">
                    <button class="next-button" id="nextBtn">Next</button>
                </div>
            </div>
        </div>    <?php else: ?>
        <div class="warranty-login-container" style="text-align: center; padding: 50px 20px;">
            <h1 class="page-title" style="margin-bottom: 20px;">Hey, log in to grab your warranty</h1>
<p style="margin-bottom: 30px; color: #666;">Gotta log in first so we can process your warranty claim.</p>

            <div class="button-group">
                <a href="signup.php" class="shop-now-link" style="display: inline-block; padding: 12px 30px; background-color: black; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500; margin-right: 15px;">Sign Up</a>
                <a href="signin.php" class="shop-now-link" style="display: inline-block; padding: 12px 30px; background-color: black; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500; border: 2px solid black;">Sign in</a>
            </div>
        </div>
    <?php endif; ?>

        

    

    <?php include "layout/footer.html"; ?>


    <script src="warranty.js"></script>
    <script>
       
        //check if nextBttn is exist
        if (document.getElementById('nextBtn')){
    
        document.getElementById('nextBtn').addEventListener('click', function(e) {
            e.preventDefault();
            // Validasi form
            const warrantyId = document.getElementById('warranty-id').value;
            const warrantyReason = document.getElementById('warranty-reason').value;
            let isValid = true;
            // Hapus validasi username, phone, email, address
            if (!warrantyId) {
                isValid = false;
                document.getElementById('warranty-id').style.border = '1px solid red';
            } else {
                document.getElementById('warranty-id').style.border = 'none';
            }
            if (!warrantyReason) {
                isValid = false;
                document.getElementById('warranty-reason').style.border = '1px solid red';
            } else {
                document.getElementById('warranty-reason').style.border = 'none';
            }
            if (isValid) {
                // Submit form via POST (AJAX)
                const formData = new FormData();
                formData.append('warranty_id', warrantyId);
                formData.append('warranty_reason', warrantyReason);                
                fetch('warranty-insert.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            warranty_id: warrantyId,
                            warranty_reason: warrantyReason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                alert("Warranty submitted successfully!");
                            }
                        } else {
                            alert(data.message || "An error occurred.");
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert(error.message || 'A system error occurred.');
                    });
            }
        });
    }
    </script>
</body>

</html>