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
  <title>UI/UX</title>
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
  <link rel="stylesheet" href="admin-style.css">
  <Style>
    /* Modern Admin Dashboard Styling */

    /* Main Dashboard Title Enhancement */
    main h1 {
      background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 700;
      margin-bottom: 2rem;
      text-align: center;
      font-size: 2.8rem;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
      animation: fadeIn 0.8s ease-out;
    }

    /* Section Headers Enhancement */
    .recent_order h2 {
      color: var(--clr-dark);
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      padding: 1rem 0;
      position: relative;
      text-align: center;
    }

    .recent_order h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 3px;
      background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
      border-radius: 2px;
    }

    /* Enhanced Insights Cards */
    main .insights>div {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(115, 128, 236, 0.1);
      border-radius: 16px;
      padding: 1.8rem;
      margin-top: 1rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    main .insights>div::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(115, 128, 236, 0.02), rgba(139, 148, 240, 0.02));
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    main .insights>div:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
      border-color: rgba(115, 128, 236, 0.2);
    }

    main .insights>div:hover::before {
      opacity: 1;
    }

    /* Enhanced Icon Styling */
    main .insights>div span {
      background: linear-gradient(135deg, coral 0%, #ff7f7f 100%);
      padding: 0.8rem;
      border-radius: 12px;
      color: var(--clr-white);
      font-size: 2.2rem;
      box-shadow: 0 4px 15px rgba(255, 127, 80, 0.3);
      transition: transform 0.3s ease;
    }

    main .insights>div.expenses span {
      background: linear-gradient(135deg, var(--clr-danger) 0%, #dc3545 100%);
      box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    main .insights>div.income span {
      background: linear-gradient(135deg, var(--clr-success) 0%, #28a745 100%);
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    main .insights>div:hover span {
      transform: scale(1.1);
    }

    /* Enhanced Typography */
    main .insights>div .middle h1 {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--clr-dark);
      margin: 0.5rem 0;
    }

    main .insights>div .middle h3 {
      font-size: 1rem;
      font-weight: 600;
      color: var(--clr-dark);
      line-height: 1.4;
      margin-bottom: 0.5rem;
    }

    main .insights>div small {
      color: var(--clr-info-dark);
      font-weight: 500;
      font-size: 0.85rem;
      margin-top: 0.5rem;
      display: block;
    }

    /* Chart Canvas Enhancement */
    canvas {
      border-radius: 8px;
      margin-top: 0.5rem;
    }

    /* Animation for page load */
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

    /* Staggered animation for insights cards */
    main .insights>div:nth-child(1) {
      animation: fadeIn 0.6s ease-out 0.1s both;
    }

    main .insights>div:nth-child(2) {
      animation: fadeIn 0.6s ease-out 0.2s both;
    }

    main .insights>div:nth-child(3) {
      animation: fadeIn 0.6s ease-out 0.3s both;
    }

    main .insights>div:nth-child(4) {
      animation: fadeIn 0.6s ease-out 0.4s both;
    }

    /* Enhanced sidebar logo clickability indicator */
    #logo-href {
      cursor: pointer;
      transition: transform 0.3s ease;
    }

    #logo-href:hover {
      transform: scale(1.05);
    }

    /* Enhanced active navigation state */


    /* Modern scrollbar for page */
    body::-webkit-scrollbar {
      width: 8px;
    }

    body::-webkit-scrollbar-track {
      background: var(--clr-light);
      border-radius: 4px;
    }

    body::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, var(--clr-primary) 0%, #8b94f0 100%);
      border-radius: 4px;
    }

    body::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #5f6dc2 0%, #7380ec 100%);
    }

    /* Responsive design improvements */
    @media screen and (max-width: 768px) {
      main h1 {
        font-size: 2.2rem;
      }

      .recent_order h2 {
        font-size: 1.5rem;
      }

      main .insights>div {
        padding: 1.5rem;
      }

      main .insights>div .middle h1 {
        font-size: 1.5rem;
      }

      main .insights>div span {
        font-size: 2rem;
        padding: 0.7rem;
      }
    }

    @media screen and (max-width: 480px) {
      main h1 {
        font-size: 2rem;
      }

      main .insights>div {
        padding: 1.2rem;
      }

      main .insights>div .middle h1 {
        font-size: 1.3rem;
      }

      main .insights>div .middle h3 {
        font-size: 0.9rem;
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
      <!-- end top -->
      <div class="sidebar">

        <a href="admin-dashboard.php" class="active" <?php if ($current_user_role_id == 2) echo 'style="display:none;"'; ?>>
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
        </a> <a href="admin-refund.php"> <span class=" material-symbols-sharp">money_off </span>
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
  --------------- -->

    <main>
      <h1>Dashboard</h1>

      <!-- start seling --------------------------------------------------------------------------------------------------------------->

      <!-- SYSTEM MANAGEMENT -->
      <div class="recent_order">
        <h2>System Management</h2>
      </div>

      <div class="insights">
        <!-- Admin Account Management Report -->
        <div class="sales">
          <span class="material-symbols-sharp">manage_accounts</span>
          <div class="middle">
            <div class="left">
              <h3>Admin Account Management Report</h3>
              <h1 id="admin-account-count"></h1>
              <div id="admin-account-active" style="font-size: 1.2em; color: #4e73df;"></div>
            </div>
          </div>
          <small>Current</small>
        </div>
        <!-- Discount Code Management Summary Report -->
        <div class="sales">
          <span class="material-symbols-sharp">percent</span>
          <div class="middle">
            <div class="left">
              <h3>Discount Code Management Summary Report</h3>
              <h1 id="discount-summary-count"></h1>
            </div>
          </div>
          <small>Available</small>
        </div>
      </div>

      <!-- SALES ANALYSIS -->
      <div class="recent_order">
        <h2>Sales Analysis</h2>
      </div>

      <div class="insights">
        <!-- Sales Report -->
        <div class="sales">
          <span class="material-symbols-sharp">trending_up</span>
          <div class="middle">
            <div class="left">
              <h3>Sales Report</h3>
              <h1 id="sales-report-total"></h1>
            </div>
          </div>
          <small>All Time</small>
        </div>
        <!-- Average Order Value Report -->
        <div class="expenses">
          <span class="material-symbols-sharp">local_mall</span>
          <div class="middle">
            <div class="left">
              <h3>Average Order Value Report</h3>
              <h1 id="avg-order-value"></h1>
            </div>
          </div>
          <small>All Time</small>
        </div>
        <!-- Sales by Product Category Report -->
        <div class="income">
          <span class="material-symbols-sharp">stacked_line_chart</span>
          <div class="middle">
            <div class="left">
              <h3>Sales by Product Category Report</h3>
              <h1 id="category-sales"></h1>
              <h1 id="category-sales-chart"></h1>
            </div>
          </div>
          <small>All Time</small>
        </div>
      </div>

      <!-- Customer Analysis -->
      <div class="recent_order">
        <h2>Customer Analysis</h2>
      </div>

      <div class="insights">
        <!-- Member Account Activity Report -->
        <div class="sales">
          <span class="material-symbols-sharp">account_circle</span>
          <div class="middle">
            <div class="left">
              <h3>Member Account Activity Report</h3>
              <h1 id="member-activity-count"></h1>
            </div>
          </div>
          <small>Inactive/Active/Suspended/Banned</small>
        </div>
        <!-- Customer Lifetime Value Report -->
        <div class="expenses">
          <span class="material-symbols-sharp">monetization_on</span>
          <div class="middle">
            <div class="left">
              <h3>Customer Lifetime Value Report</h3>
              <h1 id="customer-ltv"></h1>
            </div>
          </div>
          <small>Customer(Total Spent)</small>
        </div>
        <!-- Customer Refund Summary Report -->
        <div class="income">
          <span class="material-symbols-sharp">money_off</span>
          <div class="middle">
            <div class="left">
              <h3>Customer Refund Summary Report</h3>
              <h1 id="refund-summary"></h1>
            </div>
          </div>
          <small>Requested/Approved/Rejected/Processed/Completed/Cancelled</small>
        </div>
        <!-- Product Warranty Claim Report -->
        <div class="sales">
          <span class="material-symbols-sharp">shield</span>
          <div class="middle">
            <div class="left">
              <h3>Product Warranty Claim Report</h3>
              <h1 id="warranty-claim"></h1>
            </div>
          </div>
          <small>Available/Expired</small>
        </div>
        <!-- Customer Location (City) Analysis Report -->
        <div class="expenses">
          <span class="material-symbols-sharp">location_city</span>
          <div class="middle">
            <div class="left">
              <h3>Customer Location (City) Analysis Report</h3>
              <h1 id="city-analysis"></h1>
            </div>
          </div>
          <small>Top City by Member Count (Number of Members)</small>
        </div>
        <!-- Customer Rating & Review Trends Report -->
        <div class="income">
          <span class="material-symbols-sharp">star_rate</span>
          <div class="middle">
            <div class="left">
              <h3>Customer Rating & Review Trends Report</h3>
              <h1 id="review-trends"></h1>
              <canvas id="review-trends-chart" width="200" height="120"></canvas>
            </div>
          </div>
          <small>All Time</small>
        </div>
      </div>

      <!-- Payment Analysis -->
      <div class="recent_order">
        <h2>Payment Analysis</h2>
      </div>

      <div class="insights">
        <!-- Payment Method Distribution Report -->
        <div class="sales">
          <span class="material-symbols-sharp">credit_card</span>
          <div class="middle">
            <div class="left">
              <h3>Payment Method Distribution Report</h3>
              <canvas id="payment-method-distribution" height=120px width=200px></canvas>
            </div>
          </div>
          <small>All Time</small>
        </div>
        <!-- Transaction Success Rate Report -->
        <div class="expenses">
          <span class="material-symbols-sharp">check_circle</span>
          <div class="middle">
            <div class="left">
              <h3>Transaction Success Rate Report</h3>
              <h1 id="transaction-success"></h1>
            </div>
          </div>
          <small>Successed / Pending / Cancelled (%)</small>
        </div>
        <!-- Discount Code Usage & Impact Report -->
        <div class="income">
          <span class="material-symbols-sharp">percent</span>
          <div class="middle">
            <div class="left">
              <h3>Discount Code Usage & Impact Report</h3>
              <h1 id="discount-usage-impact"></h1>
            </div>
          </div>
          <small>Usage Rate / Total Discount Amount</small>
        </div>
      </div>

      <div class="recent_order">
        <h2>Product Analysis</h2>
      </div>

      <div class="insights">
        <!-- Product Popularity Report -->
        <div class="sales">
          <span class="material-symbols-sharp">trending_up</span>
          <div class="middle">
            <div class="left">
              <h3>Product Popularity Report</h3>
              <h1 id="product-popularity"></h1>
            </div>
          </div>
          <small>Product Name/Total Sold</small>
        </div>
        <!-- Highest Profit Product Report -->
        <div class="income">
          <span class="material-symbols-sharp">stacked_line_chart</span>
          <div class="middle">
            <div class="left">
              <h3>Highest Profit Product Report</h3>
              <h1 id="highest-profit"></h1>
            </div>
          </div>
          <small>Product/Total Profit/Total Products Sold</small>
        </div>
        <!-- Lowest Profit Product Report -->
        <div class="sales">
          <span class="material-symbols-sharp">trending_down</span>
          <div class="middle">
            <div class="left">
              <h3>Lowest Profit Product Report</h3>
              <h1 id="lowest-profit"></h1>
            </div>
          </div>
          <small>Product/Total Profit/Total Products Sold</small>
        </div>
        <!-- Product Stock & Availability Report -->
        <div class="expenses">
          <span class="material-symbols-sharp">inventory_2</span>
          <div class="middle">
            <div class="left">
              <h3>Product Stock & Availability Report</h3>
              <h1 id="product-stock"></h1>
            </div>
          </div>
          <small>Available Stock: Luggage / Luggage Accessories / Travel Accessories</small>
        </div>
      </div>

      <div class="recent_order">
        <h2>Order Analysis</h2>
      </div>

      <div class="insights">
        <!-- Customer Order Management Report -->
        <div class="sales">
          <span class="material-symbols-sharp">assignment</span>
          <div class="middle">
            <div class="left">
              <h3>Customer Order Management Report</h3>
              <h1 id="order-management"></h1>
            </div>
          </div>
          <small>All Time</small>
        </div>
        <!-- Shipment & Tracking Status Report -->
        <div class="expenses">
          <span class="material-symbols-sharp">local_shipping</span>
          <div class="middle">
            <div class="left">
              <h3>Shipment & Tracking Status Report</h3>
              <h1 id="shipment-tracking"></h1>
            </div>
          </div>
          <small>Pending/Processing/Out for Delivery/Delivered</small>
        </div>
      </div>

      <!--
     ADMIN ANALYSIS
     -->

      <div class="recent_order">
        <h2>Admin Analysis</h2>
      </div>

      <div class="insights">
        <!-- Admin Activity & Performance Report -->
        <div class="sales">
          <span class="material-symbols-sharp">trending_up</span>
          <div class="middle">
            <div class="left">
              <h3>Admin Activity & Performance Report</h3>
              <h1 id="admin-activity"></h1>
            </div>
          </div>
          <small>All Time</small>
        </div>
      </div>

      <!-- end insights --------------------------------------------------------------------------------------------------------------------------------------------->

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
      <!-- 
      <div class="recent_updates">
        <h2>Recent Update</h2>
        <div class="updates">
          <div class="update">
            <div class="profile-photo">
              <img src="images/profile-4.jpg" alt="" />
            </div>
            <div class="message">
              <p><b>Babar</b> Recived his order of USB</p>
            </div>
          </div>
          <div class="update">
            <div class="profile-photo">
              <img src="images/profile-3.jpg" alt="" />
            </div>
            <div class="message">
              <p><b>Ali</b> Recived his order of USB</p>
            </div>
          </div>
          <div class="update">
            <div class="profile-photo">
              <img src="images/profile-2.jpg" alt="" />
            </div>
            <div class="message">
              <p><b>Ramzan</b> Recived his order of USB</p>
            </div>
          </div>
        </div>
      </div> -->



    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      // 定義每個分析區塊對應的 PHP 檔案與元素ID
      const analysisConfigs = [{
          url: 'admin-account-report.php',
          id: 'admin-account-count'
        },
        {
          url: 'product-popularity-report.php',
          id: 'product-popularity'
        },
        {
          url: 'customer-refund-summary-report.php',
          id: 'refund-summary'
        },
        {
          url: 'review-trends-report.php',
          id: 'review-trends'
        },
        {
          url: 'order.php',
          id: 'avg-order-value'
        },
        {
          url: 'sales-report.php',
          id: 'sales-report-total'
        },
        {
          url: 'order-management-report.php',
          id: 'order-management'
        },
        {
          url: 'customer-ltv-report.php',
          id: 'customer-ltv'
        },
        {
          url: 'member-activity-report.php',
          id: 'member-activity-count'
        },
        {
          url: 'shipment-tracking-report.php',
          id: 'shipment-tracking'
        },
        {
          url: 'city-analysis-report.php',
          id: 'city-analysis'
        },
        {
          url: 'warranty-claim-report.php',
          id: 'warranty-claim'
        },
        {
          url: 'discount-code-report.php',
          id: 'discount-summary-count'
        },
        {
          url: 'lowest-profit-product-report.php',
          id: 'lowest-profit'
        },
        {
          url: 'highest-profit-product-report.php',
          id: 'highest-profit'
        },
        {
          url: 'category-sales-report.php',
          id: 'category-sales'
        },
        {
          url: 'payment-method-distribution-report.php',
          id: 'payment-method-distribution'
        },
        {
          url: 'transaction-success-rate-report.php',
          id: 'transaction-success'
        },
        {
          url: 'discount-code-usage-impact-report.php',
          id: 'discount-usage-impact'
        },
        {
          url: 'product-stock-report.php',
          id: 'product-stock'
        },
        // 你可以依需求繼續擴充
      ];

      // 依序抓取資料並更新畫面
      analysisConfigs.forEach(cfg => {
        if (cfg.id === 'discount-summary-count') return; // 避免覆蓋 Discount Code Management Summary Report
        fetch(cfg.url)
          .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
          })
          .then(data => {
            const el = document.getElementById(cfg.id);
            if (el) {
              // 特別處理 Transaction Success Rate Report
              if (cfg.id === 'transaction-success' && data && typeof data.successed !== 'undefined' && typeof data.pending !== 'undefined' && typeof data.cancelled !== 'undefined') {
                el.textContent = `${data.successed} / ${data.pending} / ${data.cancelled}`;
                // 特別處理 Discount Code Usage & Impact Report
              } else if (cfg.id === 'discount-usage-impact' && data && typeof data.usage_rate !== 'undefined' && typeof data.total_discount_amount !== 'undefined') {
                el.textContent = `${data.usage_rate}% / $${data.total_discount_amount}`;
                // 特別處理 Product Stock & Availability Report
              } else if (cfg.id === 'product-stock' && data && typeof data['Luggage'] !== 'undefined' && typeof data['Luggage Accessories'] !== 'undefined' && typeof data['Travel Accessories'] !== 'undefined') {
                el.textContent = `${data['Luggage']} / ${data['Luggage Accessories']} / ${data['Travel Accessories']}`;
                // 不顯示 Sales by Product Category Report 數值
              } else if (cfg.id === 'category-sales') {
                el.textContent = '';
              } else if (data && data.value !== undefined && data.value !== null && data.value !== '') {
                // Customer Refund Summary Report 顯示總訂單數與金額
                if (cfg.id === 'refund-summary' && data.labels && data.values) {
                  el.textContent = data.values.join('/');
                } else {
                  if (cfg.id === 'member-activity-count' && typeof data.value === 'string') {
                    const match = data.value.match(/^\((\d+)\) \(\$(\d+)\)$/);
                    if (match) {
                      el.textContent = `${match[1]} (${match[2]} Orders)`;
                    } else {
                      el.textContent = data.value;
                    }
                  } else {
                    el.textContent = data.value;
                  }
                  if (cfg.id === 'admin-account-count') {
                    el.textContent = `${data.value} Active Admin${data.value === 1 ? '' : 's'}`;
                  }
                  if (cfg.id === 'admin-account-count') {
                    const activityEl = document.getElementById('admin-activity');
                    if (activityEl) {
                      if (data.value <= 1) activityEl.textContent = 'Bad';
                      else if (data.value <= 4) activityEl.textContent = 'Fine';
                      else activityEl.textContent = 'Great';
                    }
                  }
                }
              } else {
                // 只有非 category-sales 才顯示 -
                if (cfg.id !== 'category-sales') el.textContent = '-';
              }
            }
          })
          .catch(err => {
            const el = document.getElementById(cfg.id);
            // 只有非 category-sales 才顯示 -
            if (el && cfg.id !== 'category-sales') el.textContent = '-';
            // 直接顯示錯誤訊息於 console
            console.warn('資料載入失敗:', cfg.url, err);
          });
      });

      // Payment Method Distribution Pie Chart
      let chartInstance;

      fetch('payment-method-distribution-report.php')
        .then(res => res.json())
        .then(data => {
          const canvas = document.getElementById('payment-method-distribution');
          if (data && data.labels && data.values) {
            new Chart(canvas, {
              type: 'pie',
              data: {
                labels: data.labels,
                datasets: [{
                  data: data.values,
                  backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'
                  ]
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                  legend: {
                    position: 'right',
                    align: 'start',
                    labels: {
                      align: 'start'
                    }
                  }
                }
              }
            });
          }
        })
        .catch(err => {
          console.warn('Chart load failed:', err);
        });


      // Category Sales Pie Chart
      fetch('category-sales-report.php')
        .then(res => res.json())
        .then(data => {
          const el = document.getElementById('category-sales');
          if (data && data.labels && data.values && data.labels.length > 0 && data.values.length > 0) {
            const ctx = document.createElement('canvas');
            ctx.width = 120;
            ctx.height = 200;
            el.innerHTML = '';
            el.appendChild(ctx);
            new Chart(ctx, {
              type: 'pie',
              data: {
                labels: data.labels,
                datasets: [{
                  data: data.values,
                  backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'
                  ],
                }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: {
                    display: true,
                    position: 'right',
                    align: 'start', // 讓圖例靠左
                    labels: {
                      align: 'start' // 讓每個 label 靠左
                    }
                  }
                }
              }
            });
            // 若有 topCategory/topPercent 需求可在此顯示
          } else if (el) {
            el.textContent = '';
          }
        })
        .catch(err => {
          const el = document.getElementById('category-sales');
          if (el) el.textContent = '';
          console.warn('Category sales chart 載入失敗:', err);
        });



      // Admin Activity & Performance Report 狀態文字顯示
      fetch('admin-account-report.php')
        .then(res => res.json())
        .then(data => {
          if (data && data.value !== undefined) {
            const el = document.getElementById('admin-activity');
            if (el) {
              const count = Number(data.value);
              if (count <= 1) {
                el.textContent = 'Bad';
              } else if (count <= 4) {
                el.textContent = 'Fine';
              } else {
                el.textContent = 'Great';
              }
            }
          }
        })
        .catch(err => {
          console.warn('Admin activity 狀態載入失敗:', err);
        });

      // Fetch and update Discount Code Management Summary Report
      window.addEventListener('DOMContentLoaded', function() {
        fetch('discount-code-report.php')
          .then(response => response.json())
          .then(data => {
            console.log('Discount count response:', data); // Debugging log
            if (data.value || data.value === 0) {
              const discountSummaryElement = document.getElementById('discount-summary-count');
              if (discountSummaryElement) {
                discountSummaryElement.textContent = data.value;
                console.log('Set discount-summary-count:', data.value);
              }
            } else {
              console.warn('Failed to fetch discount count:', data.message);
            }
          })
          .catch(error => {
            console.error('Error fetching discount count:', error);
          });
      });

      // Review Trends Bar Chart
      fetch('review-trends-report.php')
        .then(res => res.json())
        .then(data => {
          if (data && data.labels && data.values) {
            const ctx = document.getElementById('review-trends-chart').getContext('2d');
            new Chart(ctx, {
              type: 'bar',
              data: {
                labels: data.labels,
                datasets: [{
                  label: 'Rating Count',
                  data: data.values,
                  backgroundColor: '#4e73df'
                }]
              },
              options: {
                responsive: false,
                plugins: {
                  legend: {
                    display: false
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true
                  }
                }
              }
            });
          }
        });
    </script>
</body>

</html>