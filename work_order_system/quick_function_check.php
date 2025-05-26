<?php
/**
 * Quick Function Check
 * Verify all functions needed by process_work_order.php are available
 */

echo "<h1>Quick Function Check</h1>";

// Load all required files
require_once 'config.php';
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// List of functions that process_work_order.php needs
$required_functions = [
    // From functions.php
    'sanitize_input',
    'get_client_ip', 
    'validate_email',
    'redirect_with_form_data',
    'redirect_with_message',
    'send_notification_email',
    
    // From work_order_functions.php
    'generate_customer_id',
    'validate_work_order',
    'calculate_work_order_totals',
    'insert_work_order',
    'get_work_order_setting',
    'get_work_order',
    'format_work_order_email',
    'add_work_order_comment'
];

echo "<h2>Checking Required Functions:</h2>";

$missing_functions = [];
$available_functions = [];

foreach ($required_functions as $function) {
    if (function_exists($function)) {
        echo "✅ <strong>$function</strong> - Available<br>";
        $available_functions[] = $function;
    } else {
        echo "❌ <strong>$function</strong> - MISSING<br>";
        $missing_functions[] = $function;
    }
}

echo "<h2>Summary:</h2>";

if (empty($missing_functions)) {
    echo '<div style="background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;">';
    echo '<strong>🎉 SUCCESS!</strong><br>';
    echo 'All required functions are available. Your process_work_order.php should work correctly.<br>';
    echo '<strong>Functions loaded:</strong> ' . count($available_functions) . '<br>';
    echo '</div>';
} else {
    echo '<div style="background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;">';
    echo '<strong>❌ MISSING FUNCTIONS</strong><br>';
    echo 'The following functions are missing and need to be added:<br>';
    echo '<ul>';
    foreach ($missing_functions as $func) {
        echo "<li><code>$func</code></li>";
    }
    echo '</ul>';
    echo '</div>';
}

// Test database connection
echo "<h2>Database Connection Test:</h2>";
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    echo "✅ Database connection is working<br>";
    
    // Test a simple query
    $result = $conn->query("SELECT 1 as test");
    if ($result) {
        echo "✅ Database queries are working<br>";
    } else {
        echo "❌ Database query failed: " . $conn->error . "<br>";
    }
} else {
    echo "❌ Database connection failed<br>";
}

// Test work order number generation (if function exists)
if (function_exists('generate_work_order_number') && isset($conn)) {
    echo "<h2>Work Order Number Test:</h2>";
    try {
        $wo_number = generate_work_order_number($conn);
        echo "✅ Generated work order number: <strong>$wo_number</strong><br>";
    } catch (Exception $e) {
        echo "❌ Error generating work order number: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>File Check:</h2>";
$files_to_check = [
    'config.php',
    'includes/db_connect.php',
    'includes/functions.php', 
    'includes/work_order_functions.php',
    'process_work_order.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

echo "<h2>Next Steps:</h2>";
if (empty($missing_functions)) {
    echo "<p>Everything looks good! You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Test the work order form</a></li>";
    echo "<li>Submit a test work order</li>";
    echo "<li>Check if it processes without errors</li>";
    echo "</ul>";
} else {
    echo "<p>Please fix the missing functions first:</p>";
    echo "<ul>";
    echo "<li>Make sure all include files are properly loaded</li>";
    echo "<li>Check for any PHP syntax errors in the function files</li>";
    echo "<li>Verify file paths are correct</li>";
    echo "</ul>";
}

echo '<p><a href="index.php">← Back to Work Order Form</a></p>';
?>