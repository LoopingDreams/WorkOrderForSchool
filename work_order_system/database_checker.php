<?php 
// Database structure checker
require_once 'config.php';
include 'includes/db_connect.php';

if (!isset($conn) || !$conn) {
    die('Database connection failed.');
}

echo "<h2>Database Structure Checker</h2>";

// Check if work_orders table exists
$tables_result = $conn->query("SHOW TABLES LIKE 'work_orders'");
if ($tables_result->num_rows > 0) {
    echo "<h3>✅ work_orders table exists</h3>";
    
    // Show work_orders structure
    $columns_result = $conn->query("DESCRIBE work_orders");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($column = $columns_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "<h3>❌ work_orders table does NOT exist</h3>";
}

// Check if work_order_items table exists
$items_result = $conn->query("SHOW TABLES LIKE 'work_order_items'");
if ($items_result->num_rows > 0) {
    echo "<h3>✅ work_order_items table exists</h3>";
    
    // Show work_order_items structure
    $items_columns_result = $conn->query("DESCRIBE work_order_items");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($column = $items_columns_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "<h3>❌ work_order_items table does NOT exist</h3>";
}

// Show all tables in database
echo "<h3>All Tables in Database:</h3>";
$all_tables = $conn->query("SHOW TABLES");
if ($all_tables->num_rows > 0) {
    echo "<ul>";
    while ($table = $all_tables->fetch_array()) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No tables found in database.</p>";
}

echo "<hr>";
echo "<h3>💡 Recommended Database Structure</h3>";
echo "<p>Here's the SQL to create the proper tables if they don't exist or need to be updated:</p>";

$sql_work_orders = "
CREATE TABLE IF NOT EXISTS `work_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_number` varchar(50) NOT NULL,
  `order_date` date NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `street_address` text NOT NULL,
  `city_zip` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `requested_by` varchar(255) DEFAULT NULL,
  `customer_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `bill_name` varchar(255) DEFAULT NULL,
  `bill_company` varchar(255) DEFAULT NULL,
  `bill_street_address` text DEFAULT NULL,
  `bill_city_zip` varchar(100) DEFAULT NULL,
  `bill_phone` varchar(20) DEFAULT NULL,
  `description_of_work` text DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `taxable_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `payable_to` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_order_number` (`work_order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$sql_work_order_items = "
CREATE TABLE IF NOT EXISTS `work_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `description` text NOT NULL,
  `taxed` tinyint(1) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `work_order_id` (`work_order_id`),
  CONSTRAINT `work_order_items_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

echo "<h4>work_orders table:</h4>";
echo "<textarea rows='10' cols='80' readonly>" . $sql_work_orders . "</textarea>";

echo "<h4>work_order_items table:</h4>";
echo "<textarea rows='10' cols='80' readonly>" . $sql_work_order_items . "</textarea>";

echo "<hr>";

// Option to create tables automatically
if (isset($_POST['create_tables'])) {
    echo "<h3>Creating Tables...</h3>";
    
    try {
        // Create work_orders table
        if ($conn->query($sql_work_orders)) {
            echo "✅ work_orders table created/updated successfully<br>";
        } else {
            echo "❌ Error creating work_orders table: " . $conn->error . "<br>";
        }
        
        // Create work_order_items table
        if ($conn->query($sql_work_order_items)) {
            echo "✅ work_order_items table created/updated successfully<br>";
        } else {
            echo "❌ Error creating work_order_items table: " . $conn->error . "<br>";
        }
        
        echo "<p><strong>Tables created! You can now go back to customers.php</strong></p>";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
    }
}

echo "<form method='post'>";
echo "<button type='submit' name='create_tables' onclick='return confirm(\"Are you sure you want to create/update the database tables?\")'>🔧 Create/Update Tables Now</button>";
echo "</form>";

$conn->close();
?>
