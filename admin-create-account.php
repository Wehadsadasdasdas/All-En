<?php
require_once "connection.php"; // Include your database connection file
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

// Function to check if an admin is the last owner
function is_last_owner($link, $admin_id)
{
  $current_admin_role_query = "SELECT role_id FROM admins WHERE id = ?";
  $stmt_role = mysqli_prepare($link, $current_admin_role_query);
  if ($stmt_role === false) { // Check if prepare failed
    error_log("Failed to prepare statement for current admin role: " . mysqli_error($link));
    return false; // Or handle error appropriately
  }
  mysqli_stmt_bind_param($stmt_role, "i", $admin_id);
  mysqli_stmt_execute($stmt_role);
  $result_role = mysqli_stmt_get_result($stmt_role);
  $current_admin_role = mysqli_fetch_assoc($result_role)['role_id'];
  mysqli_stmt_close($stmt_role);

  if ($current_admin_role == 1) { // If the admin is an Owner
    $check_owner_query = "SELECT COUNT(*) FROM admins WHERE role_id = 1";
    $result_owner_count = mysqli_query($link, $check_owner_query);
    if (!$result_owner_count) { // Check if query failed
      error_log("Failed to count owners: " . mysqli_error($link));
      return false; // Or handle error appropriately
    }
    $owner_count = mysqli_fetch_row($result_owner_count)[0];
    return $owner_count <= 1; // True if this is the last owner
  }
  return false; // Not an owner or not the last owner
}


// --- PHP for handling AJAX requests ---

// Handle INSERT New Admin Account
if (isset($_POST['action']) && $_POST['action'] === 'add_account') {
  $email = mysqli_real_escape_string($link, $_POST['email']);
  $password = $_POST['password']; // Get plain password
  $name = mysqli_real_escape_string($link, $_POST['name']);
  $role_id = (int)$_POST['role_id'];

  // Hash the password before storing it
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  $insert_query = "INSERT INTO admins (Password, Email, Name, role_id) VALUES (?, ?, ?, ?)";
  $stmt = mysqli_prepare($link, $insert_query);
  if ($stmt === false) { // Check if prepare failed
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . mysqli_error($link)]);
    exit();
  }
  // 's' for string (hashed password, email, name), 'i' for integer (role_id)
  mysqli_stmt_bind_param($stmt, "sssi", $hashed_password, $email, $name, $role_id);

  if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Admin account added successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add admin account: ' . mysqli_error($link)]);
  }
  mysqli_stmt_close($stmt);
  exit();
}

// Handle DELETE Single Admin Account
if (isset($_POST['action']) && $_POST['action'] === 'delete_account' && isset($_POST['admin_id'])) {
  $admin_id_to_delete = (int)$_POST['admin_id'];

  // Prevent an admin from deleting their own account if they are the last owner
  if (is_last_owner($link, $admin_id_to_delete)) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot delete the last owner account.']);
    exit();
  }

  $delete_query = "DELETE FROM admins WHERE id = ?";
  $stmt = mysqli_prepare($link, $delete_query);
  if ($stmt === false) { // Check if prepare failed
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . mysqli_error($link)]);
    exit();
  }
  mysqli_stmt_bind_param($stmt, "i", $admin_id_to_delete);

  if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Admin account deleted successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete admin account: ' . mysqli_error($link)]);
  }
  mysqli_stmt_close($stmt);
  exit();
}

// Handle FETCH Admin Details for Editing
if (isset($_POST['action']) && $_POST['action'] === 'fetch_admin_details' && isset($_POST['admin_id'])) {
  $admin_id_to_fetch = (int)$_POST['admin_id'];

  $fetch_query = "SELECT id, Email, Name, role_id FROM admins WHERE id = ?";
  $stmt = mysqli_prepare($link, $fetch_query);
  if ($stmt === false) { // Check if prepare failed
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . mysqli_error($link)]);
    exit();
  }
  mysqli_stmt_bind_param($stmt, "i", $admin_id_to_fetch);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($admin_data = mysqli_fetch_assoc($result)) {
    echo json_encode(['status' => 'success', 'data' => $admin_data]);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Admin not found.']);
  }
  mysqli_stmt_close($stmt);
  exit();
}

