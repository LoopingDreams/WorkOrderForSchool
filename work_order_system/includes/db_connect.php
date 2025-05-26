<?php
/**
 * Database Connection Configuration
 * Simplified and reliable approach
 */

// Database configuration
$db_config = array(
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'work_order_db', // Fixed: was 'form_db'
    'charset' => 'utf8mb4'
);

// Create connection with error handling
try {
    $conn = new mysqli(
        $db_config['host'], 
        $db_config['username'], 
        $db_config['password'], 
        $db_config['database']
    );

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to UTF-8
    if (!$conn->set_charset($db_config['charset'])) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

    // Set SQL mode for better data integrity
    $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

    // Optional: Set timezone (adjust to your timezone)
    $conn->query("SET time_zone = '+08:00'"); // Philippine timezone

} catch (Exception $e) {
    // Log the error (in production, log to a file instead of displaying)
    error_log("Database connection error: " . $e->getMessage());
    
    // In production, show a generic error message
    if (isset($_SERVER['HTTP_HOST'])) {
        // Web request
        http_response_code(500);
        die("Sorry, we're experiencing technical difficulties. Please try again later.");
    } else {
        // Command line
        die("Database connection failed. Check your configuration.\n");
    }
}

/**
 * Function to safely close database connection (if needed manually)
 */
function close_db_connection($connection) {
    if ($connection && $connection instanceof mysqli) {
        @$connection->close();
    }
}

/**
 * Function to test database connection
 */
function test_db_connection($connection) {
    if (!$connection || !($connection instanceof mysqli)) {
        return false;
    }
    
    try {
        return $connection->ping();
    } catch (Error $e) {
        return false;
    }
}
?>