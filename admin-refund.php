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
if (isset($_GET['logout'])) { // Check if logout parameter exists
    session_unset();
    session_destroy();
    header("Location: admin-signin.php");
    exit();
}

// --- PHP for handling AJAX requests (Refund Status Update) ---
if (isset($_POST['action']) && $_POST['action'] === 'update_refund_status' && isset($_POST['refund_id']) && isset($_POST['new_status_id'])) {
    header('Content-Type: application/json'); // Respond with JSON

    $refund_id_to_update = filter_var($_POST['refund_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
    $new_status_id = filter_var($_POST['new_status_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);

    if (!empty($refund_id_to_update) && is_numeric($refund_id_to_update) && !empty($new_status_id) && is_numeric($new_status_id)) {
        // First, check if the refund_id exists in the 'refunds' table and update it.
        // It's important to update the 'refunds' table directly, not 'orders' for status.
        $stmt = $link->prepare("UPDATE refund SET status_id = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $new_status_id, $refund_id_to_update);

            if ($stmt->execute()) {
                // Get the new status name to send back to the client
                $new_status_name = getRefundStatusName($new_status_id, $link);
                echo json_encode(['status' => 'success', 'message' => 'Refund status updated.', 'new_status_id' => $new_status_id, 'new_status_name' => $new_status_name]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error updating refund status: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database prepare statement failed: ' . $link->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid refund ID or status ID.']);
    }
    exit(); // Important to exit after AJAX response
}

// --- Helper function to get refund status name (for AJAX response and display) ---
function getRefundStatusName($id, $link)
{
    $stmt = $link->prepare("SELECT Description FROM refund_status WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['Description'] ?? 'Unknown';
}

// --- PHP for fetching refund records for the main table display ---
$refunds = [];
$date_filter = $_GET['filter_date'] ?? ''; // Get date filter from GET request
$status_filter = $_GET['filter_status'] ?? 'All'; // Get status filter from GET request

$sql = "SELECT r.id AS refund_id, o.id AS order_id, r.reason, r.date AS refund_date, s.Description AS status_name, s.id AS status_id
            FROM refund r
            JOIN orders o ON o.refund_id = r.id
            JOIN refund_status s ON s.id = r.status_id";

$conditions = [];
$params = [];
$types = "";

if (!empty($date_filter)) {
    $conditions[] = "r.date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($status_filter !== 'All') { // 'All' means no specific status filter
    $conditions[] = "s.Description = ?"; // Note: filtering by Description column from refund_status
    $params[] = $status_filter;
    $types .= "s";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY r.date ASC, r.id ASC"; // Order by latest refund date

$stmt = $link->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $refunds[] = $row;
    }
    $stmt->close();
} else {
    error_log("Failed to prepare statement for fetching refunds: " . $link->error);
    // You might want to display an error message to the admin here
}

// Fetch all refund statuses for filter buttons and in-row status buttons
$refund_statuses_query = "SELECT id, Description FROM refund_status ORDER BY id";
$refund_statuses_result = mysqli_query($link, $refund_statuses_query);
$refund_statuses_array = []; // Storing as [id => name] for easy lookup
if ($refund_statuses_result) {
    while ($row = mysqli_fetch_assoc($refund_statuses_result)) {
        $refund_statuses_array[$row['id']] = $row['Description'];
    }
} else {
    die("Error fetching refund statuses: " . mysqli_error($link));
}


// Get admin name for display (from previous code)
$admin_name_display = "Admin";
$profile_image_src = "../images/profile-1.jpg"; // Default profile image
if (isset($_SESSION['admin_username'])) {
    $admin_name_display = htmlspecialchars($_SESSION['admin_username']);
}
// You might also have logic to fetch the actual admin profile image if available
// For example: if (isset($_SESSION['profile_image'])) { $profile_image_src = htmlspecialchars($_SESSION['profile_image']); }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Admin - Refund</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        /* New Filter Section Styling */
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

        /* Filter active indicator */
        .filter-active-indicator {
            background: rgba(115, 128, 236, 0.1);
            border-left: 4px solid var(--clr-primary);
            padding: 1rem 1.5rem;
            border-radius: 0 var(--border-radius-1) var(--border-radius-1) 0;
            margin-bottom: 1.5rem;
            color: var(--clr-primary);
            font-weight: 600;
        }

        .detail-reason-button {
            background: none;
            border: none;
            color: #7380ec;
            cursor: pointer;
            font-weight: bold;
            text-decoration: underline;
            /* Always underline for clarity */
            padding: 5px 0;
            /* Adjust padding for detail button */
            transition: color 300ms ease;
        }

        /* Modern Reason Modal Styling - Matching admin-review.php */
        .modal {
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
            padding: 1rem;
            box-sizing: border-box;
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal:not([style*="flex"]) {
            display: none;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
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
            position: relative;
            color: var(--clr-dark);
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
        .modal-header {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .modal-header h2::before {
            content: "money_off";
            font-family: "Material Symbols Sharp";
            font-size: 1.6rem;
        }

        /* Modal Body */
        .modal-body {
            padding: 1.5rem 2rem;
            overflow-y: auto;
            flex: 1;
            max-height: calc(85vh - 160px);
            scroll-behavior: smooth;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.6;
            font-size: 1rem;
            color: var(--clr-dark);
        }

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

        /* Close Button in Header */
        .close-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            line-height: 1;
        }

        .close-button:hover,
        .close-button:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1);
            text-decoration: none;
        }

        /* Modal Footer */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1rem 2rem;
            background: var(--clr-color-background);
            border-top: 1px solid var(--clr-light);
            flex-shrink: 0;
            margin-top: 0;
        }

        .close-modal-btn {
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

        .close-modal-btn:hover {
            background: var(--clr-light);
            transform: translateY(-1px);
        }

        .close-modal-btn::before {
            content: "close";
            font-family: "Material Symbols Sharp";
            font-size: 1.2rem;
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
                /* min-width: 700px; */
                /* Removed fixed min-width for better responsiveness */
                overflow-x: auto;
                /* Added for horizontal scrolling on small screens if table content is wide */
                display: block;
                /* Necessary for overflow-x to work on tables */
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
        }
    </style>
</head>

<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <!-- Updated logo to be an <a> tag -->
                    <a href="admin-lobby.php" id="logo-href-link">
                        <h2> <span class="danger">All-En</span> </h2>
                    </a>
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
                <a href="admin-warranty.php"> <span class="material-symbols-sharp">shield_watch </span>
                    <h3>Warranty</h3>
                </a>
                <a href="admin-refund.php" class="active"> <span class=" material-symbols-sharp">money_off </span>
                    <h3>Refund</h3>
                </a>
                <a href="suppliers.php"> <span class="material-symbols-sharp">business </span>
                    <h3>Suppliers</h3>
                </a>
                <a href="admin-create-account.php" <?php if ($current_user_role_id == 2) echo 'style="display:none;"'; ?>>
                    <span class="material-symbols-sharp">manage_accounts </span>
                    <h3>Create Account</h3>
                </a>
                <!-- Logout button using a form for better semantics -->
                <form method="post" action="">
                    <a href="?logout=true">
                        <span class="material-symbols-sharp">logout </span>
                        <h3>Log out</h3>
                    </a>
                </form>
            </div>
        </aside>
        <main>
            <h1>Refund Management</h1>
            <!-- Applying the new filter-section styling -->
            <div class="filter-section">
                <div class="filter-header">
                    <h3>Refund Filters</h3>
                </div>
                <div class="filter-body">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="filter_date">Filter by Date:</label>
                                <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                        </div>

                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Filter by Status:</label>
                                <div class="status-filter-buttons">
                                    <a href="?filter_status=All<?php echo (!empty($date_filter)) ? '&filter_date=' . urlencode(htmlspecialchars($date_filter)) : ''; ?>"
                                        class="category-filter-button <?php echo ($status_filter === 'All') ? 'active-filter' : ''; ?>">All</a>
                                    <?php foreach ($refund_statuses_array as $id => $name): ?>
                                        <a href="?filter_status=<?php echo urlencode(htmlspecialchars($name)); ?><?php echo (!empty($date_filter)) ? '&filter_date=' . urlencode(htmlspecialchars($date_filter)) : ''; ?>"
                                            class="category-filter-button <?php echo ($status_filter === $name) ? 'active-filter' : ''; ?>">
                                            <?php echo htmlspecialchars($name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="filter-row">
                            <?php if (!empty($date_filter) || $status_filter !== 'All'): ?>
                                <a href="admin-refund.php" class="clear-filter-btn">Clear All Filters</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>


            <h2>Refund Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Reason</th>
                        <th>Refund Date</th>
                        <th>Change Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($refunds)): ?>
                        <tr>
                            <td colspan="5">No refund records found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($refunds as $refund): ?>
                            <tr data-refund-id="<?php echo htmlspecialchars($refund['refund_id']); ?>">
                                <td><?php echo htmlspecialchars($refund['order_id']); ?></td>
                                <td>
                                    <button class="detail-reason-button" data-reason="<?php echo htmlspecialchars($refund['reason']); ?>">Details</button>
                                </td>
                                <td><?php echo htmlspecialchars($refund['refund_date']); ?></td>
                                <td>
                                    <div class="status-button-group">
                                        <?php foreach ($refund_statuses_array as $id => $name): ?>
                                            <button class="button-option <?php echo ($id == $refund['status_id']) ? 'highlight' : ''; ?>"
                                                data-refund-id="<?php echo htmlspecialchars($refund['refund_id']); ?>"
                                                data-status-id="<?php echo htmlspecialchars($id); ?>">
                                                <?php echo htmlspecialchars($name); ?>
                                            </button>
                                        <?php endforeach; ?> <!-- ← Fix: Added this line -->
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
    <div id="reasonModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Refund Reason</h2>
                <span class="close-button" id="closeReasonModal">&times;</span>
            </div>
            <div class="modal-body" id="modalReasonContent">
            </div>
            <div class="modal-footer">
                <button class="close-modal-btn" onclick="closeReasonModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="admin-dashboard.js"></script>
    <script>
        // Ensure jQuery is loaded before this script runs
        $(document).ready(function() {

            // --- Date Filter Submission ---
            const dateFilterInput = document.getElementById('filter_date');
            const dateFilterForm = dateFilterInput ? dateFilterInput.closest('form') : null;

            if (dateFilterInput && dateFilterForm) {
                dateFilterInput.addEventListener('change', function() {
                    dateFilterForm.submit(); // Automatically submit the form when the date changes
                });
            }

            // --- In-row Refund Status Update Logic ---
            $('table').on('click', '.status-button-group .button-option', function() {
                const $clickedButton = $(this);
                const newStatusId = $clickedButton.data('status-id');
                const newStatusName = $clickedButton.text();
                const refundId = $clickedButton.data('refund-id');
                const $row = $clickedButton.closest('tr');

                // Get the current highlighted status ID for comparison
                const currentHighlightedButton = $row.find('.status-button-group .button-option.highlight');
                const currentStatusId = currentHighlightedButton.data('status-id');

                // If clicked button is already the current status, do nothing
                if (parseInt(newStatusId) === parseInt(currentStatusId)) {
                    return;
                }

                // Using standard confirm()
                if (confirm('Change status for Refund ID ' + refundId + ' to "' + newStatusName + '"?')) {
                    $.ajax({
                        url: 'admin-refund.php', // Target this same PHP file
                        method: 'POST',
                        data: {
                            action: 'update_refund_status',
                            refund_id: refundId,
                            new_status_id: newStatusId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                // Using standard alert()
                                alert('Status updated successfully for Refund ID: ' + refundId);
                                // Remove highlight from previous button
                                currentHighlightedButton.removeClass('highlight');
                                // Add highlight to the newly selected button
                                $clickedButton.addClass('highlight');

                            } else {
                                // Using standard alert()
                                alert('Error updating status: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error, xhr.responseText);
                            // Using standard alert()
                            alert('An error occurred while updating the status.');
                        }
                    });
                }
            });

            // --- Refund Reason Modal Logic ---
            const reasonModal = document.getElementById('reasonModal');
            const modalReasonContent = document.getElementById('modalReasonContent');
            const closeReasonModalButton = document.getElementById('closeReasonModal');

            function openReasonModal(content) {
                modalReasonContent.textContent = content;
                reasonModal.style.display = 'flex'; // Use flex to center the modal
            }

            function closeReasonModal() {
                reasonModal.style.display = 'none';
                modalReasonContent.textContent = ''; // Clear content
            }

            // Make closeReasonModal function global so it can be called from inline onclick
            window.closeReasonModal = closeReasonModal;

            // Attach click listeners for closing the modal
            if (closeReasonModalButton) {
                closeReasonModalButton.addEventListener('click', closeReasonModal);
            }

            // Close modal when clicking outside of the modal content
            window.addEventListener('click', function(event) {
                if (event.target == reasonModal) {
                    closeReasonModal();
                }
            });

            // Handle 'Details' button clicks for reason modal
            $('table').on('click', '.detail-reason-button', function() {
                const reasonContent = $(this).data('reason');
                openReasonModal(reasonContent);
            });

            document.addEventListener('DOMContentLoaded', function() {
                // Get the element by its ID
                const logoElement = document.getElementById('logo-href-link'); // Changed ID to target the new <a>

                // Check if the element exists to prevent errors
                if (logoElement) {
                    // Add a click event listener to the element
                    logoElement.addEventListener('click', function(event) {
                        // Optionally prevent default if you want JS to handle navigation exclusively
                        // event.preventDefault();
                        console.log('Logo clicked! Redirecting to admin-lobby.php...');
                        window.location.href = 'admin-lobby.php';
                    });
                } else {
                    console.error("Element with ID 'logo-href-link' not found.");
                }
            });
        });
    </script>
</body>

</html>