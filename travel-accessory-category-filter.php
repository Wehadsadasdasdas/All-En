<?php

require_once "connection.php";

$rangeConditions = [];
$orderByClause = ""; // Initialize order by clause

$data = json_decode(file_get_contents("php://input"), true);
$ranges = $data['selected_ranges'] ?? [];
$sortType = $data['sort_type'] ?? 'popular'; // Get sort type, default to 'popular'

foreach ($ranges as $range) {
  switch ($range) {
    case '1': // < 21 inch
      $rangeConditions[] = "(pd.size_id = 15)";
      break;
    case '2': // 21 - 24 inch
      $rangeConditions[] = "(pd.size_id = 16)";
      break;
    case '3': // 25 - 28 inch
      $rangeConditions[] = "(pd.size_id = 17)";
      break;
    case '4': // > 28 inch
      $rangeConditions[] = "(pd.size_id = 18)";
      break;
  }
}

// Construct WHERE clause based on selected ranges
$whereClause = "";
if (!empty($rangeConditions)) {
  $whereClause = " AND (" . implode(" OR ", $rangeConditions) . ")";
}

// Determine ORDER BY clause based on sort type
switch ($sortType) {
  case 'rating':
    $orderByClause = "ORDER BY avg_rating DESC, MIN(pd.price) ASC"; // Sort by highest rating, then by lowest price
    break;
  case 'popular':
  default:
    $orderByClause = "ORDER BY total_sold DESC, MIN(pd.price) ASC"; // Sort by most popular, then by lowest price
    break;
}

// Query string
$sql = "
SELECT
    p.id AS product_id,
    p.name AS name,
    p.description AS description,
    MIN(pd.price) AS minimum_price,
    p.image_link AS image_link,
    p.category_id,
    AVG(r.rating) AS avg_rating,
    SUM(od.quantity) AS total_sold
FROM
    products p
JOIN
    product_details pd ON pd.PRODUCT_ID = p.id
LEFT JOIN
    product_sizes ps ON pd.size_id = ps.id
LEFT JOIN
    review r ON r.product_id = p.id
LEFT JOIN
    order_detail od ON od.product_id = p.id
WHERE
    p.category_id = 3
    $whereClause
GROUP BY
    p.id, p.name, p.description, p.image_link, p.category_id
$orderByClause;
";

// Run query and return results
$result = mysqli_query($link, $sql);

$filteredProducts = [];

while ($row = mysqli_fetch_assoc($result)) {
  $filteredProducts[] = [
    'id' => $row['product_id'],
    'name' => $row['name'],
    'description' => $row['description'],
    'price' => '$' . number_format($row['minimum_price'], 2),
    'image' => $row['image_link'],
    'avg_rating' => $row['avg_rating'] ? round($row['avg_rating'], 1) : 0, // Ensure avg_rating is included
    'total_sold' => $row['total_sold'] ?? 0 // Include total_sold, default to 0 if null
  ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($filteredProducts);
exit;
