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

// --- PHP for handling DELETION (AJAX POST request) ---
if (isset($_POST['action']) && $_POST['action'] === 'delete_order' && isset($_POST['order_id'])) {
    $order_id_to_delete = mysqli_real_escape_string($link, $_POST['order_id']);

    $delete_query = "DELETE FROM orders WHERE id = $order_id_to_delete";
    if (mysqli_query($link, $delete_query)) {
        echo json_encode(['status' => 'success', 'message' => 'Order deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete order: ' . mysqli_error($link)]);
    }
    exit(); // Important: terminate script after AJAX response
}
// --- End PHP for handling DELETION ---


// --- PHP for handling CONFIRMATION (AJAX POST request) ---
if (isset($_POST['action']) && $_POST['action'] === 'confirm_order' && isset($_POST['order_id'])) {
    $order_id_to_confirm = mysqli_real_escape_string($link, $_POST['order_id']);
    $admin_id = $_SESSION['admin_user_id']; // Ensure admin_id is available

    // 1. Check if the order is already confirmed
    $check_query = "SELECT admin_id FROM orders WHERE id = $order_id_to_confirm";
    $check_result = mysqli_query($link, $check_query);
    $current_admin_id = null;
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $row = mysqli_fetch_assoc($check_result);
        $current_admin_id = $row['admin_id'];
    }

    if ($current_admin_id !== NULL) {
        echo json_encode(['status' => 'info', 'message' => 'Order already confirmed by another admin.']);
        exit();
    }

    // 2. Fetch order details and check stock
    $stock_check_query = "SELECT od.product_id AS product_id, od.color AS color, od.size_id AS size_id, od.quantity AS quantity, pd.stock AS stock
                          FROM order_detail od
                          JOIN product_details pd ON od.product_id = pd.PRODUCT_ID
                                                   AND od.color = pd.color
                                                   AND od.size_id = pd.size_id
                          WHERE od.order_id = $order_id_to_confirm";

    $stock_check_result = mysqli_query($link, $stock_check_query);

    if (!$stock_check_result) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch order details for stock check: ' . mysqli_error($link)]);
        exit();
    }

    $order_items = [];
    $stock_insufficient = false;

    // Collect all order items and check stock for each
    while ($item = mysqli_fetch_assoc($stock_check_result)) {
        if ($item['quantity'] > $item['stock']) {
            $stock_insufficient = true;
            break; // No need to check further, stock is insufficient
        }
        $order_items[] = $item;
    }

    if ($stock_insufficient) {
        echo json_encode(['status' => 'error', 'message' => 'The stock is not enough for one or more items in this order.']);
        exit();
    }

    // 3. If stock is sufficient, begin transaction for atomic updates
    mysqli_begin_transaction($link);
    $all_updates_successful = true;

    // 4. Update product stock for each item
    foreach ($order_items as $item) {
        $new_stock = $item['stock'] - $item['quantity'];
        $update_stock_query = "UPDATE product_details
                               SET stock = $new_stock
                               WHERE PRODUCT_ID = '{$item['product_id']}'
                               AND color = '{$item['color']}'
                               AND size_id = {$item['size_id']}"; // Assuming size_id is numeric

        if (!mysqli_query($link, $update_stock_query)) {
            $all_updates_successful = false;
            break; // Stop if any stock update fails
        }
    }

    // 5. Update admin_id in orders table if all stock updates were successful
    if ($all_updates_successful) {
        $update_order_query = "UPDATE orders SET admin_id = $admin_id WHERE id = $order_id_to_confirm";
        if (mysqli_query($link, $update_order_query)) {
            mysqli_commit($link); // Commit transaction if all good
            echo json_encode(['status' => 'success', 'message' => 'Order confirmed and stock updated successfully.']);
        } else {
            mysqli_rollback($link); // Rollback if order update fails
            echo json_encode(['status' => 'error', 'message' => 'Failed to confirm order (admin ID update failed): ' . mysqli_error($link)]);
        }
    } else {
        mysqli_rollback($link); // Rollback if any stock update failed
        echo json_encode(['status' => 'error', 'message' => 'Failed to update stock for one or more items: ' . mysqli_error($link)]);
    }
    exit();
}
// --- End PHP for handling CONFIRMATION ---


