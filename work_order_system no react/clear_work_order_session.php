<?php
/**
 * Clear Work Order Session Data
 * Simple utility to clean up session data
 */

session_start();

// Clear work order related session data
if (isset($_SESSION['work_order_number'])) {
    unset($_SESSION['work_order_number']);
}

if (isset($_SESSION['work_order_id'])) {
    unset($_SESSION['work_order_id']);
}

if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}

// Return success response
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Session cleared']);
?>