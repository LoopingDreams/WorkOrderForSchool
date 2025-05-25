<?php
/**
 * Simple Connection Test
 * This will test the exact connection issue
 */

echo "<h1>Simple Database Connection Test</h1>";

// Test 1: Direct connection to work_order_db
echo "<h2>Test 1: Direct Connection</h2>";
$conn = new mysqli('localhost', 'root', '', 'work_order_db');

if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error . "<br>";
    
    // Try connecting without database first
    echo "<h2>Test 2: Connection without database</h2>";
    $conn2 = new mysqli('localhost', 'root', '');
    if ($conn2->connect_error) {
        echo "❌ MySQL connection failed entirely: " . $conn2->connect_error . "<br>";
        exit;
    } else {
        echo "✅ MySQL connection OK<br>";
        
        // Check if database exists
        $result = $conn2->query("SHOW DATABASES LIKE 'work_order_db'");
        if ($result && $result->num_rows > 0) {
            echo "✅ work_order_db database exists<br>";
            
            // Try to select the database
            if ($conn2->select_db('work_order_db')) {
                echo "✅ Selected work_order_db successfully<br>";
                $conn = $conn2; // Use this connection
            } else {
                echo "❌ Cannot select work_order_db: " . $conn2->error . "<br>";
                exit;
            }
        } else {
            echo "❌ work_order_db database does not exist<br>";
            echo "Creating it now...<br>";
            if ($conn2->query("CREATE DATABASE work_order_db")) {
                echo "✅ Created work_order_db<br>";
                $conn2->select_db('work_order_db');
                $conn = $conn2;
            } else {
                echo "❌ Cannot create database: " . $conn2->error . "<br>";
                exit;
            }
        }
    }
} else {
    echo "✅ Connected to work_order_db successfully<br>";
}

// Test 3: Check tables
echo "<h2>Test 3: Check Tables</h2>";
$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "Tables in work_order_db:<br>";
    echo "<ul>";
    $table_count = 0;
    while ($row = $result->fetch_assoc()) {
        $table_name = $row['Tables_in_work_order_db'];
        echo "<li>$table_name</li>";
        $table_count++;
    }
    echo "</ul>";
    echo "Total tables: $table_count<br>";
    
    if ($table_count >= 6) {
        echo "✅ All tables appear to be present<br>";
    } else {
        echo "❌ Missing tables (should have 6+)<br>";
    }
} else {
    echo "❌ Cannot list tables: " . $conn->error . "<br>";
}

// Test 4: Test your config file
echo "<h2>Test 4: Config File Test</h2>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    require_once 'config.php';
    
    $db_config = get_db_config();
    echo "Database config from config.php:<br>";
    echo "Host: " . $db_config['host'] . "<br>";
    echo "Username: " . $db_config['username'] . "<br>";
    echo "Database: " . $db_config['database'] . "<br>";
    
    if ($db_config['database'] === 'work_order_db') {
        echo "✅ Config points to correct database<br>";
    } else {
        echo "❌ Config points to wrong database: " . $db_config['database'] . "<br>";
    }
} else {
    echo "❌ config.php not found<br>";
}

// Test 5: Test includes
echo "<h2>Test 5: Include Files Test</h2>";
if (file_exists('includes/db_connect.php')) {
    echo "✅ db_connect.php exists<br>";
    try {
        include 'includes/db_connect.php';
        echo "✅ db_connect.php included successfully<br>";
        
        if (isset($conn) && $conn instanceof mysqli) {
            echo "✅ \$conn variable is properly set<br>";
            
            // Test the connection from includes
            $test_result = $conn->query("SELECT 1 as test");
            if ($test_result) {
                echo "✅ Connection from includes works<br>";
                
                // Check which database it's connected to
                $db_result = $conn->query("SELECT DATABASE() as current_db");
                if ($db_result) {
                    $current_db = $db_result->fetch_assoc();
                    echo "Current database from includes: " . ($current_db['current_db'] ?? 'NULL') . "<br>";
                    
                    if ($current_db['current_db'] === 'work_order_db') {
                        echo "✅ Connected to correct database<br>";
                    } else {
                        echo "❌ Connected to wrong database or no database selected<br>";
                    }
                }
            } else {
                echo "❌ Connection from includes failed<br>";
            }
        } else {
            echo "❌ \$conn variable not properly set<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error including db_connect.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db_connect.php not found<br>";
}

echo "<h2>Conclusion</h2>";
echo "If you see ✅ for most tests above, the issue is likely in how the test_config.php is loading the files.<br>";
echo "If you see ❌ errors, we need to fix those first.<br>";
echo "<br>";
echo '<a href="test_config.php">← Back to full test</a><br>';
echo '<a href="index.php">→ Try work order form</a><br>';
?>