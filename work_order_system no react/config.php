<?php
/**
 * Configuration Settings
 * Centralized configuration for the work order system
 */

// Prevent direct access
defined('WORK_ORDER_SYSTEM') or define('WORK_ORDER_SYSTEM', true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'work_order_db');
define('DB_CHARSET', 'utf8mb4');

// System Configuration
define('SYSTEM_NAME', 'Work Order Management System');
define('SYSTEM_VERSION', '1.0');
define('SYSTEM_URL', 'http://localhost/work_order_system/');

// Email Configuration
define('SMTP_ENABLED', false);
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// Security Configuration
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('SESSION_TIMEOUT', 7200); // 2 hours
define('RATE_LIMIT_WINDOW', 3600); // 1 hour
define('RATE_LIMIT_MAX_ATTEMPTS', 5);

// File Upload Configuration (for future use)
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx');
define('UPLOAD_PATH', 'uploads/');

// Tax Configuration
define('DEFAULT_TAX_RATE', 0.12); // 12%
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');

// Pagination
define('DEFAULT_ITEMS_PER_PAGE', 20);
define('MAX_ITEMS_PER_PAGE', 100);

// Logging
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_PATH', 'logs/');

// Development/Production Mode
define('DEBUG_MODE', true); // Set to false in production
define('ERROR_REPORTING_LEVEL', E_ALL); // Set to 0 in production

// Company Default Settings (can be overridden in database)
define('DEFAULT_COMPANY_NAME', 'Your Company Name');
define('DEFAULT_COMPANY_ADDRESS', '123 Business Street, City, State 12345');
define('DEFAULT_COMPANY_PHONE', '(555) 123-4567');
define('DEFAULT_COMPANY_EMAIL', 'info@yourcompany.com');

// Work Order Settings
define('WORK_ORDER_PREFIX', 'WO');
define('AUTO_GENERATE_CUSTOMER_ID', true);
define('EMAIL_NOTIFICATIONS_ENABLED', true);

// Timezone
define('DEFAULT_TIMEZONE', 'Asia/Manila');

// Set timezone
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(DEFAULT_TIMEZONE);
}

// Error reporting based on mode
if (DEBUG_MODE) {
    error_reporting(ERROR_REPORTING_LEVEL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
if (function_exists('ini_set')) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
}

/**
 * Get configuration value
 * @param string $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value or default
 */
function get_config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Check if debug mode is enabled
 * @return bool True if debug mode is enabled
 */
function is_debug_mode() {
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
}

/**
 * Log message based on configuration
 * @param string $level Log level (DEBUG, INFO, WARNING, ERROR)
 * @param string $message Log message
 * @param array $context Additional context data
 */
function log_message($level, $message, $context = []) {
    if (!defined('LOG_ENABLED') || !LOG_ENABLED) return;
    
    $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $current_level = $levels[get_config('LOG_LEVEL', 'INFO')] ?? 1;
    $message_level = $levels[$level] ?? 1;
    
    if ($message_level >= $current_level) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message";
        
        if (!empty($context)) {
            $log_entry .= ' Context: ' . json_encode($context);
        }
        
        $log_entry .= "\n";
        
        $log_path = get_config('LOG_PATH', 'logs/');
        
        // Create logs directory if it doesn't exist
        if (!is_dir($log_path)) {
            mkdir($log_path, 0755, true);
        }
        
        $log_file = $log_path . 'system.log';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Get database configuration as array
 * @return array Database configuration
 */
function get_db_config() {
    return [
        'host' => get_config('DB_HOST', 'localhost'),
        'username' => get_config('DB_USERNAME', 'root'),
        'password' => get_config('DB_PASSWORD', ''),
        'database' => get_config('DB_NAME', 'work_order_db'),
        'charset' => get_config('DB_CHARSET', 'utf8mb4')
    ];
}

/**
 * Check if system is properly configured
 * @return array Array with status and any errors
 */
function check_system_config() {
    $errors = [];
    $warnings = [];
    
    // Check required PHP extensions
    $required_extensions = ['mysqli', 'json', 'session'];
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Required PHP extension missing: $ext";
        }
    }
    
    // Check database configuration
    if (empty(get_config('DB_NAME'))) {
        $errors[] = "Database name is not configured";
    }
    
    // Check log directory
    $log_path = get_config('LOG_PATH', 'logs/');
    if (!is_dir($log_path) && !mkdir($log_path, 0755, true)) {
        $warnings[] = "Cannot create logs directory: $log_path";
    }
    
    // Check if in production mode with debug enabled
    if (!is_debug_mode() && get_config('ERROR_REPORTING_LEVEL', 0) > 0) {
        $warnings[] = "Production mode but error reporting is still enabled";
    }
    
    return [
        'status' => empty($errors) ? 'ok' : 'error',
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

// Perform basic configuration check
$config_check = check_system_config();
if ($config_check['status'] === 'error' && is_debug_mode()) {
    foreach ($config_check['errors'] as $error) {
        error_log("Config Error: $error");
    }
}
?>