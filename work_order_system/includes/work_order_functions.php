<?php
/**
 * Work Order Functions - Clean Version
 * All functions properly defined with no variable issues
 */

/**
 * Generate unique work order number
 */
function generate_work_order_number($conn) {
    try {
        // Get prefix from settings or use default
        $prefix = 'WO';
        if (function_exists('get_work_order_setting')) {
            $prefix = get_work_order_setting($conn, 'work_order_prefix', 'WO');
        }
        
        $year = date('Y');
        
        // Get the last work order number for this year
        $sql = "SELECT work_order_number FROM work_orders 
                WHERE work_order_number LIKE ? 
                ORDER BY work_order_number DESC LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $search_pattern = $prefix . $year . '%';
            $stmt->bind_param("s", $search_pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $next_number = 1;
            if ($result && $row = $result->fetch_assoc()) {
                // Extract number from last work order
                $last_number = (int)substr($row['work_order_number'], strlen($prefix . $year));
                $next_number = $last_number + 1;
            }
            
            $stmt->close();
            return $prefix . $year . str_pad($next_number, 4, '0', STR_PAD_LEFT);
        } else {
            // Fallback if query fails
            return $prefix . $year . '0001';
        }
    } catch (Exception $e) {
        // Fallback on any error
        error_log("Error generating work order number: " . $e->getMessage());
        return 'WO' . date('Y') . '0001';
    }
}

/**
 * Validate work order data
 */
function validate_work_order($data) {
    $errors = array();
    
    // Required contact fields
    $required_fields = array(
        'contact_name' => 'Contact name',
        'contact_email' => 'Contact email',
        'street_address' => 'Street address',
        'city_zip' => 'City/ZIP',
        'phone' => 'Phone number',
        'requested_by' => 'Requested by',
        'description_of_work' => 'Description of work'
    );
    
    // Always validate contact fields
    foreach ($required_fields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[] = $label . " is required";
        }
    }
    
    // Validate billing fields (they should have values either from form or copied from contact)
    $billing_fields = array(
        'bill_name' => 'Bill to name',
        'bill_street_address' => 'Bill to street address',
        'bill_city_zip' => 'Bill to city/ZIP'
    );
    
    foreach ($billing_fields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[] = $label . " is required";
        }
    }
    
    // Email validation
    if (!empty($data['contact_email']) && function_exists('validate_email')) {
        if (!validate_email($data['contact_email'])) {
            $errors[] = "Please enter a valid contact email address";
        }
    }
    
    // Validate items
    if (empty($data['items']) || !is_array($data['items'])) {
        $errors[] = "At least one work order item is required";
    } else {
        $valid_items = 0;
        foreach ($data['items'] as $item) {
            if (!empty($item['description']) && (float)($item['unit_price'] ?? 0) > 0) {
                $valid_items++;
            }
        }
        
        if ($valid_items == 0) {
            $errors[] = "At least one item must have a description and unit price greater than 0";
        }
    }
    
    return $errors;
}

/**
 * Calculate work order totals
 */
function calculate_work_order_totals($items) {
    $subtotal = 0;
    $taxable_amount = 0;
    $tax_rate = 0.12; // 12%
    
    if (!is_array($items)) {
        return array(
            'subtotal' => 0,
            'taxable_amount' => 0,
            'tax_amount' => 0,
            'total' => 0
        );
    }
    
    foreach ($items as $item) {
        $qty = (int)($item['qty'] ?? 1);
        $unit_price = (float)($item['unit_price'] ?? 0);
        $is_taxed = ($item['taxed'] ?? '1') === '1';
        
        $total_price = $qty * $unit_price;
        $subtotal += $total_price;
        
        if ($is_taxed) {
            $taxable_amount += $total_price;
        }
    }
    
    $tax_amount = $taxable_amount * $tax_rate;
    $total = $subtotal + $tax_amount;
    
    return array(
        'subtotal' => round($subtotal, 2),
        'taxable_amount' => round($taxable_amount, 2),
        'tax_amount' => round($tax_amount, 2),
        'total' => round($total, 2)
    );
}

/**
 * Insert work order into database
 */