// --- PHP for fetching and filtering orders ---
$sql_orders = "SELECT o.id, o.order_date, o.total_price, pm.name AS payment_method_name, o.admin_id
               FROM orders o
               JOIN order_payment_methods pm ON o.payment_method_id = pm.id
               WHERE o.admin_id IS NULL"; // Only display unconfirmed orders

$filter_date = ''; // Initialize filter_date
if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $filter_date = mysqli_real_escape_string($link, $_GET['filter_date']);
    $sql_orders .= " AND o.order_date = '$filter_date'";
}

$sql_orders .= " ORDER BY o.order_date ASC, o.id ASC";

$result_orders = mysqli_query($link, $sql_orders);
if (!$result_orders) {
    die("Error fetching orders: " . mysqli_error($link) . " SQL: " . htmlspecialchars($sql_orders));
}
// --- End PHP for fetching and filtering orders ---
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Admin - Payment</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        /* Modern Filter Section - Matching admin-create-account.php design */
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

        .date form {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 0;
        }

        .date label {
            font-weight: 600;
            color: var(--clr-dark);
            font-size: 0.9rem;
        }

        .date input[type="date"] {
            padding: 0.8rem 1rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            background-color: var(--clr-white);
            color: var(--clr-dark);
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .date input[type="date"]:focus {
            outline: none;
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
            background: rgba(115, 128, 236, 0.02);
        }

        .date input[type="date"]:hover {
            border-color: var(--clr-primary);
        }

        /* Active filter styling */
        .date input[type="date"]:not([value=""]):not([value=" "]) {
            background-color: rgba(115, 128, 236, 0.1);
            border-color: var(--clr-primary);
            color: var(--clr-primary);
        }

        /* Clear filter button */
        .date a {
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

        .date a:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .date a::before {
            content: "clear";
            font-family: "Material Symbols Sharp";
            font-size: 1.1rem;
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

        /* Simple Table Styling */
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

        table tbody td.button-confirmation,
        table tbody td.delete-button-confirmation {
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            padding: 0.8rem 1rem;
            border-radius: var(--border-radius-1);
        }

        table tbody td.button-confirmation {
            color: var(--clr-success);
            background: rgba(40, 167, 69, 0.1);
        }

        table tbody td.button-confirmation:hover {
            background: linear-gradient(135deg, var(--clr-success) 0%, #28a745 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        table tbody td.delete-button-confirmation {
            color: var(--clr-danger);
            background: rgba(255, 119, 130, 0.1);
        }

        table tbody td.delete-button-confirmation:hover {
            background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
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
                overflow-x: auto;
            }

            .date form {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            table {
                min-width: 600px;
            }

            table thead th,
            table tbody td {
                padding: 0.8rem 1rem;
                font-size: 0.8rem;
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

                <a href="admin-payment.php" class="active">
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
            <h1>Payment Management</h1>

            <?php if (!empty($filter_date)): ?>
                <div class="filter-active-indicator">
                    <strong>🔍 Filter Active:</strong> Showing orders for date: <strong><?php echo htmlspecialchars($filter_date); ?></strong>
                </div>
            <?php endif; ?> <div class="filter-section">
                <div class="filter-header">
                    <h3>Date Filter</h3>
                </div>
                <div class="filter-body">
                    <div class="date">
                        <form method="GET" action="">
                            <label for="filter_date">Filter by Date:</label>
                            <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                            <?php if (!empty($filter_date)): ?>
                                <a href="admin-payment.php">Clear Filter</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Total Price</th>
                        <th>Payment Method</th>
                        <th>Order Date</th>
                        <th>Confirmation</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result_orders) > 0) {
                        while ($row = mysqli_fetch_assoc($result_orders)) {
                            // $is_confirmed is now always false for this view, as admin_id is filtered for NULL
                            // $is_confirmed = !empty($row['admin_id']); // This line is effectively redundant here
                    ?>
                            <tr data-order-id="<?php echo htmlspecialchars($row['id']); ?>">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['total_price']); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_method_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                                <td class="button-confirmation" data-confirmed="false">
                                    Confirm
                                </td>
                                <td class="delete-button-confirmation"> Delete
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="6">No pending orders found.</td></tr>';
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
        $(document).ready(function() {
            // --- Date Filter Submission (NEW) ---
            // If the date input changes, trigger the form submission.
            document.getElementById('filter_date').addEventListener('change', function() {
                this.closest('form').submit();
            });

            // --- Dynamic styling for active filter ---
            function updateFilterStyling() {
                const dateInput = document.getElementById('filter_date');
                if (dateInput && dateInput.value) {
                    dateInput.classList.add('filter-active');
                } else if (dateInput) {
                    dateInput.classList.remove('filter-active');
                }
            }

            // Apply styling on page load
            updateFilterStyling();

            // Update styling when date changes
            document.getElementById('filter_date').addEventListener('input', updateFilterStyling);
            // --- End Date Filter Submission ---


            // Function to handle Confirmation click
            function handleConfirmationClick(event) {
                const $clickedCell = $(event.target);
                if ($clickedCell.data('confirmed') === false || $clickedCell.data('confirmed') === 'false') {
                    const $row = $clickedCell.closest('tr');
                    const orderId = $row.data('order-id');

                    if (confirm('Are you sure you want to confirm Order ID: ' + orderId + '?')) { // Added confirmation
                        $.ajax({
                            url: 'admin-payment.php',
                            method: 'POST',
                            data: {
                                action: 'confirm_order',
                                order_id: orderId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    alert('Order ' + orderId + ' confirmed successfully!');
                                    $row.fadeOut(300, function() {
                                        $(this).remove();
                                        if ($('tbody tr').length === 0) {
                                            $('tbody').append('<tr><td colspan="6">No pending orders found.</td></tr>');
                                        }
                                    });
                                } else if (response.status === 'info') {
                                    alert(response.message);
                                    $row.fadeOut(300, function() {
                                        $(this).remove();
                                        if ($('tbody tr').length === 0) {
                                            $('tbody').append('<tr><td colspan="6">No pending orders found.</td></tr>');
                                        }
                                    });
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("AJAX Error:", status, error);
                                alert('An error occurred while confirming the order.');
                            }
                        });
                    }
                }
            }

            // Function to handle DELETE click
            function handleDeleteClick(event) {
                const $clickedCell = $(event.target);
                const $row = $clickedCell.closest('tr');
                const orderId = $row.data('order-id');

                if (confirm('Are you sure you want to DELETE Order ID: ' + orderId + '? This action cannot be undone!')) {
                    $.ajax({
                        url: 'admin-payment.php',
                        method: 'POST',
                        data: {
                            action: 'delete_order', // New action for deletion
                            order_id: orderId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert('Order ' + orderId + ' deleted successfully!');
                                // Remove the row from the UI
                                $row.fadeOut(300, function() {
                                    $(this).remove();
                                    if ($('tbody tr').length === 0) {
                                        $('tbody').append('<tr><td colspan="6">No pending orders found.</td></tr>');
                                    }
                                });
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error);
                            alert('An error occurred while deleting the order.');
                        }
                    });
                }
            }

            // Attach click listeners using event delegation
            $('tbody').on('click', '.button-confirmation', handleConfirmationClick);
            $('tbody').on('click', '.delete-button-confirmation', handleDeleteClick); // Attach for delete button

            // No need for initial styling as rows with admin_id will not be fetched
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