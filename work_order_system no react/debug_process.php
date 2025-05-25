<?php
/**
 * Debug Process Work Order
 * This will show us exactly where the issue is
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Process Work Order</h1>";
echo "<p>Starting debug process...</p>";

// Check if this is a POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0;'>";
    echo "❌ Not a POST request. Request method: " . $_SERVER["REQUEST_METHOD"];
    echo "</div>";
    echo "<p><a href='index.php'>← Back to form</a></p>";
    exit;
}

echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>✅ POST request received</div>";

// Step 1: Include files
echo "<h2>Step 1: Including Files</h2>";

try {
    require_once 'config.php';
    echo "✅ config.php loaded<br>";
} catch (Exception $e) {
    echo "❌ Error loading config.php: " . $e->getMessage() . "<br>";
    exit;
}

try {
    include 'includes/db_connect.php';
    echo "✅ db_connect.php loaded<br>";
} catch (Exception $e) {
    echo "❌ Error loading db_connect.php: " . $e->getMessage() . "<br>";
    exit;
}

try {
    include 'includes/functions.php';
    echo "✅ functions.php loaded<br>";
} catch (Exception $e) {
    echo "❌ Error loading functions.php: " . $e->getMessage() . "<br>";
    exit;
}

try {
    include 'includes/work_order_functions.php';
    echo "✅ work_order_functions.php loaded<br>";
} catch (Exception $e) {
    echo "❌ Error loading work_order_functions.php: " . $e->getMessage() . "<br>";
    exit;
}

// Step 2: Check database connection
echo "<h2>Step 2: Database Connection</h2>";
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    echo "✅ Database connection OK<br>";
} else {
    echo "❌ Database connection failed<br>";
    if (isset($conn)) {
        echo "Error: " . $conn->connect_error . "<br>";
    }
    exit;
}

// Step 3: Start session
echo "<h2>Step 3: Session</h2>";
session_start();
echo "✅ Session started<br>";

// Step 4: Check CSRF token
echo "<h2>Step 4: CSRF Token</h2>";
if (!isset($_POST['csrf_token'])) {
    echo "❌ CSRF token missing from form<br>";
    exit;
} else {
    echo "✅ CSRF token present<br>";
}

if (!function_exists('verify_csrf_token')) {
    echo "❌ verify_csrf_token function not found<br>";
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'])) {
    echo "❌ CSRF token invalid<br>";
    exit;
} else {
    echo "✅ CSRF token valid<br>";
}

// Step 5: Get client IP
echo "<h2>Step 5: Client IP</h2>";
if (!function_exists('get_client_ip')) {
    echo "❌ get_client_ip function not found<br>";
    exit;
}

$client_ip = get_client_ip();
echo "✅ Client IP: " . $client_ip . "<br>";

// Step 6: Collect form data
echo "<h2>Step 6: Form Data Collection</h2>";

if (!function_exists('sanitize_input')) {
    echo "❌ sanitize_input function not found<br>";
    exit;
}

echo "POST data received:<br>";
echo "<ul>";
foreach ($_POST as $key => $value) {
    if ($key === 'items') {
        echo "<li>$key: " . count($value) . " items</li>";
    } else {
        echo "<li>$key: " . (is_string($value) ? htmlspecialchars(substr($value, 0, 50)) : 'non-string') . "</li>";
    }
}
echo "</ul>";

$form_data = array(
    // Work order info
    'work_order_number' => sanitize_input($_POST['work_order_number'] ?? ''),
    'order_date' => sanitize_input($_POST['order_date'] ?? ''),
    'requested_by' => sanitize_input($_POST['requested_by'] ?? ''),
    'customer_id' => sanitize_input($_POST['customer_id'] ?? ''),
    'department' => sanitize_input($_POST['department'] ?? ''),
    
    // Contact information
    'contact_name' => sanitize_input($_POST['contact_name'] ?? ''),
    'contact_email' => sanitize_input($_POST['contact_email'] ?? ''),
    'street_address' => sanitize_input($_POST['street_address'] ?? ''),
    'city_zip' => sanitize_input($_POST['city_zip'] ?? ''),
    'phone' => sanitize_input($_POST['phone'] ?? ''),
    
    // Bill to information
    'bill_name' => sanitize_input($_POST['bill_name'] ?? ''),
    'bill_company' => sanitize_input($_POST['bill_company'] ?? ''),
    'bill_street_address' => sanitize_input($_POST['bill_street_address'] ?? ''),
    'bill_city_zip' => sanitize_input($_POST['bill_city_zip'] ?? ''),
    'bill_phone' => sanitize_input($_POST['bill_phone'] ?? ''),
    
    // Job details
    'description_of_work' => sanitize_input($_POST['description_of_work'] ?? ''),
    'payable_to' => sanitize_input($_POST['payable_to'] ?? ''),
    
    // Items
    'items' => $_POST['items'] ?? array()
);

echo "✅ Form data collected<br>";

// Step 7: Check for "same as contact"
echo "<h2>Step 7: Same as Contact Check</h2>";
$same_as_contact = isset($_POST['same_as_contact']);
if ($same_as_contact) {
    echo "✅ 'Same as contact' checked - copying data<br>";
    $form_data['bill_name'] = $form_data['contact_name'];
    $form_data['bill_street_address'] = $form_data['street_address'];
    $form_data['bill_city_zip'] = $form_data['city_zip'];
    $form_data['bill_phone'] = $form_data['phone'];
} else {
    echo "ℹ️ 'Same as contact' not checked<br>";
}

// Step 8: Generate customer ID
echo "<h2>Step 8: Customer ID Generation</h2>";
if (empty($form_data['customer_id'])) {
    if (!function_exists('generate_customer_id')) {
        echo "❌ generate_customer_id function not found<br>";
        exit;
    }
    
    $form_data['customer_id'] = generate_customer_id($conn, $form_data['contact_name']);
    echo "✅ Generated customer ID: " . $form_data['customer_id'] . "<br>";
} else {
    echo "✅ Customer ID provided: " . $form_data['customer_id'] . "<br>";
}

// Step 9: Sanitize items
echo "<h2>Step 9: Items Processing</h2>";
$sanitized_items = array();
if (is_array($form_data['items'])) {
    foreach ($form_data['items'] as $key => $item) {
        if (is_array($item)) {
            $sanitized_items[$key] = array(
                'qty' => (int)($item['qty'] ?? 1),
                'description' => sanitize_input($item['description'] ?? ''),
                'taxed' => sanitize_input($item['taxed'] ?? '1'),
                'unit_price' => (float)($item['unit_price'] ?? 0),
                'total_price' => (float)($item['total_price'] ?? 0)
            );
        }
    }
}
$form_data['items'] = $sanitized_items;
echo "✅ Processed " . count($sanitized_items) . " items<br>";

// Step 10: Validation
echo "<h2>Step 10: Validation</h2>";
if (!function_exists('validate_work_order')) {
    echo "❌ validate_work_order function not found<br>";
    exit;
}

$errors = validate_work_order($form_data);
if (!empty($errors)) {
    echo "❌ Validation errors found:<br>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo "<p><a href='index.php'>← Back to form</a></p>";
    exit;
} else {
    echo "✅ Validation passed<br>";
}

// Step 11: Calculate totals
echo "<h2>Step 11: Calculate Totals</h2>";
if (!function_exists('calculate_work_order_totals')) {
    echo "❌ calculate_work_order_totals function not found<br>";
    exit;
}

$totals = calculate_work_order_totals($form_data['items']);
echo "✅ Totals calculated:<br>";
echo "<ul>";
echo "<li>Subtotal: ₱" . number_format($totals['subtotal'], 2) . "</li>";
echo "<li>Taxable Amount: ₱" . number_format($totals['taxable_amount'], 2) . "</li>";
echo "<li>Tax Amount: ₱" . number_format($totals['tax_amount'], 2) . "</li>";
echo "<li>Total: ₱" . number_format($totals['total'], 2) . "</li>";
echo "</ul>";

// Step 12: Prepare work order data
echo "<h2>Step 12: Prepare Work Order Data</h2>";
$work_order_data = array(
    'work_order_number' => $form_data['work_order_number'],
    'order_date' => $form_data['order_date'],
    'requested_by' => $form_data['requested_by'],
    'customer_id' => $form_data['customer_id'],
    'department' => $form_data['department'],
    'contact_name' => $form_data['contact_name'],
    'contact_email' => $form_data['contact_email'],
    'street_address' => $form_data['street_address'],
    'city_zip' => $form_data['city_zip'],
    'phone' => $form_data['phone'],
    'bill_name' => $form_data['bill_name'],
    'bill_company' => $form_data['bill_company'],
    'bill_street_address' => $form_data['bill_street_address'],
    'bill_city_zip' => $form_data['bill_city_zip'],
    'bill_phone' => $form_data['bill_phone'],
    'description_of_work' => $form_data['description_of_work'],
    'subtotal' => $totals['subtotal'],
    'taxable_amount' => $totals['taxable_amount'],
    'tax_amount' => $totals['tax_amount'],
    'total' => $totals['total'],
    'payable_to' => $form_data['payable_to'],
    'ip_address' => $client_ip,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
);
echo "✅ Work order data prepared<br>";

// Step 13: Insert work order
echo "<h2>Step 13: Insert Work Order</h2>";
if (!function_exists('insert_work_order')) {
    echo "❌ insert_work_order function not found<br>";
    exit;
}

$work_order_id = insert_work_order($conn, $work_order_data, $form_data['items']);

if ($work_order_id) {
    echo "✅ Work order inserted successfully! ID: " . $work_order_id . "<br>";
    
    // Store work order info in session for success page
    $_SESSION['work_order_number'] = $form_data['work_order_number'];
    $_SESSION['work_order_id'] = $work_order_id;
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h3>🎉 SUCCESS!</h3>";
    echo "<p>Work order created successfully with ID: <strong>" . $work_order_id . "</strong></p>";
    echo "<p>Work order number: <strong>" . $form_data['work_order_number'] . "</strong></p>";
    echo "<p><a href='work_order_success.php'>→ Go to success page</a></p>";
    echo "<p><a href='index.php'>← Create another work order</a></p>";
    echo "</div>";
} else {
    echo "❌ Work order insertion failed<br>";
    echo "<p>Check the error logs for more details.</p>";
    echo "<p><a href='index.php'>← Back to form</a></p>";
}

// Close connection manually
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
    echo "✅ Database connection closed manually<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
h2 { border-bottom: 1px solid #ccc; padding-bottom: 5px; }
ul { margin: 10px 0; }
</style>