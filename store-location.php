<?php
require_once "connection.php";
session_start();
//jika logout di klik
if (isset($_POST['logout'])) {
  session_unset();
  session_destroy();
  header("Location: index.php");
  exit();
}
?>

<?php
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  // Jika user belum login, langsung anggap keranjang kosong
  $hasItems = false;
} else {
  // Cek apakah ada item di cart untuk user ini
  $query = "SELECT COUNT(*) AS cnt FROM cart WHERE customer_id = ?";
  $stmt = mysqli_prepare($link, $query);
  mysqli_stmt_bind_param($stmt, "i", $user_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  $hasItems = ($row['cnt'] > 0);
  mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Store Locations - All-En</title>
  <link rel="stylesheet" href="store-location.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
  <body>
    <!-- Announcement Bar -->
    <!-- Header -->
    <?php if (!isset($_SESSION['is_login'])): ?>
      <div class="announcement-bar">
        <p class="announcement-text">
          New here? Sign up and start your travel journey with us.
          <span style="text-decoration: underline; cursor: pointer">Sign Up Now!</span>
        </p>
        <img src="images/X.png" alt="Close" class="close-btn" />
      </div>
    <?php endif; ?>

    <header class="header" id="mainHeader">
      <div class="logo">All-En</div>

      <nav class="nav">
        <div class="dropdown">
          <a href="#" class="nav-item">Shop <img src="images/shop-dropdown.png" alt="Dropdown" /></a>
          <div class="dropdown-menu">
            <a href="luggage-category.php">
              <h3>Luggage</h3>
            </a>

            <a href="luggage-accessory-category.php">
              <h3>Luggage Accessories</h3>
            </a>

            <a href="travel-accessory-category.php">
              <h3>Travel Accessories</h3>
            </a>
          </div>
        </div>
        <a href="index.php#bestseller" class="nav-item">Best Seller</a>
        <a href="index.php#newarrival" class="nav-item">New Arrivals</a>
      </nav>

      <div class="search-bar" style="position: relative;">
        <img src="images/search.png" alt="Search" />
        <input
          type="text"
          placeholder="Search for products..."
          class="search-input"
          id="searchInput" />

        <ul id="search-suggestions"></ul>
      </div>
      <div class="cart-icons">
        <img
          src="images/Cart.png"
          alt="Wishlist"
          class="cart-icon"
          style="cursor: pointer" />
      </div>
    </header>

    <div class="cart-content" id="cartContent">
      <div class="cart-inner">

        <?php if (isset($_SESSION['is_login']) == false): ?>
          <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
            Your Cart is empty.
          </h2>
          <p>
            <a
              href="/~D1229568/allen/signin.php"
              style="color: blue; text-decoration: underline">Sign in</a>
            to see if you have any saved items
          </p>
        <?php else: ?>
          <?php if (!$hasItems): ?>
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
              Your Cart is empty.
            </h2>
          <?php else: ?>
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px">
              Your items are waiting — checkout now!
            </h2>
          <?php endif; ?>
          <div style="margin-top: 30px">
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 10px">
              My Profile
            </h3>
            <ul style="list-style: none; padding-left: 0; line-height: 2">
              <li>
                <a
                  href="/~D1229568/allen/cart.php"
                  style="color: black; text-decoration: none">🛒 Cart</a>
              </li>
              <li>
                <a
                  href="/~D1229568/allen/history.php"
                  style="color: black; text-decoration: none">📦 History</a>
              </li>
              <form method="POST" action="">
                <button class="logout-btn" type="submit" name="logout" style="background: none; border: none; color: black; text-decoration: none; cursor: pointer;">🔒 Logout</button>
              </form>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>


    <div class="divider"></div>

    <main class="about-container">
      <h1>Our Store Locations</h1>

      <div class="store-location-container">
        <div class="location-card">
          <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d396.0539244532916!2d120.66651143591446!3d24.18674735595951!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x140f328943fa3f79%3A0x7084079032e595a5!2z5q2Q5Y2w57K-5ZOB!5e0!3m2!1sen!2sus!4v1749473633106!5m2!1sen!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <div class="location-details">
            <h2>台中旗艦店 (Taichung Flagship Store)</h2>
            <p><strong>服務時間 (Service Hours):</strong><br>
              週一至週五 (Mon-Fri): 09:00~16:00<br>
              週六 (Sat): 09:00~17:00<br>
              非營業時間如有購箱需求，請另行致電預約。(For luggage purchases outside business hours, please call to schedule an appointment.)<br>
              每週日公休，國定例假日服務時間請參考FB公告。(Closed every Sunday. For national holidays, please refer to our FB announcements.)</p>
            <p><strong>聯絡電話 (Contact Number):</strong> (04) 2291-4226</p>
            <p><strong>地址 (Address):</strong> 台中市北屯區敦化路一段565號 (敦化公園對面) (No. 565, Sec. 1, Dunhua Rd., Beitun Dist., Taichung City, Taiwan (Opposite Dunhua Park))</p>
          </div>
        </div>

        <div class="location-card">
          <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d227.95148738110137!2d120.65668900000001!3d23.917128!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3469317b62b1e2db%3A0xd4e123753d66b931!2z5Y2K5bGx5aSi5bel5bug!5e0!3m2!1szh-TW!2stw!4v1749473617306!5m2!1szh-TW!2stw" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <div class="location-details">
            <h2>半山夢工廠 (Banshan Dream Factory)</h2>
            <p><strong>服務時間 (Service Hours):</strong><br>
              週日、週一、週三、週四、週五、週六 (Sun, Mon, Wed, Thu, Fri, Sat): 10:00~18:00<br>
              每週二公休 (Closed every Tuesday)</p>
            <p><strong>聯絡電話 (Contact Number):</strong> 0939-653-911</p>
            <p><strong>地址 (Address):</strong> 南投縣南投市工業南六路6號8F (8F, No. 6, Gongye S. 6th Rd., Nantou City, Nantou County, Taiwan)</p>
          </div>
        </div>

        <div class="location-card">
          <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d689.9557958129717!2d120.58675984527162!3d24.115260878423822!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x34693fe76268e96b%3A0xe7374d273a643945!2z5q2Q5Y2w57at5L-u5Lit5b-D77yI5pel5pysTGVnZW5kV2Fsa2Vy77yJ!5e0!3m2!1szh-TW!2stw!4v1749473598315!5m2!1szh-TW!2stw" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <div class="location-details">
            <h2>歐印亞洲維修中心 (All-En Asia Repair Center)</h2>
            <p><strong>服務時間 (Service Hours):</strong><br>
              週一至週五 (Mon-Fri): 09:00~17:30<br>
              每週六日公休 (Closed every Saturday and Sunday)</p>
            <p><strong>聯絡電話 (Contact Number):</strong> (04) 2336-9926 、 0935-793-911</p>
            <p><strong>地址 (Address):</strong> 台中市烏日區振興街27號（近成功火車站） (No. 27, Zhenxing St., Wuri Dist., Taichung City, Taiwan (Near Chenggong Train Station))</p>
          </div>
        </div>

        <div class="location-card">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d226.3627741394524!2d120.96506262661299!3d24.802150926717925!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x346835eaceb0d369%3A0x646b553124f08434!2zMzAw5paw56u55biC5p2x5Y2A6KW_5aSn6LevMzIz6JmfLCA3!5e0!3m2!1szh-TW!2stw!4v1749473570362!5m2!1szh-TW!2stw" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <div class="location-details">
            <h2>新竹大遠百 (Hsinchu Far Eastern Department Store)</h2>
            <p><strong>服務時間 (Service Hours):</strong><br>
              週一至週日 (Mon-Sun): 11:00~22:00</p>
            <p><strong>地址 (Address):</strong> 新竹市東區西大路323號 (7樓) (7F, No. 323, Xida Rd., East Dist., Hsinchu City, Taiwan)</p>
          </div>
        </div>
      </div>
    </main>


    <?php include "layout/footer.html" ?>
    <script src="aboutus.js" defer></script>
  </body>

</html>