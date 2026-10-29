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

$message = '';
$message_type = '';

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'add':
            $message = "Supplier added successfully!";
            $message_type = "success";
            break;
        case 'edit':
            $message = "Supplier updated successfully!";
            $message_type = "success";
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_supplier':
                $name = mysqli_real_escape_string($link, $_POST['name']);
                $email = mysqli_real_escape_string($link, $_POST['email']);
                $phone = mysqli_real_escape_string($link, $_POST['phone']);
                $country = mysqli_real_escape_string($link, $_POST['country']);
                $city = mysqli_real_escape_string($link, $_POST['city']);
                $street = mysqli_real_escape_string($link, $_POST['street']);
                $account_number = mysqli_real_escape_string($link, $_POST['account_number']);

                $insert_query = "INSERT INTO suppliers (name, email, phone, country, city, street, account_number) 
                                VALUES ('$name', '$email', '$phone', '$country', '$city', '$street', '$account_number')";                if (mysqli_query($link, $insert_query)) {
                    // Redirect to prevent form resubmission on refresh
                    header("Location: suppliers.php?success=add");
                    exit();
                } else {
                    $message = "Error adding supplier: " . mysqli_error($link);
                    $message_type = "error";
                }
                break;
            case 'edit_supplier':
                $id = (int)$_POST['supplier_id'];
                $name = mysqli_real_escape_string($link, $_POST['name']);
                $email = mysqli_real_escape_string($link, $_POST['email']);
                $phone = mysqli_real_escape_string($link, $_POST['phone']);
                $country = mysqli_real_escape_string($link, $_POST['country']);
                $city = mysqli_real_escape_string($link, $_POST['city']);
                $street = mysqli_real_escape_string($link, $_POST['street']);
                $account_number = mysqli_real_escape_string($link, $_POST['account_number']);

                $update_query = "UPDATE suppliers SET 
                                name = '$name', 
                                email = '$email', 
                                phone = '$phone', 
                                country = '$country', 
                                city = '$city', 
                                street = '$street', 
                                account_number = '$account_number' 
                                WHERE id = $id";                if (mysqli_query($link, $update_query)) {
                    // Redirect to prevent form resubmission on refresh
                    header("Location: suppliers.php?success=edit");
                    exit();
                } else {
                    $message = "Error updating supplier: " . mysqli_error($link);
                    $message_type = "error";
                }
                break;
            case 'delete_supplier':
                $id = (int)$_POST['supplier_id'];
                $delete_query = "DELETE FROM suppliers WHERE id = $id";

                if (mysqli_query($link, $delete_query)) {
                    echo json_encode(['status' => 'success', 'message' => 'Supplier deleted successfully!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error deleting supplier: ' . mysqli_error($link)]);
                }
                exit();
                break;
        }
    }
}

// Fetch suppliers data
$suppliers_query = "SELECT id, name, email, phone, country, city, street, account_number FROM suppliers ORDER BY id DESC";
$result_suppliers = mysqli_query($link, $suppliers_query);

