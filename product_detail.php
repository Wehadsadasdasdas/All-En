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
    <title>HAUGAS Luggage - T6927 | All-En</title>
    <link rel="stylesheet" href="product_detail.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .colors-section {
            margin-bottom: 20px;
        }

        .colors-options {
            display: flex;
            gap: 12px;
        }

        .colors-option {
            padding: 12px 24px;
            border-radius: 24px;
            background-color: #efefef;
            font-size: 16px;
            font-weight: 400;
            line-height: 24px;
            color: rgba(0, 0, 0, 0.6);
            cursor: pointer;
        }

        .colors-option.selected {
            background-color: #000000;
            color: #ffffff;
            font-weight: 500;
        }

        #product-description {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>    <!-- Announcement Bar -->
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

    <!-- Breadcrumb -->
    <!-- <div class="container breadcrumb">
        <a href="#" class="breadcrumb-item">Home</a>
        <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
        <a href="#" class="breadcrumb-item">Shop</a>
        <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
        <a href="#" class="breadcrumb-item active">Luggage</a>
    </div> -->

    <!-- Product Section -->
    <div class="container product-section">
        <div class="product-images">
            <img id="image" src="product/ex-big.png" alt="HAUGAS T6927 Luggage" class="product-main-image">
            <!--        <img src="product/ex-small.png" alt="HAUGAS T6927 Luggage Thumbnail" class="product-thumbnail">-->
        </div>

        <div class="product-info">
            <h1 id="name" class="product-title">T6927</h1>
            <div id="product-description" style="white-space: pre-wrap; margin-top: 20px; font-size: 16px; line-height: 1.6;"></div>

            <div class="product-rating">
                <div class="stars" id="productStars">
                </div>
                <span class="rating-text" id="productRatingText"></span>
            </div>

            <div class="product-price">
                <span id="price" class="current-price">$0</span>
                <!-- <span class="original-price">$7680</span>
                <span class="discount-badge">-40%</span> -->
            </div>

            <div class="divider"></div>

            <div class="size-section">
                <p class="section-title">Choose Size</p>
                <div id="size" class="size-options">
                </div>
            </div>

            <div class="divider"></div>

            <div class="colors-section">
                <p class="section-title">Choose Colors</p>
                <div id="color" class="colors-options">
                </div>
            </div>

            <div class="divider"></div>

            <div class="quantity-section">
                <div class="quantity-control">
                    <button class="quantity-btn decrease">
                        <img src="cart/decrease.png" alt="Decrease">
                    </button>
                    <input type="text" class="quantity-input" value="1" readonly>
                    <button class="quantity-btn increase">
                        <img src="cart/increase.png" alt="Increase">
                    </button>
                </div>

                <button class="add-to-cart-btn">Add to Cart</button>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="container tabs">
        <div class="tab" id="tab-reviews">Rating & Reviews</div>
    </div>


    <!-- Reviews Section -->
    <div class="container" id="reviews-content">
        <div class="reviews-header">
            <div class="reviews-title">
                <h2>All Reviews</h2>
                <span class="reviews-count">(451)</span>
            </div>

            <div class="reviews-actions">
                <button class="filter-btn">
                    <img src="images/filter.png" alt="Filter">
                </button>
                <button class="sort-btn">
                    Latest
                    <img src="images/shop-dropdown.png" alt="Dropdown">
                </button>
                <button class="write-review-btn">Write a Review</button>
            </div>
        </div>
    </div>

    <?php include "layout/footer.html" ?>

    <script>
        const clickedProduct = JSON.parse(localStorage.getItem('product-clicked'));
        const productId = clickedProduct.id;

        if (productId) {
            fetch('product_detail_size.php', { // Replace with the actual path to your PHP script
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', // Sending JSON data
                    },
                    body: JSON.stringify({
                        product_id: productId
                    }) // Send the ID as a JSON object
                })
                .then(response => response.json()) // Assuming your PHP script returns JSON
                .then(data => {
                    console.log('Success:', data); // Handle the response from PHP
                    // 'data' would contain the results of your SQL query
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        } else {
            console.error('Product ID not found in localStorage.');
        }

        // Color Selection
        const colorsOptions = document.querySelectorAll('.colors-option');

        colorsOptions.forEach(option => {
            option.addEventListener('click', function() {
                colorsOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
            });
        });




        async function populateSizeOptions(productId, defaultSelectedSizeName = null) {
            const sizeContainer = document.getElementById('size');
            if (!sizeContainer) {
                console.error('Size container element (#size) not found.');
                return;
            }
            sizeContainer.innerHTML = '<div class="size-option">Loading sizes...</div>';
            // Also clear color options and reset price/stock when sizes are reloaded
            const colorContainer = document.getElementById('color');
            if (colorContainer) colorContainer.innerHTML = '';
            updateProductDisplay(); // Reset price/stock to loading or default

            try {
                // PHP script to get available sizes for a product_id
                // Expected JSON: { success: true, data: [{ size_id: 1, size_name: "Small" }, ...] }
                const response = await fetch('product_detail_size.php', { // Your PHP script for sizes
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });

                if (!response.ok) {
                    console.error('Failed to fetch sizes. Status:', response.status);
                    sizeContainer.innerHTML = '<div class="size-option">Error loading sizes.</div>';
                    return;
                }
                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    sizeContainer.innerHTML = ''; // Clear loading message
                    let hasSelected = false;
                    let firstSizeName = null;

                    result.data.forEach((sizeData, index) => {
                        const optionDiv = document.createElement('div');
                        optionDiv.classList.add('size-option');
                        optionDiv.textContent = sizeData.size;

                        if (index === 0) {
                            firstSizeName = sizeData.size;
                        }

                        if (defaultSelectedSizeName && sizeData.size === defaultSelectedSizeName) {
                            optionDiv.classList.add('selected');
                            hasSelected = true;
                            // Fetch details for this pre-selected size
                            fetchProductDetailsAndPopulateColors(productId, sizeData.size);
                        }
                        sizeContainer.appendChild(optionDiv);
                    });

                    if (!hasSelected && sizeContainer.children.length > 0) {
                        sizeContainer.children[0].classList.add('selected');
                        // Fetch details for the first size by default if nothing was pre-selected
                        if (firstSizeName) {
                            fetchProductDetailsAndPopulateColors(productId, firstSizeName);
                        }
                    }

                    sizeContainer.querySelectorAll('.size-option').forEach(option => {
                        option.addEventListener('click', function() {
                            const currentSelected = sizeContainer.querySelector('.size-option.selected');
                            if (currentSelected) {
                                currentSelected.classList.remove('selected');
                            }
                            this.classList.add('selected');
                            const selectedSizeName = this.textContent;
                            console.log('Selected Size Name:', selectedSizeName);
                            console.log('Selected Size ID:', this.dataset.sizeId);

                            // Fetch product details for the newly selected size
                            fetchProductDetailsAndPopulateColors(productId, selectedSizeName);
                        });
                    });

                } else if (result.success && result.data && result.data.length === 0) {
                    sizeContainer.innerHTML = '<div class="size-option">No sizes available.</div>';
                } else {
                    console.error('Error in API response for sizes:', result.error || 'Unknown error');
                    sizeContainer.innerHTML = '<div class="size-option">Could not load sizes.</div>';
                }
            } catch (error) {
                console.error('Error fetching or processing sizes:', error);
                sizeContainer.innerHTML = '<div class="size-option">An error occurred.</div>';
            }
        }

        // --- Function to fetch product details (price, stock, color) for a given size ---
        async function fetchProductDetailsAndPopulateColors(productId, sizeName) {
            const colorContainer = document.getElementById('color');
            if (colorContainer) {
                colorContainer.innerHTML = '<div class="colors-option">Loading colors...</div>';
            }
            updateProductDisplay(); // Reset price/stock to loading

            try {
                // PHP script to get product details (including colors) for a product_id and size_name
                // Expected JSON: { success: true, data: [{ PRODUCT_ID: ..., price: ..., stock: ..., color: "Red", size_name: "Small" }, ...] }
                const response = await fetch('product_detail_info.php', { // *** NEW PHP SCRIPT NAME ***
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        size_name: sizeName
                    })
                });

                if (!response.ok) {
                    console.error('Failed to fetch product details. Status:', response.status);
                    if (colorContainer) colorContainer.innerHTML = '<div class="colors-option">Error loading colors.</div>';
                    updateProductDisplay(null, null, true); // Indicate error
                    return;
                }

                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    populateColorOptions(result.data, productId, sizeName); // Pass all details
                    // Update price/stock with the details of the first available color for this size
                    updateProductDisplay(result.data[0].price, result.data[0].stock);
                } else if (result.success && result.data && result.data.length === 0) {
                    if (colorContainer) colorContainer.innerHTML = '<div class="colors-option">No specific details found for this size.</div>';
                    updateProductDisplay(null, null, true); // Indicate no data or error
                } else {
                    console.error('Error in API response for product details:', result.error || 'Unknown error');
                    if (colorContainer) colorContainer.innerHTML = '<div class="colors-option">Could not load colors.</div>';
                    updateProductDisplay(null, null, true); // Indicate error
                }
            } catch (error) {
                console.error('Error fetching or processing product details:', error);
                if (colorContainer) colorContainer.innerHTML = '<div class="colors-option">An error occurred.</div>';
                updateProductDisplay(null, null, true); // Indicate error
            }
        }

        // --- Function to populate color options and handle color selection ---
        function populateColorOptions(productDetailsArray, productId, sizeName) {
            const colorContainer = document.getElementById('color');
            if (!colorContainer) {
                console.error('Color container element (#color) not found.');
                return;
            }
            colorContainer.innerHTML = ''; // Clear loading/previous colors

            const uniqueColors = [...new Set(productDetailsArray.map(detail => detail.color))];

            if (uniqueColors.length === 0) {
                colorContainer.innerHTML = '<div class="colors-option">No colors available.</div>';
                return;
            }

            let firstColorName = null;

            uniqueColors.forEach((color, index) => {
                const optionDiv = document.createElement('div');
                optionDiv.classList.add('colors-option');
                optionDiv.textContent = color;
                // You could style this div with the actual color:
                // optionDiv.style.backgroundColor = color; // If color is a valid CSS color like "Red", "#FF0000"
                // For better accessibility, ensure text is readable on the background color.

                if (index === 0) {
                    firstColorName = color;
                    optionDiv.classList.add('selected'); // Select the first color by default
                    // Update price/stock based on this first color's specific details
                    const firstColorDetail = productDetailsArray.find(d => d.color === color);
                    if (firstColorDetail) {
                        updateProductDisplay(firstColorDetail.price, firstColorDetail.stock);
                    }
                }
                colorContainer.appendChild(optionDiv);
            });

            colorContainer.querySelectorAll('.colors-option').forEach(option => {
                option.addEventListener('click', function() {
                    const currentSelected = colorContainer.querySelector('.colors-option.selected');
                    if (currentSelected) {
                        currentSelected.classList.remove('selected');
                    }
                    this.classList.add('selected');
                    const selectedColorName = this.textContent;
                    console.log('Selected Color:', selectedColorName);

                    // Find the specific product detail for this product ID, size, and color
                    const selectedDetail = productDetailsArray.find(detail =>
                        detail.color === selectedColorName
                    );

                    if (selectedDetail) {
                        console.log('Details for selected color:', selectedDetail);
                        updateProductDisplay(selectedDetail.price, selectedDetail.stock);
                        // Store selected color or trigger other actions
                        localStorage.setItem(`selected-color-for-${productId}-${sizeName}`, selectedColorName);
                    }
                });
            });
            // If a color was pre-selected for this size, apply it
            const previouslySelectedColor = localStorage.getItem(`selected-color-for-${productId}-${sizeName}`);
            if (previouslySelectedColor && colorContainer.children.length > 0) {
                let foundAndSelected = false;
                colorContainer.querySelectorAll('.colors-option').forEach(opt => {
                    if (opt.textContent === previouslySelectedColor) {
                        const currentSelected = colorContainer.querySelector('.colors-option.selected');
                        if (currentSelected) currentSelected.classList.remove('selected');
                        opt.classList.add('selected');
                        const detail = productDetailsArray.find(d => d.color === opt.textContent);
                        if (detail) updateProductDisplay(detail.price, detail.stock);
                        foundAndSelected = true;
                    }
                });
                // If previous selection not found (e.g. color no longer available), select first
                if (!foundAndSelected && colorContainer.querySelector('.colors-option:not(.selected)')) {
                    const firstOpt = colorContainer.querySelector('.colors-option');
                    firstOpt.classList.add('selected');
                    const detail = productDetailsArray.find(d => d.color === firstOpt.textContent);
                    if (detail) updateProductDisplay(detail.price, detail.stock);
                }
            }
        }

        // --- Helper function to update product price and stock display ---
        function updateProductDisplay(price, stock, error = false) {
            const priceElement = document.getElementById('price');
            const stockElement = document.getElementById('product-stock');

            if (error) {
                if (priceElement) priceElement.textContent = 'Price: Error';
                if (stockElement) stockElement.textContent = 'Stock: Error';
                return;
            }
            if (priceElement) {
                if (price === null || price === undefined) {
                    priceElement.textContent = 'Loading...'; // Or 'Price: Loading...'
                } else {
                    priceElement.textContent = `$${parseFloat(price).toFixed(2)}`;
                }
            }
            if (stock === null || stock === undefined) {
                if (stockElement) stockElement.textContent = 'Stock: Loading...';
            } else {
                if (stockElement) stockElement.textContent = `Stock: ${stock}`;
            }
        }


        // --- HOW TO USE (Example on page load) ---
        document.addEventListener('DOMContentLoaded', () => {
            const clickedProduct = JSON.parse(localStorage.getItem('product-clicked'));

            if (clickedProduct && clickedProduct.id) {
                const productId = clickedProduct.id;
                // Optionally, retrieve a previously selected size for this product to pre-select it
                const previouslySelectedSize = localStorage.getItem('selected-size-for-product-' + productId);
                populateSizeOptions(productId, previouslySelectedSize);
                fetchAndDisplayProductRating(productId);
            } else {
                const sizeContainer = document.getElementById('size');
                if (sizeContainer) {
                    sizeContainer.innerHTML = '<div class="size-option">Please select a product.</div>';
                }
                updateProductDisplay(null, null, true); // Show error/unavailable for price/stock
                console.log('No product selected.');
            }
        });

        // --- NEW FUNCTION TO FETCH AND DISPLAY PRODUCT RATING ---
        async function fetchAndDisplayProductRating(productId) {
            const starsContainer = document.getElementById('productStars');
            const ratingTextElement = document.getElementById('productRatingText');

            if (!starsContainer || !ratingTextElement) {
                console.error('Rating display elements not found.');
                return;
            }

            starsContainer.innerHTML = 'Loading rating...';
            ratingTextElement.textContent = ''; // Clear previous text

            try {
                const response = await fetch('product-detail-rating.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    const avgRating = result.average_rating;
                    starsContainer.innerHTML = ''; // Clear loading message

                    if (avgRating === null || avgRating === 0) {
                        starsContainer.innerHTML = '<span class="star">☆☆☆☆☆</span>';
                        ratingTextElement.textContent = '0.0/5';
                    } else {
                        let fullStars = Math.floor(avgRating);
                        let hasHalfStar = (avgRating % 1 !== 0); // Check if there's a decimal (e.g., 4.5)
                        let emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

                        // Add full stars
                        for (let i = 0; i < fullStars; i++) {
                            const starSpan = document.createElement('span');
                            starSpan.classList.add('star');
                            starSpan.textContent = '★';
                            starsContainer.appendChild(starSpan);
                        }

                        // Add half star
                        if (hasHalfStar) {
                            const halfStarSpan = document.createElement('span');
                            halfStarSpan.classList.add('half-star');
                            const img = document.createElement('img');
                            img.src = 'images/half-star.png'; // Ensure this path is correct
                            img.alt = 'Half star';
                            halfStarSpan.appendChild(img);
                            starsContainer.appendChild(halfStarSpan);
                        }

                        // Add empty stars (if any)
                        for (let i = 0; i < emptyStars; i++) {
                            const emptyStarSpan = document.createElement('span');
                            emptyStarSpan.classList.add('star'); // You might want a different class for empty stars if you style them differently
                            emptyStarSpan.textContent = '☆'; // Unicode empty star
                            starsContainer.appendChild(emptyStarSpan);
                        }

                        ratingTextElement.textContent = `${avgRating.toFixed(1)}/5`;
                    }
                } else {
                    starsContainer.innerHTML = '<span class="error-text">Error loading rating.</span>';
                    ratingTextElement.textContent = 'N/A';
                    console.error('Error fetching rating:', result.error);
                }
            } catch (error) {
                starsContainer.innerHTML = '<span class="error-text">Failed to load rating.</span>';
                ratingTextElement.textContent = 'N/A';
                console.error('Network or parsing error fetching rating:', error);
            }
        }
        // --- END NEW FUNCTION ---

        document.addEventListener('DOMContentLoaded', function() {
            const clickedProductJSON = localStorage.getItem('product-clicked');

            if (clickedProductJSON) {
                try {
                    const clickedProduct = JSON.parse(clickedProductJSON);

                    const imageElement = document.getElementById('image');
                    const nameElement = document.getElementById('name');
                    const descriptionElement = document.getElementById('product-description'); // ✅ Add this

                    if (imageElement && clickedProduct.image) {
                        imageElement.src = "Product_images/" + clickedProduct.image;
                        if (clickedProduct.name) {
                            imageElement.alt = clickedProduct.name;
                        }
                    } else if (imageElement) {
                        console.warn('Image element found, but clickedProduct.image is missing.');
                    }

                    if (nameElement && clickedProduct.name) {
                        nameElement.textContent = clickedProduct.name;
                    } else if (nameElement) {
                        console.warn('Name element found, but clickedProduct.name is missing.');
                    }

                    // ✅ Set product description with line breaks
                    if (descriptionElement && clickedProduct.description) {
                        descriptionElement.innerHTML = clickedProduct.description.replace(/\n/g, "<br>");
                    }

                    if (!clickedProduct.image && !clickedProduct.name) {
                        console.error('Essential product data (image and name) is missing from localStorage.');
                    }

                } catch (error) {
                    console.error('Error parsing product data from localStorage:', error);
                    const imageElement = document.getElementById('image');
                    const nameElement = document.getElementById('name');
                    if (imageElement) imageElement.alt = 'Error loading product image';
                    if (nameElement) nameElement.textContent = 'Error loading product name';
                }
            } else {
                console.warn('No "product-clicked" data found in localStorage.');
                const imageElement = document.getElementById('image');
                const nameElement = document.getElementById('name');
                if (imageElement) imageElement.alt = 'No product selected';
                if (nameElement) nameElement.textContent = 'No Product Selected';
            }
        });




        document.querySelector('.add-to-cart-btn').addEventListener('click', async () => {
            const product = JSON.parse(localStorage.getItem('product-clicked'));
            const quantity = document.querySelector('.quantity-input').value;
            const sizeElement = document.querySelector('.size-option.selected');
            const colorElement = document.querySelector('.colors-option.selected');

            if (!sizeElement || !colorElement) {
                alert("Please select size and color.");
                return;
            }

            const size = sizeElement.textContent.trim();
            const color = colorElement.textContent.trim();

            try {
                const response = await fetch('product_detail_add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: product.id,
                        size: size,
                        color: color,
                        quantity: quantity
                    })
                });

                const result = await response.json();
                if (result.stock === false) {
                    alert("Insufficient stock available.");
                    return;
                }
                if (result.success) {
                    alert("Product added to cart!");
                    // Refresh the page to update cart status
                    window.location.reload();
                    s
                } else {
                    alert("You need to login first.");
                    window.location.href = "signin.php";
                }
            } catch (err) {
                console.error(err);
            }
        });





        // Hapus event handler Add to Cart di sini karena sudah ada di product_detail.js
    </script>
    <script src="product_detail.js"></script>
</body>

</html>