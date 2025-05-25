<?php 
// Include files in the correct order
require_once 'config.php';
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Generate Work Order Number (only after all includes are loaded)
$work_order_number = '';
try {
    if (isset($conn) && $conn instanceof mysqli) {
        $work_order_number = generate_work_order_number($conn);
    } else {
        $work_order_number = 'WO' . date('Y') . '0001'; // Fallback
    }
} catch (Exception $e) {
    $work_order_number = 'WO' . date('Y') . '0001'; // Fallback
    error_log("Error generating work order number: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Form</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="form-container" style="max-width: 900px;">
            <!-- Navigation Links - Updated with working links -->
            <div class="nav-links">
                <a href="dashboard.php">dashboard</a>
                <a href="work_orders.php">work orders</a>
                <a href="customers.php">customers</a>
                <a href="index.php" class="active">new order</a>
                <a href="reports.php">reports</a>
                <a href="settings.php">settings</a>
            </div>
            
            <!-- Header -->
            <div class="site-header">
                <h1>Work Order Form</h1>
                <p>Create a new work order for tracking and billing purposes.</p>
            </div>
            
            <!-- Display flash messages -->
            <?php echo display_flash_message(); ?>
            
            <!-- Work Order Form -->
            <form action="process_work_order.php" method="post" id="workOrderForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- Contact Information Section -->
                <div class="form-section">
                    <h3 class="section-title">Contact Information</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="street_address" class="form-label">Street Address *</label>
                        <input type="text" class="form-control" id="street_address" name="street_address" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city_zip" class="form-label">City/ZIP *</label>
                            <input type="text" class="form-control" id="city_zip" name="city_zip" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>
                </div>

                <!-- Work Order Info Section -->
                <div class="form-section">
                    <h3 class="section-title">Work Order Information</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="work_order_number" class="form-label">Work Order #</label>
                            <input type="text" class="form-control" id="work_order_number" name="work_order_number" 
                                   value="<?php echo $work_order_number; ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="order_date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="requested_by" class="form-label">Requested By *</label>
                            <input type="text" class="form-control" id="requested_by" name="requested_by" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="customer_id" class="form-label">Customer ID</label>
                            <input type="text" class="form-control" id="customer_id" name="customer_id">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department">
                        </div>
                    </div>
                </div>

                <!-- Bill To Section -->
                <div class="form-section">
                    <h3 class="section-title">Bill To</h3>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="same_as_contact" name="same_as_contact">
                        <label class="form-check-label" for="same_as_contact">
                            Same as contact information
                        </label>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bill_name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="bill_name" name="bill_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bill_company" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="bill_company" name="bill_company">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="bill_street_address" class="form-label">Street Address *</label>
                        <input type="text" class="form-control" id="bill_street_address" name="bill_street_address" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bill_city_zip" class="form-label">City/ZIP *</label>
                            <input type="text" class="form-control" id="bill_city_zip" name="bill_city_zip" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bill_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="bill_phone" name="bill_phone">
                        </div>
                    </div>
                </div>

                <!-- Job Details Section -->
                <div class="form-section">
                    <h3 class="section-title">Job Details</h3>
                    <div class="mb-3">
                        <label for="description_of_work" class="form-label">Description of Work *</label>
                        <textarea class="form-control" id="description_of_work" name="description_of_work" 
                                  rows="4" required placeholder="Provide detailed description of the work to be performed..."></textarea>
                    </div>
                </div>

                <!-- Work Order Items Section -->
                <div class="form-section">
                    <h3 class="section-title">Work Order Items</h3>
                    <div class="items-container">
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
                                    <label class="form-label">Unit Price</label>
                                    <input type="number" class="form-control item-unit-price" name="items[1][unit_price]" 
                                           step="0.01" min="0" placeholder="0.00">
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
                    </div>
                    <button type="button" class="btn btn-secondary add-item">+ Add Item</button>
                </div>

                <!-- Totals Section -->
                <div class="form-section">
                    <h3 class="section-title">Order Summary</h3>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <div class="totals-container">
                                <div class="row mb-2">
                                    <div class="col-6"><strong>Subtotal:</strong></div>
                                    <div class="col-6 text-end">₱<span id="subtotal">0.00</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">Taxable Amount:</div>
                                    <div class="col-6 text-end">₱<span id="taxable_amount">0.00</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">Tax Rate:</div>
                                    <div class="col-6 text-end">12%</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">Tax Amount:</div>
                                    <div class="col-6 text-end">₱<span id="tax_amount">0.00</span></div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-6"><strong>Total:</strong></div>
                                    <div class="col-6 text-end"><strong>₱<span id="total">0.00</span></strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="payable_to" class="form-label">Payable To</label>
                        <input type="text" class="form-control" id="payable_to" name="payable_to" 
                               value="Your Company Name" placeholder="Company or individual name">
                    </div>
                </div>

                <!-- Hidden fields for totals -->
                <input type="hidden" name="subtotal" id="subtotal_hidden">
                <input type="hidden" name="taxable_amount" id="taxable_amount_hidden">
                <input type="hidden" name="tax_amount" id="tax_amount_hidden">
                <input type="hidden" name="total" id="total_hidden">

                <!-- Submit Button -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Create Work Order</button>
                </div>
                
                <div class="form-footer mt-3">
                    <small class="text-muted">* Required fields</small>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/work-order.js"></script>
</body>
</html>