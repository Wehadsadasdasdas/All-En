<?php
require_once "connection.php";

echo "<h2>Database Structure Test</h2>";

// Test if capital column exists in product_details table
echo "<h3>Testing product_details table structure:</h3>";
$result = mysqli_query($link, "DESCRIBE product_details");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error describing product_details table: " . mysqli_error($link) . "</p>";
}

// Test if description column exists in products table
echo "<h3>Testing products table structure:</h3>";
$result = mysqli_query($link, "DESCRIBE products");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error describing products table: " . mysqli_error($link) . "</p>";
}

// Test a sample query with the new fields
echo "<h3>Testing sample query with capital and description:</h3>";
$query = "SELECT 
    product_details.PRODUCT_ID as product_id,
    product_details.stock as stock,
    product_details.price as price,
    product_details.capital as capital,
    products.description as description,
    products.image_link as image_link,
    product_details.color as color,
    product_sizes.size as sizes
FROM product_details
JOIN products ON product_details.PRODUCT_ID = products.id
JOIN product_sizes ON product_details.size_id = product_sizes.id
LIMIT 3";

$result = mysqli_query($link, $query);
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Product ID</th><th>Stock</th><th>Price</th><th>Capital</th><th>Description</th><th>Image</th><th>Color</th><th>Size</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['stock']) . "</td>";
        echo "<td>" . htmlspecialchars($row['price']) . "</td>";
        echo "<td>" . htmlspecialchars($row['capital'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['description'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['image_link']) . "</td>";
        echo "<td>" . htmlspecialchars($row['color']) . "</td>";
        echo "<td>" . htmlspecialchars($row['sizes']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error executing sample query: " . mysqli_error($link) . "</p>";
}

mysqli_close($link);
?>
