<?php
session_start();
require_once "connection.php";

// Ambil order_id dari URL
$order_id = $_GET['order_id'] ?? null;
$order = null;

// Jika ada order_id, ambil detail order dari database
if ($order_id) {    // Ambil detail order dan hitung nomor urut berdasarkan tanggal (sama seperti di history.php)
    $query = "
        WITH OrderedList AS (
            SELECT *, 
                   ROW_NUMBER() OVER (ORDER BY order_date DESC) as row_num
            FROM orders 
            WHERE customer_id = (SELECT customer_id FROM orders WHERE id = ?)
        )
        SELECT o.id, o.order_date, o.total_price, os.name AS status_name,
            (
                SELECT p.image_link 
                FROM order_detail od 
                JOIN products p ON od.product_id = p.id 
                WHERE od.order_id = o.id 
                LIMIT 1
            ) AS image_link,
            ol.row_num as order_number
        FROM orders o
        JOIN order_status os ON o.status_id = os.id
        JOIN OrderedList ol ON o.id = ol.id
        WHERE o.id = ?
    ";    $stmt = $link->prepare($query);
    $stmt->bind_param("ii", $order_id, $order_id); // bind order_id dua kali karena digunakan 2x dalam query
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
}
?>


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
    <title>All-En Warranty Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ffffff;
        }

        .header {
            display: flex;
            align-items: center;
            padding: 15px 100px;
            justify-content: space-between;

            overflow: visible;
            max-height: 80px; /* tinggi default */

            background-color: rgba(255, 255, 255, 0.6); /* semi-transparent */
            backdrop-filter: none;
            transition: all 0.6s ease; /* smooth semua perubahan */
            z-index: 10;
        }

        .header.expand {
            max-height: 240px; /* tinggi saat cart diklik */
            padding-bottom: 250px; /* Tambahan ruang ke bawah */
            padding-top: 45px;
            backdrop-filter: blur(10px); /* ⬅️ efek blur aktif */
            background-color: rgba(255, 255, 255, 0.8); /* lebih terang */
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .cart-content {
            width: 100%;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            background: #ffffff;
            padding: 0 100px;
            position: relative;
            z-index: 9;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: max-height 0.5s ease, opacity 0.3s ease, padding 0.3s ease;
        }

        .cart-content.active {
            max-height: 300px; /* atur sesuai tinggi konten */
            opacity: 1;
            padding: 30px 100px;
        }

        .logo {
            font-size: 32px;
            font-weight: 700;
            line-height: 48px;
            color: #000000;
            text-decoration: none;
            cursor: pointer;
        }

        .nav {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .nav-item {
            font-size: 16px;
            font-weight: 400;
            line-height: 24px;
            color: #000000;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        .dropdown {
            position: relative;
            overflow: visible;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            padding: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);

            visibility: hidden;
            margin-top: 10px;
            transition: opacity 0.3s ease, transform 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
            background: #D9D9D9;
            width: 450%;
        }


        .dropdown.open .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }


        .dropdown-menu a {
            text-decoration: none;
            color: #333;
            font-weight: 400;
        }

        .dropdown-menu a:hover {
            color: #000;
            font-weight: 500;
        }

        .dropdown.open .dropdown-menu {
            display: flex;
        }

        .search-bar {
  display: flex;
  align-items: center;
  background-color: #efefef;
  border-radius: 24px;
  padding: 12px 16px;
  width: 689px;
}

.search-input {
  border: none;
  background: transparent;
  margin-left: 12px;
  width: 100%;
  font-size: 16px;
  color: #00000066;
  outline: none;
}

#search-suggestions {
  top: 100%;
  left: 0;
  border: 1px solid #00000019;
  border-radius: 8px;
    position: absolute;
    background: white;
    list-style: none;
    padding: 0;
  margin-top: 10px;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    display: none;
}


