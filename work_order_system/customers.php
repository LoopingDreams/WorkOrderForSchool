<?php 
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include files in the correct order
try {
    require_once 'config.php';
    include 'includes/db_connect.php';
    include 'includes/functions.php';
    
    // Check if work_order_functions.php exists before including
    if (file_exists('includes/work_order_functions.php')) {
        include 'includes/work_order_functions.php';
    }
} catch (Exception $e) {
    die('Error loading configuration files: ' . $e->getMessage());
}

// Check database connection
if (!isset($conn) || !$conn) {
    die('Database connection failed. Please check your configuration.');
}

// Initialize variables
$work_order = null;
$work_order_items = [];
$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'lookup':
                $work_order_number = trim($_POST['work_order_number'] ?? '');
                $customer_email = trim($_POST['customer_email'] ?? '');
                
                if (empty($work_order_number) || empty($customer_email)) {
                    $error_message = 'Please enter both work order number and email address.';
                } else {
                    $result = lookup_work_order($conn, $work_order_number, $customer_email);
                    if ($result['success']) {
                        $work_order = $result['work_order'];
                        $work_order_items = $result['items'];
                    } else {
                        $error_message = $result['message'];
                    }
                }
                break;
                
            case 'update':
                // Verify CSRF token
                if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                    $error_message = 'Invalid security token. Please try again.';
                } else {
                    $result = update_work_order($conn, $_POST);
                    if ($result['success']) {
                        $success_message = 'Work order updated successfully!';
                        // Reload the work order to show updated data
                        $lookup_result = lookup_work_order($conn, $_POST['work_order_number'], $_POST['contact_email']);
                        if ($lookup_result['success']) {
                            $work_order = $lookup_result['work_order'];
                            $work_order_items = $lookup_result['items'];
                        }
                    } else {
                        $error_message = $result['message'];
                        // Keep the form data for editing
                        $work_order = $_POST;
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred: ' . $e->getMessage();
        error_log("Error in customers.php: " . $e->getMessage());
    }
}

