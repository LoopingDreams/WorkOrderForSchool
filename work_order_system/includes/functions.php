<?php
/**
 * Complete Functions Library
 * Updated with work order functionality and fixed session management
 */

/**
 * Sanitize input data
 * Removes whitespace, backslashes, and converts special characters to HTML entities
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 * Uses PHP's built-in email validation filter
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate required fields
 * Returns array of error messages for empty required fields
 */
function validate_required($fields) {
    $errors = array();
    
    foreach ($fields as $field_name => $field_value) {
        if (empty(trim($field_value))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field_name)) . " is required";
        }
    }
    
    return $errors;
}

/**
 * Generate CSRF token for form security
 * Creates a random token and stores it in session
 */
function generate_csrf_token() {
    // Use safe session start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * Compares submitted token with session token
 */
function verify_csrf_token($token) {
    // Use safe session start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Clean phone number format
 * Removes all non-numeric characters except +, -, (, ), and spaces
 */
function clean_phone_number($phone) {
    return preg_replace('/[^0-9+\-\(\)\s]/', '', $phone);
}

/**
 * Validate phone number (basic validation)
 * Checks if phone number has at least 10 digits
 */
function validate_phone($phone) {
    $cleaned_phone = clean_phone_number($phone);
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $cleaned_phone);
}

/**
 * Validate name field
 * Checks if name contains only letters, spaces, apostrophes, and hyphens
 */
function validate_name($name) {
    return preg_match("/^[a-zA-Z\s\'\-]+$/", $name);
}

/**
 * Validate message length
 * Checks if message is within acceptable length limits
 */
function validate_message_length($message, $min_length = 10, $max_length = 1000) {
    $length = strlen(trim($message));
    return $length >= $min_length && $length <= $max_length;
}

/**
 * Generate success message HTML
 * Creates Bootstrap success alert
 */
function show_success_message($message) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Generate error message HTML
 * Creates Bootstrap error alert
 */
function show_error_message($message) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Generate multiple error messages HTML
 * Creates Bootstrap error alert with list of errors
 */
function show_error_messages($errors) {
    if (empty($errors)) return '';
    
    $html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">';
    
    foreach ($errors as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    
    $html .= '</ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    
    return $html;
}

/**
 * Log form submissions (optional - for debugging)
 * Logs submission data to a file for monitoring
 */
function log_submission($data) {
    // Create logs directory if it doesn't exist
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $log_data = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
    );
    
    $log_entry = json_encode($log_data) . "\n";
    file_put_contents('logs/submissions.log', $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Send email notification (basic setup)
 * Sends HTML email notification
 */
function send_notification_email($to, $subject, $message, $from_email = '', $from_name = '') {
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    
    if (!empty($from_email)) {
        $from_header = !empty($from_name) ? "$from_name <$from_email>" : $from_email;
        $headers[] = 'From: ' . $from_header;
        $headers[] = 'Reply-To: ' . $from_email;
    }
    
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate customer ID
 * Creates customer ID from name initials + random number
 */
function generate_customer_id($conn, $contact_name) {
    try {
        // Create customer ID from name initials + random number
        $words = explode(' ', trim($contact_name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // Limit to 3 characters
        $initials = substr($initials, 0, 3);
        if (strlen($initials) < 2) {
            $initials = 'CUS'; // Default if name is too short
        }
        
        // Add random number
        $random_number = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $customer_id = $initials . $random_number;
        
        // Check if it already exists (if customers table exists)
        $sql = "SHOW TABLES LIKE 'customers'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $sql = "SELECT id FROM customers WHERE customer_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $customer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Generate a new one recursively
                    $stmt->close();
                    return generate_customer_id($conn, $contact_name);
                }
                $stmt->close();
            }
        }
        
        return $customer_id;
    } catch (Exception $e) {
        error_log("Error generating customer ID: " . $e->getMessage());
        return 'CUS' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Add comment to work order
 * Adds a comment/note to a work order for tracking
 */
function add_work_order_comment($conn, $work_order_id, $comment, $type = 'note', $added_by = '', $is_internal = true) {
    try {
        // Check if table exists
        $sql = "SHOW TABLES LIKE 'work_order_comments'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $sql = "INSERT INTO work_order_comments (work_order_id, comment_type, comment, added_by, is_internal) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isssi", $work_order_id, $type, $comment, $added_by, $is_internal);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
    } catch (Exception $e) {
        error_log("Error adding work order comment: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Redirect with message
 * Redirects to specified URL with flash message
 */
function redirect_with_message($url, $message, $type = 'success') {
    // Use safe session start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    
    header("Location: " . $url);
    exit();
}

/**
 * Redirect with form data
 * Redirects back to form with preserved data on validation errors
 */
function redirect_with_form_data($url, $form_data, $message, $type = 'error') {
    // Use safe session start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    $_SESSION['form_data'] = $form_data;
    
    header("Location: " . $url);
    exit();
}

/**
 * Display flash message
 * Shows and clears flash messages from session
 */
function display_flash_message() {
    // Use safe session start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        // Clear the message
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        if ($type === 'success') {
            return show_success_message($message);
        } else {
            return show_error_message($message);
        }
    }
    
    return '';
}

/**
 * Database helper - Insert form data using prepared statements
 * Safely inserts data into database using prepared statements
 */
function insert_form_data($conn, $table, $data) {
    $fields = array_keys($data);
    $values = array_values($data);
    
    // Prepare placeholders
    $placeholders = str_repeat('?,', count($values) - 1) . '?';
    
    // Build query
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $fields) . "`) VALUES ($placeholders)";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Create type string (assuming all strings for simplicity)
        $types = str_repeat('s', count($values));
        
        // Bind parameters
        $stmt->bind_param($types, ...$values);
        
        // Execute
        $result = $stmt->execute();
        
        if ($result) {
            $insert_id = $conn->insert_id;
            $stmt->close();
            return $insert_id;
        } else {
            $error = $stmt->error;
            $stmt->close();
            error_log("Database insert error: " . $error);
            return false;
        }
    }
    
    error_log("Database prepare statement failed: " . $conn->error);
    return false;
}

/**
 * Rate limiting helper
 * Prevents spam by limiting submissions per IP
 */
function check_rate_limit($conn, $ip_address, $time_window = 3600, $max_attempts = 5) {
    $sql = "SELECT COUNT(*) as attempt_count FROM work_orders 
            WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $ip_address, $time_window);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['attempt_count'] < $max_attempts;
    }
    
    return true; // Allow if can't check
}

/**
 * Validate contact form data (for simple contact forms)
 */
function validate_contact_form($data) {
    $errors = array();
    
    // Required fields validation
    $required_fields = array(
        'name' => $data['name'],
        'email' => $data['email'],
        'message' => $data['message']
    );
    
    $errors = array_merge($errors, validate_required($required_fields));
    
    // Name validation
    if (!empty($data['name']) && !validate_name($data['name'])) {
        $errors[] = "Name can only contain letters, spaces, apostrophes, and hyphens";
    }
    
    // Email validation
    if (!empty($data['email']) && !validate_email($data['email'])) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Message length validation
    if (!empty($data['message']) && !validate_message_length($data['message'])) {
        $errors[] = "Message must be between 10 and 1000 characters";
    }
    
    // Subject length validation (optional field)
    if (!empty($data['subject']) && strlen($data['subject']) > 200) {
        $errors[] = "Subject must be less than 200 characters";
    }
    
    return $errors;
}

/**
 * Get client IP address
 * Safely gets the real IP address of the client
 */
function get_client_ip() {
    $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Clean and format output
 * Safely outputs data for display
 */
function safe_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if request is AJAX
 * Determines if the request was made via AJAX
 */
function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * JSON response helper
 * Sends JSON response for AJAX requests
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Format auto-response email for form submitter
 * Creates a personalized thank you email
 */
function format_auto_response_email($form_data) {
    $html = '
    <html>
    <head>
        <title>Thank you for contacting us</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #6c63ff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .message-box { background-color: white; padding: 20px; border-left: 4px solid #6c63ff; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Thank you for reaching out!</h2>
            </div>
            <div class="content">
                <p>Hello ' . htmlspecialchars($form_data['name']) . ',</p>
                
                <p>Thank you for contacting me through my website. I have received your message and will get back to you as soon as possible.</p>
                
                <div class="message-box">
                    <strong>Your message:</strong><br>
                    ' . nl2br(htmlspecialchars($form_data['message'])) . '
                </div>
                
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>I typically respond within 24-48 hours</li>
                    <li>For urgent matters, please mention it in your subject line</li>
                    <li>I\'ll reply directly to the email address you provided: ' . htmlspecialchars($form_data['email']) . '</li>
                </ul>
                
                <div class="footer">
                    <p>Best regards,<br>
                    <strong>Your Name</strong><br>
                    <small>Web Developer</small></p>
                    
                    <p><small>This is an automated response. Please do not reply to this email.</small></p>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Get form settings from database
 */
function get_form_setting($conn, $setting_name, $default_value = '') {
    $sql = "SELECT setting_value FROM form_settings WHERE setting_name = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $setting_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['setting_value'];
        }
        $stmt->close();
    }
    
    return $default_value;
}

/**
 * Update form setting in database
 */
function update_form_setting($conn, $setting_name, $setting_value) {
    $sql = "UPDATE form_settings SET setting_value = ?, updated_at = NOW() WHERE setting_name = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ss", $setting_value, $setting_name);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Check if form is enabled
 */
function is_form_enabled($conn) {
    return get_form_setting($conn, 'form_enabled', '1') === '1';
}

/**
 * Enhanced rate limiting with database settings
 */
function check_rate_limit_enhanced($conn, $ip_address) {
    $time_window = (int)get_form_setting($conn, 'rate_limit_window', '3600');
    $max_attempts = (int)get_form_setting($conn, 'rate_limit_max_attempts', '5');
    
    return check_rate_limit($conn, $ip_address, $time_window, $max_attempts);
}

/**
 * Format admin email for form submissions
 */
function format_admin_email($form_data) {
    $html = '
    <html>
    <head>
        <title>New Contact Form Submission</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #6c63ff; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #555; }
            .value { margin-top: 5px; padding: 10px; background-color: white; border-left: 3px solid #6c63ff; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>New Contact Form Submission</h2>
            </div>
            <div class="content">
                <div class="field">
                    <div class="label">Name:</div>
                    <div class="value">' . htmlspecialchars($form_data['name']) . '</div>
                </div>
                <div class="field">
                    <div class="label">Email:</div>
                    <div class="value">' . htmlspecialchars($form_data['email']) . '</div>
                </div>';
    
    if (!empty($form_data['subject'])) {
        $html .= '
                <div class="field">
                    <div class="label">Subject:</div>
                    <div class="value">' . htmlspecialchars($form_data['subject']) . '</div>
                </div>';
    }
    
    $html .= '
                <div class="field">
                    <div class="label">Message:</div>
                    <div class="value">' . nl2br(htmlspecialchars($form_data['message'])) . '</div>
                </div>
                <div class="field">
                    <div class="label">Submitted:</div>
                    <div class="value">' . date('F j, Y \a\t g:i A') . '</div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>