.logout-btn {
  background: none;
  border: none;
  padding: 0;
  font-family: inherit;
  font-size: inherit;
  color: black;
  text-decoration: none;
}
.logout-btn:hover {
  text-decoration: underline;
}

        .cart-icons {
            display: flex;
            gap: 14px;
        }

        .divider {
            height: 1px;
            background-color: #00000019;
            width: 100%;
            margin: 10px 0;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            padding: 20px 100px;
        }

        .breadcrumb a {
            font-size: 16px;
            line-height: 24px;
            color: rgba(0, 0, 0, 0.6);
            text-decoration: none;
        }

        .breadcrumb span {
            font-size: 16px;
            line-height: 24px;
            color: #000000;
            margin-left: 10px;
        }

        .breadcrumb-separator {
            margin: 0 10px;
        }

        .content-container {
            display: flex;
            padding: 0 100px 60px;
            gap: 26px;
            margin-bottom: 40px;
            max-width: 2480x;
        }

        .order-card {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            padding: 20px;
            width: 715px;
            height: 164px;
            display: flex;
            flex: 1;
        }

        .order-image {
            width: 124px;
            height: 124px;
            background-color: #f0eeed;
            border-radius: 8px;
        }

        .order-details {
            margin-left: 16px;
        }

        .order-title {
            font-size: 20px;
            font-weight: 700;
            line-height: 30px;
            color: #000000;
            margin-bottom: 2px;
        }

        .order-status, .order-date {
            font-size: 14px;
            font-weight: 400;
            line-height: 21px;
            color: #000000;
            margin-bottom: 4px;
        }

        .order-price {
            font-size: 24px;
            font-weight: 700;
            line-height: 36px;
            color: #000000;
            margin-top: 4px;
        }

        .warranty-terms {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            padding: 20px 24px;
            width: 505px;
        }

        .terms-title, .shipping-title {
            font-size: 24px;
            font-weight: 700;
            line-height: 36px;
            color: #000000;
            margin-bottom: 24px;
        }

        .term-divider {
            height: 2px;
            background-color:rgb(0, 0, 0);
            width: 456px;
            margin-bottom: 24px;
        }

        .terms-content, .shipping-content {
            font-size: 20px;
            font-weight: 400;
            line-height: 30px;
            color: #000000;
        }

        .terms-content {
            margin-bottom: 30px;
        }

        .terms-content ul {
            list-style-type: none;
            margin-left: 20px;
        }

        .terms-content li::before {
            content: "•";
            margin-right: 10px;
        }

        .form-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            margin-bottom: 40px;
            padding: 0 100px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 3;
        }

        .form-label {
            font-size: 16px;
            font-weight: 400;
            line-height: 24px;
            color: #000000;
            margin-bottom: 4px;
        }

        .form-input {
            height: 48px;
            background-color: #efefef;
            border-radius: 24px;
            border: none;
            padding: 0 16px;
            font-size: 16px;
            font-weight: 400;
            line-height: 24px;
            color: rgba(0, 0, 0, 0.4);
            outline: none;
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 60px;
        }

        .next-button {
            background-color: #000000;
            color: #ffffff;
            border-radius: 24px;
            height: 48px;
            width: 119px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            line-height: 24px;
            cursor: pointer;
        }

        .footer {
            background-color: #efefef;
            padding: 30px 100px 30px;
        }

        .footer-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .footer-logo {
            font-size: 33px;
            font-weight: 700;
            line-height: 51px;
            color: #000000;
            margin-bottom: 16px;
        }

        .footer-description {
            font-size: 14px;
            font-weight: 400;
            line-height: 22px;
            color: #00000099;
            max-width: 248px;
            margin-bottom: 35px;
        }

        .social-icons {
            display: flex;
            gap: 12px;
        }

        .social-icon {
            width: 28px;
            height: 28px;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .social-icon:hover {
            opacity: 0.7;
        }

        .footer-column {
            margin-right: 60px;
        }

        .footer-column:last-child {
            margin-right: 0;
        }

        .footer-heading {
            font-size: 16px;
            font-weight: 500;
            line-height: 24px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #000000;
            margin-bottom: 24px;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 19px;
        }

        .footer-link {
            font-size: 16px;
            font-weight: 400;
            line-height: 19px;
            color: #00000099;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #000000;
        }

        .footer-divider {
            height: 1px;
            background-color: #00000019;
            margin: 40px 0;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .copyright {
            font-size: 14px;
            font-weight: 400;
            line-height: 21px;
            color: #00000099;
        }

        .payment-methods {
            display: flex;
            gap: 12px;
        }

        .payment-method {
            background-color: #ffffff;
            border-radius: 5px;
            width: 46px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0px 0px 4px rgba(183, 183, 183, 0.08);
        }

        @media (max-width: 1200px) {
            .header, .breadcrumb, .main-content, .footer {
                padding-left: 50px;
                padding-right: 50px;
            }

            .search-bar {
                width: 500px;
            }

            .footer-top {
                flex-wrap: wrap;
                gap: 30px;
            }
        }

        @media (max-width: 992px) {
            .search-bar {
                width: 300px;
            }

            .form-row {
                flex-direction: column;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .header, .breadcrumb, .main-content, .footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .nav-menu {
                display: none;
            }

            .search-bar {
                width: 200px;
            }

            .footer-top {
                flex-direction: column;
                gap: 40px;
            }
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

<div class="breadcrumb">
    <a href="index.php">Home</a>
    <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
    <span>Cart</span>
</div>

<!-- Page Title -->
<h1 class="page-title" style = "padding: 0 100px 20px;">Refund</h1>

<!-- Content Container -->
<div class="content-container">
    <!-- Order Card -->
    <?php if (!isset($_GET['order_id'])): ?>
        <div class="order-card"><div class="order-details"><h2 class="order-title">Loading...</h2></div></div>
    <?php else: ?>
    <div class="order-card">
        <?php if ($order): ?>
            <div class="order-image">
                <?php if (!empty($order['image_link'])): ?>
                    <img src="Product_images/<?= htmlspecialchars($order['image_link']) ?>" alt="Order Image" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" >
                <?php endif; ?>
            </div>
            <div class="order-details">                
                <h2 class="order-title">Order #<?= htmlspecialchars($order['order_number']) ?></h2>
                <p class="order-status">Status: <?= htmlspecialchars($order['status_name']) ?></p>
                <p class="order-date">Date: <?= htmlspecialchars($order['order_date']) ?></p>
                <p class="order-price">$<?= number_format($order['total_price'], 2) ?></p>
            </div>
        <?php else: ?>
            <div class="order-details">
                <h2 class="order-title">Order not found</h2>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <!-- Warranty Terms -->
    <div class="warranty-terms">
        <h2 class="terms-title">Refund & Return Policy</h2>
        <div class="term-divider"></div>
        <h2>Return</h2>
        <div class="terms-content">
            <p>You have 14 days from the date you received your item to request a return.</p>
            <p>To be eligible for a return:</p>
            <ul>
                <li>The item must be unused and in the same condition that you received it.</li>
                <li>It must also be in the original packaging.</li>
                <li>A receipt or proof of purchase is required.</li>
            </ul>
            <p>Please do not send your purchase back to the manufacturer.</p>
        </div>
        <h2 class="shipping-title">Shipping</h2>
        <div class="shipping-content">
            <p>You will be responsible for paying for your own shipping costs for returning your item. Shipping costs are non-refundable.</p>
        </div>
    </div>
</div>

<!-- Form Container -->
<div class="form-container">
    <div class="form-group full-width">
        <label for="refund-reason" class="form-label">Refund reason</label>
        <textarea id="refund-reason" class="form-input" placeholder="Please explain your reason for refund" style="height: 120px; padding-top: 12px; resize: none;"></textarea>
    </div>
    <div class="form-group full-width">
        <label for="proof" class="form-label">Proof (Link to evidence, e.g. Google Drive, Dropbox, etc)</label>
        <input type="text" id="proof" class="form-input" placeholder="Paste your evidence link here">
    </div>
</div>

<!-- Button Container -->
<div class="button-container">
    <button class="next-button" id="nextBtn">Next</button>
</div>

<?php include "layout/footer.html"; ?>
<script>
    (function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('order_id')) {
            const refundOrderId = localStorage.getItem('refund_order_id');
            if (refundOrderId) {
                window.location.href = window.location.pathname + '?order_id=' + refundOrderId;
            }
        }
    })();

    const logo = document.querySelector('.logo');
    logo.addEventListener('click', function() {
        window.location.href = 'index.php';
    });    document.getElementById('nextBtn').addEventListener('click', function() {
        const refundReason = document.getElementById('refund-reason').value;
        const proofLink = document.getElementById('proof').value;
        let isValid = true;

        if (!refundReason) {
            isValid = false;
            document.getElementById('refund-reason').style.border = '1px solid red';
        } else {
            document.getElementById('refund-reason').style.border = 'none';
        }

        if (!proofLink) {
            isValid = false;
            document.getElementById('proof').style.border = '1px solid red';
        } else {
            document.getElementById('proof').style.border = 'none';
        }

        if (isValid) {
            const formData = new FormData();
            formData.append('refund_reason', refundReason);
            formData.append('proof_link', proofLink);
            formData.append('order_id', <?php echo $order_id; ?>);          
            fetch('refund-process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    window.location.href = 'refunded.php';
                } else {
                    alert(data.message || 'Failed to submit refund request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting refund request');
            });
        }
    });

    // Add hover effects to navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.color = '#000000';
            this.style.fontWeight = '500';
        });

        link.addEventListener('mouseleave', function() {
            this.style.color = '#000000';
            this.style.fontWeight = '400';
        });
    });

    // Add hover effect to Next button
    const nextButton = document.querySelector('.next-button');
    nextButton.addEventListener('mouseenter', function() {
        this.style.backgroundColor = '#333333';
    });

    nextButton.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '#000000';
    });

    const cartIcon = document.querySelector('.cart-icons');
    const cartContent = document.getElementById('cartContent');

    // Saat mouse masuk ke icon, tampilkan cart
    cartIcon.addEventListener('click', (e) => {
        e.stopPropagation(); // agar tidak ikut tertangkap oleh event document
        cartContent.classList.toggle('active');
    });

    // Saat mouse keluar dari kotak cart, sembunyikan
    cartContent.addEventListener('mouseleave', () => {
        cartContent.classList.remove('active');
    });


    //dropdown
    const dropdown = document.querySelector('.dropdown');
    const dropdownLink = dropdown.querySelector('.nav-item');

    dropdownLink.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function(e) {
        // .contains() akan true kalau klik ada di dalam elemen dropdown
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });


    // === Search Input + AJAX Suggestion ===
  const input = document.getElementById("searchInput");
  const suggestionBox = document.getElementById("search-suggestions");

  if (input) {
    input.addEventListener("input", function () {
      const keyword = this.value.trim();

      if (keyword.length === 0) {
        suggestionBox.style.display = "none";
        return;
      }

      fetch(`index.php?ajax_search=${encodeURIComponent(keyword)}`)
        .then((res) => res.json())
        .then((data) => {
          suggestionBox.innerHTML = "";

          if (data.length === 0) {
            suggestionBox.innerHTML =
              "<li style='padding: 10px;'>No results</li>";
          } else {
            data.forEach((item) => {
              const li = document.createElement("li");
              li.textContent = item.name;
              li.style.padding = "10px";
              li.style.cursor = "pointer";
              li.addEventListener("click", () => {
                localStorage.setItem(
                  "product-clicked",
                  JSON.stringify({
                    id: item.id,
                    name: item.name,
                    image: item.image_link, // ini harus ada dari PHP
                  })
                );
                window.location.href = "product_detail.php";
              });
              suggestionBox.appendChild(li);
            });
          }

          suggestionBox.style.display = "block";
        })
        .catch((err) => {
          console.error("Search error:", err);
          suggestionBox.style.display = "none";
        });
    });
  }

  //saat click di luar input, sembunyikan suggestion box
document.addEventListener("click", (e) => {
  if (!input.contains(e.target) && !suggestionBox.contains(e.target)) {
    suggestionBox.style.display = "none";
  }
}
);



</script>
</body>
</html>