function insert_work_order($conn, $work_order_data, $items_data) {
    // Validate inputs
    if (!$conn || !is_array($work_order_data)) {
        error_log("Invalid parameters passed to insert_work_order");
        return false;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert work order
        $sql = "INSERT INTO work_orders (
            work_order_number, order_date, requested_by, customer_id, department,
            contact_name, contact_email, street_address, city_zip, phone,
            bill_name, bill_company, bill_street_address, bill_city_zip, bill_phone,
            description_of_work, subtotal, taxable_amount, tax_amount, total, payable_to,
            ip_address, user_agent, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssssssssssssssssddddsss",
            $work_order_data['work_order_number'],
            $work_order_data['order_date'],
            $work_order_data['requested_by'],
            $work_order_data['customer_id'],
            $work_order_data['department'],
            $work_order_data['contact_name'],
            $work_order_data['contact_email'],
            $work_order_data['street_address'],
            $work_order_data['city_zip'],
            $work_order_data['phone'],
            $work_order_data['bill_name'],
            $work_order_data['bill_company'],
            $work_order_data['bill_street_address'],
            $work_order_data['bill_city_zip'],
            $work_order_data['bill_phone'],
            $work_order_data['description_of_work'],
            $work_order_data['subtotal'],
            $work_order_data['taxable_amount'],
            $work_order_data['tax_amount'],
            $work_order_data['total'],
            $work_order_data['payable_to'],
            $work_order_data['ip_address'],
            $work_order_data['user_agent']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $work_order_id = $conn->insert_id;
        $stmt->close();
        
        // Insert work order items if provided
        if (!empty($items_data) && is_array($items_data)) {
            $sql = "INSERT INTO work_order_items (
                work_order_id, item_order, quantity, description, is_taxed, unit_price, total_price
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Items prepare failed: " . $conn->error);
            }
            
            foreach ($items_data as $order => $item) {
                if (!is_array($item)) continue;
                
                $qty = (int)($item['qty'] ?? 1);
                $unit_price = (float)($item['unit_price'] ?? 0);
                $total_price = $qty * $unit_price;
                $is_taxed = ($item['taxed'] ?? '1') === '1' ? 1 : 0;
                $description = $item['description'] ?? '';
                
                $stmt->bind_param("iiisidd",
                    $work_order_id,
                    $order,
                    $qty,
                    $description,
                    $is_taxed,
                    $unit_price,
                    $total_price
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Item insert failed: " . $stmt->error);
                }
            }
            
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        return $work_order_id;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        error_log("Work order insertion failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get work order setting from database
 */
function get_work_order_setting($conn, $setting_name, $default_value = '') {
    try {
        // Check if table exists first
        $sql = "SHOW TABLES LIKE 'work_order_settings'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $sql = "SELECT setting_value FROM work_order_settings WHERE setting_name = ?";
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
        }
    } catch (Exception $e) {
        error_log("Error getting work order setting: " . $e->getMessage());
    }
    
    return $default_value;
}

/**
 * Format work order email
 */
function format_work_order_email($work_order_data, $conn = null) {
    $html = '<h2>Work Order Confirmation</h2>';
    $html .= '<p><strong>Work Order #:</strong> ' . htmlspecialchars($work_order_data['work_order_number']) . '</p>';
    $html .= '<p><strong>Customer:</strong> ' . htmlspecialchars($work_order_data['contact_name']) . '</p>';
    $html .= '<p><strong>Email:</strong> ' . htmlspecialchars($work_order_data['contact_email']) . '</p>';
    $html .= '<p><strong>Total:</strong> ₱' . number_format($work_order_data['total'], 2) . '</p>';
    
    return $html;
}

/**
 * Get work order by ID
 */
function get_work_order($conn, $work_order_id) {
    try {
        $sql = "SELECT * FROM work_orders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $work_order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($work_order = $result->fetch_assoc()) {
            // Get items
            $sql = "SELECT * FROM work_order_items WHERE work_order_id = ? ORDER BY item_order";
            $stmt2 = $conn->prepare($sql);
            if ($stmt2) {
                $stmt2->bind_param("i", $work_order_id);
                $stmt2->execute();
                $items_result = $stmt2->get_result();
                
                $work_order['items'] = array();
                while ($item = $items_result->fetch_assoc()) {
                    $work_order['items'][] = $item;
                }
                $stmt2->close();
            }
            
            $stmt->close();
            return $work_order;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error getting work order: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Search work orders
 */
function search_work_orders($conn, $search_term = '', $status = '', $limit = 50, $offset = 0) {
    $where_conditions = array();
    $params = array();
    $types = '';
    
    if (!empty($search_term)) {
        $where_conditions[] = "(work_order_number LIKE ? OR contact_name LIKE ? OR contact_email LIKE ? OR requested_by LIKE ?)";
        $search_pattern = '%' . $search_term . '%';
        $params = array_merge($params, [$search_pattern, $search_pattern, $search_pattern, $search_pattern]);
        $types .= 'ssss';
    }
    
    if (!empty($status)) {
        $where_conditions[] = "status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $sql = "SELECT * FROM work_orders $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $work_orders = array();
    while ($work_order = $result->fetch_assoc()) {
        // Add item count
        $count_sql = "SELECT COUNT(*) as item_count FROM work_order_items WHERE work_order_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        if ($count_stmt) {
            $count_stmt->bind_param("i", $work_order['id']);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            $work_order['item_count'] = $count_row['item_count'];
            $count_stmt->close();
        } else {
            $work_order['item_count'] = 0;
        }
        
        $work_orders[] = $work_order;
    }
    
    $stmt->close();
    return $work_orders;
}

/**
 * Get work order statistics
 */
function get_work_order_stats($conn, $date_from = null, $date_to = null) {
    $where_clause = '';
    $params = array();
    $types = '';
    
    if ($date_from && $date_to) {
        $where_clause = 'WHERE order_date BETWEEN ? AND ?';
        $params = array($date_from, $date_to);
        $types = 'ss';
    }
    
    $sql = "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_amount,
                COALESCE(AVG(total), 0) as average_order_value,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_orders
            FROM work_orders $where_clause";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    return $stats;
}
?>