<?php
// data.php (MySQL)

$conn = mysqli_connect("localhost", "root", "", "");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Utility function to sanitize input
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $wo_number = sanitize($_POST['work_order_id']);
    $date = sanitize($_POST['date']);
    $requested_by = sanitize($_POST['requested_by']);
    $customer_id = sanitize($_POST['customer_id']);
    $department = sanitize($_POST['department']);

    // Insert into WorkOrders
    $sql1 = "INSERT INTO workorderinfo (work_order_id, date, requested_by, customer_id, department) VALUES (?, ?, ?, ?, ?)";
    $params1 = [$wo_number, $date, $requested_by, $customer_id, $department];
    $stmt1 = mysqli_query($conn, $sql1);

    // Bill To
    $bill_to_name = sanitize($_POST['bill_to_name']);
    $bill_to_company = sanitize($_POST['bill_to_company']);
    $bill_to_address = sanitize($_POST['bill_to_address']);
    $bill_to_city_zip = sanitize($_POST['bill_to_city_zip']);
    $bill_to_phone = sanitize($_POST['bill_to_phone']);

    // Insert into BillTo
    $sql2 = "INSERT INTO billto (bill_to_name, bill_to_company, bill_to_address, bill_to_city_zip, bill_to_phone) VALUES (?, ?, ?, ?, ?)";
    $params2 = [$bill_to_name, $bill_to_company, $bill_to_address, $bill_to_city_zip, $bill_to_phone];
    $stmt2 = mysqli_query($conn, $sql2);

    // Job Details
    $job_details = sanitize($_POST['job_details']);
    $sql3 = "INSERT INTO jobdetails (job_details) VALUES (?)";
    $params3 = [$job_details];
    $stmt3 = mysqli_query($conn, $sql3);

    // Work Order Items
    foreach ($_POST['items'] as $item) {
        $qty = intval($item['qty']);
        $description = sanitize($item['description']);
        $taxed = isset($item['taxed']) ? 1 : 0;
        $unit_price = floatval($item['unit_price']);
        $total_price = floatval($item['total_price']);

        $subtotal = floatval($_POST['subtotal']);
        $taxable_amount = floatval($_POST['taxable_amount']);
        $tax_rate = floatval($_POST['tax_rate']);
        $tax_amount = floatval($_POST['tax_amount']);
        $total = floatval($_POST['total']);

        $sql4 = "INSERT INTO workorderitems 
                (work_order_id, qty, description, taxed, unit_price, total_price, subtotal, taxable_amount, tax_rate, tax_amount, total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params4 = [$wo_number, $qty, $description, $taxed, $unit_price, $total_price, $subtotal, $taxable_amount, $tax_rate, $tax_amount, $total];

        $stmt4 = mysqli_query($conn, $sql4);
    }

    echo "<p>Work Order submitted successfully!</p>";
}

mysqli_close($conn);
?>
