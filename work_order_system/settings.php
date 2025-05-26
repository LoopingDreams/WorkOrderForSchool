<?php
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        redirect_with_message('settings.php', 'Security token mismatch. Please try again.', 'error');
    }
    
    $settings_to_update = [
        'company_name' => sanitize_input($_POST['company_name'] ?? ''),
        'company_address' => sanitize_input($_POST['company_address'] ?? ''),
        'company_phone' => sanitize_input($_POST['company_phone'] ?? ''),
        'company_email' => sanitize_input($_POST['company_email'] ?? ''),
        'tax_rate' => (float)($_POST['tax_rate'] ?? 0.12),
        'work_order_prefix' => sanitize_input($_POST['work_order_prefix'] ?? 'WO'),
        'default_payable_to' => sanitize_input($_POST['default_payable_to'] ?? ''),
        'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
        'admin_email' => sanitize_input($_POST['admin_email'] ?? '')
    ];
    
    $success_count = 0;
    foreach ($settings_to_update as $setting_name => $setting_value) {
        // Try to update existing setting
        $sql = "UPDATE work_order_settings SET setting_value = ?, updated_at = NOW() WHERE setting_name = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $setting_value, $setting_name);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_count++;
            } else {
                // If update didn't affect any rows, try insert
                $insert_sql = "INSERT INTO work_order_settings (setting_name, setting_value, description) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt) {
                    $description = ucwords(str_replace('_', ' ', $setting_name));
                    $insert_stmt->bind_param("sss", $setting_name, $setting_value, $description);
                    if ($insert_stmt->execute()) {
                        $success_count++;
                    }
                    $insert_stmt->close();
                }
            }
            $stmt->close();
        }
    }
    
    if ($success_count > 0) {
        redirect_with_message('settings.php', 'Settings updated successfully!', 'success');
    } else {
        redirect_with_message('settings.php', 'No settings were updated. Please check your input.', 'error');
    }
}

// Get current settings
$current_settings = [];
$settings_sql = "SELECT setting_name, setting_value FROM work_order_settings";
$settings_result = $conn->query($settings_sql);
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $current_settings[$row['setting_name']] = $row['setting_value'];
    }
}

// Default values if settings don't exist
$defaults = [
    'company_name' => 'Your Company Name',
    'company_address' => '123 Business Street, City, State 12345',
    'company_phone' => '(555) 123-4567',
    'company_email' => 'info@yourcompany.com',
    'tax_rate' => '0.12',
    'work_order_prefix' => 'WO',
    'default_payable_to' => 'Your Company Name',
    'email_notifications' => '1',
    'admin_email' => 'admin@yourcompany.com'
];