// Handle UPDATE Admin Account
if (isset($_POST['action']) && $_POST['action'] === 'update_account' && isset($_POST['admin_id'])) {
  $admin_id_to_update = (int)$_POST['admin_id'];
  $email = mysqli_real_escape_string($link, $_POST['email']);
  $name = mysqli_real_escape_string($link, $_POST['name']);
  $role_id = (int)$_POST['role_id'];
  $password = $_POST['password'] ?? ''; // Optional password update

  $update_fields = [];
  $bind_types = '';
  $bind_params = [];

  $update_fields[] = 'Email = ?';
  $bind_types .= 's';
  $bind_params[] = $email;

  $update_fields[] = 'Name = ?';
  $bind_types .= 's';
  $bind_params[] = $name;

  $update_fields[] = 'role_id = ?';
  $bind_types .= 'i';
  $bind_params[] = $role_id;

  // Check if the current admin is trying to change their own role from owner to non-owner,
  // and if they are the last owner.
  if ($admin_id_to_update == $_SESSION['admin_user_id'] && $_SESSION['admin_role_id'] == 1 && $role_id != 1) {
    if (is_last_owner($link, $admin_id_to_update)) {
      echo json_encode(['status' => 'error', 'message' => 'Cannot change the role of the last owner account.']);
      exit();
    }
  }

  if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $update_fields[] = 'Password = ?';
    $bind_types .= 's';
    $bind_params[] = $hashed_password;
  }

  $update_query = "UPDATE admins SET " . implode(', ', $update_fields) . " WHERE id = ?";
  $bind_types .= 'i'; // Add type for the admin_id
  $bind_params[] = $admin_id_to_update;

  $stmt = mysqli_prepare($link, $update_query);
  if ($stmt === false) { // Check if prepare failed
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . mysqli_error($link)]);
    exit();
  }
  mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);

  if (mysqli_stmt_execute($stmt)) {
    // If the current user updated their own role, update the session
    if ($admin_id_to_update == $_SESSION['admin_user_id'] && $_SESSION['admin_role_id'] != $role_id) {
      $_SESSION['admin_role_id'] = $role_id;
    }
    echo json_encode(['status' => 'success', 'message' => 'Admin account updated successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update admin account: ' . mysqli_error($link)]);
  }
  mysqli_stmt_close($stmt);
  exit();
}

// --- End PHP for handling AJAX requests ---


// --- PHP for fetching and displaying admin accounts ---
$sql_admins = "SELECT id, Email, Name, role_id FROM admins ORDER BY id ASC";
$result_admins = mysqli_query($link, $sql_admins);

if (!$result_admins) {
  die("Error fetching admin accounts: " . mysqli_error($link));
}
// --- End PHP for fetching and displaying admin accounts ---

