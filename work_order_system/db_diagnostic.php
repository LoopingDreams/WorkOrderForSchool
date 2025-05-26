<?php
/**
 * Database Diagnostic Tool
 * This will help us see exactly what's happening with your database
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 5px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 5px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; margin: 5px 0; }
        .code { font-family: monospace; background: #f8f9fa; padding: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Database Diagnostic Tool</h1>
    
    <?php
    echo "<h2>Step 1: Testing Basic Connection</h2>";
    
    // Test basic MySQL connection
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new mysqli($host, $username, $password);
        
        if ($conn->connect_error) {
            echo '<div class="error">❌ Cannot connect to MySQL: ' . $conn->connect_error . '</div>';
            exit;
        } else {
            echo '<div class="success">✅ Connected to MySQL successfully</div>';
        }
        
        echo "<h2>Step 2: Checking Available Databases</h2>";
        
        // Show all databases
        $result = $conn->query("SHOW DATABASES");
        if ($result) {
            echo '<div class="info">Available databases:</div>';
            echo '<ul>';
            $db_exists = false;
            while ($row = $result->fetch_assoc()) {
                $db_name = $row['Database'];
                echo '<li>' . $db_name;
                if ($db_name === 'work_order_db') {
                    echo ' <strong>(TARGET DATABASE FOUND!)</strong>';
                    $db_exists = true;
                }
                echo '</li>';
            }
            echo '</ul>';
            
            if (!$db_exists) {
                echo '<div class="error">❌ work_order_db database does not exist!</div>';
                echo '<h2>Creating Database Now...</h2>';
                
                // Try to create the database
                if ($conn->query("CREATE DATABASE work_order_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                    echo '<div class="success">✅ Created work_order_db database successfully!</div>';
                    $db_exists = true;
                } else {
                    echo '<div class="error">❌ Failed to create database: ' . $conn->error . '</div>';
                    exit;
                }
            }
        } else {
            echo '<div class="error">❌ Cannot list databases: ' . $conn->error . '</div>';
            exit;
        }
        
        if ($db_exists) {
            echo "<h2>Step 3: Connecting to work_order_db</h2>";
            
            // Connect to the specific database
            $conn->close();
            $conn = new mysqli($host, $username, $password, 'work_order_db');
            
            if ($conn->connect_error) {
                echo '<div class="error">❌ Cannot connect to work_order_db: ' . $conn->connect_error . '</div>';
                exit;
            } else {
                echo '<div class="success">✅ Connected to work_order_db successfully</div>';
            }
            
            echo "<h2>Step 4: Checking Tables</h2>";
            
            // Check existing tables
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                if ($result->num_rows > 0) {
                    echo '<div class="info">Existing tables:</div>';
                    echo '<ul>';
                    while ($row = $result->fetch_assoc()) {
                        $table_name = $row['Tables_in_work_order_db'];
                        echo '<li>' . $table_name . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<div class="error">❌ No tables found in work_order_db</div>';
                    echo '<div class="info">This means the SQL script wasn\'t executed or failed.</div>';
                }
            } else {
                echo '<div class="error">❌ Cannot list tables: ' . $conn->error . '</div>';
            }
            
            echo "<h2>Step 5: Creating Tables Now</h2>";
            echo '<div class="info">Attempting to create all required tables...</div>';
            
            // Create tables one by one with individual error checking
            $tables_sql = [
                'work_order_settings' => "CREATE TABLE IF NOT EXISTS work_order_settings (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    setting_name VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_setting_name (setting_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'customers' => "CREATE TABLE IF NOT EXISTS customers (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    customer_id VARCHAR(50) UNIQUE,
                    name VARCHAR(100) NOT NULL,
                    company VARCHAR(150),
                    email VARCHAR(150),
                    phone VARCHAR(20),
                    address TEXT,
                    city_zip VARCHAR(100),
                    total_orders INT(6) DEFAULT 0,
                    total_amount DECIMAL(12,2) DEFAULT 0.00,
                    last_order_date DATE NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_customer_id (customer_id),
                    INDEX idx_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'work_orders' => "CREATE TABLE IF NOT EXISTS work_orders (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    work_order_number VARCHAR(50) NOT NULL UNIQUE,
                    order_date DATE NOT NULL,
                    requested_by VARCHAR(100) NOT NULL,
                    customer_id VARCHAR(50),
                    department VARCHAR(100),
                    contact_name VARCHAR(100) NOT NULL,
                    contact_email VARCHAR(150) NOT NULL,
                    street_address TEXT NOT NULL,
                    city_zip VARCHAR(100) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    bill_name VARCHAR(100) NOT NULL,
                    bill_company VARCHAR(150),
                    bill_street_address TEXT NOT NULL,
                    bill_city_zip VARCHAR(100) NOT NULL,
                    bill_phone VARCHAR(20),
                    description_of_work TEXT NOT NULL,
                    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    taxable_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    payable_to VARCHAR(150),
                    status ENUM('draft', 'pending', 'in_progress', 'completed', 'cancelled', 'billed') DEFAULT 'draft',
                    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    INDEX idx_work_order_number (work_order_number),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'work_order_items' => "CREATE TABLE IF NOT EXISTS work_order_items (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    work_order_id INT(11) NOT NULL,
                    item_order INT(3) NOT NULL DEFAULT 1,
                    quantity INT(6) NOT NULL DEFAULT 1,
                    description TEXT NOT NULL,
                    is_taxed BOOLEAN DEFAULT TRUE,
                    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_work_order_id (work_order_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'work_order_comments' => "CREATE TABLE IF NOT EXISTS work_order_comments (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    work_order_id INT(11) NOT NULL,
                    comment_type ENUM('note', 'status_change', 'customer_communication') DEFAULT 'note',
                    comment TEXT NOT NULL,
                    added_by VARCHAR(100),
                    is_internal BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_work_order_id (work_order_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'work_order_attachments' => "CREATE TABLE IF NOT EXISTS work_order_attachments (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    work_order_id INT(11) NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    original_filename VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    file_size INT(11) NOT NULL,
                    mime_type VARCHAR(100),
                    description TEXT,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_work_order_id (work_order_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ];
            
            $tables_created = 0;
            foreach ($tables_sql as $table_name => $sql) {
                if ($conn->query($sql)) {
                    echo '<div class="success">✅ Created table: ' . $table_name . '</div>';
                    $tables_created++;
                } else {
                    echo '<div class="error">❌ Failed to create table ' . $table_name . ': ' . $conn->error . '</div>';
                }
            }
            
            // Insert default settings
            if ($tables_created > 0) {
                echo "<h2>Step 6: Inserting Default Settings</h2>";
                
                $settings_sql = "INSERT INTO work_order_settings (setting_name, setting_value, description) VALUES
                    ('company_name', 'Your Company Name', 'Company name for work orders'),
                    ('company_address', '123 Business St, City, State 12345', 'Company address'),
                    ('company_phone', '(555) 123-4567', 'Company phone number'),
                    ('company_email', 'info@yourcompany.com', 'Company email address'),
                    ('tax_rate', '0.12', 'Tax rate (12%)'),
                    ('work_order_prefix', 'WO', 'Prefix for work order numbers'),
                    ('auto_generate_customer_id', '1', 'Auto-generate customer IDs'),
                    ('default_payable_to', 'Your Company Name', 'Default payable to field'),
                    ('email_notifications', '1', 'Send email notifications'),
                    ('admin_email', 'admin@yourcompany.com', 'Admin email for notifications')
                    ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)";
                
                if ($conn->query($settings_sql)) {
                    echo '<div class="success">✅ Inserted default settings</div>';
                } else {
                    echo '<div class="error">❌ Failed to insert settings: ' . $conn->error . '</div>';
                }
            }
            
            echo "<h2>Step 7: Final Verification</h2>";
            
            // Final check
            $result = $conn->query("SHOW TABLES");
            if ($result && $result->num_rows >= 6) {
                echo '<div class="success">🎉 SUCCESS! All tables created successfully!</div>';
                echo '<div class="info">You can now test your work order system.</div>';
                echo '<p><a href="test_config.php">← Go back to configuration test</a></p>';
                echo '<p><a href="index.php">→ Try the work order form</a></p>';
            } else {
                echo '<div class="error">❌ Something went wrong. Only ' . ($result ? $result->num_rows : 0) . ' tables were created.</div>';
            }
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <h2>📝 Manual Steps if Automated Creation Failed:</h2>
    <ol>
        <li>Go to phpMyAdmin</li>
        <li>Make sure MySQL service is running</li>
        <li>Create database manually: <code>work_order_db</code></li>
        <li>Select the database</li>
        <li>Use the SQL tab to run the table creation scripts</li>
    </ol>
    
</body>
</html>