if (!$result_suppliers) {
    die("Error fetching suppliers: " . mysqli_error($link));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-En Admin - Suppliers</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="admin-style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>    <style>
        /* Modern Add Supplier Form Container - Matching admin-create-account.php design */
        .add-supplier-form-container {
            background: var(--clr-white);
            padding: 0;
            border-radius: var(--card-border-radius);
            width: 90%;
            max-width: 600px;
            margin: 1.5rem auto 0;
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

        /* Form Header */
        .add-supplier-form-container h2 {
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

        .add-supplier-form-container h2::before {
            content: "business";
            font-family: "Material Symbols Sharp";
            font-size: 1.6rem;
        }

        /* Modern Table Container - Matching admin-create-account.php design */
        .table-container {
            background: var(--clr-white);
            padding: 0;
            border-radius: var(--card-border-radius);
            width: 100%;
            margin: 1.5rem 0 0;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            animation: modalSlideIn 0.3s ease-out;
        }

        /* Table Header */
        .table-header {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white !important;
            margin: 0;
            padding: 1.2rem 2rem;
            flex-shrink: 0;
        }

        .table-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: white !important;
        }        .table-header h2::before {
            content: "table_view";
            font-family: "Material Symbols Sharp";
            font-size: 1.6rem;
        }

        /* Enhanced Table Styling - Matching admin-review.php */
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
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
            padding: 0;
        }        .edit-btn, .delete-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: rgba(115, 128, 236, 0.1);
            color: var(--clr-primary);
            border: 1px solid rgba(115, 128, 236, 0.2);
        }

        .edit-btn:hover {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
        }

        .delete-btn {
            background: rgba(255, 119, 130, 0.1);
            color: var(--clr-danger);
            border: 1px solid rgba(255, 119, 130, 0.2);
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }        /* Modern Add Button */
        .add-supplier-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin: 1rem auto 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .add-supplier-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        /* Modern Modal Styling - Matching admin-create-account.php */
        .modal-overlay {
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
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay:not(.active) {
            display: none;
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

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
            display: flex;
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

        /* Modal Header */
        .modal-content h2 {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            margin: 0;
            padding: 1.2rem 2rem;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
            flex-shrink: 0;
        }

        .modal-content h2::before {
            content: "business";
            font-family: "Material Symbols Sharp";
            font-size: 1.6rem;
            margin-right: 0.8rem;
        }

        /* Close Button in Header */
        .modal-content .close-button {
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
            position: static;
        }

        .modal-content .close-button:hover,
        .modal-content .close-button:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1);
            text-decoration: none;
        }

        /* Modal Body */
        .modal-content form {
            padding: 1.5rem 2rem;
            overflow-y: auto;
            flex: 1;
            max-height: calc(85vh - 160px);
            scroll-behavior: smooth;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .modal-content form::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content form::-webkit-scrollbar-track {
            background: var(--clr-light);
            border-radius: 3px;
        }

        .modal-content form::-webkit-scrollbar-thumb {
            background: #8b94f0;
            border-radius: 3px;
        }

        .modal-content form::-webkit-scrollbar-thumb:hover {
            background: rgb(86, 98, 231);
        }

        .modal-content label {
            display: block;
            margin-bottom: 1rem;
            font-weight: 600;
            color: var(--clr-dark);
            position: relative;
        }

        .modal-content input[type="text"],
        .modal-content input[type="email"] {
            width: 100%;
            padding: 0.8rem 1rem;
            margin-top: 0.5rem;
            border: 2px solid var(--clr-light);
            border-radius: var(--border-radius-1);
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
            background: var(--clr-white);
        }

        .modal-content input[type="text"]:focus,
        .modal-content input[type="email"]:focus {
            outline: none;
            border-color: var(--clr-primary);
            box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
            background: rgba(115, 128, 236, 0.02);
        }

        /* Modal Footer */
        .modal-content .form-actions {
            grid-column: span 2;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1rem 2rem;
            background: var(--clr-color-background);
            border-top: 1px solid var(--clr-light);
            flex-shrink: 0;
            margin-top: 0;
        }

        .modal-content button {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: var(--border-radius-1);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .modal-content button.cancel-button {
            background: var(--clr-color-background);
            color: var(--clr-dark-variant);
            border: 2px solid var(--clr-light);
        }

        .modal-content button.cancel-button:hover {
            background: var(--clr-light);
            transform: translateY(-1px);
        }

        .modal-content button.save-button {
            background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
        }

        .modal-content button.save-button:hover {
            background: linear-gradient(135deg, #6b7ce8 0%, #7c85ed 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(115, 128, 236, 0.4);
        }

        /* Message styling */
        .message {
            background: var(--clr-white);
            padding: 1rem 2rem;
            margin: 1rem auto 0;
            border-radius: var(--card-border-radius);
            max-width: 600px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .message.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(32, 201, 151, 0.05) 100%);
            color: #155724;
        }

        .message.error {
            border-left-color: var(--clr-danger);
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(255, 119, 130, 0.05) 100%);
            color: #721c24;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .modal-content {
                width: 95%;
                max-height: 90vh;
                margin: 1rem;
            }

            .modal-content h2 {
                padding: 1rem 1.5rem;
                font-size: 1.3rem;
            }

            .modal-content form {
                padding: 1rem 1.5rem;
                grid-template-columns: 1fr;
                max-height: calc(90vh - 140px);
            }

            .modal-content .form-actions {
                padding: 1rem 1.5rem;
                flex-direction: column;
                grid-column: span 1;
            }

            .modal-content button {
                width: 100%;
            }

            .table-container {
                width: 95%;
                margin: 1rem auto 0;
            }

            .table-header {
                padding: 1rem 1.5rem;
            }

            .table-header h2 {
                font-size: 1.3rem;            }

            table {
                min-width: 800px;
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
                <a href="admin-refund.php"> <span class=" material-symbols-sharp">money_off </span>
                    <h3>Refund</h3>
                </a>
                <a href="suppliers.php" class="active"> <span class="material-symbols-sharp">business </span>
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
            <h1>Suppliers Management</h1>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <button class="add-supplier-btn" onclick="openAddModal()">
                <span class="material-symbols-sharp">add</span>
                Add New Supplier
            </button>            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Street</th>
                        <th>Account Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result_suppliers) > 0) {
                        while ($row = mysqli_fetch_assoc($result_suppliers)) {
                    ?>
                            <tr data-supplier-id="<?php echo htmlspecialchars($row['id']); ?>">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td title="<?php echo htmlspecialchars($row['name']); ?>"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td title="<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['country']); ?></td>
                                <td><?php echo htmlspecialchars($row['city']); ?></td>
                                <td title="<?php echo htmlspecialchars($row['street']); ?>"><?php echo htmlspecialchars($row['street']); ?></td>
                                <td><?php echo htmlspecialchars($row['account_number']); ?></td>
                                <td class="action-buttons">
                                    <button class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                    <button class="delete-btn" onclick="deleteSupplier(<?php echo $row['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="9">No suppliers found.</td></tr>';
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
        </div>
    </div>    <!-- Add Supplier Modal -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-content">
            <h2>
                Add New Supplier
                <button class="close-button" onclick="closeAddModal()">&times;</button>
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_supplier">

                <label for="add_name">
                    Name:
                    <input type="text" id="add_name" name="name" required>
                </label>

                <label for="add_email">
                    Email:
                    <input type="email" id="add_email" name="email" required>
                </label>

                <label for="add_phone">
                    Phone:
                    <input type="text" id="add_phone" name="phone" required>
                </label>

                <label for="add_country">
                    Country:
                    <input type="text" id="add_country" name="country" required>
                </label>

                <label for="add_city">
                    City:
                    <input type="text" id="add_city" name="city" required>
                </label>

                <label for="add_street">
                    Street:
                    <input type="text" id="add_street" name="street" required>
                </label>

                <label for="add_account_number">
                    Account Number:
                    <input type="text" id="add_account_number" name="account_number" required>
                </label>

                <div class="form-actions">
                    <button type="button" class="cancel-button" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="save-button">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <h2>
                Edit Supplier
                <button class="close-button" onclick="closeEditModal()">&times;</button>
            </h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_supplier">
                <input type="hidden" id="edit_supplier_id" name="supplier_id">

                <label for="edit_name">
                    Name:
                    <input type="text" id="edit_name" name="name" required>
                </label>

                <label for="edit_email">
                    Email:
                    <input type="email" id="edit_email" name="email" required>
                </label>

                <label for="edit_phone">
                    Phone:
                    <input type="text" id="edit_phone" name="phone" required>
                </label>

                <label for="edit_country">
                    Country:
                    <input type="text" id="edit_country" name="country" required>
                </label>

                <label for="edit_city">
                    City:
                    <input type="text" id="edit_city" name="city" required>
                </label>

                <label for="edit_street">
                    Street:
                    <input type="text" id="edit_street" name="street" required>
                </label>

                <label for="edit_account_number">
                    Account Number:
                    <input type="text" id="edit_account_number" name="account_number" required>
                </label>

                <div class="form-actions">
                    <button type="button" class="cancel-button" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="save-button">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>    <script src="admin-dashboard.js"></script>
    <script>
        // Modal functions
        function openAddModal() {
            const modal = document.getElementById('addModal');
            modal.classList.add('active');
            modal.style.display = 'flex';
        }

        function closeAddModal() {
            const modal = document.getElementById('addModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function openEditModal(supplier) {
            document.getElementById('edit_supplier_id').value = supplier.id;
            document.getElementById('edit_name').value = supplier.name;
            document.getElementById('edit_email').value = supplier.email;
            document.getElementById('edit_phone').value = supplier.phone;
            document.getElementById('edit_country').value = supplier.country;
            document.getElementById('edit_city').value = supplier.city;
            document.getElementById('edit_street').value = supplier.street;
            document.getElementById('edit_account_number').value = supplier.account_number;
            
            const modal = document.getElementById('editModal');
            modal.classList.add('active');
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Delete supplier function
        function deleteSupplier(supplierId) {
            if (confirm('Are you sure you want to delete this supplier? This action cannot be undone!')) {
                $.ajax({
                    url: 'suppliers.php',
                    method: 'POST',
                    data: {
                        action: 'delete_supplier',
                        supplier_id: supplierId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            location.reload(); // Refresh the page to update the table
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        alert('An error occurred while deleting the supplier.');
                    }
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }        // Logo click event
        document.addEventListener('DOMContentLoaded', function() {
            // Clear URL parameters after showing success message
            if (window.location.search.includes('success=')) {
                setTimeout(() => {
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    window.history.replaceState({}, document.title, url.pathname);
                }, 3000); // Clear after 3 seconds
            }

            const logoElement = document.getElementById('logo-href');
            if (logoElement) {
                logoElement.addEventListener('click', function() {
                    console.log('Logo clicked! Redirecting to admin-lobby.php...');
                    window.location.href = 'admin-lobby.php';
                });
            } else {
                console.error("Element with ID 'logo-href' not found.");
            }
        });
    </script>
</body>

</html>