<?php
header('Content-Type: application/json');
require_once 'connection.php';

// 1. 取得每個 product_id 的總售出數量
$sql = "SELECT product_id, SUM(quantity) AS total_sold FROM order_detail GROUP BY product_id";
$result = mysqli_query($link, $sql);

$product_sales = [];
$total_quantity = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $product_sales[$row['product_id']] = (int)$row['total_sold'];
    $total_quantity += (int)$row['total_sold'];
}

// 2. 取得每個 product_id 對應的 category_id
$category_sales = [];
if (!empty($product_sales)) {
    $product_ids = array_map(function($id) use ($link) {
        return "'" . mysqli_real_escape_string($link, $id) . "'";
    }, array_keys($product_sales));
    $sql2 = "SELECT id, category_id FROM products WHERE id IN (" . implode(',', $product_ids) . ")";
    $result2 = mysqli_query($link, $sql2);
    $product_to_category = [];
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $product_to_category[$row2['id']] = $row2['category_id'];
    }

    // 3. 累加每個 category_id 的銷售數量
    foreach ($product_sales as $pid => $qty) {
        $cat_id = isset($product_to_category[$pid]) ? $product_to_category[$pid] : 'Unknown';
        if (!isset($category_sales[$cat_id])) $category_sales[$cat_id] = 0;
        $category_sales[$cat_id] += $qty;
    }
}

// 4. 過濾只保留數量>0的category
$filtered_category_sales = array_filter($category_sales, function($qty) {
    return $qty > 0;
});

// 5. 取得category_id對應的name
$category_names = [];
if (!empty($filtered_category_sales)) {
    $cat_ids = array_filter(array_keys($filtered_category_sales), function($id) { return $id !== 'Unknown'; });
    if (!empty($cat_ids)) {
        $cat_ids_sql = array_map('intval', $cat_ids);
        $sql3 = "SELECT id, name FROM product_categories WHERE id IN (" . implode(',', $cat_ids_sql) . ")";
        $result3 = mysqli_query($link, $sql3);
        while ($row3 = mysqli_fetch_assoc($result3)) {
            $category_names[$row3['id']] = $row3['name'];
        }
    }
}

// 6. 準備labels與values
$labels = [];
$values = [];
foreach ($filtered_category_sales as $cat_id => $qty) {
    if ($cat_id === 'Unknown') {
        $labels[] = 'Unknown';
    } else {
        $labels[] = isset($category_names[$cat_id]) ? $category_names[$cat_id] : $cat_id;
    }
    $values[] = $qty;
}

echo json_encode([
    'labels' => $labels,
    'values' => $values
], JSON_UNESCAPED_UNICODE);
exit;
?>