// Function to lookup work order by number and email
function lookup_work_order($conn, $work_order_number, $customer_email) {
    try {
        // Check if tables exist first
        $table_check = $conn->query("SHOW TABLES LIKE 'work_orders'");
        if ($table_check->num_rows == 0) {
            return ['success' => false, 'message' => 'Work orders table not found. Please contact administrator.'];
        }
        
        $stmt = $conn->prepare("
            SELECT wo.* 
            FROM work_orders wo 
            WHERE wo.work_order_number = ? AND wo.contact_email = ?
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
        
        $stmt->bind_param("ss", $work_order_number, $customer_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($work_order = $result->fetch_assoc()) {
            // Ensure required fields exist with defaults
            $work_order['order_date'] = $work_order['order_date'] ?? $work_order['created_at'] ?? date('Y-m-d');
            $work_order['subtotal'] = $work_order['subtotal'] ?? 0;
            $work_order['taxable_amount'] = $work_order['taxable_amount'] ?? 0;
            $work_order['tax_amount'] = $work_order['tax_amount'] ?? 0;
            $work_order['total'] = $work_order['total'] ?? 0;
            
            // Get work order items with correct column names
            $items = [];
            $items_check = $conn->query("SHOW TABLES LIKE 'work_order_items'");
            if ($items_check->num_rows > 0) {
                $items_stmt = $conn->prepare("
                    SELECT id, work_order_id, quantity as qty, description, is_taxed as taxed, unit_price, total_price, created_at
                    FROM work_order_items 
                    WHERE work_order_id = ? 
                    ORDER BY item_order ASC, id ASC
                ");
                
                if ($items_stmt) {
                    $items_stmt->bind_param("i", $work_order['id']);
                    $items_stmt->execute();
                    $items_result = $items_stmt->get_result();
                    $items = $items_result->fetch_all(MYSQLI_ASSOC);
                    
                    // Ensure item fields have defaults
                    foreach ($items as &$item) {
                        $item['qty'] = $item['qty'] ?? 1;
                        $item['description'] = $item['description'] ?? '';
                        $item['taxed'] = $item['taxed'] ?? 1;
                        $item['unit_price'] = $item['unit_price'] ?? 0;
                        $item['total_price'] = $item['total_price'] ?? 0;
                    }
                }
            }
            
            return [
                'success' => true,
                'work_order' => $work_order,
                'items' => $items
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Work order not found. Please check your work order number and email address.'
            ];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Function to update work order
function update_work_order($conn, $data) {
    $required_fields = ['work_order_id', 'contact_name', 'contact_email', 'street_address', 'city_zip', 'phone'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => 'All required fields must be filled.'];
        }
    }
    
    // Check if work_order_items table exists and has correct structure
    $table_check = $conn->query("SHOW TABLES LIKE 'work_order_items'");
    if ($table_check->num_rows == 0) {
        return ['success' => false, 'message' => 'Database tables are not properly set up. Please contact administrator.'];
    }
    
    // Start transaction
    try {
        $conn->begin_transaction();
        
        // Prepare variables for bind_param (can't use ?? directly in bind_param)
        $contact_name = $data['contact_name'];
        $contact_email = $data['contact_email'];
        $street_address = $data['street_address'];
        $city_zip = $data['city_zip'];
        $phone = $data['phone'];
        $requested_by = $data['requested_by'] ?? '';
        $department = $data['department'] ?? '';
        $description_of_work = $data['description_of_work'] ?? '';
        $bill_name = $data['bill_name'] ?? '';
        $bill_company = $data['bill_company'] ?? '';
        $bill_street_address = $data['bill_street_address'] ?? '';
        $bill_city_zip = $data['bill_city_zip'] ?? '';
        $bill_phone = $data['bill_phone'] ?? '';
        $subtotal = floatval($data['subtotal'] ?? 0);
        $taxable_amount = floatval($data['taxable_amount'] ?? 0);
        $tax_amount = floatval($data['tax_amount'] ?? 0);
        $total = floatval($data['total'] ?? 0);
        $payable_to = $data['payable_to'] ?? '';
        $work_order_id = intval($data['work_order_id']);
        
        // Update main work order
        $stmt = $conn->prepare("
            UPDATE work_orders SET 
                contact_name = ?, contact_email = ?, street_address = ?, city_zip = ?, phone = ?,
                requested_by = ?, department = ?, description_of_work = ?,
                bill_name = ?, bill_company = ?, bill_street_address = ?, bill_city_zip = ?, bill_phone = ?,
                subtotal = ?, taxable_amount = ?, tax_amount = ?, total = ?, payable_to = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }
        
        $stmt->bind_param("sssssssssssssddddsi",
            $contact_name, $contact_email, $street_address, $city_zip, $phone,
            $requested_by, $department, $description_of_work,
            $bill_name, $bill_company, $bill_street_address, $bill_city_zip, $bill_phone,
            $subtotal, $taxable_amount, $tax_amount, $total, $payable_to,
            $work_order_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update work order: ' . $stmt->error);
        }
        
        // Delete existing items
        $delete_stmt = $conn->prepare("DELETE FROM work_order_items WHERE work_order_id = ?");
        if ($delete_stmt) {
            $delete_stmt->bind_param("i", $work_order_id);
            $delete_stmt->execute();
        }
        
        // Insert updated items using correct column names
        if (!empty($data['items'])) {
            $item_stmt = $conn->prepare("
                INSERT INTO work_order_items (work_order_id, item_order, quantity, description, is_taxed, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($item_stmt) {
                $item_order = 1;
                foreach ($data['items'] as $item) {
                    if (!empty($item['description']) && !empty($item['qty'])) {
                        // Prepare item variables using correct field names
                        $item_quantity = intval($item['qty']);
                        $item_description = $item['description'];
                        $item_is_taxed = intval($item['taxed'] ?? 1);
                        $item_unit_price = floatval($item['unit_price'] ?? 0);
                        $item_total_price = floatval($item['total_price'] ?? 0);
                        
                        $item_stmt->bind_param("iiisidd",
                            $work_order_id, $item_order, $item_quantity, $item_description, 
                            $item_is_taxed, $item_unit_price, $item_total_price
                        );
                        
                        if (!$item_stmt->execute()) {
                            throw new Exception('Failed to insert item: ' . $item_stmt->error);
                        }
                        
                        $item_order++;
                    }
                }
            }
        }
        
        $conn->commit();
        return ['success' => true];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Helper function to safely escape HTML and handle null values
function safe_html($value, $default = '') {
    return htmlspecialchars($value ?? $default);
}

// Helper function to safely format dates
function safe_date($date_value, $format = 'M j, Y', $default = 'Not specified') {
    if (empty($date_value)) {
        return $default;
    }
    
    $timestamp = strtotime($date_value);
    if ($timestamp === false) {
        return $default;
    }
    
    return date($format, $timestamp);
}

// CSRF token functions (simplified)
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal - Edit Work Order</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-container" style="max-width: 900px;">
            <!-- Navigation Links -->
            <div class="nav-links">
                <a href="dashboard.php">dashboard</a>
                <a href="work_orders.php">work orders</a>
                <a href="customers.php" class="active">customer portal</a>
                <a href="index.php">new order</a>
                <a href="reports.php">reports</a>
                <a href="settings.php">settings</a>
            </div>
            
            <!-- Header -->
            <div class="site-header">
                <h1>Customer Portal</h1>
                <p>Look up and edit your work order details.</p>
            </div>
            
            <!-- Display messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo safe_html($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo safe_html($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!$work_order): ?>
                <!-- Work Order Lookup Form -->
                <div class="form-section">
                    <h3 class="section-title">Find Your Work Order</h3>
                    <p class="text-muted mb-4">Enter your work order number and email address to access and edit your order details.</p>
                    
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="lookup">
                        
                        <div class="col-md-6">
                            <label for="work_order_number" class="form-label">Work Order Number *</label>
                            <input type="text" class="form-control" id="work_order_number" name="work_order_number" 
                                   placeholder="e.g., WO20240001" required>
                            <div class="form-text">Find this on your work order document</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="customer_email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                   placeholder="your@email.com" required>
                            <div class="form-text">The email used when placing the order</div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Find My Work Order
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Information Section -->
                <div class="form-section">
                    <div class="card border-info">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-info-circle"></i> How to use this portal</h5>
                            <ul class="card-text mb-0">
                                <li>Enter your work order number (found on your work order document)</li>
                                <li>Enter the email address you used when placing the order</li>
                                <li>Once found, you can edit contact information, job details, and order items</li>
                                <li>Changes will be saved immediately and you'll receive a confirmation</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Work Order Edit Form -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="mb-1">Edit Work Order: <?php echo safe_html($work_order['work_order_number']); ?></h3>
                        <p class="text-muted mb-0">
                            Order Date: <?php echo safe_date($work_order['order_date'] ?? $work_order['created_at'] ?? null); ?>
                        </p>
                    </div>
                    <a href="customers.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Look Up Different Order
                    </a>
                </div>
                
                <form method="post" id="editWorkOrderForm">
                    <?php
                    try {
                        $csrf_token = generate_csrf_token();
                        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">';
                    } catch (Exception $e) {
                        echo '<input type="hidden" name="csrf_token" value="">';
                    }
                    ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="work_order_id" value="<?php echo intval($work_order['id'] ?? 0); ?>">
                    <input type="hidden" name="work_order_number" value="<?php echo safe_html($work_order['work_order_number'] ?? ''); ?>">
                    
                    <!-- Contact Information Section -->
                    <div class="form-section">
                        <h4 class="section-title">Contact Information</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                       value="<?php echo safe_html($work_order['contact_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                       value="<?php echo safe_html($work_order['contact_email']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="street_address" class="form-label">Street Address *</label>
                            <input type="text" class="form-control" id="street_address" name="street_address" 
                                   value="<?php echo safe_html($work_order['street_address']); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city_zip" class="form-label">City/ZIP *</label>
                                <input type="text" class="form-control" id="city_zip" name="city_zip" 
                                       value="<?php echo safe_html($work_order['city_zip']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo safe_html($work_order['phone']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Work Order Info Section -->
                    <div class="form-section">
                        <h4 class="section-title">Work Order Information</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="requested_by" class="form-label">Requested By</label>
                                <input type="text" class="form-control" id="requested_by" name="requested_by" 
                                       value="<?php echo safe_html($work_order['requested_by']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo safe_html($work_order['department']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Order Status</label>
                                <input type="text" class="form-control" value="<?php echo safe_html($work_order['status'] ?? 'Pending'); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Bill To Section -->
                    <div class="form-section">
                        <h4 class="section-title">Billing Information</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bill_name" class="form-label">Billing Name</label>
                                <input type="text" class="form-control" id="bill_name" name="bill_name" 
                                       value="<?php echo safe_html($work_order['bill_name']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="bill_company" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="bill_company" name="bill_company" 
                                       value="<?php echo safe_html($work_order['bill_company']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="bill_street_address" class="form-label">Billing Address</label>
                            <input type="text" class="form-control" id="bill_street_address" name="bill_street_address" 
                                   value="<?php echo safe_html($work_order['bill_street_address']); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bill_city_zip" class="form-label">City/ZIP</label>
                                <input type="text" class="form-control" id="bill_city_zip" name="bill_city_zip" 
                                       value="<?php echo safe_html($work_order['bill_city_zip']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="bill_phone" class="form-label">Billing Phone</label>
                                <input type="tel" class="form-control" id="bill_phone" name="bill_phone" 
                                       value="<?php echo safe_html($work_order['bill_phone']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Job Details Section -->
                    <div class="form-section">
                        <h4 class="section-title">Job Details</h4>
                        <div class="mb-3">
                            <label for="description_of_work" class="form-label">Description of Work</label>
                            <textarea class="form-control" id="description_of_work" name="description_of_work" 
                                      rows="4" placeholder="Provide detailed description of the work to be performed..."><?php echo safe_html($work_order['description_of_work']); ?></textarea>
                        </div>
                    </div>

                    <!-- Work Order Items Section -->
                    <div class="form-section">
                        <h4 class="section-title">Order Items</h4>
                        <div class="items-container">
                            <?php if (!empty($work_order_items)): ?>
                                <?php foreach ($work_order_items as $index => $item): ?>
                                    <div class="item-row" data-item="<?php echo $index + 1; ?>">
                                        <div class="row align-items-end">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Qty</label>
                                                <input type="number" class="form-control item-qty" name="items[<?php echo $index + 1; ?>][qty]" 
                                                       min="1" value="<?php echo intval($item['qty'] ?? 1); ?>">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Description</label>
                                                <input type="text" class="form-control item-description" name="items[<?php echo $index + 1; ?>][description]" 
                                                       value="<?php echo safe_html($item['description'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Taxed</label>
                                                <select class="form-control item-taxed" name="items[<?php echo $index + 1; ?>][taxed]">
                                                    <option value="1" <?php echo (isset($item['taxed']) && $item['taxed']) ? 'selected' : ''; ?>>Yes</option>
                                                    <option value="0" <?php echo (isset($item['taxed']) && !$item['taxed']) ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Unit Price (VAT Inc.)</label>
                                                <input type="number" class="form-control item-unit-price" name="items[<?php echo $index + 1; ?>][unit_price]" 
                                                       step="0.01" min="0" value="<?php echo number_format(floatval($item['unit_price'] ?? 0), 2, '.', ''); ?>" 
                                                       title="Enter price including 12% VAT">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Total Price</label>
                                                <input type="number" class="form-control item-total-price" name="items[<?php echo $index + 1; ?>][total_price]" 
                                                       step="0.01" readonly value="<?php echo number_format(floatval($item['total_price'] ?? 0), 2, '.', ''); ?>">
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                                    <i class="bi bi-trash"></i> ×
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="item-row" data-item="1">
                                    <div class="row align-items-end">
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Qty</label>
                                            <input type="number" class="form-control item-qty" name="items[1][qty]" min="1" value="1">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control item-description" name="items[1][description]">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Taxed</label>
                                            <select class="form-control item-taxed" name="items[1][taxed]">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Unit Price (VAT Inc.)</label>
                                            <input type="number" class="form-control item-unit-price" name="items[1][unit_price]" 
                                                   step="0.01" min="0" placeholder="0.00" title="Enter price including 12% VAT">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Total Price</label>
                                            <input type="number" class="form-control item-total-price" name="items[1][total_price]" 
                                                   step="0.01" readonly placeholder="0.00">
                                        </div>
                                        <div class="col-md-1 mb-3">
                                            <button type="button" class="btn btn-danger btn-sm remove-item" disabled>
                                                <i class="bi bi-trash"></i> ×
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-secondary add-item">+ Add Item</button>
                    </div>

                    <!-- Totals Section -->
                    <div class="form-section">
                        <h4 class="section-title">Order Summary</h4>
                        <div class="row">
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <div class="totals-container">
                                    <div class="row mb-2">
                                        <div class="col-6"><strong>Subtotal (VAT Exclusive):</strong></div>
                                        <div class="col-6 text-end">₱<span id="subtotal"><?php echo number_format(floatval($work_order['subtotal'] ?? 0), 2); ?></span></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6">VAT Amount (12%):</div>
                                        <div class="col-6 text-end">₱<span id="tax_amount"><?php echo number_format(floatval($work_order['tax_amount'] ?? 0), 2); ?></span></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6">Total VAT:</div>
                                        <div class="col-6 text-end">₱<span id="taxable_amount"><?php echo number_format(floatval($work_order['taxable_amount'] ?? 0), 2); ?></span></div>
                                    </div>
                                    <hr>
                                    <div class="row mb-3">
                                        <div class="col-6"><strong>Total Amount (VAT Inclusive):</strong></div>
                                        <div class="col-6 text-end"><strong>₱<span id="total"><?php echo number_format(floatval($work_order['total'] ?? 0), 2); ?></span></strong></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i> 
                                                Prices entered are VAT-inclusive. VAT (12%) is extracted automatically for taxed items.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="payable_to" class="form-label">Payable To</label>
                            <input type="text" class="form-control" id="payable_to" name="payable_to" 
                                   value="<?php echo safe_html($work_order['payable_to']); ?>" placeholder="Company or individual name">
                        </div>
                    </div>

                    <!-- Hidden fields for totals -->
                    <input type="hidden" name="subtotal" id="subtotal_hidden" value="<?php echo floatval($work_order['subtotal'] ?? 0); ?>">
                    <input type="hidden" name="taxable_amount" id="taxable_amount_hidden" value="<?php echo floatval($work_order['taxable_amount'] ?? 0); ?>">
                    <input type="hidden" name="tax_amount" id="tax_amount_hidden" value="<?php echo floatval($work_order['tax_amount'] ?? 0); ?>">
                    <input type="hidden" name="total" id="total_hidden" value="<?php echo floatval($work_order['total'] ?? 0); ?>">

                    <!-- Submit Button -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Update Work Order
                        </button>
                    </div>
                    
                    <div class="form-footer mt-3">
                        <small class="text-muted">* Required fields | Changes will be saved immediately</small>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript for Work Order functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let itemCounter = <?php echo !empty($work_order_items) ? count($work_order_items) : 1; ?>;
            
            // Add item functionality
            document.querySelector('.add-item')?.addEventListener('click', function() {
                itemCounter++;
                const itemsContainer = document.querySelector('.items-container');
                const newItemHTML = `
                    <div class="item-row" data-item="${itemCounter}">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Qty</label>
                                <input type="number" class="form-control item-qty" name="items[${itemCounter}][qty]" min="1" value="1">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control item-description" name="items[${itemCounter}][description]">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Taxed</label>
                                <select class="form-control item-taxed" name="items[${itemCounter}][taxed]">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Unit Price</label>
                                <input type="number" class="form-control item-unit-price" name="items[${itemCounter}][unit_price]" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Total Price</label>
                                <input type="number" class="form-control item-total-price" name="items[${itemCounter}][total_price]" 
                                       step="0.01" readonly placeholder="0.00">
                            </div>
                            <div class="col-md-1 mb-3">
                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                    <i class="bi bi-trash"></i> ×
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                itemsContainer.insertAdjacentHTML('beforeend', newItemHTML);
                updateRemoveButtons();
                attachItemEvents();
            });
            
            // Remove item functionality
            function attachItemEvents() {
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.addEventListener('click', function() {
                        const itemRow = this.closest('.item-row');
                        itemRow.remove();
                        updateRemoveButtons();
                        calculateTotals();
                    });
                });
                
                // Calculate totals when values change
                document.querySelectorAll('.item-qty, .item-unit-price, .item-taxed').forEach(input => {
                    input.addEventListener('input', function() {
                        const itemRow = this.closest('.item-row');
                        const qty = parseFloat(itemRow.querySelector('.item-qty').value) || 0;
                        const unitPrice = parseFloat(itemRow.querySelector('.item-unit-price').value) || 0;
                        const totalPrice = qty * unitPrice;
                        itemRow.querySelector('.item-total-price').value = totalPrice.toFixed(2);
                        calculateTotals();
                    });
                });
            }
            
            // Update remove button states
            function updateRemoveButtons() {
                const itemRows = document.querySelectorAll('.item-row');
                itemRows.forEach((row, index) => {
                    const removeBtn = row.querySelector('.remove-item');
                    removeBtn.disabled = itemRows.length <= 1;
                });
            }
            
            // Calculate order totals (Philippines VAT - tax inclusive pricing)
            function calculateTotals() {
                let subtotalTaxInclusive = 0;
                let subtotalTaxExclusive = 0;
                let totalVatAmount = 0;
                
                const VAT_RATE = 0.12; // 12% VAT in Philippines
                const VAT_MULTIPLIER = 1 + VAT_RATE; // 1.12
                
                document.querySelectorAll('.item-row').forEach(row => {
                    const totalPriceInclusive = parseFloat(row.querySelector('.item-total-price').value) || 0;
                    const isTaxed = row.querySelector('.item-taxed').value === '1';
                    
                    subtotalTaxInclusive += totalPriceInclusive;
                    
                    if (isTaxed && totalPriceInclusive > 0) {
                        // Extract VAT from tax-inclusive price
                        const vatAmount = totalPriceInclusive * (VAT_RATE / VAT_MULTIPLIER);
                        const exclusivePrice = totalPriceInclusive - vatAmount;
                        
                        subtotalTaxExclusive += exclusivePrice;
                        totalVatAmount += vatAmount;
                    } else {
                        // Non-taxed items
                        subtotalTaxExclusive += totalPriceInclusive;
                    }
                });
                
                const total = subtotalTaxInclusive; // Total is the same as inclusive subtotal
                
                // Update display
                document.getElementById('subtotal').textContent = subtotalTaxExclusive.toFixed(2);
                document.getElementById('taxable_amount').textContent = (subtotalTaxInclusive - subtotalTaxExclusive).toFixed(2);
                document.getElementById('tax_amount').textContent = totalVatAmount.toFixed(2);
                document.getElementById('total').textContent = total.toFixed(2);
                
                // Update hidden fields
                document.getElementById('subtotal_hidden').value = subtotalTaxExclusive.toFixed(2);
                document.getElementById('taxable_amount_hidden').value = (subtotalTaxInclusive - subtotalTaxExclusive).toFixed(2);
                document.getElementById('tax_amount_hidden').value = totalVatAmount.toFixed(2);
                document.getElementById('total_hidden').value = total.toFixed(2);
            }
            
            // Initialize events
            attachItemEvents();
            updateRemoveButtons();
            calculateTotals();
        });
    </script>
</body>
</html>