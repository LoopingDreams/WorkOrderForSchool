<?php
/**
 * Process Work Order Form Submission
 * Handles work order validation, calculation, and database insertion
 */

// Include required files
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Start session
session_start();

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    redirect_with_message('index.php', 'Invalid request method.', 'error');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    redirect_with_message('index.php', 'Security token mismatch. Please try again.', 'error');
}

// Get client IP for tracking
$client_ip = get_client_ip();

// Collect and sanitize form data
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

// Sanitize items array
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

// Check if "same as contact" was selected and copy data if needed
$same_as_contact = isset($_POST['same_as_contact']);
if ($same_as_contact) {
    // Copy contact info to billing info if "same as contact" was checked
    $form_data['bill_name'] = $form_data['contact_name'];
    $form_data['bill_street_address'] = $form_data['street_address'];
    $form_data['bill_city_zip'] = $form_data['city_zip'];
    $form_data['bill_phone'] = $form_data['phone'];
}

// Generate customer ID if not provided
if (empty($form_data['customer_id'])) {
    $form_data['customer_id'] = generate_customer_id($conn, $form_data['contact_name']);
}

// Validate work order data
$errors = validate_work_order($form_data);

// If validation errors exist, redirect back with errors and form data
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    redirect_with_form_data('index.php', $form_data, $error_message, 'error');
}

// Calculate totals
$totals = calculate_work_order_totals($form_data['items']);

// Prepare work order data for database
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

// Insert work order into database
$work_order_id = insert_work_order($conn, $work_order_data, $form_data['items']);

if ($work_order_id) {
    // Log the work order creation
    error_log("Work order created successfully: ID " . $work_order_id . ", Number: " . $form_data['work_order_number']);
    
    // Get the complete work order data for email
    $complete_work_order = get_work_order($conn, $work_order_id);
    
    // Send email notifications
    $admin_email = get_work_order_setting($conn, 'admin_email', '');
    $enable_notifications = get_work_order_setting($conn, 'email_notifications', '1');
    
    if ($enable_notifications === '1' && !empty($admin_email)) {
        // Send admin notification
        $admin_subject = 'New Work Order Received - ' . $form_data['work_order_number'];
        $admin_body = format_work_order_email($complete_work_order, $conn);
        
        if (send_notification_email($admin_email, $admin_subject, $admin_body, $form_data['contact_email'], $form_data['contact_name'])) {
            error_log("Admin notification sent for work order: " . $form_data['work_order_number']);
        } else {
            error_log("Failed to send admin notification for work order: " . $form_data['work_order_number']);
        }
        
        // Send customer confirmation
        $customer_subject = 'Work Order Confirmation - ' . $form_data['work_order_number'];
        $customer_body = format_work_order_email($complete_work_order, $conn);
        
        if (send_notification_email($form_data['contact_email'], $customer_subject, $customer_body, $admin_email)) {
            error_log("Customer confirmation sent for work order: " . $form_data['work_order_number']);
        } else {
            error_log("Failed to send customer confirmation for work order: " . $form_data['work_order_number']);
        }
    }
    
    // Add initial comment
    add_work_order_comment($conn, $work_order_id, 'Work order created and submitted for review.', 'note', 'System', true);
    
    // Clear any stored form data
    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']);
    }
    
    // Redirect to success page with work order number
    $_SESSION['work_order_number'] = $form_data['work_order_number'];
    $_SESSION['work_order_id'] = $work_order_id;
    redirect_with_message('work_order_success.php', 'Work order created successfully!');
    
} else {
    // Database insertion failed
    error_log("Work order insertion failed for: " . $form_data['contact_email']);
    redirect_with_form_data('index.php', $form_data, 'Sorry, there was an error creating your work order. Please try again.', 'error');
}

// Close database connection
$conn->close();

// This should never be reached, but just in case
redirect_with_message('index.php', 'An unexpected error occurred. Please try again.', 'error');
?>