// Merge current settings with defaults
foreach ($defaults as $key => $default_value) {
    if (!isset($current_settings[$key])) {
        $current_settings[$key] = $default_value;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="container py-4" style="max-width: 800px;">
                    <!-- Navigation Links -->
                    <div class="nav-links text-center mb-4">
                        <a href="dashboard.php">dashboard</a>
                        <a href="work_orders.php">work orders</a>
                        <a href="customers.php">customers</a>
                        <a href="index.php">new order</a>
                        <a href="reports.php">reports</a>
                        <a href="settings.php" class="active">settings</a>
                    </div>
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h1 class="h3 mb-0">System Settings</h1>
                        <p class="text-muted">Configure your work order system settings</p>
                    </div>
                    
                    <!-- Display flash messages -->
                    <?php echo display_flash_message(); ?>
                    
                    <!-- Settings Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <!-- Company Information -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-building"></i> Company Information
                            </h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="company_name" class="form-label">Company Name *</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?php echo htmlspecialchars($current_settings['company_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="company_email" class="form-label">Company Email *</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" 
                                           value="<?php echo htmlspecialchars($current_settings['company_email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="company_phone" class="form-label">Company Phone</label>
                                    <input type="tel" class="form-control" id="company_phone" name="company_phone" 
                                           value="<?php echo htmlspecialchars($current_settings['company_phone']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="default_payable_to" class="form-label">Default Payable To</label>
                                    <input type="text" class="form-control" id="default_payable_to" name="default_payable_to" 
                                           value="<?php echo htmlspecialchars($current_settings['default_payable_to']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Company Address</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="2"><?php echo htmlspecialchars($current_settings['company_address']); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Work Order Settings -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-file-earmark-text"></i> Work Order Settings
                            </h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="work_order_prefix" class="form-label">Work Order Prefix</label>
                                    <input type="text" class="form-control" id="work_order_prefix" name="work_order_prefix" 
                                           value="<?php echo htmlspecialchars($current_settings['work_order_prefix']); ?>" maxlength="5">
                                    <div class="form-text">e.g., "WO" will generate WO20240001</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tax_rate" class="form-label">Tax Rate</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                               value="<?php echo htmlspecialchars($current_settings['tax_rate'] * 100); ?>" 
                                               step="0.01" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Enter as percentage (e.g., 12 for 12%)</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Settings -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-envelope"></i> Email Settings
                            </h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="admin_email" class="form-label">Admin Email</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                           value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>">
                                    <div class="form-text">Email address to receive work order notifications</div>
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                               <?php echo $current_settings['email_notifications'] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_notifications">
                                            Enable Email Notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Information (Read-only) -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-info-circle"></i> System Information
                            </h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PHP Version</label>
                                    <input type="text" class="form-control" value="<?php echo phpversion(); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Database</label>
                                    <input type="text" class="form-control" value="MySQL <?php echo $conn->server_info; ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">System Version</label>
                                    <input type="text" class="form-control" value="Work Order System v1.0" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Updated</label>
                                    <input type="text" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-secondary me-md-2" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Settings
                            </button>
                        </div>
                    </form>
                    
                    <!-- Database Actions -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-database"></i> Database Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6>Backup & Maintenance</h6>
                                    <p class="text-muted">Manage your database and system files.</p>
                                    <button type="button" class="btn btn-outline-info btn-sm me-2" onclick="testConnection()">
                                        <i class="bi bi-check-circle"></i> Test Connection
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearLogs()">
                                        <i class="bi bi-trash"></i> Clear Logs
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6>Debug Tools</h6>
                                    <p class="text-muted">Development and troubleshooting tools.</p>
                                    <a href="test_config.php" class="btn btn-outline-secondary btn-sm me-2" target="_blank">
                                        <i class="bi bi-gear"></i> Test Config
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="showSystemInfo()">
                                        <i class="bi bi-info-circle"></i> System Info
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Info Modal -->
    <div class="modal fade" id="systemInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">System Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="systemInfoContent">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        function resetForm() {
            if (confirm('Are you sure you want to reset all settings to their current saved values?')) {
                location.reload();
            }
        }
        
        function testConnection() {
            fetch('test_config.php')
                .then(response => response.text())
                .then(data => {
                    if (data.includes('All Tests Passed')) {
                        alert('✅ Database connection test passed!');
                    } else {
                        alert('❌ Database connection test failed. Check the test config page for details.');
                    }
                })
                .catch(error => {
                    alert('❌ Error testing connection: ' + error.message);
                });
        }
        
        function clearLogs() {
            if (confirm('Are you sure you want to clear all system logs?')) {
                alert('Log clearing functionality would be implemented here.');
            }
        }
        
        function showSystemInfo() {
            const modal = new bootstrap.Modal(document.getElementById('systemInfoModal'));
            const content = document.getElementById('systemInfoContent');
            
            content.innerHTML = `
                <table class="table table-sm">
                    <tr><th>PHP Version</th><td><?php echo phpversion(); ?></td></tr>
                    <tr><th>MySQL Version</th><td><?php echo $conn->server_info; ?></td></tr>
                    <tr><th>Server Software</th><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
                    <tr><th>Document Root</th><td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></td></tr>
                    <tr><th>Current Time</th><td><?php echo date('Y-m-d H:i:s'); ?></td></tr>
                    <tr><th>Memory Limit</th><td><?php echo ini_get('memory_limit'); ?></td></tr>
                    <tr><th>Upload Max Size</th><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
                    <tr><th>Error Reporting</th><td><?php echo error_reporting(); ?></td></tr>
                </table>
            `;
            
            modal.show();
        }
        
        // Convert tax rate from percentage to decimal on form submission
        document.querySelector('form').addEventListener('submit', function() {
            const taxRateInput = document.getElementById('tax_rate');
            taxRateInput.value = (parseFloat(taxRateInput.value) || 0) / 100;
        });
    </script>
</body>
</html>