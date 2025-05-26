<?php
/**
 * Get Work Order API
 * Returns work order details as JSON
 */

include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    json_response(['success' => false, 'message' => 'Invalid work order ID'], 400);
}

$work_order_id = (int)$_GET['id'];

// Get work order details
$work_order = get_work_order($conn, $work_order_id);

if (!$work_order) {
    json_response(['success' => false, 'message' => 'Work order not found'], 404);
}

// Return work order data
json_response([
    'success' => true,
    'work_order' => $work_order
]);
?>