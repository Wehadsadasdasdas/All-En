<?php
session_start();
require_once "connection.php";

// Handle Ajax Search Request
if (isset($_GET['ajax_search'])) {
    $term = mysqli_real_escape_string($link, $_GET['ajax_search']);
    $query = "
        SELECT p.id, p.name, p.image_link
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

// Cek apakah user sudah login
if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true) {
    header("Location: index.php");
    exit();
}

// === BAGIAN 1: HANDLE POST REQUEST ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT); // Password is now hashed
    $name = $_POST["name"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $country = $_POST["country"];
    $city = $_POST["city"];
    $street = $_POST["address"];

    // Cek apakah username sudah ada
    $query = "SELECT * FROM customers WHERE username='$username' OR email = '$email'";
    $result = $link->query($query);

    if ($result->num_rows > 0) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Username or email already exists. Please choose a different one.';
        header("Location: signup.php");
        exit();
    } else {
        // Insert data baru dengan hashed password
        $insertQuery = "INSERT INTO customers (name, username, email, password, phone, country, city, street) 
                        VALUES ('$name', '$username', '$email', '$password', '$phone', '$country', '$city', '$street')";

        if ($link->query($insertQuery) === TRUE) {
            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'Your account has successfully been registered.';
        } else {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Failed to create account.';
        }

        header("Location: signup.php");
        exit();
    }
}

// === BAGIAN 2: JIKA BUKAN POST (DISPLAY ALERT DI HTML) ===
$status = $_SESSION['status'] ?? '';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['status'], $_SESSION['message']); // Reset supaya tidak muncul terus
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Sign-Up</title>
    <link rel="stylesheet" href="signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Announcement Bar -->
<!-- Header -->
    <div class="announcement-bar">
        <p class="announcement-text">New here? Sign up and start your travel journey with us. <span style="text-decoration: underline; cursor: pointer;">Sign Up Now!</span></p>
        <img src="images/X.png" alt="Close" class="close-btn">
    </div>

    <header class="header" id="mainHeader">
        <div class="logo">All-En</div>

        <nav class="nav">
            <div class="dropdown">
                <a href="#" class="nav-item">Shop <img src="images/shop-dropdown.png" alt="Dropdown"></a>
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
            <img src="images/search.png" alt="Search">
            <input
                type="text"
                placeholder="Search for products..."
                class="search-input"
                id="searchInput" />

            <ul id="search-suggestions"></ul>
        </div>

        <div class="cart-icons">
            <img src="images/Cart.png" alt="Wishlist" class="cart-icon" style="cursor: pointer;">
        </div>
    </header>

    <div class="cart-content" id="cartContent">
        <div class="cart-inner">
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
                Your Cart is empty.
            </h2>
            <p>
                <a
                    href="/~D1229568/allen/signin.php"
                    style="color: blue; text-decoration: underline">Sign in</a>
                to see if you have any saved items
            </p>

        </div>
    </div>


    <div class="divider"></div>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php" class="breadcrumb-item">Home</a>
        <img src="images/arrowtologin.png" alt="Arrow" class="breadcrumb-separator">
        <a href="#" class="breadcrumb-item active">Log in</a>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <h1 class="signup-title">Sign-Up</h1>

        <form id="signUpForm" class="form-container" action="signup.php" method="POST" autocomplete="off">
            <input type="text" name="username" class="input-field" placeholder="Username" required>
            <input type="password" name="password" class="input-field" placeholder="Password" autocomplete="new-password" required>

            <!-- Sign Up Details -->
            <section class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Name" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="Phone Number" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Country</label>
                        <select name="country" id="countrySelect" class="form-control" required>
                            <option value="">Select a country</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" placeholder="City" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Address" required>
                    </div>
                </div>
            </section>

            <button type="submit" class="create-account-btn" name="create">Create Account</button>
        </form>




        <a href="signin.php" name="create" class="signin-link">Already have an account? Click here</a>
    </main>

    <?php include "layout/footer.html" ?>

    <div class="custom-alert" id="customAlert" style="display: none;">
        <div class="custom-alert-box">
            <p class="custom-alert-message" id="alertMessage">Your account has successfully been registered</p>
            <button class="custom-alert-btn" id="alertButton">Go Signin</button>
        </div>
    </div>



    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const status = "<?= $status ?>";
            const message = "<?= $message ?>";

            if (status && message) {
                const alertBox = document.getElementById("customAlert");
                const alertMessage = document.getElementById("alertMessage");
                const alertButton = document.getElementById("alertButton");

                alertMessage.textContent = message;

                if (status === "error") {
                    alertButton.textContent = "Please try again";
                    alertButton.onclick = () => (window.location.href = "signup.php");
                } else if (status === "success") {
                    alertButton.textContent = "Go Signin";
                    alertButton.onclick = () => (window.location.href = "signin.php");
                }

                alertBox.style.display = "flex";
            }
        });
    </script>

    <script src="signup.js"></script>

</body>

</html>