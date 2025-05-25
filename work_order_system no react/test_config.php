<?php
/**
 * Configuration and Database Test
 * Run this file to test your setup
 */

// Include configuration
require_once 'config.php';
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Start output buffering to catch any errors
ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order System - Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        h1 { color: #333; border-bottom: 2px solid #6c63ff; padding-bottom: 10px; }
        h2 { color: #6c63ff; margin-top: 25px; }
        .test-item { margin: 10px 0; padding: 8px; border-left: 4px solid #6c63ff; background: #f8f9fa; }
        .status { font-weight: bold; float: right; }
        .passed { color: #28a745; }
        .failed { color: #dc3545; }
        .code { font-family: monospace; background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Work Order System - Configuration Test</h1>
        
        <?php
        $all_tests_passed = true;
        $test_results = [];
        
        // Test 1: Configuration Loading
        echo "<h2>1. Configuration Test</h2>";
        try {
            $config_check = check_system_config();
            
            if ($config_check['status'] === 'ok') {
                echo '<div class="test-section success">✅ Configuration loaded successfully</div>';
                $test_results['config'] = true;
            } else {
                echo '<div class="test-section error">❌ Configuration has errors:</div>';
                foreach ($config_check['errors'] as $error) {
                    echo '<div class="test-section error">• ' . htmlspecialchars($error) . '</div>';
                }
                $test_results['config'] = false;
                $all_tests_passed = false;
            }
            
            if (!empty($config_check['warnings'])) {
                foreach ($config_check['warnings'] as $warning) {
                    echo '<div class="test-section warning">⚠️ Warning: ' . htmlspecialchars($warning) . '</div>';
                }
            }
        } catch (Exception $e) {
            echo '<div class="test-section error">❌ Configuration error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $test_results['config'] = false;
            $all_tests_passed = false;
        }
        
        // Test 2: Database Connection
        echo "<h2>2. Database Connection Test</h2>";
        try {
            if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                echo '<div class="test-section success">✅ Database connected successfully</div>';
                echo '<div class="test-item">Host: <span class="code">' . get_config('DB_HOST') . '</span></div>';
                echo '<div class="test-item">Database: <span class="code">' . get_config('DB_NAME') . '</span></div>';
                echo '<div class="test-item">Charset: <span class="code">' . get_config('DB_CHARSET') . '</span></div>';
                $test_results['database'] = true;
            } else {
                echo '<div class="test-section error">❌ Database connection failed</div>';
                $test_results['database'] = false;
                $all_tests_passed = false;
            }
        } catch (Exception $e) {
            echo '<div class="test-section error">❌ Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $test_results['database'] = false;
            $all_tests_passed = false;
        }
        
        // Test 3: Required Tables
        echo "<h2>3. Database Tables Test</h2>";
        if ($test_results['database']) {
            echo '<div class="info">Testing database connection details:</div>';
            echo '<div class="test-item">Host: <span class="code">' . $conn->server_info . '</span></div>';
            echo '<div class="test-item">Database: <span class="code">' . $conn->get_server_info() . '</span></div>';
            
            // First, let's see what database we're actually connected to
            $current_db_result = $conn->query("SELECT DATABASE() as current_db");
            if ($current_db_result) {
                $current_db = $current_db_result->fetch_assoc();
                echo '<div class="test-item">Current Database: <span class="code">' . ($current_db['current_db'] ?? 'NULL') . '</span></div>';
            }
            
            // Show all databases to verify work_order_db exists
            echo '<div class="info">Available databases:</div>';
            $db_result = $conn->query("SHOW DATABASES");
            if ($db_result) {
                echo '<ul>';
                $work_order_db_exists = false;
                while ($row = $db_result->fetch_assoc()) {
                    $db_name = $row['Database'];
                    echo '<li>' . $db_name;
                    if ($db_name === 'work_order_db') {
                        echo ' <strong>(TARGET)</strong>';
                        $work_order_db_exists = true;
                    }
                    echo '</li>';
                }
                echo '</ul>';
                
                if (!$work_order_db_exists) {
                    echo '<div class="error">❌ work_order_db database not found in MySQL!</div>';
                    $test_results['tables'] = false;
                    $all_tests_passed = false;
                } else {
                    echo '<div class="success">✅ work_order_db database exists in MySQL</div>';
                }
            }
            
            // Now try to connect specifically to work_order_db
            echo '<div class="info">Attempting to use work_order_db database...</div>';
            if ($conn->select_db('work_order_db')) {
                echo '<div class="success">✅ Successfully selected work_order_db</div>';
                
                // Now check tables in work_order_db specifically
                $required_tables = [
                    'work_orders',
                    'work_order_items', 
                    'customers',
                    'work_order_settings',
                    'work_order_comments',
                    'work_order_attachments'
                ];
                
                echo '<div class="info">Checking tables in work_order_db:</div>';
                
                // Get all tables in work_order_db
                $result = $conn->query("SHOW TABLES FROM work_order_db");
                $existing_tables = [];
                
                if ($result) {
                    echo '<div class="info">Tables found in work_order_db:</div>';
                    echo '<ul>';
                    while ($row = $result->fetch_assoc()) {
                        $table_name = $row['Tables_in_work_order_db'];
                        $existing_tables[] = $table_name;
                        echo '<li><span class="code">' . $table_name . '</span></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<div class="error">❌ Cannot list tables: ' . $conn->error . '</div>';
                }
                
                // Check each required table
                $missing_tables = [];
                foreach ($required_tables as $table) {
                    if (in_array($table, $existing_tables)) {
                        echo '<div class="test-item">✅ Table <span class="code">' . $table . '</span> exists</div>';
                    } else {
                        echo '<div class="test-item">❌ Table <span class="code">' . $table . '</span> missing</div>';
                        $missing_tables[] = $table;
                    }
                }
                
                if (empty($missing_tables)) {
                    echo '<div class="test-section success">✅ All required tables exist</div>';
                    $test_results['tables'] = true;
                } else {
                    echo '<div class="test-section error">❌ Missing tables: ' . implode(', ', $missing_tables) . '</div>';
                    
                    // Try to create missing tables
                    echo '<div class="info">Attempting to create missing tables...</div>';
                    foreach ($missing_tables as $missing_table) {
                        echo '<div class="test-item">Creating ' . $missing_table . '...</div>';
                        // You can add table creation logic here if needed
                    }
                    
                    $test_results['tables'] = false;
                    $all_tests_passed = false;
                }
                
            } else {
                echo '<div class="error">❌ Cannot select work_order_db database: ' . $conn->error . '</div>';
                $test_results['tables'] = false;
                $all_tests_passed = false;
            }
            
        } else {
            echo '<div class="test-section error">❌ Cannot test tables - database connection failed</div>';
            $test_results['tables'] = false;
            $all_tests_passed = false;
        }
        
        // Test 4: PHP Functions
        echo "<h2>4. PHP Functions Test</h2>";
        $required_functions = [
            'generate_work_order_number',
            'validate_work_order',
            'calculate_work_order_totals',
            'insert_work_order',
            'get_work_order',
            'format_work_order_email'
        ];
        
        $missing_functions = [];
        foreach ($required_functions as $function) {
            if (function_exists($function)) {
                echo '<div class="test-item">✅ Function <span class="code">' . $function . '</span> loaded</div>';
            } else {
                echo '<div class="test-item">❌ Function <span class="code">' . $function . '</span> missing</div>';
                $missing_functions[] = $function;
            }
        }
        
        if (empty($missing_functions)) {
            echo '<div class="test-section success">✅ All required functions loaded</div>';
            $test_results['functions'] = true;
        } else {
            echo '<div class="test-section error">❌ Missing functions: ' . implode(', ', $missing_functions) . '</div>';
            $test_results['functions'] = false;
            $all_tests_passed = false;
        }
        
        // Test 5: Work Order Number Generation
        echo "<h2>5. Work Order Number Generation Test</h2>";
        if ($test_results['database'] && $test_results['functions']) {
            try {
                $work_order_number = generate_work_order_number($conn);
                if (!empty($work_order_number) && preg_match('/^WO\d{8}$/', $work_order_number)) {
                    echo '<div class="test-section success">✅ Work order number generated: <span class="code">' . $work_order_number . '</span></div>';
                    $test_results['work_order_generation'] = true;
                } else {
                    echo '<div class="test-section error">❌ Invalid work order number format: ' . htmlspecialchars($work_order_number) . '</div>';
                    $test_results['work_order_generation'] = false;
                    $all_tests_passed = false;
                }
            } catch (Exception $e) {
                echo '<div class="test-section error">❌ Work order generation error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $test_results['work_order_generation'] = false;
                $all_tests_passed = false;
            }
        } else {
            echo '<div class="test-section error">❌ Cannot test work order generation - prerequisites failed</div>';
            $test_results['work_order_generation'] = false;
            $all_tests_passed = false;
        }
        
        // Test 6: File Permissions
        echo "<h2>6. File Permissions Test</h2>";
        $log_path = get_config('LOG_PATH', 'logs/');
        if (is_dir($log_path) && is_writable($log_path)) {
            echo '<div class="test-section success">✅ Logs directory is writable</div>';
            $test_results['permissions'] = true;
        } else {
            echo '<div class="test-section error">❌ Logs directory not writable or missing</div>';
            $test_results['permissions'] = false;
            $all_tests_passed = false;
        }
        
        // Test 7: System Settings
        echo "<h2>7. System Settings Test</h2>";
        if ($test_results['database']) {
            try {
                $company_name = get_work_order_setting($conn, 'company_name', 'Default Company');
                $tax_rate = get_work_order_setting($conn, 'tax_rate', '0.12');
                
                echo '<div class="test-item">Company Name: <span class="code">' . htmlspecialchars($company_name) . '</span></div>';
                echo '<div class="test-item">Tax Rate: <span class="code">' . htmlspecialchars($tax_rate) . '</span></div>';
                echo '<div class="test-section success">✅ System settings accessible</div>';
                $test_results['settings'] = true;
            } catch (Exception $e) {
                echo '<div class="test-section error">❌ Settings error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $test_results['settings'] = false;
                $all_tests_passed = false;
            }
        } else {
            echo '<div class="test-section error">❌ Cannot test settings - database connection failed</div>';
            $test_results['settings'] = false;
            $all_tests_passed = false;
        }
        
        // Summary
        echo "<h2>📊 Test Summary</h2>";
        if ($all_tests_passed) {
            echo '<div class="test-section success">';
            echo '<h3>🎉 All Tests Passed!</h3>';
            echo '<p>Your Work Order System is properly configured and ready to use.</p>';
            echo '<p><strong>Next Steps:</strong></p>';
            echo '<ul>';
            echo '<li>Visit <a href="index.php">index.php</a> to test the work order form</li>';
            echo '<li>Visit <a href="dashboard.php">dashboard.php</a> to test the management interface</li>';
            echo '<li>Delete this test file when you\'re satisfied with the setup</li>';
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="test-section error">';
            echo '<h3>❌ Some Tests Failed</h3>';
            echo '<p>Please fix the issues above before using the system.</p>';
            echo '<p><strong>Common Solutions:</strong></p>';
            echo '<ul>';
            echo '<li>Make sure XAMPP Apache and MySQL services are running</li>';
            echo '<li>Verify the database was created and SQL script was executed</li>';
            echo '<li>Check file permissions on the logs directory</li>';
            echo '<li>Verify all files are in the correct locations</li>';
            echo '</ul>';
            echo '</div>';
        }
        
        echo "<h2>🔧 System Information</h2>";
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th></tr>';
        echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
        echo '<tr><td>System Name</td><td>' . get_config('SYSTEM_NAME') . '</td></tr>';
        echo '<tr><td>Debug Mode</td><td>' . (is_debug_mode() ? 'Enabled' : 'Disabled') . '</td></tr>';
        echo '<tr><td>Tax Rate</td><td>' . (get_config('DEFAULT_TAX_RATE') * 100) . '%</td></tr>';
        echo '<tr><td>Currency</td><td>' . get_config('CURRENCY_SYMBOL') . ' (' . get_config('CURRENCY_CODE') . ')</td></tr>';
        echo '<tr><td>Timezone</td><td>' . get_config('DEFAULT_TIMEZONE') . '</td></tr>';
        echo '</table>';
        ?>
        
        <div class="test-section info">
            <p><strong>💡 Note:</strong> Delete this <code>test_config.php</code> file after testing for security reasons.</p>
        </div>
    </div>
</body>
</html>