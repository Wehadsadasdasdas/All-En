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

        .main {
            display: flex;
            justify-content: center; /* center horizontally */
            align-items: center;     /* center vertically */
            padding: 170px 20px 200px; 
            flex-direction: column
        }
        
        .main h1 {
            text-align: center;
            line-height: 1.4;
            color: #000;
        }

        .btn {
            margin-top: 30px;
            padding: 10px 20px;
            background-color: #000;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }


    </style>
</head>
<body>

<header class="header" id="mainHeader">
    <div class="logo">All-En</div>

    <nav class="nav">

        <div class="dropdown">
            <a href="#" class="nav-item">Shop <img src="images/shop-dropdown.png" alt="Dropdown"></a>
            <div class="dropdown-menu">
                <a href="men.html"><h3>Luggage</h3></a>

                <a href="women.html"><h3>Luggage Accessories</h3></a>

                <a href="accessories.html"><h3>Acessories</h3></a>
            </div>
        </div>
        <a href="#" class="nav-item">On Sale</a>
        <a href="#" class="nav-item">New Arrivals</a>
    </nav>

    <div class="search-bar">
        <img src="images/search.png" alt="Search">
        <input type="text" placeholder="Search for products..." class="search-input">
    </div>
    <div class="cart-icons">
        <img src="images/Cart.png" alt="Wishlist" class = "cart-icon" style="cursor: pointer;">
    </div>
</header>

<div class="cart-content" id="cartContent">
    <div class="cart-inner">
        <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px;">Your Bag is empty.</h2>
        <p><a href="signin.php" style="color: blue; text-decoration: underline;">Sign in</a> to see if you have any saved items</p>
        <div style="margin-top: 30px;">
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">My Profile</h3>
            <ul style="list-style: none; padding-left: 0; line-height: 2;">
                <li><a href="cart.php" style="color: black; text-decoration: none">🧾 Wishlist</a></li>
                <li><a href="#" style="color: black; text-decoration: none">👤 History</a></li>
                <li><a href="signin.php" style="color: black; text-decoration: none">🔐 Sign in</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="divider"></div>

<div class="breadcrumb">
    <a href="index.php">Home</a>
    <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
    <span>Cart</span>
</div>


<div class = "main">
    <h1>
      We’ll check you the<br>
      validity of the <br>
      warranty product
    </h1>

    <button class="btn" onclick="window.location.href= 'index.php'">Go back to homepage</button>
</div>


<?php include "layout/footer.html"; ?>
<script>

    const logo = document.querySelector('.logo');
    logo.addEventListener('click', function() {
        window.location.href = 'index.php';
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

</script>
</body>
</html>