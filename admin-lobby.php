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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All-En Admin - Delivery</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link rel="stylesheet" href="admin-style.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <style>
    /* Modern Admin Lobby Styling */
    .main-logo {
      text-align: center;
      margin-bottom: 2rem;
      padding: 2rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(10px);
      animation: fadeIn 0.6s ease-out;
    }

    .main-logo::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
      pointer-events: none;
    }

    .main-logo h2 {
      font-size: 3rem;
      font-weight: 700;
      color: white;
      margin: 0;
      text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      position: relative;
      z-index: 1;
    }

    .main-logo .danger {
      color: rgb(255, 255, 255);
      filter: drop-shadow(0 0 10px rgba(109, 99, 250, 0.3));
    }

    .main-nav-buttons {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
      padding: 0 1rem;
    }

    .main-nav-buttons a {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 2rem 1.5rem;
      border-radius: 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .main-nav-buttons a::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    .main-nav-buttons a:hover {
      transform: translateY(-2px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .main-nav-buttons a:hover::before {
      opacity: 1;
    }

    .main-nav-buttons a .material-symbols-sharp {
      font-size: 3rem;
      margin-bottom: 1rem;
      filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
      transition: transform 0.3s ease;
    }

    .main-nav-buttons a:hover .material-symbols-sharp {
      transform: scale(1.1);
    }

    .main-nav-buttons a h3 {
      font-size: 1.2rem;
      font-weight: 600;
      margin: 0;
      text-align: center;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .main-nav-buttons a .msg_count {
      background: linear-gradient(45deg, #ff6b6b, #ee5a6f);
      color: white;
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-top: 0.5rem;
      box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
      border: 2px solid rgba(255, 255, 255, 0.2);
      animation: pulse 2s infinite;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.05);
      }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .main-logo {
        margin: 1rem;
        padding: 1.5rem;
      }

      .main-logo h2 {
        font-size: 2.5rem;
      }

      .main-nav-buttons {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        padding: 0 0.5rem;
      }

      .main-nav-buttons a {
        padding: 1.5rem 1rem;
      }

      .main-nav-buttons a .material-symbols-sharp {
        font-size: 2.5rem;
      }

      .main-nav-buttons a h3 {
        font-size: 1.1rem;
      }
    }

    /* Additional styling for main section */
    main {
      animation: fadeIn 0.8s ease-out;
    }

    main h1 {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 700;
      margin-bottom: 2rem;
      text-align: center;
      font-size: 2.5rem;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }
  </style>
</head>

<body>
  <div class="container">
    <aside>
      <div class="top">
        <div class="logo">
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
      <h1>Admin-Backend</h1>

      <div class="main-logo">
        <h2> <span class="danger">All-En</span> </h2>
      </div>

      <div class="main-nav-buttons">
        <a href="admin-dashboard.php" <?php if ($current_user_role_id == 2) echo 'style="display:none;"'; ?>>
          <span class="material-symbols-sharp">grid_view </span>
          <h3>Dashboard</h3>
        </a>
        <a href="admin-payment.php"> <span class="material-symbols-sharp">credit_card </span>
          <h3>Payment</h3>
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
        <a href="admin-review.php"> <span class="material-symbols-sharp">star_rate </span>
          <h3>Review</h3>
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
        <a href="?logout=true">
          <span class="material-symbols-sharp">logout </span>
          <h3>Log out</h3>
        </a>
      </div>

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
  </div>

  <script src="admin-dashboard.js"></script>
</body>

</html>