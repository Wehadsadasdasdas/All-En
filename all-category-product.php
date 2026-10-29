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
  <title>ALL-En Luggage</title>
  <link rel="stylesheet" href="luggage-accessory-category.css">
  <style>
    .product-img {
      height: auto;
      width: 100%;
      background-color: #f0eeed;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .divide-left {
      flex: 1;
      /* Allow this division to grow and take up available space */
      display: flex;
      flex-direction: column;
      /* Stack items vertically within the left division */
      gap: 10px;
      /* Space between elements inside divide-left */
    }

    /* Style for the right division */
    .divide-right {
      flex-shrink: 0;
      /* Prevent the right division from shrinking */
      width: 300px;
      /* Set a fixed width for the image container */
      display: flex;
      justify-content: center;
      /* Center the image horizontally */
      align-items: center;
      /* Center the image vertically */
    }

    .product-details {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      max-width: 600px;
    }

    .check-box {
      width: 16px;
      height: 16px;
      transform: rotate(-90deg);
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.00) 85%, #000 100%), linear-gradient(90deg, rgba(0, 0, 0, 0.00) 85%, #000 100%), linear-gradient(0deg, rgba(0, 0, 0, 0.09) 85%, #000 100%), linear-gradient(270deg, rgba(0, 0, 0, 0.00) 85%, #000 100%), #FFF;
    }

    .product-card {
      background-color: #f0eeed;
      border-radius: 20px;
      overflow: hidden;
      position: relative;
      cursor: pointer;
      width: 200px;
      transition: transform 0.3s ease;
    }

    .sort-by {
      position: relative;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      /* Space between "Sort by:", text, and arrow */
    }

    .sort-options {
      display: none;
      /* Hidden by default */
      position: absolute;
      top: 100%;
      /* Position below the "Sort by" text */
      left: 0;
      background-color: #f9f9f9;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
      z-index: 1;
      border-radius: 5px;
      overflow: hidden;
      /* Ensures rounded corners */
    }

    .sort-options.show {
      display: block;
      /* Show when active */
    }

    .sort-option {
      color: black;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      font-size: 14px;
      /* Adjust as needed */
    }

    .sort-option:hover {
      background-color: #ddd;
    }

    .sort-option.active {
      background-color: #007bff;
      /* Highlight active option */
      color: white;
    }

    .dropdown-arrow {
      transition: transform 0.3s ease;
      /* Smooth rotation */
    }

    .sort-by.active .dropdown-arrow {
      transform: rotate(180deg);
      /* Rotate arrow when dropdown is open */
    }
  </style>
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
  <div class="container breadcrumb">
    <a href="#" class="breadcrumb-item">Home></a>
    <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
    <a href="#" class="breadcrumb-item">Shop</a>
    <img src="images/arrowtologin.png" alt=">" class="breadcrumb-separator">
    <a href="#" class="breadcrumb-item active">Luggage</a>
  </div>

  <main class="main-content">
    <aside class="filters">
      <div class="filters-header">
        <h3 class="filters-title">Filters</h3>
        <img src="images/filter.png" alt="Filter options">
      </div>

      <div class="divider"></div>

      <div class="filter-section">
        <div class="filter-option">
          <span class="filter-text">&lt 21 inch</span>
          <div class="check-box" data-size-range="1"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">21 inch - 24 inch</span>
          <div class="check-box" data-size-range="2"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">25 inch - 28 inch</span>
          <div class="check-box" data-size-range="3"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">&gt 28 inch</span>
          <div class="check-box" data-size-range="4"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">S</span>
          <div class="check-box" data-size-range="5"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">M</span>
          <div class="check-box" data-size-range="6"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">L</span>
          <div class="check-box" data-size-range="7"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">Default</span>
          <div class="check-box" data-size-range="8"></div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="filter-section">
        <div class="filter-option">
          <span class="filter-text">Luggage</span>
          <div class="check-box" data-size-range="9"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">Luggage-Accessories</span>
          <div class="check-box" data-size-range="10"></div>
        </div>

        <div class="filter-option">
          <span class="filter-text">Travel-Accessories</span>
          <div class="check-box" data-size-range="11"></div>
        </div>
      </div>

      <!-- <div class="divider"></div> -->

      <!-- <div class="filter-section">
                <div class="filters-header">
                    <h3 class="filters-title">Price</h3>
                    <img src="images/shop-dropdown.png" alt="Dropdown">
                </div>

                <div class="price-slider">
                    <input type="range" min="50" max="200" value="125" id="priceRange">
                    <div class="price-range">
                        <span class="price-value">$50</span>
                        <span class="price-value">$200</span>
                    </div>
                </div>
            </div> -->

    </aside>

    <section class="products">
      <div class="products-header">
        <h1 class="page-title">All Products</h1>
      </div>

      <div class="products-info">
        <div class="sort-by" id="sortByDropdown">
          Sort by: <span id="selectedSortOption">Most Popular</span>
          <img src="images/shop-dropdown.png" alt="Dropdown" class="dropdown-arrow">
          <div class="sort-options" id="sortOptions">
            <span class="sort-option active" data-sort="popular">Most Popular</span>
            <span class="sort-option" data-sort="rating">Highest Rating</span>
          </div>
        </div>
      </div>

      <div class="product-grid" id="productList" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 50px;">
    </section>
  </main>

  <?php include "layout/footer.html" ?>

  <script src="travel-accessory-category.js"></script>
  <script>
    // const products = <?php
                        //                     $query = "SELECT product_details.PRODUCT_ID as product_id, 
                        //                                 products.name as name,
                        //                                 products.description as description,
                        //                                 product_details.stock as stock, 
                        //                                 product_details.price as price, 
                        //                                 products.image_link as image_link,
                        //                                 product_details.color as color,
                        //                                 product_sizes.size as sizes

                        //                               FROM product_details, products, product_sizes
                        //                               WHERE product_details.PRODUCT_ID = products.id
                        //                               AND product_details.size_id = product_sizes.id
                        //                               AND products.category_id = 1";
                        //                     $result = mysqli_query($link, $query);

                        //                     $productArray = [];

                        //                     while ($row = mysqli_fetch_assoc($result)) {
                        //                         $productArray[] = [
                        //                             'id' => $row['product_id'],
                        //                             'name' => $row['name'],
                        //                             'description' => $row['description'],
                        //                             'stock' => (int)$row['stock'],
                        //                             'price' => '$' . number_format($row['price'], 2),
                        //                             'image' => $row['image_link'],
                        //                             'color' => $row['color'],
                        //                             'size' => $row['sizes'],
                        //                             //'discount' => $row['discount'] . '%'
                        //                         ];
                        //                     }

                        //                     echo json_encode($productArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                        //                     
                        ?>;


    const products = <?php
                      $query = "SELECT
            p.id AS product_id,
            p.name AS name,
            p.description AS description,
            MIN(pd.price) AS minimum_price,
            p.image_link AS image_link,
            p.category_id,
            AVG(r.rating) AS avg_rating -- Add this line to get the average rating
          FROM
            products p
          JOIN product_details pd ON pd.PRODUCT_ID = p.id
          LEFT JOIN review r ON r.product_id = p.id -- LEFT JOIN review table
          WHERE p.category_id = 2
          GROUP BY
            p.id,
            p.name,
            p.description,
            p.image_link,
            p.category_id; -- Add p.category_id to GROUP BY as well
          ";
                      $result = mysqli_query($link, $query);

                      $productArray = [];

                      while ($row = mysqli_fetch_assoc($result)) {
                        $productArray[] = [
                          'id' => $row['product_id'],
                          'name' => $row['name'],
                          'description' => $row['description'],
                          'price' => '$' . number_format($row['minimum_price'], 2),
                          'image' => $row['image_link'],
                          'avg_rating' => $row['avg_rating'] ? round($row['avg_rating'], 1) : 0 // Get average rating, round to 1 decimal, default to 0 if null
                        ];
                      }

                      echo json_encode($productArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                      ?>



    function displayProducts(products) {
      const displayContainer = document.getElementById('productList');
      displayContainer.innerHTML = ''; // Clear any previous content

      products.forEach(product => {
        const productEntryWrapper = document.createElement('div');
        productEntryWrapper.classList.add('product-entry');

        const imageOnlyCard = document.createElement('div');
        imageOnlyCard.classList.add('product-card');

        imageOnlyCard.innerHTML = `
            <div class="product-image">
                <img src="Product_images/${product.image}" alt="${product.name}" class="product-img" />
            </div>
        `;
        productEntryWrapper.appendChild(imageOnlyCard);

        const detailsContainer = document.createElement('div');
        detailsContainer.classList.add('product-details-content');

        // Generate stars dynamically
        let starsHtml = '';
        const roundedRating = Math.round(product.avg_rating); // Round to nearest whole number for star display
        for (let i = 1; i <= 5; i++) {
          if (i <= roundedRating) {
            starsHtml += '<span class="star">★</span>'; // Filled star
          } else {
            starsHtml += '<span class="star">☆</span>'; // Empty star
          }
        }

        detailsContainer.innerHTML = `
            <div>
                <span class="product-title">${product.name}</span>
                <div class="rating">
                    <div class="stars">
                        ${starsHtml} </div>
                    <span class="rating-text">${product.avg_rating.toFixed(1)}/5</span>
                </div>
                <div class="price-container">
                    <span class="current-price">${product.price}</span>
                </div>
            </div>
        `;
        productEntryWrapper.appendChild(detailsContainer);

        productEntryWrapper.addEventListener('click', () => {
          localStorage.setItem('product-clicked', JSON.stringify({
            id: product.id,
            name: product.name,
            description: product.description,
            image: product.image,
            price: product.price,
            avg_rating: product.avg_rating // Pass avg_rating to detail page if needed
          }));
          window.location.href = 'product_detail.php';
        });

        displayContainer.appendChild(productEntryWrapper);
      });
    }

    // Example of how you might call it (assuming you have a products array):
    // const sampleProducts = [
    //     { id: 1, name: "Awesome T-Shirt", description: "A really cool t-shirt.", image: "tshirt.jpg", price: "$19.99" },
    //     { id: 2, name: "Cool Mug", description: "A mug that is cool.", image: "mug.jpg", price: "$12.50" }
    // ];
    // displayProducts(sampleProducts);

    // Call the function to display products on page load
    displayProducts(products);

    const selectedRanges = [];

    // const productList = document.getElementById('productList');

    // function showInfo(product) {
    //     // Save product info to localStorage
    //     localStorage.setItem('product-clicked', JSON.stringify({
    //         id: product.id,
    //         size: product.size ?? null,
    //         color: product.color ?? null,
    //         name: product.name,
    //         price: product.price
    //     }));
    // }

    const sortByDropdown = document.getElementById('sortByDropdown');
    const sortOptions = document.getElementById('sortOptions');
    const selectedSortOption = document.getElementById('selectedSortOption');

    sortByDropdown.addEventListener('click', () => {
      sortOptions.classList.toggle('show'); // Toggle visibility of options
    });

    sortOptions.querySelectorAll('.sort-option').forEach(option => {
      option.addEventListener('click', function(event) {
        event.stopPropagation(); // Prevent the main dropdown click from closing it immediately

        // Update selected text and active class
        sortOptions.querySelectorAll('.sort-option').forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        selectedSortOption.textContent = this.textContent;
        sortOptions.classList.remove('show'); // Hide options after selection

        const sortType = this.dataset.sort;
        fetchFilteredProducts(selectedRanges, sortType); // Call a new function to fetch and sort
      });
    });

    // Close the dropdown if the user clicks outside of it
    window.addEventListener('click', (event) => {
      if (!sortByDropdown.contains(event.target)) {
        sortOptions.classList.remove('show');
      }
    });

    // Modified fetch function to include sorting
    function fetchFilteredProducts(ranges, sortType = 'popular') {
      fetch('all-category-product-filter.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            selected_ranges: ranges,
            sort_type: sortType // Pass the sort type to the backend
          })
        })
        .then(response => response.json())
        .then(data => {
          displayProducts(data);
        })
        .catch(error => console.error('Error fetching filtered products:', error));
    }

    // Call fetchFilteredProducts initially with default popular sort
    fetchFilteredProducts(selectedRanges, 'popular');


    // ... (your existing filter logic for check-box) ...
    document.querySelectorAll('.filter-option').forEach(option => {
      const checkBox = option.querySelector('.check-box');
      const sizeRange = checkBox.dataset.sizeRange;

      checkBox.addEventListener('click', () => {
        const isActive = checkBox.classList.toggle('active');

        if (isActive) {
          checkBox.style.background = '#000';
          if (!selectedRanges.includes(sizeRange)) {
            selectedRanges.push(sizeRange);
          }
        } else {
          checkBox.style.background = '';
          const index = selectedRanges.indexOf(sizeRange);
          if (index > -1) selectedRanges.splice(index, 1);
        }

        // When filters change, re-fetch with the current sort option
        const currentSortType = document.querySelector('.sort-option.active').dataset.sort;
        fetchFilteredProducts(selectedRanges, currentSortType);
      });
    });
  </script>
</body>

</html>