// --- PHP for fetching admin roles for dropdowns ---
$sql_roles = "SELECT id, role_name FROM admin_role ORDER BY id ASC";
$result_roles = mysqli_query($link, $sql_roles);
$admin_roles = [];
if ($result_roles) {
  while ($row = mysqli_fetch_assoc($result_roles)) {
    $admin_roles[] = $row;
  }
} else {
  die("Error fetching admin roles: " . mysqli_error($link));
}
// --- End PHP for fetching admin roles ---
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All-En Admin - Create Account</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link rel="stylesheet" href="admin-style.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <style>    /* Modern Add Account Form Container - Matching modal-content-discount design */
    .add-account-form-container {
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

    /* Modal Header */
    .add-account-form-container h2 {
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

    .add-account-form-container h2::before {
      content: "manage_accounts";
      font-family: "Material Symbols Sharp";
      font-size: 1.6rem;
    }

    /* Modal Body */
    .add-account-form-container form {
      padding: 1.5rem 2rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      overflow-y: auto;
      flex: 1;
      scroll-behavior: smooth;
    }

    .add-account-form-container form::-webkit-scrollbar {
      width: 6px;
    }

    .add-account-form-container form::-webkit-scrollbar-track {
      background: var(--clr-light);
      border-radius: 3px;
    }

    .add-account-form-container form::-webkit-scrollbar-thumb {
      background: #8b94f0;
      border-radius: 3px;
    }

    .add-account-form-container form::-webkit-scrollbar-thumb:hover {
      background: rgb(86, 98, 231);
    }

    /* Form Styling */
    .add-account-form-container label {
      display: block;
      margin-bottom: 1rem;
      font-weight: 600;
      color: var(--clr-dark);
      position: relative;
    }

    .add-account-form-container input[type="text"],
    .add-account-form-container input[type="email"],
    .add-account-form-container input[type="password"],
    .add-account-form-container select {
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

    .add-account-form-container input[type="text"]:focus,
    .add-account-form-container input[type="email"]:focus,
    .add-account-form-container input[type="password"]:focus,
    .add-account-form-container select:focus {
      outline: none;
      border-color: var(--clr-primary);
      box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
      background: rgba(115, 128, 236, 0.02);
    }

    /* Modal Actions */
    .add-account-form-container .form-actions {
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

    .add-account-form-container button {
      padding: 0.8rem 2rem;
      border: none;
      border-radius: var(--border-radius-1);
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: inherit;
    }

    .add-account-form-container button[type="reset"] {
      background: var(--clr-color-background);
      color: var(--clr-dark-variant);
      border: 2px solid var(--clr-light);
    }

    .add-account-form-container button[type="reset"]:hover {
      background: var(--clr-light);
      transform: translateY(-1px);
    }

    .add-account-form-container button[type="submit"] {
      background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
    }

    .add-account-form-container button[type="submit"]:hover {
      background: linear-gradient(135deg, #6b7ce8 0%, #7c85ed 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(115, 128, 236, 0.4);
    }    /* Responsive */
    @media screen and (max-width: 768px) {
      .add-account-form-container {
        width: 95%;
        margin: 1rem auto 0;
      }

      .add-account-form-container h2 {
        padding: 1rem 1.5rem;
        font-size: 1.3rem;
      }

      .add-account-form-container form {
        padding: 1rem 1.5rem;
        grid-template-columns: 1fr;
      }

      .add-account-form-container .form-actions {
        padding: 1rem 1.5rem;
        flex-direction: column;
        grid-column: span 1;
      }

      .add-account-form-container button {
        width: 100%;
      }
    }    /* Enhanced Table Styling - Matching admin-review.php */
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

    /* Admin action buttons styling - Matching admin-review.php */
    .edit-button,
    .delete-button-confirmation {
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      text-align: center;
      padding: 0.8rem 1rem;
      border-radius: var(--border-radius-1);
    }

    .edit-button {
      color: var(--clr-primary);
      background: rgba(115, 128, 236, 0.1);
    }

    .edit-button:hover {
      background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
    }

    .delete-button-confirmation {
      color: var(--clr-danger);
      background: rgba(255, 119, 130, 0.1);
    }

    .delete-button-confirmation:hover {
      background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    .primary {
      color: var(--clr-primary);
      font-weight: 600;
      background: rgba(115, 128, 236, 0.1);
      border-radius: var(--border-radius-1);
      text-align: center;
    }

    /* Responsive Table Design */
    @media screen and (max-width: 768px) {
      table {
        min-width: 600px;
      }

      table thead th,
      table tbody td {
        padding: 0.8rem 1rem;
        font-size: 0.8rem;
      }

      .edit-button,
      .delete-button-confirmation {
        padding: 0.6rem 0.8rem;
        font-size: 0.8rem;
      }
    }    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }    /* Modern Modal Styling - Matching discountModal design from admin-discount.php */
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
      animation: fadeIn 0.3s ease-out;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .modal-overlay:not(.active) {
      display: none;
    }

    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
      display: flex;
    }

    /* Modal Content */
    .modal-content {
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
    .modal-content h2 {
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
      position: relative;
    }

    .modal-content h2::before {
      content: "edit";
      font-family: "Material Symbols Sharp";
      font-size: 1.6rem;
    }

    /* Close Button in Header */
    .modal-content .close-button {
      position: absolute;
      top: 50%;
      right: 1.5rem;
      transform: translateY(-50%);
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

    .modal-content .close-button:hover,
    .modal-content .close-button:focus {
      background: rgba(255, 255, 255, 0.3);
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-50%) scale(1.1);
      text-decoration: none;
    }

    /* Modal Body */
    .modal-content form {
      padding: 1.5rem 2rem;
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 1rem;
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
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      font-weight: 600;
      color: var(--clr-dark);
      font-size: 0.9rem;
    }

    .modal-content input[type="text"],
    .modal-content input[type="email"],
    .modal-content input[type="password"],
    .modal-content select {
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

    .modal-content input[type="text"]:focus,
    .modal-content input[type="email"]:focus,
    .modal-content input[type="password"]:focus,
    .modal-content select:focus {
      outline: none;
      border-color: var(--clr-primary);
      box-shadow: 0 0 0 3px rgba(115, 128, 236, 0.1);
      background: rgba(115, 128, 236, 0.02);
    }

    /* Modal Actions */
    .modal-content .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      padding: 1rem 2rem;
      background: var(--clr-color-background);
      border-top: 1px solid var(--clr-light);
      flex-shrink: 0;
    }

    .modal-content .form-actions button {
      padding: 0.8rem 2rem;
      border: none;
      border-radius: var(--border-radius-1);
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: inherit;
    }

    .modal-content .form-actions .cancel-button {
      background: var(--clr-color-background);
      color: var(--clr-dark-variant);
      border: 2px solid var(--clr-light);
    }

    .modal-content .form-actions .cancel-button:hover {
      background: var(--clr-light);
      transform: translateY(-1px);
    }

    .modal-content .form-actions .save-button {
      background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
    }

    .modal-content .form-actions .save-button:hover {
      background: linear-gradient(135deg, #6b7ce8 0%, #7c85ed 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(115, 128, 236, 0.4);
    }    /* Message/Confirmation Modal specific styles - Matching discountModal design */
    .message-modal-content {
      text-align: center;
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

    .message-modal-content h2 {
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
      text-align: center;
      justify-content: center;
    }

    .message-modal-content h2::before {
      content: "info";
      font-family: "Material Symbols Sharp";
      font-size: 1.6rem;
    }

    .message-modal-content .message-text {
      font-size: 1.1rem;
      margin: 0;
      padding: 1.5rem 2rem;
      color: var(--clr-dark);
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .message-modal-content .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      padding: 1rem 2rem;
      background: var(--clr-color-background);
      border-top: 1px solid var(--clr-light);
      flex-shrink: 0;
    }

    .message-modal-content button {
      min-width: 100px;
      padding: 0.8rem 2rem;
      border: none;
      border-radius: var(--border-radius-1);
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: inherit;
    }

    .message-modal-content .cancel-button {
      background: var(--clr-color-background);
      color: var(--clr-dark-variant);
      border: 2px solid var(--clr-light);
    }

    .message-modal-content .cancel-button:hover {
      background: var(--clr-light);
      transform: translateY(-1px);
    }

    .message-modal-content .save-button {
      background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
    }

    .message-modal-content .save-button:hover {
      background: linear-gradient(135deg, #6b7ce8 0%, #7c85ed 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(115, 128, 236, 0.4);
    }

    /* Responsive Modal Design */
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
      }

      .modal-content .form-actions {
        padding: 1rem 1.5rem;
        flex-direction: column;
      }

      .modal-content .form-actions button {
        width: 100%;
      }

      .message-modal-content {
        width: 95%;
        margin: 1rem;
      }

      .message-modal-content h2 {
        padding: 1rem 1.5rem;
        font-size: 1.3rem;
      }

      .message-modal-content .message-text {
        padding: 1rem 1.5rem;
      }

      .message-modal-content .modal-buttons {
        padding: 1rem 1.5rem;
        flex-direction: column;
      }

      .message-modal-content button {
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
        <a href="admin-create-account.php" class="active" <?php if ($current_user_role_id == 2) echo 'style="display:none;"'; ?>>
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
      <h1>Admin Account Management</h1>

      <div class="add-account-form-container">
        <h2>Add New Admin Account</h2>
        <form id="addAccountForm">
          <label>
            Email:
            <input type="email" name="email" required />
          </label>
          <label>
            Password:
            <input type="password" name="password" required />
          </label>
          <label>
            Name:
            <input type="text" name="name" required />
          </label>
          <label>
            Role:
            <select name="role_id" required>
              <?php foreach ($admin_roles as $role) : ?>
                <option value="<?php echo htmlspecialchars($role['id']); ?>">
                  <?php echo htmlspecialchars($role['role_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="form-actions">
            <button type="reset">Reset</button>
            <button type="submit">Add Account</button>
          </div>
        </form>      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Name</th>
            <th>Role</th>
            <th>Edit</th>
            <th>Delete</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if (mysqli_num_rows($result_admins) > 0) {
            while ($row = mysqli_fetch_assoc($result_admins)) {
              $role_name = 'Unknown';
              foreach ($admin_roles as $role) {
                if ($role['id'] == $row['role_id']) {
                  $role_name = $role['role_name'];
                  break;
                }
              }
          ?>
              <tr data-admin-id="<?php echo htmlspecialchars($row['id']); ?>" data-admin-role-id="<?php echo htmlspecialchars($row['role_id']); ?>">
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                <td><?php echo htmlspecialchars($row['Name']); ?></td>
                <td class="primary"><?php echo htmlspecialchars($role_name); ?></td>
                <td class="edit-button" data-admin-id="<?php echo htmlspecialchars($row['id']); ?>">
                  <span>Edit</span>
                </td>
                <td class="delete-button-confirmation" data-admin-id="<?php echo htmlspecialchars($row['id']); ?>">
                  <span>Delete</span>
                </td>
              </tr>
          <?php
            }
          } else {
            echo '<tr><td colspan="6">No admin accounts found.</td></tr>';
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
                  $current_user_role_name = 'Guest';
                  foreach ($admin_roles as $role) {
                    if ($role['id'] == $roleId) {
                      $current_user_role_name = $role['role_name'];
                      break;
                    }
                  }
                  echo $current_user_role_name;
                } else {
                  echo "Guest";
                } ?></p>
            <small class="text-muted"></small>
          </div>
        </div>
      </div>

    </div>
  </div>

  <div class="modal-overlay" id="editAccountModal">
    <div class="modal-content">
      <button class="close-button">&times;</button>
      <h2>Edit Admin Account</h2>
      <form id="editAccountForm">
        <input type="hidden" name="admin_id" id="editAdminId">
        <label>
          Email:
          <input type="email" name="email" id="editEmail" required />
        </label>
        <label>
          Name:
          <input type="text" name="name" id="editName" required />
        </label>
        <label>
          Password (leave blank to keep current):
          <input type="password" name="password" id="editPassword" />
        </label>
        <label>
          Role:
          <select name="role_id" id="editRoleId" required>
            <?php foreach ($admin_roles as $role) : ?>
              <option value="<?php echo htmlspecialchars($role['id']); ?>">
                <?php echo htmlspecialchars($role['role_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="form-actions">
          <button type="button" class="cancel-button">Cancel</button>
          <button type="submit" class="save-button">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="messageModal">
    <div class="modal-content message-modal-content">
      <h2 id="messageModalTitle"></h2>
      <p class="message-text" id="messageModalText"></p>
      <div class="modal-buttons" id="messageModalButtons">
      </div>
    </div>
  </div>


  <script src="admin-dashboard.js"></script>
  <script>
    // Custom Modal functions (replacing alert and confirm)
    function showMessageModal(title, message, type = 'alert', callback = null) {
      const modal = $('#messageModal');
      $('#messageModalTitle').text(title);
      $('#messageModalText').text(message);
      const buttonsContainer = $('#messageModalButtons');
      buttonsContainer.empty(); // Clear existing buttons

      if (type === 'alert') {
        buttonsContainer.append('<button class="save-button" id="messageModalOk">OK</button>');
        $('#messageModalOk').off().on('click', function() {
          modal.removeClass('active');
          if (callback) callback(true);
        });
      } else if (type === 'confirm') {
        buttonsContainer.append('<button class="cancel-button" id="messageModalCancel">Cancel</button>');
        buttonsContainer.append('<button class="save-button" id="messageModalConfirm">Confirm</button>');

        $('#messageModalCancel').off().on('click', function() {
          modal.removeClass('active');
          if (callback) callback(false);
        });
        $('#messageModalConfirm').off().on('click', function() {
          modal.removeClass('active');
          if (callback) callback(true);
        });
      }
      modal.addClass('active');
    }

    // Function to hide any active modal
    function hideAllModals() {
      $('.modal-overlay').removeClass('active');
    }


    $(document).ready(function() {
      // Close button for all modals
      $('.modal-overlay .close-button, .modal-overlay .cancel-button').on('click', function() {
        hideAllModals();
      });

      // Close modal on outside click
      $('.modal-overlay').on('click', function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
          hideAllModals();
        }
      });


      // --- AJAX: Add Admin Account Form Submission ---
      document.getElementById('addAccountForm').addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.action = 'add_account'; // Add action to identify request on server

        // Basic validation (can be enhanced)
        if (!data.email || !data.password || !data.name || !data.role_id) {
          showMessageModal('Validation Error', 'Please fill in all fields.', 'alert');
          return;
        }

        $.ajax({
          url: 'admin-create-account.php', // Send to the same file
          method: 'POST',
          data: data,
          dataType: 'json',
          success: function(response) {
            if (response.status === 'success') {
              showMessageModal('Success', response.message, 'alert', function() {
                e.target.reset(); // Reset form
                location.reload(); // Reload page to show new account
              });
            } else {
              showMessageModal('Error', 'Error: ' + response.message, 'alert');
            }
          },
          error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error, xhr.responseText);
            showMessageModal('Network Error', 'An error occurred while adding the admin account.', 'alert');
          }
        });
      });

      // --- AJAX: Delete Single Admin Account ---
      $('table').on('click', '.delete-button-confirmation', function() {
        // Correctly get adminId from the clicked <td>
        const $clickedCell = $(this);
        const adminId = $clickedCell.data('admin-id');

        showMessageModal('Confirm Deletion', 'Are you sure you want to DELETE this admin account (ID: ' + adminId + ')? This action cannot be undone!', 'confirm', function(confirmed) {
          if (confirmed) {
            $.ajax({
              url: 'admin-create-account.php',
              method: 'POST',
              data: {
                action: 'delete_account',
                admin_id: adminId
              },
              dataType: 'json',
              success: function(response) {
                if (response.status === 'success') {
                  showMessageModal('Success', response.message, 'alert', function() {
                    // Find the row using the data-admin-id attribute and remove it
                    $('tr[data-admin-id="' + adminId + '"]').fadeOut(300, function() {
                      $(this).remove();
                      // If no rows left, display "No accounts found"
                      if ($('tbody tr').length === 0) {
                        $('tbody').append('<tr><td colspan="5">No admin accounts found.</td></tr>');
                      }
                    });
                  });
                } else {
                  showMessageModal('Error', 'Error: ' + response.message, 'alert');
                }
              },
              error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                showMessageModal('Network Error', 'An error occurred while deleting the admin account.', 'alert');
              }
            });
          }
        });
      });

      // --- AJAX: Fetch Admin Details for Edit Modal ---
      $('table').on('click', '.edit-button', function() {
        // Correctly get adminId from the clicked <td>
        const adminId = $(this).data('admin-id');
        const editModal = $('#editAccountModal');

        $.ajax({
          url: 'admin-create-account.php',
          method: 'POST',
          data: {
            action: 'fetch_admin_details',
            admin_id: adminId
          },
          dataType: 'json',
          success: function(response) {
            if (response.status === 'success') {
              // Populate the form fields
              $('#editAdminId').val(response.data.id);
              $('#editEmail').val(response.data.Email);
              $('#editName').val(response.data.Name);
              $('#editRoleId').val(response.data.role_id);
              $('#editPassword').val(''); // Clear password field for security

              editModal.addClass('active'); // Show the modal
            } else {
              showMessageModal('Error', 'Error fetching admin details: ' + response.message, 'alert');
            }
          },
          error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error, xhr.responseText);
            showMessageModal('Network Error', 'An error occurred while fetching admin details.', 'alert');
          }
        });
      });

      // --- AJAX: Update Admin Account Form Submission ---
      document.getElementById('editAccountForm').addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.action = 'update_account'; // Add action

        // Basic validation
        if (!data.admin_id || !data.email || !data.name || !data.role_id) {
          showMessageModal('Validation Error', 'Please fill in all required fields.', 'alert');
          return;
        }

        $.ajax({
          url: 'admin-create-account.php',
          method: 'POST',
          data: data,
          dataType: 'json',
          success: function(response) {
            if (response.status === 'success') {
              showMessageModal('Success', response.message, 'alert', function() {
                hideAllModals();
                // Refresh the table row dynamically if possible, or reload page
                location.reload(); // Simple reload for now
              });
            } else {
              showMessageModal('Error', 'Error updating admin account: ' + response.message, 'alert');
            }
          },
          error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error, xhr.responseText);
            showMessageModal('Network Error', 'An error occurred while updating the admin account.', 'alert');
          }
        });
      });


      // Original logo click functionality
      document.addEventListener('DOMContentLoaded', function() {
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
    });
  </script>
</body>

</html>