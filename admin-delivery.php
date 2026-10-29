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

// --- PHP for handling Delivery Status Update (AJAX POST request) ---
if (isset($_POST['action']) && $_POST['action'] === 'update_delivery_status' && isset($_POST['order_id']) && isset($_POST['new_status_id'])) {
    $order_id_to_update = mysqli_real_escape_string($link, $_POST['order_id']);
    $new_delivery_status_id = (int)$_POST['new_status_id'];

    // Determine the corresponding orders.status_id based on delivery.status_id
    $new_order_status_id = 0; // Default or fallback
    switch ($new_delivery_status_id) {
        case 1: // Pending
            $new_order_status_id = 1;
            break;
        case 2: // Processing
            $new_order_status_id = 2;
            break;
        case 3: // Shipped
        case 4: // Out for Delivery
            $new_order_status_id = 3;
            break;
        case 5: // Delivered
            $new_order_status_id = 4;
            break;
        default:
            // Handle unexpected status_id or set a default error status
            echo json_encode(['status' => 'error', 'message' => 'Invalid delivery status ID.']);
            exit();
    }

    // Start a transaction for atomicity
    mysqli_begin_transaction($link);

    try {
        // Update delivery.status_id
        $delivery_update_query = "UPDATE delivery SET status_id = ? WHERE id = (SELECT delivery_id FROM orders WHERE id = ?)";
        $stmt_delivery = mysqli_prepare($link, $delivery_update_query);
        mysqli_stmt_bind_param($stmt_delivery, "ii", $new_delivery_status_id, $order_id_to_update);
        mysqli_stmt_execute($stmt_delivery);
        if (mysqli_stmt_affected_rows($stmt_delivery) === 0) {
            throw new Exception("No delivery record found or updated for Order ID: " . $order_id_to_update);
        }

        // Update orders.status_id
        $stmt_orders = mysqli_prepare($link, "UPDATE orders SET status_id = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_orders, "ii", $new_order_status_id, $order_id_to_update);
        mysqli_stmt_execute($stmt_orders);
        if (mysqli_stmt_affected_rows($stmt_orders) === 0) {
            throw new Exception("No order record found or updated for Order ID: " . $order_id_to_update);
        }

        mysqli_commit($link);
        echo json_encode(['status' => 'success', 'message' => 'Delivery status updated successfully.', 'new_delivery_status_id' => $new_delivery_status_id, 'new_delivery_status_name' => getStatusName($new_delivery_status_id)]);
    } catch (Exception $e) {
        mysqli_rollback($link);
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage() . " SQL Error: " . mysqli_error($link)]);
    }

    exit(); // Terminate script after AJAX response
}
// --- End PHP for handling Delivery Status Update ---


// --- PHP for fetching and filtering orders (main display) ---
$sql_select = "SELECT o.id AS order_id, o.order_date, ds.id AS delivery_status_id, ds.name AS delivery_status_name, d.id AS delivery_id
               FROM orders o
               JOIN delivery d ON o.delivery_id = d.id
               JOIN delivery_status ds ON d.status_id = ds.id";

$conditions = [];
$params = [];
$param_types = '';

// Date filter
if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $filter_date = $_GET['filter_date'];
    $conditions[] = "o.order_date = ?";
    $params[] = $filter_date;
    $param_types .= 's'; // 's' for string (date)
} else {
    $filter_date = ''; // Initialize for HTML value
}

// Category filter
if (isset($_GET['filter_category']) && !empty($_GET['filter_category'])) {
    $filter_category = $_GET['filter_category'];
    if ($filter_category !== 'All') { // 'All' means no filter
        $conditions[] = "ds.name = ?";
        $params[] = $filter_category;
        $param_types .= 's'; // 's' for string (category name)
    }
} else {
    $filter_category = 'All'; // Default to 'All'
}

if (count($conditions) > 0) {
    $sql_select .= " WHERE " . implode(" AND ", $conditions);
}

$sql_select .= " ORDER BY o.order_date DESC, o.id ASC";

// Prepare and execute the query using prepared statements
$stmt_orders = mysqli_prepare($link, $sql_select);
if ($stmt_orders && count($params) > 0) {
    mysqli_stmt_bind_param($stmt_orders, $param_types, ...$params);
}
mysqli_stmt_execute($stmt_orders);
$result_orders = mysqli_stmt_get_result($stmt_orders);

if (!$result_orders) {
    die("Error fetching orders: " . mysqli_error($link) . " SQL: " . htmlspecialchars($sql_select));
}
// --- End PHP for fetching and filtering orders ---

// Helper function to get status name (for AJAX response)
function getStatusName($id)
{
    global $delivery_statuses_array; // Access the globally fetched statuses
    return $delivery_statuses_array[$id] ?? 'Unknown';
}

