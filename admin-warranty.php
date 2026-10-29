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

// --- PHP for handling AJAX requests (Warranty Status Update) ---
if (isset($_POST['action']) && $_POST['action'] === 'update_warranty_status' && isset($_POST['warranty_id']) && isset($_POST['new_status_id'])) {
    header('Content-Type: application/json'); // Respond with JSON

    $warranty_id_to_update = filter_var($_POST['warranty_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
    $new_status_id = filter_var($_POST['new_status_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);

    if (!empty($warranty_id_to_update) && is_numeric($warranty_id_to_update) && !empty($new_status_id) && is_numeric($new_status_id)) {
        $stmt = $link->prepare("UPDATE warranty SET status_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status_id, $warranty_id_to_update);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Warranty status updated.', 'new_status_id' => $new_status_id, 'new_status_name' => getWarrantyStatusName($new_status_id, $link)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating warranty status: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid warranty ID or status ID.']);
    }
    exit(); // Important to exit after AJAX response
}

// --- Helper function to get warranty status name (for AJAX response) ---
function getWarrantyStatusName($id, $link)
{
    // This function needs to fetch the name from the database, as global array might not be available in AJAX context
    $stmt = $link->prepare("SELECT name FROM warranty_status WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['name'] ?? 'Unknown';
}


// --- PHP for fetching warranty records for the main table display ---
$warranties = [];
$date_filter = $_GET['filter_date'] ?? ''; // Get date filter from GET request
$status_filter = $_GET['filter_status'] ?? 'All'; // Get status filter from GET request

$sql = "SELECT w.id, w.claim_date, w.description, c.name AS customer_name, pd.product_id, pd.size_id, pd.color, s.name AS status_name, s.id AS status_id
        FROM warranty w
        JOIN product_details pd ON pd.warranty_token = w.warranty_token
        JOIN customers c ON w.customer_id = c.id
        JOIN warranty_status s ON w.status_id = s.id";

$conditions = [];
$params = [];
$types = "";

if (!empty($date_filter)) {
    $conditions[] = "w.claim_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($status_filter !== 'All') { // 'All' means no specific status filter
    $conditions[] = "s.name = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY w.claim_date ASC, w.id ASC"; // Order by latest claim date

$stmt = $link->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $warranties[] = $row;
    }
    $stmt->close();
} else {
    error_log("Failed to prepare statement for fetching warranties: " . $link->error);
    // You might want to display an error message to the admin here
}

// Fetch all warranty statuses for filter buttons and in-row status buttons
$warranty_statuses_query = "SELECT id, name FROM warranty_status ORDER BY id";
$warranty_statuses_result = mysqli_query($link, $warranty_statuses_query);
$warranty_statuses_array = [];
if ($warranty_statuses_result) {
    while ($row = mysqli_fetch_assoc($warranty_statuses_result)) {
        $warranty_statuses_array[$row['id']] = $row['name'];
    }
} else {
    die("Error fetching warranty statuses: " . mysqli_error($link));
}


// Get admin name for display (from previous code)
$admin_name_display = "Admin";
$profile_image_src = "../images/profile-1.jpg";
if (isset($_SESSION['admin_username'])) {
    $admin_name_display = htmlspecialchars($_SESSION['admin_username']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Admin - Warranty</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        /* Modern Filter Section - Matching admin-delivery.php design */
        .filter-section {
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

        /* Filter Header */
        .filter-header {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.2rem 2rem;
            flex-shrink: 0;
        }

        .filter-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: white;
        }

        .filter-header h3::before {
            content: "filter_alt";
            font-family: "Material Symbols Sharp";
            font-size: 1.4rem;
        }

        /* Filter Body */
        .filter-body {
            padding: 1.5rem 2rem;
            flex: 1;
        }

        .filter-row {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .filter-row:last-child {
            margin-bottom: 0;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--clr-dark);
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .filter-group input[type="date"] {
            padding: 0.8rem 1rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            background-color: var(--clr-white);
            color: var(--clr-dark);
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .filter-group input[type="date"]:focus {
            outline: none;
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
            background: rgba(115, 128, 236, 0.02);
        }

        .filter-group input[type="date"]:hover {
            border-color: var(--clr-primary);
        }

        /* Active filter styling */
        .filter-group input[type="date"]:not([value=""]):not([value=" "]) {
            background-color: rgba(115, 128, 236, 0.1);
            border-color: var(--clr-primary);
            color: var(--clr-primary);
        }

        /* Status Filter Buttons */
        .status-filter-buttons {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .status-filter-buttons .category-filter-button {
            padding: 0.6rem 1.2rem;
            border: 2px solid var(--clr-light);
            background-color: var(--clr-white);
            border-radius: var(--border-radius-1);
            cursor: pointer;
            text-decoration: none;
            color: var(--clr-dark);
            transition: all 0.3s ease;
            white-space: nowrap;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .status-filter-buttons .category-filter-button:hover {
            background-color: rgba(115, 128, 236, 0.1);
            border-color: var(--clr-primary);
            color: var(--clr-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.2);
        }

        .status-filter-buttons .category-filter-button.active-filter {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            border-color: var(--clr-primary);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
            font-weight: 600;
        }

        /* Clear filter button */
        .clear-filter-btn {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius-1);
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .clear-filter-btn:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .clear-filter-btn::before {
            content: "clear";
            font-family: "Material Symbols Sharp";
            font-size: 1.1rem;
        }

        /* Enhanced Table Styling */
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

        /* Status Buttons in Table */
        .status-button-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .status-button-group .button-option {
            padding: 0.6rem 1rem;
            border: 2px solid var(--clr-light);
            background-color: var(--clr-white);
            color: var(--clr-dark);
            border-radius: var(--border-radius-1);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-button-group .button-option:hover {
            background-color: rgba(115, 128, 236, 0.1);
            border-color: var(--clr-primary);
            color: var(--clr-primary);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(115, 128, 236, 0.2);
        }

        .status-button-group .button-option.highlight {
            background: linear-gradient(135deg, var(--clr-success) 0%, #28a745 100%);
            color: white;
            border-color: var(--clr-success);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
            font-weight: 600;
        }

        .status-button-group .button-option.highlight:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        /* Detail Button Enhancement */
        .detail-description-button {
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

        .detail-description-button:hover {
            background: linear-gradient(135deg, #5f6dc2 0%, #7380ec 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
        }

        /* Filter active indicator */
        .filter-active-indicator {
            background: rgba(115, 128, 236, 0.1);
            border-left: 4px solid var(--clr-primary);
            padding: 1rem 1.5rem;
            border-radius: 0 var(--border-radius-1) var(--border-radius-1) 0;
            margin-bottom: 1.5rem;
            color: var(--clr-primary);
            font-weight: 600;
        }        /* Modern Description Modal Styling - Enhanced design matching admin-delivery.php */
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
            content: "description";
            font-family: "Material Symbols Sharp";
            font-size: 1.8rem;
            opacity: 0.9;
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

        /* Modal Body */
        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
            max-height: calc(85vh - 180px);
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.7;
            font-size: 1.05rem;
            scroll-behavior: smooth;
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
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .filter-section {
                width: 95%;
                margin: 1rem auto;
            }

            .filter-header {
                padding: 1rem 1.5rem;
            }

            .filter-header h3 {
                font-size: 1.3rem;
            }

            .filter-body {
                padding: 1rem 1.5rem;
            }

            .filter-row {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .status-filter-buttons {
                justify-content: center;
            }

            table {
                min-width: 700px;
            }

            table thead th,
            table tbody td {
                padding: 0.8rem 1rem;
                font-size: 0.8rem;
            }

            .status-button-group .button-option {
                font-size: 0.75rem;
                padding: 0.5rem 0.8rem;
            }

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

                <a href="admin-review.php">
                    <span class="material-symbols-sharp">star_rate </span>
                    <h3>Review</h3>
                    <span class="msg_count" id="review_msg_count"><?php echo $review_count; ?></span>
                </a>
                <a href="admin-warranty.php" class="active"> <span class="material-symbols-sharp">shield_watch </span>
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
            <h1>Warranty Management</h1>

            <?php if (!empty($date_filter) || $status_filter !== 'All'): ?>
                <div class="filter-active-indicator">
                    <strong>🔍 Active Filters:</strong> 
                    <?php if (!empty($date_filter)): ?>
                        Date: <strong><?php echo htmlspecialchars($date_filter); ?></strong>
                    <?php endif; ?>
                    <?php if ($status_filter !== 'All'): ?>
                        <?php echo (!empty($date_filter)) ? ' | ' : ''; ?>Status: <strong><?php echo htmlspecialchars($status_filter); ?></strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="filter-section">
                <div class="filter-header">
                    <h3>Filters</h3>
                </div>
                <div class="filter-body">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="filter_date">Filter by Date:</label>
                                <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            <?php if (!empty($date_filter) || $status_filter !== 'All'): ?>
                                <a href="admin-warranty.php" class="clear-filter-btn">Clear All Filters</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Filter by Status:</label>
                                <div class="status-filter-buttons">
                                    <a href="?filter_status=All<?php echo (!empty($date_filter)) ? '&filter_date=' . urlencode(htmlspecialchars($date_filter)) : ''; ?>"
                                        class="category-filter-button <?php echo ($status_filter === 'All') ? 'active-filter' : ''; ?>">All</a>
                                    <?php foreach ($warranty_statuses_array as $id => $name): ?>
                                        <a href="?filter_status=<?php echo urlencode(htmlspecialchars($name)); ?><?php echo (!empty($date_filter)) ? '&filter_date=' . urlencode(htmlspecialchars($date_filter)) : ''; ?>"
                                            class="category-filter-button <?php echo ($status_filter === $name) ? 'active-filter' : ''; ?>">
                                            <?php echo htmlspecialchars($name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <button type="submit" style="display: none;">Apply Filters</button>
                    </form>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Warranty ID</th>
                        <th>Claim Date</th>
                        <th>Customer Name</th>
                        <th>Product ID</th>
                        <th>Size ID</th>
                        <th>Color</th>
                        <th>Description</th>
                        <th>Change Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($warranties)): ?>
                        <tr>
                            <td colspan="8">No warranty records found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($warranties as $warranty): ?>
                            <tr data-warranty-id="<?php echo htmlspecialchars($warranty['id']); ?>">
                                <td><?php echo htmlspecialchars($warranty['id']); ?></td>
                                <td><?php echo htmlspecialchars($warranty['claim_date']); ?></td>
                                <td><?php echo htmlspecialchars($warranty['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($warranty['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($warranty['size_id']); ?></td>
                                <td><?php echo htmlspecialchars($warranty['color']); ?></td>
                                <td>
                                    <button class="detail-description-button" data-description="<?php echo htmlspecialchars($warranty['description']); ?>">Details</button>
                                </td>
                                <td>
                                    <div class="status-button-group">
                                        <?php foreach ($warranty_statuses_array as $id => $name): ?>
                                            <button class="button-option <?php echo ($id == $warranty['status_id']) ? 'highlight' : ''; ?>"
                                                data-warranty-id="<?php echo htmlspecialchars($warranty['id']); ?>"
                                                data-value="<?php echo htmlspecialchars($id); ?>">
                                                <?php echo htmlspecialchars($name); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
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
    </div>    <div id="descriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Warranty Description</h2>
                <span class="close-button" id="closeDescriptionModal">&times;</span>
            </div>
            <div class="modal-body" id="modalDescriptionContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="close-modal-btn" id="closeDescriptionModalBtn">

                    Close
                </button>
            </div>
        </div>
    </div>


    <script src="admin-dashboard.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // --- Date Filter Submission ---
            const dateFilterInput = document.getElementById('filter_date');
            const dateFilterForm = dateFilterInput ? dateFilterInput.closest('form') : null;

            if (dateFilterInput && dateFilterForm) {
                dateFilterInput.addEventListener('change', function() {
                    dateFilterForm.submit(); // Automatically submit the form when the date changes
                });
            }

            // --- In-row Status Update Logic for Warranty ---
            $('table').on('click', '.status-button-group .button-option', function() {
                const $clickedButton = $(this);
                const newStatusId = $clickedButton.data('value');
                const newStatusName = $clickedButton.text();
                const warrantyId = $clickedButton.data('warranty-id');
                const $row = $clickedButton.closest('tr');
                const currentStatusId = $row.find('.current-status-display').data('status-id') || $row.data('current-status-id');

                if (parseInt(newStatusId) === parseInt(currentStatusId)) {
                    return;
                }

                if (confirm('Change status for Warranty ID ' + warrantyId + ' to "' + newStatusName + '"?')) {
                    $.ajax({
                        url: 'admin-warranty.php',
                        method: 'POST',
                        data: {
                            action: 'update_warranty_status',
                            warranty_id: warrantyId,
                            new_status_id: newStatusId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert('Status updated successfully for Warranty ID: ' + warrantyId);
                                $row.find('.current-status-display').text(response.new_status_name);
                                $row.data('current-status-id', response.new_status_id);

                                $row.find('.status-button-group .button-option').removeClass('highlight');
                                $clickedButton.addClass('highlight');

                            } else {
                                alert('Error updating status: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error, xhr.responseText);
                            alert('An error occurred while updating the status.');
                        }
                    });
                }
            });            // --- Description Modal Logic ---
            const descriptionModal = document.getElementById('descriptionModal');
            const modalDescriptionContent = document.getElementById('modalDescriptionContent');
            const closeDescriptionModalButtons = document.querySelectorAll('#closeDescriptionModal, #closeDescriptionModalBtn');

            function openDescriptionModal(content) {
                modalDescriptionContent.textContent = content;
                descriptionModal.style.display = 'flex'; // Use flex to center
            }

            function closeDescriptionModal() {
                descriptionModal.style.display = 'none';
                modalDescriptionContent.textContent = ''; // Clear content
            }

            // Attach click listeners for closing the modal
            closeDescriptionModalButtons.forEach(button => {
                button.addEventListener('click', closeDescriptionModal);
            });

            // Close modal when clicking outside of the modal content
            window.addEventListener('click', function(event) {
                if (event.target === descriptionModal) {
                    closeDescriptionModal();
                }
            });

            // Handle 'Details' button clicks for description modal
            $('table').on('click', '.detail-description-button', function() {
                const descriptionContent = $(this).data('description');
                openDescriptionModal(descriptionContent);
            });
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