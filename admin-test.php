<?php
require_once "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $category = $_POST['category'] ?? 'All';
  $size = $_POST['size'] ?? '';
  $price = $_POST['price'] ?? '';
  $stock = $_POST['stock'] ?? '';

  $categoryMap = [
    'Luggage' => 1,
    'Luggage Accessories' => 2,
    'Travel Accessories' => 3
  ];

  $where = [];

  if ($category !== 'All' && isset($categoryMap[$category])) {
    $where[] = 'products.category_id = ' . (int)$categoryMap[$category];
  }

  if (!empty($size) && $size !== 'All') {
    $where[] = "product_details.size = '" . mysqli_real_escape_string($link, $size) . "'";
  }

  if (!empty($price) && $price !== 'All') {
    // Example: "< 100", "> 200", "= 150"
    $where[] = "product_details.price $price";
  }

  if (!empty($stock) && $stock !== 'All') {
    $where[] = "product_details.stock $stock";
  }

  $query = "SELECT product_details.PRODUCT_ID as product_id, 
                     product_details.stock, 
                     product_details.price, 
                     products.image_link
              FROM product_details
              INNER JOIN products ON product_details.PRODUCT_ID = products.id";

  if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
  }

  $result = mysqli_query($link, $query);

  $productArray = [];

  while ($row = mysqli_fetch_assoc($result)) {
    $productArray[] = [
      'id' => $row['product_id'],
      'stock' => (int)$row['stock'],
      'price' => '$' . number_format($row['price'], 2),
      'image' => $row['image_link']
    ];
  }

  header('Content-Type: application/json');
  echo json_encode($productArray);
  exit;
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Filtered Products</title>
  <style>
    .dropdown {
      position: relative;
      width: 200px;
      margin-bottom: 100px;
    }

    .dropdown input {
      width: 100%;
      padding: 8px;
      cursor: pointer;
    }

    .choices {
      display: none;
      position: absolute;
      background: #fff;
      border: 1px solid #ccc;
      width: 100%;
    }

    .choice {
      cursor: pointer;
    }

    .choice:hover {
      background-color: #eee;
    }

    .image-crop {
      border: 1px solid #ddd;
      padding: 10px;
      display: inline-block;
      margin: 10px;
      text-align: center;
    }

    .image-crop img {
      width: 100px;
      height: 100px;
      object-fit: cover;
    }
  </style>
</head>

<body>

  <!-- CATEGORY -->
  <div class="dropdown">
    <input type="text" id="category" placeholder="All" readonly>
    <p>Category</p>
    <div class="choices" id="choiceList-category">
      <div class="choice">All</div>
      <div class="choice">Luggage</div>
      <div class="choice">Luggage Accessories</div>
      <div class="choice">Travel Accessories</div>
    </div>
  </div>

  <!-- SIZE -->
  <div class="dropdown">
    <input type="text" id="size" placeholder="Any" readonly>
    <p>Size</p>
    <div class="choices" id="choiceList-size">
      <div class="choice">All</div>
      <div class="choice">S</div>
      <div class="choice">M</div>
      <div class="choice">L</div>
      <div class="choice">XL</div>
    </div>
  </div>

  <!-- PRICE -->
  <div class="dropdown">
    <input type="text" id="price" placeholder="Any" readonly>
    <p>Price</p>
    <div class="choices" id="choiceList-price">
      <div class="choice">
        < 5000 </div>
          <div class="choice">> 10000</div>
      </div>
    </div>

    <!-- STOCK -->
    <div class="dropdown">
      <input type="text" id="stock" placeholder="Any" readonly>
      <p>Stock</p>
      <div class="choices" id="choiceList-stock">
        <div class="choice">> 0</div>
        <div class="choice">= 0</div>
        <div class="choice">
          < 10</div>
        </div>
      </div>


      <div id="productList"></div>

      <script>
        function setupDropdown(inputId, listId) {
          const input = document.getElementById(inputId);
          const list = document.getElementById(listId);
          const choices = list.querySelectorAll('.choice');

          input.addEventListener('click', () => {
            list.style.display = list.style.display === 'block' ? 'none' : 'block';
          });

          choices.forEach(choice => {
            choice.addEventListener('click', () => {
              input.value = choice.textContent;
              list.style.display = 'none';
              loadProducts(); // reload after each filter change
            });
          });

          document.addEventListener('click', e => {
            if (!e.target.closest('.dropdown')) {
              list.style.display = 'none';
            }
          });
        }

        ['category', 'size', 'price', 'stock'].forEach(field => {
          setupDropdown(field, `choiceList-${field}`);
        });

        function loadProducts() {
          const category = document.getElementById('category').value;
          const size = document.getElementById('size').value;
          const price = document.getElementById('price').value;
          const stock = document.getElementById('stock').value;

          const params = new URLSearchParams({
            category,
            size,
            price,
            stock
          });

          fetch(window.location.href, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: params.toString()
            })
            .then(res => res.json())
            .then(products => {
              const productList = document.getElementById('productList');
              productList.innerHTML = '';

              products.forEach(product => {
                const div = document.createElement('div');
                div.className = 'image-crop';
                div.innerHTML = `
                    <img src="${product.image}" alt="Product Image">
                    <div><h3>Stock: ${product.stock}</h3></div>
                    <h1>${product.id}</h1>
                    <small>${product.price}</small>
                `;
                productList.appendChild(div);
              });
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
          document.getElementById('category').value = 'All';
          loadProducts();
        });
      </script>

</body>

</html>