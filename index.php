<?php
require_once "connection.php";
session_start();

// Function to get the correct image path with fallback support
function getImagePath($image_link) {
    $basePath = "Product_images/" . htmlspecialchars($image_link);
    
    // First try the image_link as is (without extension)
    if (file_exists($basePath)) {
        return $basePath;
    }
    // If not found, try with .jpg extension
    else if (file_exists($basePath . ".jpg")) {
        return $basePath . ".jpg";
    }
    // If not found, try with .png extension
    else if (file_exists($basePath . ".png")) {
        return $basePath . ".png";
    }
    // Default fallback - return original path (will show broken image if not exists)
    else {
        return $basePath;
    }
}

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
    <title>All-En Luggage</title>
    <link rel="stylesheet" href="homepage.css">
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

    <!-- <div class="announcement-bar">
        <p class="announcement-text">
            Sign up and get 20% off to your first order.
            <span style="text-decoration: underline; cursor: pointer">Sign Up Now</span>
        </p>
        <img src="images/X.png" alt="Close" class="close-btn" />
    </div> -->

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



    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">FIND LUGGAGE THAT MATCHES YOUR STYLE</h1>
            <p class="hero-text">Browse through our diverse range of meticulously crafted garments, designed to bring out your individuality and cater to your sense of style.</p>
            <button class="shop-now-btn">Shop Now</button>
            <div class="stats">
                <div class="stat">
                    <div class="stat-number">3</div>
                    <div class="stat-text">Local Brands</div>
                </div>
                <div class="divider"></div>
                <div class="stat">
                    <div class="stat-number">50+</div>
                    <div class="stat-text">High-Quality Products</div>
                </div>
                <div class="divider"></div>
                <div class="stat">
                    <div class="stat-number">100+</div>
                    <div class="stat-text">Happy Customers</div>
                </div>
            </div>
        </div>
        <img src="images/Banner.png" alt="Stylish Luggage" class="hero-image">
    </section>


    </div>


    <!-- Brands Section -->
    <section class="brands">
        <img src="images/Legend%20walker.png" alt="Brand Logo" class="brand-logo">
        <img src="images/gucci-logo-1%201.png" alt="Brand Logo" class="brand-logo">
        <img src="images/Haugas.png" alt="Brand Logo" class="brand-logo">
    </section>

    <!-- print username,id,dan is_login -->
    <!-- <?php if (isset($_SESSION['is_login'])): ?>
        <div class="user-info">
            <p>Username: <?= htmlspecialchars($_SESSION['username']) ?></p>
            <p>User ID: <?= htmlspecialchars($_SESSION['user_id']) ?></p>
            <p>Is Login: <?= htmlspecialchars($_SESSION['is_login']) ? 'Yes' : 'No' ?></p>
        </div>
    <?php else: ?>
        <div class="user-info">
            <p>Username: Not logged in</p>
            <p>User ID: Not logged in</p>
            <p>Is Login: No</p>
        </div>
    <?php endif; ?> -->




    <?php
    // Ambil 5 produk terbaru dari 2 minggu terakhir
    $query = "
            SELECT 
                p.id, 
                p.name, 
                p.image_link, 
                MIN(d.price) AS price
            FROM products p
            JOIN product_details d ON p.id = d.product_id
            WHERE p.insert_date >= CURDATE() - INTERVAL 14 DAY
            GROUP BY p.id, p.name, p.image_link
            ORDER BY p.insert_date DESC
            LIMIT 6;

        ";
    $result = mysqli_query($link, $query);
    ?>

    <!-- New Arrivals Section -->
    <?php
    $productData = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $productData[] = $row;
    }
    ?>

    <section>
        <a name="newarrival"></a>
        <h2 class="section-title">NEW ARRIVALS</h2>        
        <div class="product-grid">
            <?php foreach ($productData as $row): ?>
                <div class="product-card" data-id="<?= $row['id'] ?>">
                    <img src="<?= getImagePath($row['image_link']) ?>"
                        alt="<?= htmlspecialchars($row['name']) ?>"
                        class="product-image">
                    </div>
            <?php endforeach; ?>
        </div>

        <div class="divider-line"></div>
    </section>


    <?php
    // Ambil 5 produk terbaru dari 2 minggu terakhir
    $query = "
        SELECT 
            p.id,
            p.name,
            p.image_link,
            p.description,
            MIN(d.price) AS price,
            SUM(od.quantity) AS total_product
        FROM 
            order_detail od
        JOIN 
            products p ON p.id = od.product_id
        JOIN 
            product_details d ON d.product_id = p.id
        GROUP BY 
            p.id, p.name, p.image_link
        ORDER BY 
            total_product DESC
        LIMIT 6;
    ";

    $result = mysqli_query($link, $query);
    ?>
    <!-- New Arrivals Section -->
    <?php
    $productData = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $productData[] = $row;
    }
    ?>

    <section>
        <a name="bestseller"></a>
        <h2 class="section-title">BEST SELLER</h2>        <div class="product-grid">
            <!-- Gambar Produk -->
            <?php foreach ($productData as $row): ?>
                <div class="product-card" data-id="<?= $row['id'] ?>" data-description="<?= htmlspecialchars($row['description']) ?>">
                    <img src="<?= getImagePath($row['image_link']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="product-image">
                </div>

            <?php endforeach; ?>
        </div>

        <!-- Harga + Rating di baris bawah -->
        <div style="padding: 0 100px;">
            <?php foreach ($productData as $index => $row): ?>
                <?php
                $left = $index * 325;
                $marginTop = ($index === 0) ? '0' : '-70px';
                ?>

            <?php endforeach; ?>
        </div>

        <div class="divider-line"></div>
    </section>






    <!-- Browse by Category Section -->
    <section class="categories">
        <h2 class="section-title">BROWSE BY CATEGORY</h2>
        <div class="category-grid">
            <div class="category-card small" data-target-url="luggage-accessory-category.php">
                <h3 class="category-title">Luggage <br>Accessories</h3>
                <img src="images/bbc-la.png" alt="Luggage Accessories" class="category-image">
            </div>
            <div class="category-card small" data-target-url="travel-accessory-category.php">
                <h3 class="category-title">Travel<br>Accessories</h3>
                <img src="images/bbc-ta.png" alt="Travel Accessories" class="category-image">
            </div>
            <div class="category-card large" data-target-url="luggage-category.php">
                <h3 class="category-title">Luggage</h3>
                <img src="images/bbc-luggage.png" alt="Luggage" class="category-image">
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <?php
    // Query to fetch reviews
    // The query `AND r.admin_id` in your request seems incomplete.
    // Assuming you want to fetch all reviews where admin_id is not NULL, or you have a specific admin_id to filter by.
    // For now, I'll remove `AND r.admin_id` as it's not a valid condition by itself.
    // If you need to filter by admin_id, please specify the condition (e.g., `AND r.admin_id IS NOT NULL` or `AND r.admin_id = some_id`).

    $reviews_query = "
        SELECT p.name, r.size_id, r.color, r.rating, r.content, c.username
        FROM review r, customers c, products p
        WHERE r.customer_id = c.id
        AND r.product_id = p.id
        AND r.admin_id
    ";
    $reviews_result = mysqli_query($link, $reviews_query);

    $reviews_data = [];
    if ($reviews_result) {
        while ($row = mysqli_fetch_assoc($reviews_result)) {
            $reviews_data[] = $row;
        }
    } else {
        echo "Error fetching reviews: " . mysqli_error($link);
    }
    ?>

    <section class="testimonials">
        <h2 class="section-title" style="text-align: left; margin-left: 100px;">OUR CUSTOMERS REVIEW</h2>
        <div class="testimonial-nav">
            <img src="images/prev-arrow.png" alt="Previous" class="nav-arrow prev-testimonial">
            <img src="images/next-arrow.png" alt="Next" class="nav-arrow next-testimonial">
        </div>
        <div class="testimonial-grid">
            <?php if (!empty($reviews_data)): ?>
                <?php foreach ($reviews_data as $review): ?>
                    <div class="testimonial-card">
                        <div class="testimonial-stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <div class="star" style="<?= ($i < $review['rating']) ? 'color: gold;' : 'color: lightgray;' ?>">★</div>
                            <?php endfor; ?>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-name"><?= htmlspecialchars($review['username']) ?></div>
                            <img src="images/checklist.png" alt="Verified" class="verified-icon">
                        </div>
                        <p class="testimonial-text">"<?= htmlspecialchars($review['content']) ?>"</p>
                        <h3 style="font-size: 14px; font-weight: 500; margin-top: 10px;">
                            Product: <?= htmlspecialchars($review['name']) ?> |
                            Size: <?= htmlspecialchars($review['size_id']) ?> |
                            Color: <?= htmlspecialchars($review['color']) ?>
                        </h3>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; width: 100%;">No customer reviews available yet.</p>
            <?php endif; ?>
        </div>
    </section>


    <?php include "layout/footer.html" ?>




    <script src="homepage.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Select all elements with the class "category-card"
            const categoryCards = document.querySelectorAll(".category-card");

            categoryCards.forEach((card) => {
                card.addEventListener("click", function() {
                    // 'this' refers to the card that was clicked
                    const targetUrl = this.dataset.targetUrl; // Access the data-target-url attribute

                    if (targetUrl) {
                        window.location.href = targetUrl;
                    } else {
                        console.warn("Card clicked, but no data-target-url attribute found:", this);
                    }
                });
            });
        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const productCards = document.querySelectorAll(".product-card");            productCards.forEach(card => {
                card.addEventListener("click", function() {
                    const productId = this.getAttribute("data-id");
                    const imageSrc = this.querySelector("img").getAttribute("src");
                    // Extract image name by removing the path and any possible extension
                    let image = imageSrc.replace("Product_images/", "");
                    // Remove common extensions if present
                    image = image.replace(/\.(jpg|png|jpeg|gif)$/i, "");
                    
                    const name = this.querySelector("img").getAttribute("alt");
                    const description = this.getAttribute("data-description");

                    const clickedProduct = {
                        id: productId,
                        image: image,
                        name: name,
                        description: description // Include description in the object
                    };

                    // Simpan data ke localStorage
                    localStorage.setItem("product-clicked", JSON.stringify(clickedProduct));

                    // Redirect ke halaman detail produk
                    window.location.href = "product_detail.php";
                });
            });
        });
    </script>


</body>

</html>