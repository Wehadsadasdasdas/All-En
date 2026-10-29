<?php
require_once "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $category = $_POST['category'] ?? 'All';
  $size = $_POST['size'] ?? 'All';
  $price = $_POST['price'] ?? 'All';
  $stock = $_POST['stock'] ?? 'All';
  $search = $_POST['productid-search'] ?? '';

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
    if ($size === 'S') {
      $where[] = "product_details.size_id = 15";
    } elseif ($size === 'M') {
      $where[] = "product_details.size_id = 16";
    } elseif ($size === 'L') {
      $where[] = "product_details.size_id = 17";
    } elseif ($size === 'Default') {
      $where[] = "product_details.size_id = 18";
    } elseif ($size === 'under 21') {
      $where[] = "product_details.size_id < 6"; // Assuming size is a number
    } elseif ($size === '21-24') {
      $where[] = "product_details.size_id BETWEEN 6 AND 8";
    } elseif ($size === '25-28') {
      $where[] = "product_details.size_id BETWEEN 9 AND 12";
    } elseif ($size === 'above 28') {
      $where[] = "product_details.size_id BETWEEN 13 AND 14";
    }
  }

  if (!empty($price) && $price !== 'All') {
    if ($price === 'under 2000') {
      $where[] = "product_details.price < 2001";
    } elseif ($price === '2000-4999') {
      $where[] = "product_details.price BETWEEN 2001 AND 4999";
    } elseif ($price === '5000-9999') {
      $where[] = "product_details.price BETWEEN 5000 AND 9999";
    } elseif ($price === 'above 10000') {
      $where[] = "product_details.price > 9999";
    }
  }

  if ($stock !== 'All') {
    if ($stock === '0') {
      $where[] = "product_details.stock = 0";
    } elseif ($stock === '1-200') {
      $where[] = "product_details.stock BETWEEN 1 AND 200";
    } elseif ($stock === '201-500') {
      $where[] = "product_details.stock BETWEEN 201 AND 500";
    } elseif ($stock === '>500') {
      $where[] = "product_details.stock > 500";
    }
  }

  if (!empty($search)) {
    $search = mysqli_real_escape_string($link, $search);
    $where[] = "product_details.PRODUCT_ID LIKE '%$search%'";
  }
  $query = "SELECT product_details.PRODUCT_ID as product_id, 
                     product_details.stock, 
                     product_details.price, 
                     product_details.capital,
                     products.description,
                     products.image_link,
                     product_sizes.size AS size,
                     product_details.color
              FROM product_details
              INNER JOIN products ON product_details.PRODUCT_ID = products.id
              INNER JOIN product_sizes ON product_details.size_id = product_sizes.id";

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
      'capital' => '$' . number_format($row['capital'], 2),
      'description' => $row['description'],
      'image' => $row['image_link'],
      'color' => $row['color'],
      'size' => $row['size']
    ];
  }

  header('Content-Type: application/json');
  echo json_encode($productArray);
  exit;
}
