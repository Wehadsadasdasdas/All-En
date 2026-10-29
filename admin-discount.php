<?php
require_once "connection.php";
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_is_login']) || $_SESSION['admin_is_login'] !== true) {
    header("Location: admin-signin.php");
    exit();
}

$admin_id = $_SESSION['admin_user_id']; // Get the logged-in admin's ID
$current_user_role_id = $_SESSION['admin_role_id'] ?? null;

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: admin-signin.php");
    exit();
}

// --- PHP for handling AJAX requests ---

// Handle INSERT Discount Code
if (isset($_POST['action']) && $_POST['action'] === 'add_discount') {
    $code = mysqli_real_escape_string($link, $_POST['code']);
    $discount_percent = (int)$_POST['off']; // 'off' from form name
    $start_date = mysqli_real_escape_string($link, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($link, $_POST['end_date']);
    $description = mysqli_real_escape_string($link, $_POST['description']);

    $insert_query = "INSERT INTO discount_code (Code, discount_percent, Start_date, End_date, Description) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $insert_query);
    mysqli_stmt_bind_param($stmt, "sisss", $code, $discount_percent, $start_date, $end_date, $description);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Discount code added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add discount code: ' . mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
    exit();
}

// Handle UPDATE Discount Code
if (isset($_POST['action']) && $_POST['action'] === 'edit_discount') {
    $id = (int)$_POST['id'];
    $code = mysqli_real_escape_string($link, $_POST['code']);
    $discount_percent = (int)$_POST['off'];
    $start_date = mysqli_real_escape_string($link, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($link, $_POST['end_date']);
    $description = mysqli_real_escape_string($link, $_POST['description']);

    $update_query = "UPDATE discount_code SET Code = ?, discount_percent = ?, Start_date = ?, End_date = ?, Description = ? WHERE id = ?";
    $stmt = mysqli_prepare($link, $update_query);
    mysqli_stmt_bind_param($stmt, "sisssi", $code, $discount_percent, $start_date, $end_date, $description, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Discount code updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update discount code: ' . mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
    exit();
}


// Handle DELETE Single Discount Code
if (isset($_POST['action']) && $_POST['action'] === 'delete_discount' && isset($_POST['discount_id'])) {
    $discount_id_to_delete = (int)$_POST['discount_id'];

    $delete_query = "DELETE FROM discount_code WHERE id = ?";
    $stmt = mysqli_prepare($link, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $discount_id_to_delete);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Discount code deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete discount code: ' . mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
    exit();
}

// Handle DELETE Expired Discount Codes
if (isset($_POST['action']) && $_POST['action'] === 'delete_expired_discounts') {
    $current_date = date('Y-m-d'); // Get today's date inญี่ป-MM-DD format

    $delete_expired_query = "DELETE FROM discount_code WHERE End_date < ?";
    $stmt = mysqli_prepare($link, $delete_expired_query);
    mysqli_stmt_bind_param($stmt, "s", $current_date);

    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        echo json_encode(['status' => 'success', 'message' => $affected_rows . ' expired discount code(s) deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete expired discount codes: ' . mysqli_error($link)]);
    }
    mysqli_stmt_close($stmt);
    exit();
}

// --- End PHP for handling AJAX requests ---


// --- PHP for fetching and displaying discount codes ---
$sql_discounts = "SELECT id, Code, discount_percent, Start_date, End_date, Description FROM discount_code ORDER BY End_date ASC, Start_date ASC";
$result_discounts = mysqli_query($link, $sql_discounts);

if (!$result_discounts) {
    die("Error fetching discount codes: " . mysqli_error($link));
}
// --- End PHP for fetching and displaying discount codes ---
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Admin - Discount Code</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        /* Modern Filter Section - Matching admin-delivery.php design */
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
            content: "settings";
            font-family: "Material Symbols Sharp";
            font-size: 1.4rem;
        }

        /* Action Body */
        .action-body {
            padding: 1.5rem 2rem;
            flex: 1;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .action-button {
            padding: 0.8rem 1.5rem;
            border: 2px solid var(--clr-light);
            background-color: var(--clr-white);
            border-radius: var(--border-radius-1);
            cursor: pointer;
            text-decoration: none;
            color: var(--clr-dark);
            transition: all 0.3s ease;
            white-space: nowrap;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-button.add-button {
            background: linear-gradient(135deg, var(--clr-success) 0%, #28a745 100%);
            color: white;
            border-color: var(--clr-success);
            font-weight: 600;
        }

        .action-button.add-button:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .action-button.add-button::before {
            content: "add";
            font-family: "Material Symbols Sharp";
            font-size: 1.1rem;
        }

        .action-button.delete-expired {
            background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
            color: white;
            border-color: var(--clr-danger);
            font-weight: 600;
        }

        .action-button.delete-expired:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .action-button.delete-expired::before {
            content: "delete_sweep";
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

        /* Action buttons in table */
        table tbody td.edit-button,
        table tbody td.delete-button-confirmation {
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            padding: 0.8rem 1rem;
            border-radius: var(--border-radius-1);
        }

        table tbody td.edit-button {
            color: var(--clr-primary);
            background: rgba(115, 128, 236, 0.1);
        }

        table tbody td.edit-button:hover {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
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

        /* Discount percentage styling */
        table tbody td.primary {
            background: rgba(40, 167, 69, 0.1);
            color: var(--clr-success);
            font-weight: 600;
            border-radius: var(--border-radius-1);
        }/* Modern Discount Modal Styling - Matching admin-delivery.php */
        .modal-discount {
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
            animation: fadeIn 0.3s ease-out;
        }

        .hidden-discount {
            display: none !important;
        }

        /* Modal Content */
        .modal-content-discount {
            background: var(--clr-white);
            border-radius: var(--card-border-radius);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: modalSlideIn 0.3s ease-out;
        }

        /* Modal Header */
        .modal-content-discount h2 {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.2rem 2rem;
            font-size: 1.4rem;
            font-weight: 600;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .modal-content-discount h2::before {
            content: "percent";
            font-family: "Material Symbols Sharp";
            font-size: 1.6rem;
        }

        /* Modal Body */
        .modal-body-discount {
            padding: 1.5rem 2rem;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .modal-body-discount label {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--clr-dark);
            font-size: 0.9rem;
        }

        .modal-content-discount input:not([type="hidden"]),
        .modal-content-discount textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
            background: var(--clr-white);
        }

        .modal-content-discount input:focus,
        .modal-content-discount textarea:focus {
            outline: none;
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
            background: rgba(115, 128, 236, 0.02);
        }

        .modal-content-discount textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Modal Actions */
        .modal-actions-discount {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1rem 2rem;
            background: var(--clr-color-background);
            border-top: 1px solid var(--clr-light);
            flex-shrink: 0;
        }

        .modal-actions-discount button {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: var(--border-radius-1);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .modal-actions-discount #cancelBtn,
        .modal-actions-discount #editCancelBtn {
            background: var(--clr-color-background);
            color: var(--clr-dark-variant);
            border: 2px solid var(--clr-light);
        }

        .modal-actions-discount #cancelBtn:hover,
        .modal-actions-discount #editCancelBtn:hover {
            background: var(--clr-light);
            transform: translateY(-1px);
        }

        .modal-actions-discount button[type="submit"] {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
        }

        .modal-actions-discount button[type="submit"]:hover {
            background: linear-gradient(135deg, #6b7ce8 0%, #7c85ed 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(115, 128, 236, 0.4);
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .action-section {
                width: 95%;
                margin: 1rem auto;
            }

            .action-header {
                padding: 1rem 1.5rem;
            }

            .action-header h3 {
                font-size: 1.3rem;
            }

            .action-body {
                padding: 1rem 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            table {
                min-width: 700px;
            }

            table thead th,
            table tbody td {
                padding: 0.8rem 1rem;
                font-size: 0.8rem;
            }

            .modal-content-discount {
                width: 95%;
                max-height: 90vh;
                margin: 1rem;
            }

            .modal-content-discount h2 {
                padding: 1rem 1.5rem;
                font-size: 1.3rem;
            }

            .modal-body-discount {
                padding: 1rem 1.5rem;
                max-height: calc(90vh - 140px);
            }

            .modal-actions-discount {
                padding: 1rem 1.5rem;
                flex-direction: column;
            }
        }

        /* Custom Message Box Styles */
        .message-box {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: var(--border-radius-2);
            box-shadow: var(--box-shadow);
            color: var(--color-white);
            font-weight: bold;
            z-index: 1001;
            display: none;
            /* Hidden by default */
            opacity: 0;
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
        }

        .message-box.show {
            display: block;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .message-box.success {
            background-color: var(--color-success);
        }

        .message-box.error {
            background-color: var(--color-danger);
        }

        .message-box.info {
            background-color: var(--color-info-dark);
        }
    </style>
</head>

<body>
    <!-- Custom Message Box -->
    <div id="messageBox" class="message-box"></div>

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
                <a href="admin-product.php"> <span class="material-symbols-sharp">shopping_bag </span>
                    <h3>Product</h3>
                </a>
                <a href="admin-discount.php" class="active"> <span class="material-symbols-sharp">percent </span>
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
        <!-- --------------
        end asid
    -------------------- -->

        <!-- --------------
        start main part
    --------------- -->        <main>
            <h1>Discount Code Management</h1>

            <div class="action-section">
                <div class="action-header">
                    <h3>Actions</h3>
                </div>
                <div class="action-body">
                    <div class="action-buttons">
                        <button id="add_discount" class="action-button add-button">Add Discount Code</button>
                        <button id="deleteExpiredBtn" class="action-button delete-expired">Delete Expired Codes</button>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Discount Code</th>
                        <th>Discount Off (%)</th>
                        <th>Description</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Edit</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result_discounts) > 0) {
                        while ($row = mysqli_fetch_assoc($result_discounts)) {
                            // Determine if the discount is expired for visual indication (optional)
                            $is_expired = (strtotime($row['End_date']) < strtotime(date('Y-m-d')));
                    ?>
                            <tr data-discount-id="<?php echo htmlspecialchars($row['id']); ?>" class="<?php echo $is_expired ? 'expired-discount' : ''; ?>">
                                <td><?php echo htmlspecialchars($row['Code']); ?></td>
                                <td class="primary"><?php echo htmlspecialchars($row['discount_percent']); ?>%</td>
                                <td><?php echo htmlspecialchars($row['Description']); ?></td>
                                <td><?php echo htmlspecialchars($row['Start_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['End_date']); ?></td>
                                <td class="edit-button">Edit</td>
                                <td class="delete-button-confirmation">Delete</td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="7">No discount codes found.</td></tr>';
                    }
                    ?>
                </tbody>            </table>

            <!-- Add Discount Modal -->
            <div id="discountModal" class="modal-discount hidden-discount">
                <div class="modal-content-discount">
                    <h2>Add New Discount</h2>
                    <div class="modal-body-discount">
                        <form id="discountForm">
                            <label>
                                Discount Code:
                                <input type="text" name="code" required />
                            </label>
                            <label>
                                Discount Off (%):
                                <input type="number" name="off" min="0" max="100" required />
                            </label>
                            <label>
                                Description:
                                <textarea name="description"></textarea>
                            </label>
                            <label>
                                Start Date:
                                <input type="date" name="start_date" required />
                            </label>
                            <label>
                                End Date:
                                <input type="date" name="end_date" required />
                            </label>
                        </form>
                    </div>
                    <div class="modal-actions-discount">
                        <button type="button" id="cancelBtn">Cancel</button>
                        <button type="submit" form="discountForm">Submit</button>
                    </div>
                </div>
            </div>

            <!-- Edit Discount Modal -->
            <div id="editDiscountModal" class="modal-discount hidden-discount">
                <div class="modal-content-discount">
                    <h2>Edit Discount Code</h2>
                    <div class="modal-body-discount">
                        <form id="editDiscountForm">
                            <input type="hidden" name="id" id="editDiscountId" />
                            <label>
                                Discount Code:
                                <input type="text" name="code" id="editCode" required />
                            </label>
                            <label>
                                Discount Off (%):
                                <input type="number" name="off" id="editOff" min="0" max="100" required />
                            </label>
                            <label>
                                Description:
                                <textarea name="description" id="editDescription"></textarea>
                            </label>
                            <label>
                                Start Date:
                                <input type="date" name="start_date" id="editStartDate" required />
                            </label>
                            <label>
                                End Date:
                                <input type="date" name="end_date" id="editEndDate" required />
                            </label>
                        </form>
                    </div>
                    <div class="modal-actions-discount">
                        <button type="button" id="editCancelBtn">Cancel</button>
                        <button type="submit" form="editDiscountForm">Update</button>
                    </div>
                </div>
            </div>

        </main>
        <!------------------
        end main
    ------------------->

        <!----------------
        start right main
    ---------------------->
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
        </div>
    </div>

    <script src="admin-dashboard.js"></script>
    <script>
        // Function to show a custom message box
        function showMessageBox(message, type = 'info') {
            const messageBox = $('#messageBox');
            messageBox.html(message); // Use .html() to allow buttons in message
            messageBox.removeClass('success error info').addClass(type);
            messageBox.addClass('show');

            // Set timeout to hide, but clear it if buttons are clicked immediately
            const timeoutId = setTimeout(() => {
                messageBox.removeClass('show');
            }, 3000); // Message disappears after 3 seconds

            // Store timeout ID on the message box element
            messageBox.data('timeoutId', timeoutId);
        }

        // Function to hide the message box and clear its timeout
        function hideMessageBox() {
            const messageBox = $('#messageBox');
            messageBox.removeClass('show');
            clearTimeout(messageBox.data('timeoutId'));
        }        $(document).ready(function() {
            // --- Modal Open/Close Logic (Add Discount) ---
            $('#add_discount').on('click', function() {
                $('#discountModal').removeClass('hidden-discount');
            });

            $('#cancelBtn').on('click', function() {
                $('#discountModal').addClass('hidden-discount');
                $('#discountForm')[0].reset();
            });

            // Close Add modal if clicked outside content
            $(document).on('click', function(event) {
                if ($(event.target).is('#discountModal')) {
                    $('#discountModal').addClass('hidden-discount');
                    $('#discountForm')[0].reset();
                }
            });


            // --- AJAX: Add Discount Code Form Submission ---
            document.getElementById('discountForm').addEventListener('submit', (e) => {
                e.preventDefault();

                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData.entries());
                data.action = 'add_discount'; // Add action to identify request on server

                $.ajax({
                    url: 'admin-discount.php', // Send to the same file
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showMessageBox(response.message, 'success');
                            document.getElementById('discountModal').classList.add('hidden-discount');
                            e.target.reset();
                            location.reload(); // Reload page to show new discount
                        } else {
                            showMessageBox('Error: ' + response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error, xhr.responseText);
                        showMessageBox('An error occurred while adding the discount.', 'error');
                    }
                });
            });

            // --- Edit Discount Modal Logic ---
            $('table').on('click', '.edit-button', function() {
                const $row = $(this).closest('tr');
                const discountId = $row.data('discount-id');
                // The order of children might change, explicitly get by index if stable, or by class if unique
                const code = $row.find('td:nth-child(1)').text();
                const discountPercent = parseInt($row.find('td:nth-child(2)').text()); // Parse int to remove '%'
                const description = $row.find('td:nth-child(3)').text();
                const startDate = $row.find('td:nth-child(4)').text();
                const endDate = $row.find('td:nth-child(5)').text();

                // Populate the edit form
                $('#editDiscountId').val(discountId);
                $('#editCode').val(code);
                $('#editOff').val(discountPercent);
                $('#editDescription').val(description);
                $('#editStartDate').val(startDate);
                $('#editEndDate').val(endDate);

                // Show the edit modal
                $('#editDiscountModal').removeClass('hidden-discount');
            });

            // Close Edit modal on Cancel button click
            $('#editCancelBtn').on('click', function() {
                $('#editDiscountModal').addClass('hidden-discount');
                $('#editDiscountForm')[0].reset(); // Reset form on cancel
            });

            // Close Edit modal if clicked outside content
            $(document).on('click', function(event) {
                if ($(event.target).is('#editDiscountModal')) {
                    $('#editDiscountModal').addClass('hidden-discount');
                    $('#editDiscountForm')[0].reset();
                }
            });


            // --- AJAX: Edit Discount Code Form Submission ---
            $('#editDiscountForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                data.action = 'edit_discount'; // Action for server-side PHP

                $.ajax({
                    url: 'admin-discount.php',
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showMessageBox(response.message, 'success');
                            $('#editDiscountModal').addClass('hidden-discount');
                            // Reload the page to reflect changes
                            location.reload();
                        } else {
                            showMessageBox('Error: ' + response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error, xhr.responseText);
                        showMessageBox('An error occurred while updating the discount.', 'error');
                    }
                });
            });            // --- AJAX: Delete Single Discount Code ---
            $('table').on('click', '.delete-button-confirmation', function() {
                const $clickedCell = $(this);
                const $row = $clickedCell.closest('tr');
                const discountId = $row.data('discount-id');
                const code = $row.find('td:nth-child(1)').text();

                if (confirm('Are you sure you want to delete discount code "' + code + '"?\nThis action cannot be undone!')) {
                    $.ajax({
                        url: 'admin-discount.php',
                        method: 'POST',
                        data: {
                            action: 'delete_discount',
                            discount_id: discountId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert(response.message);
                                $row.fadeOut(300, function() {
                                    $(this).remove();
                                    if ($('tbody tr').length === 0) {
                                        $('tbody').append('<tr><td colspan="7">No discount codes found.</td></tr>');
                                    }
                                });
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error);
                            alert('An error occurred while deleting the discount code.');
                        }
                    });
                }
            });            // --- AJAX: Delete Expired Discount Codes Button ---
            $('#deleteExpiredBtn').on('click', function() {
                if (confirm('Are you sure you want to DELETE ALL EXPIRED discount codes?\nThis action cannot be undone!')) {
                    $.ajax({
                        url: 'admin-discount.php',
                        method: 'POST',
                        data: {
                            action: 'delete_expired_discounts'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert(response.message);
                                location.reload(); // Reload page to reflect deletions
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error);
                            alert('An error occurred while deleting expired discount codes.');
                        }
                    });
                }
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