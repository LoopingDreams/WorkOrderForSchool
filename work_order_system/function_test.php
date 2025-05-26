<?php
/**
 * Function Loading Test
 * Test if all required functions are loading properly
 */

echo "<h1>Function Loading Test</h1>";

// Test include order
echo "<h2>Step 1: Testing Include Order</h2>";

echo "1. Loading config.php...<br>";
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "✅ config.php loaded<br>";
} else {
    echo "❌ config.php not found<br>";
    exit;
}

echo "2. Loading db_connect.php...<br>";
if (file_exists('includes/db_connect.php')) {
    include 'includes/db_connect.php';
    echo "✅ db_connect.php loaded<br>";
} else {
    echo "❌ db_connect.php not found<br>";
    exit;
}

echo "3. Loading functions.php...<br>";
if (file_exists('includes/functions.php')) {
    include 'includes/functions.php';
    echo "✅ functions.php loaded<br>";
} else {
    echo "❌ functions.php not found<br>";
}

echo "4. Loading work_order_functions.php...<br>";
if (file_exists('includes/work_order_functions.php')) {
    include 'includes/work_order_functions.php';
    echo "✅ work_order_functions.php loaded<br>";
} else {
    echo "❌ work_order_functions.php not found<br>";
    exit;
}

// Test functions
echo "<h2>Step 2: Testing Function Availability</h2>";

$required_functions = [
    'generate_work_order_number',
    'validate_work_order',
    'calculate_work_order_totals',
    'insert_work_order',
    'get_work_order_setting',
    'format_work_order_email',
    'get_work_order'
];

$missing_functions = [];
foreach ($required_functions as $function) {
    if (function_exists($function)) {
        echo "✅ Function $function exists<br>";
    } else {
        echo "❌ Function $function missing<br>";
        $missing_functions[] = $function;
    }
}

if (empty($missing_functions)) {
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✅ All required functions are available!</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0;'>❌ Missing functions: " . implode(', ', $missing_functions) . "</div>";
}

// Test database connection
echo "<h2>Step 3: Testing Database Connection</h2>";
if (isset($conn) && $conn instanceof mysqli) {
    echo "✅ Database connection object exists<br>";
    
    if (!$conn->connect_error) {
        echo "✅ Database connection is working<br>";
        
        // Test generate_work_order_number function
        echo "<h2>Step 4: Testing Work Order Number Generation</h2>";
        try {
            $work_order_number = generate_work_order_number($conn);
            echo "✅ Generated work order number: <strong>$work_order_number</strong><br>";
        } catch (Exception $e) {
            echo "❌ Error generating work order number: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Database connection error: " . $conn->connect_error . "<br>";
    }
} else {
    echo "❌ Database connection object not found<br>";
}

echo "<h2>Conclusion</h2>";
if (empty($missing_functions) && isset($conn) && !$conn->connect_error) {
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
    echo "<strong>🎉 SUCCESS!</strong><br>";
    echo "All functions are loaded and working. Your index.php should work now.<br>";
    echo "<a href='index.php'>→ Try the work order form</a>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
    echo "<strong>❌ Issues Found</strong><br>";
    echo "Please fix the issues above before proceeding.<br>";
    echo "</div>";
}
?>