// Fetch all delivery statuses for dropdown and buttons
$delivery_statuses_query = "SELECT id, name FROM delivery_status ORDER BY id";
$delivery_statuses_result = mysqli_query($link, $delivery_statuses_query);
$delivery_statuses_array = [];
if ($delivery_statuses_result) {
    while ($row = mysqli_fetch_assoc($delivery_statuses_result)) {
        $delivery_statuses_array[$row['id']] = $row['name'];
    }
} else {
    die("Error fetching delivery statuses: " . mysqli_error($link));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Admin - Delivery</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        /* Modern Filter Section - Matching admin-payment.php design */
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
        }        /* Status Buttons in Table */
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
                // Assuming you have a database connection ablished as $conn
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
                <a href="admin-delivery.php" class="active"> <span class="material-symbols-sharp">local_shipping </span>
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
            <h1>Delivery Management</h1>

            <?php if (!empty($filter_date) || $filter_category !== 'All'): ?>
                <div class="filter-active-indicator">
                    <strong>🔍 Active Filters:</strong> 
                    <?php if (!empty($filter_date)): ?>
                        Date: <strong><?php echo htmlspecialchars($filter_date); ?></strong>
                    <?php endif; ?>
                    <?php if ($filter_category !== 'All'): ?>
                        <?php echo (!empty($filter_date)) ? ' | ' : ''; ?>Status: <strong><?php echo htmlspecialchars($filter_category); ?></strong>
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
                                <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                            </div>
                            <?php if (!empty($filter_date) || $filter_category !== 'All'): ?>
                                <a href="admin-delivery.php" class="clear-filter-btn">Clear All Filters</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Filter by Status:</label>
                                <div class="status-filter-buttons">
                                    <a href="?filter_category=All<?php echo (!empty($filter_date)) ? '&filter_date=' . urlencode(htmlspecialchars($filter_date)) : ''; ?>"
                                        class="category-filter-button <?php echo ($filter_category === 'All') ? 'active-filter' : ''; ?>">All</a>
                                    <?php foreach ($delivery_statuses_array as $id => $name): ?>
                                        <a href="?filter_category=<?php echo urlencode(htmlspecialchars($name)); ?><?php echo (!empty($filter_date)) ? '&filter_date=' . urlencode(htmlspecialchars($filter_date)) : ''; ?>"
                                            class="category-filter-button <?php echo ($filter_category === $name) ? 'active-filter' : ''; ?>">
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
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Change Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result_orders) > 0) {
                        while ($row = mysqli_fetch_assoc($result_orders)) {
                    ?>
                            <tr data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>" data-current-status-id="<?php echo htmlspecialchars($row['delivery_status_id']); ?>">
                                <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                                <td class="input-group">
                                    <div class="status-button-group">
                                        <?php foreach ($delivery_statuses_array as $id => $name): ?>
                                            <button class="button-option <?php echo ($id == $row['delivery_status_id']) ? 'highlight' : ''; ?>"
                                                data-order-id="<?php echo htmlspecialchars($row['order_id']); ?>"
                                                data-value="<?php echo htmlspecialchars($id); ?>">
                                                <?php echo htmlspecialchars($name); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="3">No orders found matching your criteria.</td></tr>';
                    }
                    ?>
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

        $(document).ready(function() {
            // --- Date Filter Submission ---
            // If the date input changes, trigger the form submission.
            $('#filter_date').on('change', function() {
                $(this).closest('form').submit();
            });

            // --- In-row Status Update Logic ---
            $('table').on('click', '.status-button-group .button-option', function() {
                const $clickedButton = $(this);
                const newStatusId = $clickedButton.data('value');
                const newStatusName = $clickedButton.text();
                const orderId = $clickedButton.data('order-id'); // Get order ID from the button's data attribute
                const $row = $clickedButton.closest('tr');
                const currentStatusId = $row.data('current-status-id');


                // Prevent updating if it's already the current status
                if (parseInt(newStatusId) === parseInt(currentStatusId)) {
                    // console.log("Already current status. No update needed.");
                    return; // Do nothing if it's the same status
                }

                if (confirm('Change status for Order ID ' + orderId + ' to "' + newStatusName + '"?')) {
                    $.ajax({
                        url: 'admin-delivery.php', // Send to the same file
                        method: 'POST',
                        data: {
                            action: 'update_delivery_status',
                            order_id: orderId,
                            new_status_id: newStatusId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert('Status updated successfully for Order ID: ' + orderId);
                                // Update UI
                                $row.find('.current-status-display').text(response.new_delivery_status_name); // Update the displayed status name in the main cell
                                $row.data('current-status-id', response.new_delivery_status_id); // Update the data attribute on the row

                                // Remove highlight from all buttons in this row's group
                                $row.find('.status-button-group .button-option').removeClass('highlight');
                                // Add highlight to the newly selected button
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
            });
        });
    </script>
</body>

</html>