<?php
require_once 'connection.php';

// Check if suppliers table exists
$result = mysqli_query($conn, 'DESCRIBE suppliers');
if ($result) {
    echo "Suppliers table exists with the following structure:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Suppliers table does not exist. Error: " . mysqli_error($conn) . "\n";
    echo "Creating suppliers table...\n";
    
    // Create suppliers table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS suppliers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        country VARCHAR(100),
        city VARCHAR(100),
        street VARCHAR(255),
        account_number VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_sql)) {
        echo "Suppliers table created successfully!\n";
    } else {
        echo "Error creating suppliers table: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>
