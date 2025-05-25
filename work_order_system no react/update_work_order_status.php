<?php
/**
 * Update Work Order Status API
 * Updates work order status and adds comment
 */

include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(['success' => false, 'message' => 'Invalid JSON data'], 400);
}

// Validate required fields
if (!isset($input['work_order_id']) || !isset($input['status'])) {
    json_response(['success' => false, 'message' => 'Missing required fields'], 400);
}

$work_order_id = (int)$input['work_order_id'];
$new_status = sanitize_input($input['status']);
$comment = sanitize_input($input['comment'] ?? '');

// Validate status
$valid_statuses = ['draft', 'pending', 'in_progress', 'completed', 'cancelled', 'billed'];
if (!in_array($new_status, $valid_statuses)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
}

// Check if work order exists
$work_order = get_work_order($conn, $work_order_id);
if (!$work_order) {
    json_response(['success' => false, 'message' => 'Work order not found'], 404);
}

// Update status
$success = update_work_order_status($conn, $work_order_id, $new_status, $comment);

if ($success) {
    // Log the status change
    $status_message = "Status changed from '{$work_order['status']}' to '{$new_status}'";
    if (!empty($comment)) {
        $status_message .= ". Comment: " . $comment;
    }
    
    add_work_order_comment($conn, $work_order_id, $status_message, 'status_change', 'System');
    
    json_response(['success' => true, 'message' => 'Status updated successfully']);
} else {
    json_response(['success' => false, 'message' => 'Failed to update status'], 500);
}
?>