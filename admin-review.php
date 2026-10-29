<?php
require_once "connection.php"; // Make sure this connects to your database and provides $link
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_is_login']) || $_SESSION['admin_is_login'] !== true) {
    header("Location: admin-signin.php");
    exit();
}

$admin_id = isset($_SESSION['admin_user_id']) ? $_SESSION['admin_user_id'] : null;
$current_user_role_id = $_SESSION['admin_role_id'] ?? null; // Get the logged-in admin's role ID

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: admin-signin.php");
    exit();
}

// --- PHP for handling AJAX requests (Confirmation and Cancellation) ---

// Handle Review Confirmation
if (isset($_POST['action']) && $_POST['action'] === 'confirm_review') {
    header('Content-Type: application/json'); // Respond with JSON

    $review_id = filter_var($_POST['review_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);

    if (!empty($review_id) && is_numeric($review_id)) {
        // Update the admin_id for the specific review
        $stmt = $link->prepare("UPDATE review SET admin_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $admin_id, $review_id); // 'ii' for two integers

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Review confirmed.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error confirming review: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid review ID.']);
    }
    exit(); // Important to exit after AJAX response
}

// Handle Review Cancellation (Deletion)
if (isset($_POST['action']) && $_POST['action'] === 'cancel_review') {
    header('Content-Type: application/json'); // Respond with JSON

    $review_id = filter_var($_POST['review_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);

    if (!empty($review_id) && is_numeric($review_id)) {
        // Delete the review from the table
        $stmt = $link->prepare("DELETE FROM review WHERE id = ?");
        $stmt->bind_param("i", $review_id); // 'i' for integer

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Review cancelled and deleted.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error cancelling review: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid review ID.']);
    }
    exit(); // Important to exit after AJAX response
}


// --- PHP for fetching reviews for the main table display ---
$reviews = [];
$date_filter = $_GET['submit_date'] ?? ''; // Get date filter from GET request

$sql = "SELECT id, product_id, Content, rating, Submit_date, admin_id FROM review WHERE admin_id IS NULL"; // Only show unconfirmed reviews
$params = [];
$types = "";

if (!empty($date_filter)) {
    $sql .= " AND Submit_date = ?"; // Add AND for additional filter
    $params[] = $date_filter;
    $types .= "s"; // 's' for string (date is treated as a string)
}

$sql .= " ORDER BY Submit_date ASC"; // Order by date, latest first

$stmt = $link->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();
} else {
    // Handle prepare error
    error_log("Failed to prepare statement: " . $link->error);
    // You might want to display an error message to the admin here
}

// Get admin name for display
$admin_name_display = "Admin"; // Default
$profile_image_src = "../images/profile-1.jpg"; // Default
if (isset($_SESSION['admin_username'])) {
    $admin_name_display = htmlspecialchars($_SESSION['admin_username']);
    // You could fetch the actual admin name and profile image from your 'admins' table here
    // For example:
    // $stmt_admin = $link->prepare("SELECT Name, ProfileImage FROM admins WHERE id = ?");
    // $stmt_admin->bind_param("i", $admin_id);
    // $stmt_admin->execute();
    // $admin_result = $stmt_admin->get_result();
    // if ($admin_row = $admin_result->fetch_assoc()) {
    //      $admin_name_display = htmlspecialchars($admin_row['Name']);
    //      $profile_image_src = htmlspecialchars($admin_row['ProfileImage']);
    // }
    // $stmt_admin->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Review Management</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">
    <style>        /* Enhanced Table Styling */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: var(--border-radius-1);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 1.5rem 0;
            background: var(--clr-white);
        }

        table thead {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
        }

        table thead th {
            padding: 1rem 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        table tbody tr {
            background: var(--clr-white);
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--clr-light);
        }

        table tbody tr:hover {
            background: rgba(115, 128, 236, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.15);
        }

        table tbody tr:last-child {
            border-bottom: none;
        }

        table tbody td {
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
            color: var(--clr-dark);
            border: none;
            vertical-align: middle;
            text-align: center;
        }

        /* Review action buttons styling */
        .review-confirm-button,
        .review-delete-button {
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            padding: 0.8rem 1rem;
            border-radius: var(--border-radius-1);
        }

        .review-confirm-button {
            color: var(--clr-success);
            background: rgba(40, 167, 69, 0.1);
        }

        .review-confirm-button:hover {
            background: linear-gradient(135deg, var(--clr-success) 0%, #28a745 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .review-delete-button {
            color: var(--clr-danger);
            background: rgba(255, 119, 130, 0.1);
        }

        .review-delete-button:hover {
            background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Detail Button Enhancement */
        .detail-button {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            border: none;
            color: white;
            cursor: pointer;
            font-weight: 600;
            padding: 0.8rem 1.2rem;
            border-radius: var(--border-radius-1);
            transition: all 0.3s ease;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(115, 128, 236, 0.2);
        }

        .detail-button:hover {
            background: linear-gradient(135deg, #5f6dc2 0%, #7380ec 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
        }        /* Modern Review Modal Styling - Enhanced design matching admin-discount.php */
        .modal {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(6px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 1rem;
            box-sizing: border-box;
            animation: modalFadeIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal:not([style*="flex"]) {
            display: none;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-content {
            background: var(--clr-white);
            padding: 0;
            border-radius: var(--card-border-radius);
            width: 90%;
            max-width: 650px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            color: var(--clr-dark);
            border: 1px solid rgba(115, 128, 236, 0.1);
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-30px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        /* Modal Header */
        .modal-header {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
            flex-shrink: 0;
            position: relative;
        }

        .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .modal-header h2::before {
            content: "rate_review";
            font-family: "Material Symbols Sharp";
            font-size: 1.8rem;
            opacity: 0.9;
        }

        /* Modal Body */
        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
            max-height: calc(85vh - 180px);
            scroll-behavior: smooth;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.7;
            font-size: 1.05rem;
            color: var(--clr-dark);
            background: linear-gradient(to bottom, var(--clr-white), rgba(115, 128, 236, 0.02));
        }

        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: rgba(115, 128, 236, 0.1);
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            border-radius: 4px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5f6dc2 0%, #7380ec 100%);
            background-clip: content-box;
        }

        /* Close Button in Header */
        .close-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            line-height: 1;
            backdrop-filter: blur(10px);
        }

        .close-button:hover,
        .close-button:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.6);
            transform: scale(1.1) rotate(90deg);
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        /* Modal Footer */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: linear-gradient(to top, rgba(115, 128, 236, 0.05), transparent);
            border-top: 1px solid rgba(115, 128, 236, 0.1);
            flex-shrink: 0;
            margin-top: 0;
        }

        .close-modal-btn {
            background: linear-gradient(135deg, var(--clr-color-background) 0%, rgba(115, 128, 236, 0.1) 100%);
            color: var(--clr-dark-variant);
            border: 2px solid rgba(115, 128, 236, 0.2);
            padding: 1rem 2rem;
            border-radius: var(--border-radius-1);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 10px rgba(115, 128, 236, 0.1);
        }

        .close-modal-btn:hover {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(115, 128, 236, 0.3);
            border-color: var(--clr-primary);
        }

        .close-modal-btn::before {
            content: "close";
            font-family: "Material Symbols Sharp";
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }

        .close-modal-btn:hover::before {
            transform: rotate(90deg);
        }        /* Responsive Modal Design */
        @media screen and (max-width: 768px) {
            .modal-content {
                width: 95%;
                max-height: 90vh;
                margin: 1rem;
            }

            .modal-header {
                padding: 1.2rem 1.5rem;
            }

            .modal-header h2 {
                font-size: 1.3rem;
            }

            .modal-body {
                padding: 1.5rem;
                max-height: calc(90vh - 160px);
                font-size: 1rem;
            }

            .modal-footer {
                padding: 1rem 1.5rem;
            }

            .close-modal-btn {
                width: 100%;
                justify-content: center;
                padding: 1rem;
            }

            .close-button {
                width: 40px;
                height: 40px;
                font-size: 1.4rem;
            }
        }

        @media screen and (max-width: 480px) {
            .modal-content {
                width: 98%;
                max-height: 95vh;
            }

            .modal-header {
                padding: 1rem;
            }

            .modal-header h2 {
                font-size: 1.2rem;
            }

            .modal-header h2::before {
                font-size: 1.5rem;
            }

            .modal-body {
                padding: 1rem;
                max-height: calc(95vh - 140px);
                font-size: 0.95rem;
            }

            .modal-footer {
                padding: 1rem;
            }

            .close-button {
                width: 36px;
                height: 36px;
                font-size: 1.3rem;
            }
        }


        /* Adjustments for the logout link to be a form button */
        .sidebar form {
            width: 100%;
        }

        .sidebar form button {
            background: none;
            border: none;
            color: var(--color-info-dark);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0;
            padding-left: 2rem;
            width: 100%;
            transition: all 300ms ease;
            cursor: pointer;
        }

        .sidebar form button:hover {
            color: var(--color-primary);
        }

        .sidebar form button .material-symbols-sharp {
            font-size: 1.6rem;
        }        /* Modern Filter Section - Matching admin-discount.php design */
        .action-section {
            background: var(--clr-white);
            padding: 0;
            border-radius: var(--card-border-radius);
            width: 100%;
            margin: 1.5rem 0;
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

        /* Action Header */
        .action-header {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.2rem 2rem;
            flex-shrink: 0;
        }

        .action-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: white;
        }

        .action-header h3::before {
            content: "filter_list";
            font-family: "Material Symbols Sharp";
            font-size: 1.4rem;
        }

        /* Action Body */
        .action-body {
            padding: 1.5rem 2rem;
            flex: 1;
        }

        .date-filter-form {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .date-filter-form label {
            font-weight: 600;
            color: var(--clr-dark);
            font-size: 0.9rem;
        }

        .date-filter-form input[type="date"] {
            padding: 0.8rem 1rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            background-color: var(--clr-white);
            color: var(--clr-dark);
            font-size: 0.9rem;
            transition: all 300ms ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .date-filter-form input[type="date"]:focus {
            outline: none;
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.25);
        }

        .date-filter-form input[type="date"]:hover {
            background-color: rgba(115, 128, 236, 0.05);
            border-color: var(--clr-primary-variant);
        }

        /* Active date input styling */
        .date-filter-form input[type="date"]:not([value=""]):not([value=" "]) {
            background: rgba(115, 128, 236, 0.1);
            border-color: var(--clr-primary);
            color: var(--clr-primary);
            font-weight: 600;
        }

        /* Clear filter button */
        .clear-filter-button {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius-1);
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 300ms ease;
            border: 2px solid transparent;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .clear-filter-button:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .clear-filter-button::before {
            content: "filter_list_off";
            font-family: "Material Symbols Sharp";
            font-size: 1.1rem;
        }

        /* Responsive Design for Action Section */
        @media screen and (max-width: 768px) {
            .action-section {
                margin: 1rem 0;
            }

            .action-header {
                padding: 1rem 1.5rem;
            }

            .action-header h3 {
                font-size: 1.1rem;
            }

            .action-body {
                padding: 1rem 1.5rem;
            }

            .date-filter-form {
                flex-direction: column;
                align-items: stretch;
                gap: 0.8rem;
            }

            .date-filter-form input[type="date"],
            .clear-filter-button {
                width: 100%;
                justify-content: center;
            }
        }

        @media screen and (max-width: 480px) {
            .action-header {
                padding: 0.8rem 1rem;
            }

            .action-header h3 {
                font-size: 1rem;
            }            .action-body {
                padding: 0.8rem 1rem;
            }

            table thead th,
            table tbody td {
                padding: 0.8rem 0.5rem;
                font-size: 0.8rem;
            }

            .detail-button {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }

            .review-confirm-button,
            .review-delete-button {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
            }
        }</style>
</head>

<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo" id="logo-href">
                    <h2> <span class="danger">All-En</span> </h2>
                </div>
                <div class="close" id="close_btn">
                    <span class="material-symbols-sharp">close</span>
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
                <a href="admin-product.php"> <span class="material-symbols-sharp">shopping_bag </span>
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

                <a href="admin-review.php" class="active">
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

        </aside>        <main>
            <h1>Review Management</h1>

            <div class="action-section">
                <div class="action-header">
                    <h3>Filter Reviews</h3>
                </div>
                <div class="action-body">
                    <form action="" method="GET" id="dateFilterForm" class="date-filter-form">
                        <label for="dateFilterInput">Filter by Date:</label>
                        <input type="date" name="submit_date" id="dateFilterInput" value="<?php echo htmlspecialchars($date_filter); ?>">
                        <?php if (!empty($date_filter)): ?>
                            <a href="admin-review.php" class="clear-filter-button">Clear Filter</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Content</th>
                        <th>Rating</th>
                        <th>Submit Date</th>
                        <th>Confirm</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6">No pending reviews found<?php echo !empty($date_filter) ? ' for ' . htmlspecialchars($date_filter) : ''; ?>.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr data-review-id="<?php echo htmlspecialchars($review['id']); ?>">
                                <td><?php echo htmlspecialchars($review['product_id']); ?></td>
                                <td>
                                    <button class="detail-button" data-content="<?php echo htmlspecialchars($review['Content']); ?>">Detail</button>
                                </td>
                                <td><?php echo htmlspecialchars($review['rating'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($review['Submit_date'] ?? 'N/A'); ?></td>
                                <td class="review-confirm-button">
                                    Confirm
                                </td>
                                <td class="review-delete-button">
                                    Delete
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

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
                        <p><b><?php echo $admin_name_display; ?></b></p>
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
                            <p><b>Babar</b> Received his order of USB</p>
                        </div>
                    </div>
                    <div class="update">
                        <div class="profile-photo">
                            <img src="../images/profile-3.jpg" alt="" />
                        </div>
                        <div class="message">
                            <p><b>Ali</b> Received his order of USB</p>
                        </div>
                    </div>
                    <div class="update">
                        <div class="profile-photo">
                            <img src="../images/profile-2.jpg" alt="" />
                        </div>
                        <div class="message">
                            <p><b>Ramzan</b> Received his order of USB</p>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Review Content</h2>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body" id="modalReviewContent">
            </div>
            <div class="modal-footer">
                <button class="close-modal-btn" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>


    <script src="admin-dashboard.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>        document.addEventListener("DOMContentLoaded", function() {
            // Get the modal elements
            const reviewModal = document.getElementById('reviewModal');
            const closeButtons = document.querySelectorAll('.close-button, .close-modal-btn');
            const modalReviewContent = document.getElementById('modalReviewContent');

            // Function to open the modal
            function openModal(content) {
                modalReviewContent.textContent = content;
                reviewModal.style.display = 'flex'; // Use flex to center
            }

            // Function to close the modal
            function closeModal() {
                reviewModal.style.display = 'none';
                modalReviewContent.textContent = ''; // Clear content
            }

            // Make closeModal function global so it can be called from inline onclick
            window.closeModal = closeModal;

            // Close modal when clicking on x or Close button
            closeButtons.forEach(button => {
                button.addEventListener('click', closeModal);
            });

            // Close modal when clicking outside of the modal content
            window.addEventListener('click', function(event) {
                if (event.target == reviewModal) {
                    closeModal();
                }
            });

            // Handle 'Detail' button clicks for content modal
            document.querySelectorAll('.detail-button').forEach(button => {
                button.addEventListener('click', function() {
                    const content = this.dataset.content;
                    openModal(content);
                });
            });

            // --- New JavaScript for Auto-Applying Date Filter ---
            const dateFilterInput = document.getElementById('dateFilterInput');
            const dateFilterForm = document.getElementById('dateFilterForm');

            if (dateFilterInput && dateFilterForm) {
                dateFilterInput.addEventListener('change', function() {
                    dateFilterForm.submit(); // Automatically submit the form when the date changes
                });
            }
            // --- End New JavaScript ---

            // --- jQuery for Confirm and Delete button functionality ---
            $(document).ready(function() {
                // Function to handle Confirmation click
                // Function to handle Confirmation click
                function handleConfirmationClick(event) {
                    const $clickedCell = $(event.target);
                    // Ensure the click was on the styled TD, not just any part of the row
                    if ($clickedCell.hasClass('review-confirm-button')) {
                        const $row = $clickedCell.closest('tr');
                        const reviewId = $row.data('review-id');

                        if (confirm('Are you sure you want to confirm this review?')) {
                            $.ajax({
                                url: 'admin-review.php', // Same page
                                method: 'POST',
                                data: {
                                    action: 'confirm_review',
                                    review_id: reviewId
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status === 'success') {
                                        alert(response.message);
                                        $row.fadeOut(300, function() {
                                            $(this).remove();
                                            if ($('tbody tr').length === 0) {
                                                $('tbody').append('<tr><td colspan="6">No pending reviews found.</td></tr>');
                                            }
                                        });
                                    } else {
                                        alert('Error: ' + response.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("AJAX Error:", status, error);
                                    alert('An error occurred while confirming the review.');
                                }
                            });
                        }
                    }
                }

                // Function to handle Delete click
                function handleDeleteClick(event) {
                    const $clickedCell = $(event.target);
                    // Ensure the click was on the styled TD, not just any part of the row
                    if ($clickedCell.hasClass('review-delete-button')) {
                        const $row = $clickedCell.closest('tr');
                        const reviewId = $row.data('review-id');

                        if (confirm('Are you sure you want to DELETE this review? This action cannot be undone!')) {
                            $.ajax({
                                url: 'admin-review.php', // Same page
                                method: 'POST',
                                data: {
                                    action: 'cancel_review', // This action deletes the review
                                    review_id: reviewId
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status === 'success') {
                                        alert(response.message);
                                        $row.fadeOut(300, function() {
                                            $(this).remove();
                                            if ($('tbody tr').length === 0) {
                                                $('tbody').append('<tr><td colspan="6">No pending reviews found.</td></tr>');
                                            }
                                        });
                                    } else {
                                        alert('Error: ' + response.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("AJAX Error:", status, error);
                                    alert('An error occurred while deleting the review.');
                                }
                            });
                        }
                    }
                }

                // Attach click listeners using event delegation to the tbody
                // Now target the new specific classes:
                $('tbody').on('click', '.review-confirm-button', handleConfirmationClick);
                $('tbody').on('click', '.review-delete-button', handleDeleteClick);
            });
            // --- End jQuery ---
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