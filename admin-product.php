<?php
require_once "connection.php";
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_is_login']) || $_SESSION['admin_is_login'] !== true) {
    header("Location: admin-signin.php");
    exit();
}

$admin_id = $_SESSION['admin_user_id']; // Get the logged-in admin's ID
$current_user_role_id = $_SESSION['admin_role_id'] ?? null; // Get the logged-in admin's role ID

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: admin-signin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UI/UX</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .delete-button {
            background-color: red;
            color: white;
            padding: 6px 12px;
            margin: 9px 0;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            width: 100%;
            cursor: pointer;
            text-align: center;
        }

        /* Modern Product Modal Styling */
        .modal-product {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-content-product {
            background: var(--clr-white);
            padding: 0;
            border-radius: var(--card-border-radius);
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Modal Header */
        .modal-content-product h2 {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.2rem 2rem;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-shrink: 0;
        }

        .modal-content-product h2::before {
            content: "add_box";
            font-family: "Material Symbols Sharp";
            font-size: 1.6rem;
        }

        /* Modal Body */
        .modal-body {
            padding: 1.5rem 2rem;
            overflow-y: auto;
            flex: 1;
            max-height: calc(85vh - 160px);
            /* Account for header and footer */
        }

        /* Smooth scrolling behavior for modal */
        .modal-body {
            scroll-behavior: smooth;
        }

        /* Visual indicator when modal content is scrollable */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: var(--clr-light);
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #8b94f0;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgb(86, 98, 231);
        }

        /* Ensure the modal doesn't exceed viewport height */
        .modal-product {
            padding: 1rem;
            box-sizing: border-box;
        }

        /* Final size adjustments for perfect fit */
        @media screen and (max-width: 600px) {
            .modal-content-product {
                width: 98%;
                max-height: 95vh;
            }

            .modal-body {
                max-height: calc(95vh - 120px);
                padding: 1rem;
            }
        }

        /* Form Styling */
        .modal-content-product label {
            display: block;
            margin-bottom: 1rem;
            font-weight: 600;
            color: var(--clr-dark);
        }

        .modal-content-product label:not(.category-label-container) {
            position: relative;
        }

        .modal-content-product input:not([type="hidden"]),
        .modal-content-product textarea {
            width: 100%;
            padding: 0.6rem 0.8rem;
            margin-top: 0.3rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            font-size: 0.85rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .modal-content-product input:focus,
        .modal-content-product textarea:focus {
            outline: none;
            border-color: #00a2d8;
            box-shadow: 0 0 0 3px rgba(0, 162, 216, 0.1);
        }

        .modal-content-product textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* File Input Styling */
        .modal-content-product input[type="file"] {
            padding: 0.6rem;
            background: var(--clr-color-background);
            border: 2px dashed var(--clr-light);
            border-radius: var(--border-radius-1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-content-product input[type="file"]:hover {
            border-color: #00a2d8;
            background: rgba(0, 162, 216, 0.05);
        }

        /* Size and Color Groups */
        .size-group,
        .color-group {
            background: var(--clr-color-background);
            border: 2px solid var(--clr-light);
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius-2);
            transition: all 0.3s ease;
            position: relative;
        }

        .size-group:hover,
        .color-group:hover {
            border-color: var(--clr-primary);
            background: rgba(115, 128, 236, 0.02);
        }

        .size-group h4,
        .color-group h4 {
            color: var(--clr-primary);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .size-group h4::before {
            content: "straighten";
            font-family: "Material Symbols Sharp";
            font-size: 1.2rem;
        }

        .color-group h4::before {
            content: "palette";
            font-family: "Material Symbols Sharp";
            font-size: 1.2rem;
        }

        /* Sizes Container */
        #sizesContainer {
            background: var(--clr-color-background);
            border-radius: var(--border-radius-2);
            padding: 1rem;
            margin: 1rem 0;
        }

        #sizesContainer h3 {
            color: var(--clr-primary);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #sizesContainer h3::before {
            content: "category";
            font-family: "Material Symbols Sharp";
            font-size: 1.3rem;
        }

        /* Buttons */
        .modal-content-product button[type="button"] {
            background: var(--clr-info-light);
            color: var(--clr-primary);
            border: 2px solid var(--clr-primary);
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius-1);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .modal-content-product button[type="button"]:hover {
            background: var(--clr-primary);
            color: white;
            transform: translateY(-1px);
        }

        /* Remove buttons */
        .modal-content-product button[onclick*="remove"] {
            background: rgba(255, 119, 130, 0.1);
            color: var(--clr-danger);
            border-color: var(--clr-danger);
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        .modal-content-product button[onclick*="remove"]:hover {
            background: var(--clr-danger);
            color: white;
        }

        /* Modal Actions */
        .modal-actions-product {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1rem 2rem;
            background: var(--clr-color-background);
            border-top: 1px solid var(--clr-light);
            flex-shrink: 0;
        }

        .modal-actions-product button {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: var(--border-radius-1);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .modal-actions-product #cancelProductBtn {
            background: var(--clr-color-background);
            color: var(--clr-dark-variant);
            border: 2px solid var(--clr-light);
        }

        .modal-actions-product #cancelProductBtn:hover {
            background: var(--clr-light);
            transform: translateY(-1px);
        }

        .modal-actions-product button[type="submit"] {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
        }

        .modal-actions-product button[type="submit"]:hover {
            background: linear-gradient(135deg, #6b7ce8 0%, #7c85ed 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(115, 128, 236, 0.4);
        }

        /* Select styling */
        .modal-content-product select {
            width: 100%;
            padding: 0.8rem 1rem;
            margin-top: 0.5rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .modal-content-product select:focus {
            outline: none;
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            .modal-content-product {
                width: 95%;
                max-height: 90vh;
                margin: 1rem;
            }

            .modal-content-product h2 {
                padding: 1rem 1.5rem;
                font-size: 1.3rem;
            }

            .modal-body {
                padding: 1rem 1.5rem;
                max-height: calc(90vh - 140px);
            }

            .modal-actions-product {
                padding: 1rem 1.5rem;
                flex-direction: column;
            }

            .modal-actions-product button {
                width: 100%;
            }
        }

        .hidden-product {
            display: none;
        }

        .search {
            flex-direction: column;
        }

        .search input[type="text"] {
            padding: 0.2rem;
            width: 50%;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
        }

        /* Additional Modal Improvements */
        .modal-content-product .category-label-container {
            margin-bottom: 0.8rem !important;
        }

        .modal-content-product .modal-dropdown input {
            padding: 0.6rem 2.5rem 0.6rem 0.8rem !important;
            font-size: 0.85rem !important;
        }

        /* Compact size/color groups for better fit */
        .size-group label,
        .color-group label {
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .size-group select,
        .color-group input {
            padding: 0.5rem 0.7rem;
            font-size: 0.85rem;
        }

        /* Ensure buttons don't take too much space */
        .modal-content-product button[type="button"]:not(.modal-actions-product button) {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            margin: 0.5rem 0;
        }

        /* Make the modal actions more compact */
        .modal-actions-product button {
            padding: 0.7rem 1.5rem;
            font-size: 0.9rem;
        }

        /* Improve spacing for the colors container */
        .colorsContainer {
            margin-top: 0.8rem;
        }

        .colorsContainer h4 {
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        /* Modern Product Info Modal Styling */
        #infoModal.modal-product {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 1rem;
            box-sizing: border-box;
        }

        #infoModal .modal-content-product {
            background: var(--clr-white);
            padding: 0;
            border-radius: var(--card-border-radius);
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            animation: modalSlideIn 0.3s ease-out;
        }

        /* Product info specific header styling */
        #infoModal .modal-content-product h2 {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.2rem 2rem;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-shrink: 0;
        }

        /* Product info body */
        #infoModal .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }

        /* Product info section */
        .product-info-section {
            margin-bottom: 2rem;
        }

        .info-card {
            background: var(--clr-color-background);
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-2);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            border-color: var(--clr-primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-title {
            color: var(--clr-dark);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            border-radius: var(--border-radius-1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .product-title::before {
            content: "badge";
            font-family: "Material Symbols Sharp";
            font-size: 1.6rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: white;
            padding: 1.2rem;
            border-radius: var(--border-radius-1);
            border: 1px solid var(--clr-light);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--clr-info-dark);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .info-label .material-symbols-sharp {
            font-size: 1.1rem;
            color: var(--clr-success);
        }

        .info-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--clr-dark);
        }

        /* Divider */
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--clr-light), transparent);
            margin: 2rem 0;
        }

        /* Edit section */
        .edit-section h3 {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: var(--clr-primary);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .edit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .edit-item {
            background: var(--clr-color-background);
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-2);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .edit-item:hover {
            border-color: var(--clr-primary);
            background: rgba(115, 128, 236, 0.02);
        }

        .edit-item label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--clr-dark);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .edit-item label .material-symbols-sharp {
            color: var(--clr-primary);
            font-size: 1.1rem;
        }

        .edit-item .input-number {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .edit-item .input-textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
        }

        .edit-item .input-number:focus,
        .edit-item .input-textarea:focus {
            outline: none;
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
        }

        .edit-item .change-button {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            border: none;
            padding: 0.8rem 1rem;
            border-radius: var(--border-radius-1);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .edit-item .change-button:hover {
            background: linear-gradient(135deg, #6b7ce8 0%, #7c85ed 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
        }

        /* Modal actions for info modal */
        #infoModal .modal-actions-product {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: var(--clr-color-background);
            border-top: 1px solid var(--clr-light);
            flex-shrink: 0;
        }

        #infoModal .cancel-btn {
            background: var(--clr-color-background);
            color: var(--clr-dark-variant);
            border: 2px solid var(--clr-light);
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius-1);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #infoModal .cancel-btn:hover {
            background: var(--clr-light);
            transform: translateY(-1px);
        }

        #infoModal .delete-button {
            background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius-1);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #infoModal .delete-button:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Responsive design */
        @media screen and (max-width: 768px) {
            #infoModal .modal-content-product {
                width: 95%;
                margin: 1rem;
            }

            .info-grid,
            .edit-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            #infoModal .modal-actions-product {
                flex-direction: column;
            }

            #infoModal .modal-actions-product button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <aside>

            <div class="top">
                <div class="logo" id="logo-href">
                    <h2> <span class="danger">All-En</span> </h2>
                </div>
                <div class="close" id="close_btn">
                    <span class="material-symbols-sharp">
                        close
                    </span>
                </div>
            </div>
            <div class="sidebar">

                <a href="admin-dashboard.php" <?php if ($current_user_role_id == 2) echo 'style="display:none;"'; ?>>
                    <span class="material-symbols-sharp">grid_view </span>
                    <h3>Dashboard</h3>
                </a>
                <?php
                // Assuming you have a database connection established as $conn
                $payment_query = "SELECT COUNT(*) FROM `orders` WHERE admin_id IS NULL";
                $payment_result = mysqli_query($link, $payment_query);
                $payment_row = mysqli_fetch_row($payment_result);
                $payment_count = $payment_row[0];
                ?>

                <a href="admin-payment.php">
                    <span class="material-symbols-sharp">credit_card </span>
                    <h3>Payment</h3>
                    <span class="msg_count" id="payment_msg_count"><?php echo $payment_count; ?></span>
                </a>
                <a href="admin-delivery.php"> <span class="material-symbols-sharp">local_shipping </span>
                    <h3>Delivery</h3>
                </a>
                <a href="admin-product.php" class="active"> <span class="material-symbols-sharp">shopping_bag </span>
                    <h3>Product</h3>
                </a>
                <a href="admin-discount.php"> <span class="material-symbols-sharp">percent </span>
                    <h3>Discount_code</h3>
                </a>
                <?php
                // Assuming you have a database connection established as $conn
                $review_query = "SELECT COUNT(*) FROM `review` WHERE admin_id IS NULL";
                $review_result = mysqli_query($link, $review_query);
                $review_row = mysqli_fetch_row($review_result);
                $review_count = $review_row[0];
                ?>

                <a href="admin-review.php">
                    <span class="material-symbols-sharp">star_rate </span>
                    <h3>Review</h3>
                    <span class="msg_count" id="review_msg_count"><?php echo $review_count; ?></span>
                </a>
                <a href="admin-warranty.php"> <span class="material-symbols-sharp">shield_watch </span>
                    <h3>Warranty</h3>
                </a>
                <a href="admin-refund.php"> <span class=" material-symbols-sharp">money_off </span>
                    <h3>Refund</h3>
                </a>
                <a href="suppliers.php"> <span class="material-symbols-sharp">business </span>
                    <h3>Suppliers</h3>
                </a>
                <a href="admin-create-account.php" <?php if ($current_user_role_id == 2) echo 'style="display:none;"'; ?>>
                    <span class="material-symbols-sharp">manage_accounts </span>
                    <h3>Create Account</h3>
                </a>
                <form method="post" action="">
                    <a href="?logout=true">
                        <span class="material-symbols-sharp">logout </span>
                        <h3>Log out</h3>
                    </a>
                </form>

            </div>

        </aside>
        <main>
            <div class="recent_order">
                <h1>Product Management</h1>
            </div> <!-- Filters and Add Product Container -->
            <div class="management-header">
                <div class="filters-container">
                    <h2 class="filters-title">
                        <span class="material-symbols-sharp">filter_list</span>
                        Filter Products
                    </h2>

                    <div class="filters-grid">
                        <div class="filter-item">
                            <div class="dropdown">
                                <input type="text" id="category" placeholder="All" readonly>
                                <label>Category</label>
                                <span class="material-symbols-sharp dropdown-icon">expand_more</span>
                                <div class="choices" id="choiceList-category">
                                    <div class="choice">All</div>
                                    <div class="choice">Luggage</div>
                                    <div class="choice">Luggage Accessories</div>
                                    <div class="choice">Travel Accessories</div>
                                </div>
                            </div>
                        </div>

                        <div class="filter-item">
                            <div class="dropdown">
                                <input type="text" id="size" placeholder="All" readonly>
                                <label>Size</label>
                                <span class="material-symbols-sharp dropdown-icon">expand_more</span>
                                <div class="choices" id="choiceList-size">
                                    <div class="choice">All</div>
                                    <div class="choice">under 21</div>
                                    <div class="choice">21-24</div>
                                    <div class="choice">25-28</div>
                                    <div class="choice">above 28</div>
                                    <div class="choice">S</div>
                                    <div class="choice">M</div>
                                    <div class="choice">L</div>
                                    <div class="choice">Default</div>
                                </div>
                            </div>
                        </div>

                        <div class="filter-item">
                            <div class="dropdown">
                                <input type="text" id="stock" placeholder="All" readonly>
                                <label>Stock</label>
                                <span class="material-symbols-sharp dropdown-icon">expand_more</span>
                                <div class="choices" id="choiceList-stock">
                                    <div class="choice">All</div>
                                    <div class="choice">0</div>
                                    <div class="choice">1-200</div>
                                    <div class="choice">201-500</div>
                                    <div class="choice">>500</div>
                                </div>
                            </div>
                        </div>

                        <div class="filter-item">
                            <div class="dropdown">
                                <input type="text" id="price" placeholder="All" readonly>
                                <label>Price</label>
                                <span class="material-symbols-sharp dropdown-icon">expand_more</span>
                                <div class="choices" id="choiceList-price">
                                    <div class="choice">All</div>
                                    <div class="choice">under 2000</div>
                                    <div class="choice">2000-4999</div>
                                    <div class="choice">5000-9999</div>
                                    <div class="choice">above 10000</div>
                                </div>
                            </div>
                        </div>

                        <div class="filter-item search-filter">
                            <div class="search-box">
                                <span class="material-symbols-sharp search-icon">search</span>
                                <input type="text" id="productid-search" placeholder="Search by Product ID">
                                <label>Product ID Search</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modern Add Product Button -->
                <div class="add-product-container">
                    <button id="add_product" class="add-product-btn">
                        <span class="material-symbols-sharp">add_circle</span>
                        <span class="btn-text">Add Product</span>
                    </button>
                </div>
            </div> <!-- Add Product Modal -->
            <div id="productModal" class="modal-product hidden-product">
                <div class="modal-content-product">
                    <h2>Add Product</h2>
                    <div class="modal-body">
                        <form id="productForm" enctype="multipart/form-data">
                            <label class="category-label-container">
                                <span class="category-label-text">Category:</span>
                                <div class="modal-dropdown">
                                    <input type="text" id="modal-category" placeholder="--Select Category--" readonly>
                                    <span class="material-symbols-sharp modal-dropdown-icon">expand_more</span>
                                    <div class="modal-choices" id="modal-choiceList-category">
                                        <div class="modal-choice" data-value="">--Select Category--</div>
                                        <div class="modal-choice" data-value="1">Luggage</div>
                                        <div class="modal-choice" data-value="2">Luggage Accessories</div>
                                        <div class="modal-choice" data-value="3">Travel Accessories</div>
                                    </div>
                                </div>
                                <input type="hidden" name="category" id="category-id-hidden" required />
                            </label>

                            <label>Product ID:
                                <input type="text" name="product_id" required />
                            </label>

                            <label>Product Name:
                                <input type="text" name="product_name" required />
                            </label>

                            <label>Product Image:
                                <input type="file" name="product_image" accept=".jpg, .jpeg, .png, .gif" />
                            </label>

                            <label>Description:
                                <textarea name="description_link"></textarea>
                            </label>

                            <div id="sizesContainer">
                                <h3>Sizes</h3>
                                <button type="button" onclick="addSize()">+ Add Size</button>
                            </div>

                            <div class="modal-actions-product">
                                <button type="button" id="cancelProductBtn">Cancel</button>
                                <button type="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="product" id="productList"></div> <!-- Modern Product Info Modal -->
            <div id="infoModal" class="modal-product">
                <div class="modal-content-product">
                    <h2>
                        <span class="material-symbols-sharp">inventory_2</span>
                        Product Details
                    </h2>
                    <div class="modal-body">
                        <div class="product-info-section">
                            <div class="info-card">
                                <h3 id="modalTitle" class="product-title">Product ID</h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">
                                            <span class="material-symbols-sharp">inventory</span>
                                            Stock
                                        </span>
                                        <span id="modalStock" class="info-value">0</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">
                                            <span class="material-symbols-sharp">payments</span>
                                            Price
                                        </span>
                                        <span id="modalPrice" class="info-value">$0.00</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">
                                            <span class="material-symbols-sharp">account_balance_wallet</span>
                                            Capital
                                        </span>
                                        <span id="modalCapital" class="info-value">$0.00</span>
                                    </div>
                                    <div class="info-item" style="grid-column: span 2;">
                                        <span class="info-label">
                                            <span class="material-symbols-sharp">description</span>
                                            Description
                                        </span>
                                        <span id="modalDescription" class="info-value" style="white-space: pre-wrap;">No description</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="edit-section">
                            <h3>
                                <span class="material-symbols-sharp">edit</span>
                                Update Product
                            </h3>
                            <div class="edit-grid">
                                <div class="edit-item">
                                    <label for="priceInput">
                                        <span class="material-symbols-sharp">payments</span>
                                        New Price
                                    </label>
                                    <input type="number" id="priceInput" class="input-number" placeholder="Enter new price" step="0.01">
                                    <button class="change-button" data-field="price">Update Price</button>
                                </div>
                                <div class="edit-item">
                                    <label for="stockInput">
                                        <span class="material-symbols-sharp">inventory</span>
                                        New Stock
                                    </label>
                                    <input type="number" id="stockInput" class="input-number" placeholder="Enter new stock" min="0">
                                    <button class="change-button" data-field="stock">Update Stock</button>
                                </div>
                                <div class="edit-item">
                                    <label for="capitalInput">
                                        <span class="material-symbols-sharp">account_balance_wallet</span>
                                        New Capital
                                    </label>
                                    <input type="number" id="capitalInput" class="input-number" placeholder="Enter new capital" step="0.01" min="0">
                                    <button class="change-button" data-field="capital">Update Capital</button>
                                </div>
                                <div class="edit-item">
                                    <label for="descriptionInput">
                                        <span class="material-symbols-sharp">description</span>
                                        New Description
                                    </label>
                                    <textarea id="descriptionInput" class="input-textarea" placeholder="Enter new description" rows="3"></textarea>
                                    <button class="change-button" data-field="description">Update Description</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions-product">
                        <button type="button" onclick="closeModal()" class="cancel-btn">
                            <span class="material-symbols-sharp">close</span>
                            Close
                        </button>
                        <button class="delete-button" onclick="Deleteproduct()">
                            <span class="material-symbols-sharp">delete</span>
                            Delete Product
                        </button>
                    </div>
                </div>
            </div>

            <div id="overlay" class="overlay" onclick="closeModal()"></div>


        </main>
        <div class="right">

            <div class="top">
                <button id="menu_bar">
                    <span class="material-symbols-sharp">menu</span>
                </button>

                <div class="theme-toggler">
                    <span class="material-symbols-sharp active">light_mode</span>
                    <span class="material-symbols-sharp">dark_mode</span>
                </div>
                <div class="profile">
                    <div class="info">
                        <p><b><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></b></p>
                        <p><?php
                            if (isset($_SESSION['admin_role_id'])) {
                                $roleId = $_SESSION['admin_role_id'];
                                if ($roleId == 1) {
                                    echo "Owner";
                                } elseif ($roleId == 2) {
                                    echo "Admin";
                                }
                            } else {
                                echo "Guest";
                            } ?></p>
                        <small class="text-muted"></small>
                    </div>
                </div>
            </div>

            <!-- <div class="recent_updates">
                <h2>Recent Update</h2>
                <div class="updates">
                    <div class="update">
                        <div class="profile-photo">
                            <img src="../images/profile-4.jpg" alt="" />
                        </div>
                        <div class="message">
                            <p><b>Babar</b> Recived his order of USB</p>
                        </div>
                    </div>
                    <div class="update">
                        <div class="profile-photo">
                            <img src="../images/profile-3.jpg" alt="" />
                        </div>
                        <div class="message">
                            <p><b>Ali</b> Recived his order of USB</p>
                        </div>
                    </div>
                    <div class="update">
                        <div class="profile-photo">
                            <img src="../images/profile-2.jpg" alt="" />
                        </div>
                        <div class="message">
                            <p><b>Ramzan</b> Recived his order of USB</p>
                        </div>
                    </div>
                </div>
            </div> -->



        </div>
    </div>
    <script src="admin-dashboard.js"></script>


    <script>
        const PRODUCTS = <?php
                            $query = "SELECT id as product_id,
                                                name,
                                                category_id as category,
                                                description,
                                                image_link as image_link
                                              FROM products"; // Changed 'image' to 'image_link' for consistency
                            $result = mysqli_query($link, $query);

                            $productArray = [];

                            while ($row = mysqli_fetch_assoc($result)) {
                                $productArray[] = [
                                    'id' => $row['product_id'],
                                    'category' => (int)$row['category'],
                                    'image' => $row['image_link'],
                                    'description' => $row['description'],
                                    'name' => $row['name'],
                                    //'discount' => $row['discount'] . '%'
                                ];
                            }

                            echo json_encode($productArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                            ?>;
        const products = <?php
                            $query = "SELECT product_details.PRODUCT_ID as product_id,
                                                product_details.stock as stock,
                                                product_details.price as price,
                                                product_details.capital as capital,
                                                products.image_link as image_link,
                                                products.description as description,
                                                product_details.color as color,
                                                product_details.warranty_token as warranty_token, /* Added warranty_token here */
                                                product_sizes.size as sizes
                                              FROM product_details
                                              JOIN products ON product_details.PRODUCT_ID = products.id
                                              JOIN product_sizes ON product_details.size_id = product_sizes.id"; // Use JOINs for clarity
                            $result = mysqli_query($link, $query);

                            $productArray = [];

                            while ($row = mysqli_fetch_assoc($result)) {
                                $productArray[] = [
                                    'id' => $row['product_id'],
                                    'stock' => (int)$row['stock'],
                                    'price' => '$' . number_format($row['price'], 2),
                                    'capital' => '$' . number_format($row['capital'], 2),
                                    'description' => $row['description'],
                                    'image' => $row['image_link'], // Changed to 'image' for consistency with frontend usage
                                    'color' => $row['color'],
                                    'size' => $row['sizes'],
                                    'warranty_token' => $row['warranty_token'], // Added to JS product object
                                    //'discount' => $row['discount'] . '%'
                                ];
                            }

                            echo json_encode($productArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                            ?>;

        const productList = document.getElementById('productList');

        function showInfo(product) {
            // Update the product title (ID)
            document.getElementById('modalTitle').textContent = product.id;

            // Update the info values with just the values (not labels)
            document.getElementById('modalStock').textContent = product.stock;
            document.getElementById('modalPrice').textContent = product.price;
            document.getElementById('modalCapital').textContent = product.capital;
            document.getElementById('modalDescription').textContent = product.description || 'No description';

            // Clear any previous input values
            document.getElementById('priceInput').value = '';
            document.getElementById('stockInput').value = '';
            document.getElementById('capitalInput').value = '';
            document.getElementById('descriptionInput').value = '';

            // Show the modal with the new styling
            document.getElementById('infoModal').style.display = 'flex';
            document.getElementById('overlay').style.display = 'flex';

            // Save product info to localStorage for updates
            localStorage.setItem('selectedProductId', JSON.stringify({
                id: product.id,
                size: product.size,
                color: product.color
            }));
        }

        function closeModal() {
            document.getElementById('infoModal').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }


        // Enhanced dropdown functionality for all filters
        function initializeDropdowns() {
            const dropdowns = [{
                    inputId: 'category',
                    listId: 'choiceList-category'
                },
                {
                    inputId: 'size',
                    listId: 'choiceList-size'
                },
                {
                    inputId: 'stock',
                    listId: 'choiceList-stock'
                },
                {
                    inputId: 'price',
                    listId: 'choiceList-price'
                }
            ];

            dropdowns.forEach(({
                inputId,
                listId
            }) => {
                const input = document.getElementById(inputId);
                const choiceList = document.getElementById(listId);
                const choices = choiceList.querySelectorAll('.choice');
                const dropdown = input.closest('.dropdown');
                const dropdownIcon = dropdown.querySelector('.dropdown-icon');

                input.addEventListener('click', (e) => {
                    e.stopPropagation();

                    // Close other dropdowns
                    dropdowns.forEach(({
                        inputId: otherId,
                        listId: otherListId
                    }) => {
                        if (otherId !== inputId) {
                            const otherList = document.getElementById(otherListId);
                            const otherDropdown = document.getElementById(otherId).closest('.dropdown');
                            otherList.classList.remove('show');
                            otherDropdown.classList.remove('active');
                        }
                    });

                    // Toggle current dropdown
                    const isVisible = choiceList.classList.contains('show');
                    choiceList.classList.toggle('show');
                    dropdown.classList.toggle('active');
                });

                choices.forEach(choice => {
                    choice.addEventListener('click', () => {
                        // Remove selected class from all choices
                        choices.forEach(c => c.classList.remove('selected'));
                        // Add selected class to clicked choice
                        choice.classList.add('selected');

                        input.value = choice.textContent;
                        choiceList.classList.remove('show');
                        dropdown.classList.remove('active');
                        loadProducts();
                    });
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.dropdown')) {
                    dropdowns.forEach(({
                        listId,
                        inputId
                    }) => {
                        const choiceList = document.getElementById(listId);
                        const dropdown = document.getElementById(inputId).closest('.dropdown');
                        choiceList.classList.remove('show');
                        dropdown.classList.remove('active');
                    });
                }
            });
        } // Initialize dropdowns when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            initializeDropdowns();
            initializeModalDropdown();
            initializeProductModal();
        }); // Initialize the product modal functionality
        function initializeProductModal() {
            const changeButtons = document.querySelectorAll('#infoModal .change-button');

            changeButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const field = btn.getAttribute('data-field');
                    let inputElement;

                    // Get the appropriate input element based on field
                    if (field === 'price') {
                        inputElement = document.getElementById('priceInput');
                    } else if (field === 'stock') {
                        inputElement = document.getElementById('stockInput');
                    } else if (field === 'capital') {
                        inputElement = document.getElementById('capitalInput');
                    } else if (field === 'description') {
                        inputElement = document.getElementById('descriptionInput');
                    }

                    const inputValue = inputElement.value.trim();

                    if (inputValue === '') {
                        alert('Please enter a value.');
                        return;
                    }

                    const stored = localStorage.getItem('selectedProductId');
                    if (!stored) {
                        alert('No product selected.');
                        return;
                    }

                    const selected = JSON.parse(stored);
                    const selectedId = selected.id;
                    const selectedSize = selected.size;
                    const selectedColor = selected.color;

                    const product = products.find(p =>
                        p.id === selectedId &&
                        p.size === selectedSize &&
                        p.color === selectedColor
                    );

                    if (product) {
                        let cleanValue = inputValue;

                        // Prepare value and update UI
                        if (field === 'price') {
                            const numericPrice = parseFloat(inputValue).toFixed(2);
                            product.price = `$${numericPrice}`;
                            cleanValue = numericPrice;
                            // Update the modal display
                            document.getElementById('modalPrice').textContent = product.price;
                        } else if (field === 'stock') {
                            product.stock = parseInt(inputValue);
                            cleanValue = parseInt(inputValue);
                            // Update the modal display
                            document.getElementById('modalStock').textContent = `${product.stock}`;
                        } else if (field === 'capital') {
                            const numericCapital = parseFloat(inputValue).toFixed(2);
                            product.capital = `$${numericCapital}`;
                            cleanValue = numericCapital;
                            // Update the modal display
                            document.getElementById('modalCapital').textContent = product.capital;
                        } else if (field === 'description') {
                            product.description = inputValue;
                            cleanValue = inputValue;
                            // Update the modal display
                            document.getElementById('modalDescription').textContent = product.description;
                        }

                        // Send update to PHP server
                        fetch('admin-update_product.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    product_id: product.id,
                                    color: product.color,
                                    size: product.size,
                                    [field]: cleanValue
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    alert(`Server: ${data.message}`);
                                    // Clear the input after successful update
                                    inputElement.value = '';
                                } else {
                                    alert(`Server Error: ${data.error}`);
                                }
                            })
                            .catch(err => {
                                alert('Fetch Error: ' + err);
                            });

                        // Re-render the product list
                        loadProducts();

                        alert(`Product ${product.id} ${field} updated successfully!`);
                    } else {
                        alert(`Product not found! ${selectedId} ${selectedSize} ${selectedColor}`);
                    }
                });
            });
        }

        // Modal Category Dropdown Functionality
        function initializeModalDropdown() {
            const modalInput = document.getElementById('modal-category');
            const modalChoices = document.getElementById('modal-choiceList-category');
            const modalDropdown = modalInput?.closest('.modal-dropdown');
            const hiddenInput = document.getElementById('category-id-hidden');

            if (!modalInput || !modalChoices || !modalDropdown) return;

            const choices = modalChoices.querySelectorAll('.modal-choice');

            // Toggle dropdown visibility
            modalInput.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                modalChoices.classList.toggle('show');
                modalDropdown.classList.toggle('active');
            });

            // Handle choice selection
            choices.forEach(choice => {
                choice.addEventListener('click', (e) => {
                    e.stopPropagation();

                    // Remove selected class from all choices
                    choices.forEach(c => c.classList.remove('selected'));
                    // Add selected class to clicked choice
                    choice.classList.add('selected');

                    // Update display and hidden values
                    const value = choice.getAttribute('data-value');
                    const text = choice.textContent;

                    modalInput.value = text;
                    hiddenInput.value = value;

                    // Close dropdown
                    modalChoices.classList.remove('show');
                    modalDropdown.classList.remove('active');
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.modal-dropdown')) {
                    modalChoices.classList.remove('show');
                    modalDropdown.classList.remove('active');
                }
            });
        }

        function Deleteproduct() {
            // Confirm with the user before proceeding with the deletion
            if (confirm("Are you sure you want to delete this product detail (size and color variant)? This action cannot be undone.")) {

                // Retrieve selected product details from localStorage, similar to your update logic
                const stored = localStorage.getItem('selectedProductId'); // Assumes this key holds the needed info

                if (!stored) {
                    alert('No product detail selected for deletion.');
                    return;
                }

                const selected = JSON.parse(stored);

                // Ensure you have all necessary identifiers
                if (!selected || !selected.id || !selected.size || !selected.color) {
                    alert('Incomplete product detail information in storage. Cannot delete.');
                    return;
                }

                const selectedId = selected.id;
                const selectedSize = selected.size; // Assuming this corresponds to size_id in DB
                const selectedColor = selected.color;

                // Send a request to the PHP script to perform the deletion
                fetch('admin-delete_product.php', { // You'll create this PHP file
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        // Send the identifiers needed for deletion
                        body: new URLSearchParams({
                            product_id: selectedId,
                            size: selectedSize, // Make sure this matches the column name used in your PHP (likely size_id)
                            color: selectedColor
                        })
                    })
                    .then(res => res.json()) // Expecting JSON response from PHP
                    .then(data => {
                        if (data.success) {
                            alert('Server: ' + data.message);
                            // Optional: Update UI or reload page after successful deletion
                            // For simplicity, let's reload the page to reflect changes
                            window.location.reload();
                            // Or if you have the product list rendered, you might remove the item
                            // from the list display without a full reload.
                        } else {
                            alert('Server Error: ' + data.error);
                        }
                    })
                    .catch(err => {
                        alert('Fetch Error: ' + err.message);
                    });
            } else {
                // User cancelled the deletion
                alert("Deletion cancelled.");
            }
        }










        const productModal = document.getElementById('productModal');

        document.getElementById('add_product').addEventListener('click', () => {
            document.getElementById('productModal').classList.remove('hidden-product');
        });

        document.getElementById('cancelProductBtn').addEventListener('click', () => {
            document.getElementById('productModal').classList.add('hidden-product');
        });

        document.getElementById('productForm').addEventListener('submit', async (e) => { // Added 'async'
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form); // Use FormData for file uploads

            const productName = formData.get('product_name').trim();
            const productId = formData.get('product_id').trim();
            const categoryId = parseInt(formData.get('category'));
            const description = formData.get('description_link').trim();
            const productImage = formData.get('product_image'); // Get the file input

            // Find if the product ID already exists in the PRODUCTS array
            const productExists = PRODUCTS.find(p => p.id === productId);

            const sizeGroups = document.querySelectorAll('.size-group');
            const sizes = [];

            sizeGroups.forEach(sizeGroup => {
                const sizeId = parseInt(sizeGroup.querySelector('select[name="size_id"]').value);

                const colorInputs = sizeGroup.querySelectorAll('.color-group');
                const colors = [];

                colorInputs.forEach(colorGroup => {
                    const color = colorGroup.querySelector('input[name="color"]').value.trim();
                    const stock = parseInt(colorGroup.querySelector('input[name="stock"]').value);
                    const price = parseFloat(colorGroup.querySelector('input[name="price"]').value);
                    const capital = parseFloat(colorGroup.querySelector('input[name="capital"]').value);
                    const warrantyToken = colorGroup.querySelector('input[name="warranty_token"]').value.trim();

                    colors.push({
                        color,
                        stock,
                        price,
                        capital: capital,
                        warrantyToken
                    });
                });

                sizes.push({
                    size_id: sizeId,
                    colors
                });
            });

            // Append non-file data to formData
            formData.append('id', productId);
            formData.append('name', productName);
            formData.append('category_id', categoryId);
            formData.append('description', description);
            formData.append('sizes', JSON.stringify(sizes)); // Stringify the sizes array
            console.log(formData.id);
            console.log(formData.name);

            if (!productName || !productId || isNaN(categoryId) || !description || !productImage || sizes.length === 0) {
                alert("Please complete all product details including name, ID, category, description, image, and at least one size.");
                return;
            }

            for (const size of sizes) {
                if (!size.size_id || isNaN(size.size_id)) {
                    alert("Each size must have a valid size selected.");
                    return;
                }

                if (!size.colors.length) {
                    alert("Each size must have at least one color.");
                    return;
                }

                for (const color of size.colors) {
                    if (!color.color || isNaN(color.stock) || isNaN(color.price) || isNaN(color.capital) || !color.warrantyToken) {
                        alert("Please complete all fields for each color, including color name, stock, price, capital, and warranty token.");
                        return;
                    }
                }
            }
            // Determine which backend script to call based on product existence
            const backendScript = productExists ? 'admin-insert_existproduct.php' : 'admin-insert_product.php';

            try {
                const res = await fetch(backendScript, {
                    method: 'POST',
                    body: formData // Use formData directly for file uploads
                });

                if (!res.ok) {
                    const errorText = await res.text();
                    throw new Error(`Server returned status ${res.status}: ${errorText}`);
                }

                const data = await res.json();

                if (data.success) {
                    alert("Product " + (productExists ? "details " : "") + "inserted successfully!");
                    document.getElementById('productModal').classList.add('hidden-product');
                    form.reset(); // Clear the form
                    // Remove dynamically added size/color groups (optional but good for clean-up)
                    document.getElementById('sizesContainer').innerHTML = '<h3>Sizes</h3><button type="button" onclick="addSize()">+ Add Size</button>';
                    loadProducts(); // Refresh the product list
                } else {
                    alert("Error: " + (data.error || "Unknown error"));
                }
            } catch (err) {
                console.error('Fetch error:', err);
                alert("Request failed: " + err.message);
            }
        });

        function addSize() {
            const container = document.getElementById('sizesContainer');
            // Remove existing "+ Add Size" button before adding new elements
            const oldAddSizeButton = container.querySelector('button[onclick="addSize()"]');
            if (oldAddSizeButton) {
                oldAddSizeButton.remove();
            }

            const sizeDiv = document.createElement('div');
            sizeDiv.className = 'size-group';
            sizeDiv.innerHTML = `
                <label>Size:
                    <select name="size_id" required>
                        <option value="">--Select Size--</option>
                        <option value="1">12</option>
                        <option value="2">16</option>
                        <option value="3">18</option>
                        <option value="4">19</option>
                        <option value="5">20</option>
                        <option value="6">21</option>
                        <option value="7">23</option>
                        <option value="8">24</option>
                        <option value="9">25</option>
                        <option value="10">26</option>
                        <option value="11">27</option>
                        <option value="12">28</option>
                        <option value="13">29</option>
                        <option value="14">30</option>
                        <option value="15">S</option>
                        <option value="16">M</option>
                        <option value="17">L</option>
                        <option value="18">Default Size</option>
                    </select>
                </label>
                <div class="colorsContainer">
                    <h4>Colors</h4>
                    <button type="button" onclick="addColor(this)">+ Add Color</button>
                </div>
                <button type="button" onclick="this.parentNode.remove()">Remove Size</button> `;
            container.appendChild(sizeDiv);

            // Add the "+ Add Size" button back at the end of the container
            const newAddSizeButton = document.createElement('button');
            newAddSizeButton.type = 'button';
            newAddSizeButton.setAttribute('onclick', 'addSize()');
            newAddSizeButton.textContent = '+ Add Size';
            container.appendChild(newAddSizeButton);
        }

        function addColor(button) {
            const container = button.closest('.colorsContainer');
            const colorDiv = document.createElement('div');
            colorDiv.className = 'color-group';
            colorDiv.innerHTML = `
                <label>Color:
                    <input type="text" name="color" required />
                </label>
                <label>Capital:
                    <input type="number" name="capital" required />
                </label>
                <label>Price:
                    <input type="number" name="price" required />
                </label>
                <label>Stock:
                    <input type="number" name="stock" required />
                </label>
                <label>Warranty Token:
                    <input type="text" name="warranty_token" required/>
                </label>
                <button type="button" onclick="this.parentNode.remove()">Remove Color</button> `;
            container.appendChild(colorDiv);
        }




        const productidInput = document.getElementById('productid-search');

        // Check if the element was found to avoid errors
        if (productidInput) {
            // Add an event listener to the input field
            // The 'input' event fires whenever the value of the input changes
            productidInput.addEventListener('input', function() {
                // 'this' inside the event listener refers to the input element
                const currentValue = this.value;
                loadProducts();
                // --- You can add more logic here ---
                // For example, you could:
                // - Perform real-time validation
                // - Trigger an AJAX request to search for products based on the input
                // - Update other parts of the page based on the input
            });
        }









        function loadProducts() {
            const category = document.getElementById('category').value;
            const size = document.getElementById('size').value;
            const price = document.getElementById('price').value;
            const stock = document.getElementById('stock').value;
            const search = document.getElementById('productid-search').value;
            const params = new URLSearchParams({
                category,
                size,
                price,
                stock,
                'productid-search': search
            });

            const productList = document.getElementById('productList');

            // Show loading state
            productList.innerHTML = `
                <div class="product-loading">
                    <div class="spinner"></div>
                    <span>Loading products...</span>
                </div>
            `;

            fetch('admin-filter_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params.toString()
                }).then(res => res.json())
                .then(products => {
                    const productList = document.getElementById('productList');
                    productList.innerHTML = ''; // Clear existing products

                    if (products.length === 0) {
                        // Show empty state
                        productList.innerHTML = `
                            <div class="product-empty">
                                <span class="material-symbols-sharp">inventory_2</span>
                                <h3>No products found</h3>
                                <p>Try adjusting your filters or search criteria</p>
                            </div>
                        `;
                        return;
                    }

                    products.forEach(product => {
                        const div = document.createElement('div');
                        div.className = 'image-crop';

                        // Determine stock status
                        let stockStatus = 'in-stock';
                        let stockText = `Stock: ${product.stock}`;

                        if (product.stock === 0) {
                            stockStatus = 'out-of-stock';
                            stockText = 'Out of Stock';
                        } else if (product.stock <= 10) {
                            stockStatus = 'low-stock';
                            stockText = `Low Stock: ${product.stock}`;
                        }

                        // Updated HTML structure with improved design
                        div.innerHTML = `
                            <div class="stock-indicator ${stockStatus}"></div>
                            <img src="Product_images/${product.image}" alt="Product Image" onerror="this.src='Product_images/placeholder.jpg'">
                            <div class="middle">
                                <div class="left">
                                    <h2>${product.id}</h2>
                                    <h3><span class="material-symbols-sharp">inventory_2</span> ${stockText}</h3>
                                    <h3>Size: ${product.size}</h3>
                                    <h3>Color: ${product.color}</h3>
                                </div>
                            </div>
                            <small>${product.price}</small>
                        `;
                        div.addEventListener('click', () => showInfo(product));
                        productList.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    const productList = document.getElementById('productList');
                    productList.innerHTML = `
                        <div class="product-empty">
                            <span class="material-symbols-sharp">error</span>
                            <h3>Error loading products</h3>
                            <p>Please try again later</p>
                        </div>
                    `;
                });
        }

        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('category').value = 'All';
            loadProducts();
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Get the element by its ID
            const logoElement = document.getElementById('logo-href');

            // Check if the element exists to prevent errors
            if (logoElement) {
                // Add a click event listener to the element
                logoElement.addEventListener('click', function() {
                    console.log('Logo clicked! Redirecting to admin-lobby.php...');
                    // Redirect the browser to the specified URL
                    window.location.href = 'admin-lobby.php';
                });
            } else {
                console.error("Element with ID 'logo-href' not found.");
            }
        });
    </script>
</body>

</html>