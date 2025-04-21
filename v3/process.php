<?php
session_start();
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $serverName = "LAPTOP-FV9HE2GD\SQLEXPRESS";
    $connectionInfo = array("Database" => "cpesfd", "TrustServerCertificate" => true);
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if (!$conn) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Process the data
    $data = $_POST;
    // ... [rest of your database processing logic] ...

    sqlsrv_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
            color: #344767;
        }
        .confirmation-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .confirmation-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .details-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .order-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 1rem;
        }
        .order-table th {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: left;
        }
        .order-table td {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
        }
        .status-badge {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="confirmation-header text-center">
    <h1>Order Confirmation</h1>
    <span class="status-badge">Successfully Submitted</span>
</div>

<div class="confirmation-container">
    <?php


    if ($_SERVER["REQUEST_METHOD"] == "POST") {

	
        $department = sanitize($_POST['department']);
        $name = sanitize($_POST['requested_by']);
        $company_name = sanitize($_POST['bill_to_company']);
        $street_address = sanitize($_POST['bill_to_address']);
        $city = sanitize($_POST['bill_to_city_zip']);
        $phone = sanitize($_POST['bill_to_phone']);
        $wo_number = sanitize($_POST['work_order_id']);
        $job_description = sanitize($_POST['job_details']);
        $tax_rate = floatval($_POST['tax_rate']);

        $subtotal = floatval($_POST['subtotal']);
        $tax_amount = floatval($_POST['tax_amount']);
        $total = floatval($_POST['total']);
        ?>
        
        <div class="details-card">
            <h3 class="mb-4">Customer Information</h3>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Department:</strong> <?= $department; ?></p>
                    <p><strong>Name:</strong> <?= $name; ?></p>
                    <p><strong>Company:</strong> <?= $company_name; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Address:</strong> <?= $street_address; ?></p>
                    <p><strong>City/ZIP:</strong> <?= $city; ?></p>
                    <p><strong>Phone:</strong> <?= $phone; ?></p>
                </div>
            </div>
        </div>

        <div class="details-card">
            <h3 class="mb-4">Work Order Details</h3>
            <p><strong>W.O.#:</strong> <?= $wo_number; ?></p>
            <p><strong>Job Description:</strong> <?= $job_description; ?></p>

            <table class="order-table">
                <thead>
                    <tr>
                        <th>QTY</th>
                        <th>Description</th>
                        <th>Taxed</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_POST['items'] as $item): 
                        $qty = intval($item['qty']);
                        $desc = sanitize($item['description']);
                        $taxed = isset($item['taxed']) ? 'Yes' : 'No';
                        $unit_price = floatval($item['unit_price']);
                        $item_total = floatval($item['total_price']);
                    ?>
                    <tr>
                        <td><?= $qty; ?></td>
                        <td><?= $desc; ?></td>
                        <td><?= $taxed; ?></td>
                        <td>$<?= number_format($unit_price, 2); ?></td>
                        <td>$<?= number_format($item_total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td><strong>Subtotal:</strong></td>
                        <td>$<?= number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                        <td><strong>Tax (<?= $tax_rate; ?>%):</strong></td>
                        <td>$<?= number_format($tax_amount, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                        <td><strong>Total:</strong></td>
                        <td>$<?= number_format($total, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="text-center mt-4 mb-5">
            <a href="form.php" class="btn btn-primary">Submit New Order</a>
            <button onclick="window.print()" class="btn btn-outline-primary ms-2">Print Confirmation</button>
        </div>

    <?php } ?>
</div>

<script src="bootstrap.bundle.min.js"></script>
<?php



?>

</body>
</html>
    // Database connection
    $serverName = "LAPTOP-FV9HE2GD\SQLEXPRESS";
    $connectionInfo = array("Database" => "cpesfd", "TrustServerCertificate" => true);
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if (!$conn) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Process form data
    $department = sanitize($_POST['department']);
    $name = sanitize($_POST['requested_by']);
    $company_name = sanitize($_POST['bill_to_company']);
    $street_address = sanitize($_POST['bill_to_address']);
    $city = sanitize($_POST['bill_to_city_zip']);
    $phone = sanitize($_POST['bill_to_phone']);
    $wo_number = sanitize($_POST['work_order_id']);
    $job_description = sanitize($_POST['job_details']);

    // Database operations here
    // ...

    sqlsrv_close($conn);

    // Store data in session
    $_SESSION['process_success'] = true;
    
    // Redirect to receipt page
    header('Location: receipt.php');
    exit;
} else {
    // If not POST request, redirect to registration
    header('Location: Registration.html');
    exit;
}

if (!defined('INTERNAL_ACCESS')) {
    // Prevent direct access to this file
    header('Location: Registration.html');
    exit;
}

// Database connection
$serverName = "LAPTOP-FV9HE2GD\SQLEXPRESS";
$connectionInfo = array("Database" => "cpesfd", "TrustServerCertificate" => true);
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Process the data from $_SESSION['work_order_data'] instead of $_POST
$data = $_SESSION['work_order_data'];

// Utility function already defined at the top of the file

// Process the work order data
$wo_number = sanitize($data['work_order_id']);
$date = sanitize($data['date']);
$requested_by = sanitize($data['requested_by']);
$customer_id = sanitize($data['customer_id']);
$department = sanitize($data['department']);

// Insert into WorkOrders
$sql1 = "INSERT INTO workorderinfo (work_order_id, date, requested_by, customer_id, department) VALUES (?, ?, ?, ?, ?)";
$params1 = [$wo_number, $date, $requested_by, $customer_id, $department];
$stmt1 = sqlsrv_query($conn, $sql1, $params1);

// Bill To
$bill_to_name = sanitize($data['bill_to_name']);
$bill_to_company = sanitize($data['bill_to_company']);
$bill_to_address = sanitize($data['bill_to_address']);
$bill_to_city_zip = sanitize($data['bill_to_city_zip']);
$bill_to_phone = sanitize($data['bill_to_phone']);

// Insert into BillTo
$sql2 = "INSERT INTO billto (bill_to_name, bill_to_company, bill_to_address, bill_to_city_zip, bill_to_phone) VALUES (?, ?, ?, ?, ?)";
$params2 = [$bill_to_name, $bill_to_company, $bill_to_address, $bill_to_city_zip, $bill_to_phone];
$stmt2 = sqlsrv_query($conn, $sql2, $params2);

// Job Details
$job_details = sanitize($data['job_details']);
$sql3 = "INSERT INTO jobdetails (job_details) VALUES (?)";
$params3 = [$job_details];
$stmt3 = sqlsrv_query($conn, $sql3, $params3);

// Work Order Items
foreach ($data['items'] as $item) {
    $qty = intval($item['qty']);
    $description = sanitize($item['description']);
    $taxed = isset($item['taxed']) ? 1 : 0;
    $unit_price = floatval($item['unit_price']);
    $total_price = floatval($item['total_price']);

    $subtotal = floatval($data['subtotal']);
    $taxable_amount = floatval($data['taxable_amount']);
    $tax_rate = floatval($data['tax_rate']);
    $tax_amount = floatval($data['tax_amount']);
    $total = floatval($data['total']);

    $sql4 = "INSERT INTO workorderitems 
            (work_order_id, qty, description, taxed, unit_price, total_price, subtotal, taxable_amount, tax_rate, tax_amount, total) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $params4 = [$wo_number, $qty, $description, $taxed, $unit_price, $total_price, $subtotal, $taxable_amount, $tax_rate, $tax_amount, $total];

    $stmt4 = sqlsrv_query($conn, $sql4, $params4);
}

// Add to your existing POST data processing
$contact_name = sanitize($data['contact_name']);
$contact_phone = sanitize($data['contact_phone']);
$contact_email = sanitize($data['contact_email']);

// Add to your SQL query
$sql = "INSERT INTO contact_info (work_order_id, contact_name, contact_phone, contact_email) 
        VALUES (?, ?, ?, ?)";
$params = [$wo_number, $contact_name, $contact_phone, $contact_email];
$stmt = sqlsrv_query($conn, $sql, $params);

echo "<p>Work Order submitted successfully!</p>";

sqlsrv_close($conn);
?>

<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store all POST data in session
    $_SESSION['work_order_data'] = $_POST;
    
    // Calculate totals
    $subtotal = 0;
    $taxable_amount = 0;
    
    if (!empty($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            $qty = floatval($item['qty']);
            $unit_price = floatval($item['unit_price']);
            $item_total = $qty * $unit_price;
            $subtotal += $item_total;
            
            if (isset($item['taxed']) && $item['taxed']) {
                $taxable_amount += $item_total;
            }
        }
    }
    
    $tax_rate = 0.12; // 12%
    $tax_amount = $taxable_amount * $tax_rate;
    $total = $subtotal + $tax_amount;
    
    // Store calculated values in session
    $_SESSION['work_order_data']['subtotal'] = $subtotal;
    $_SESSION['work_order_data']['taxable_amount'] = $taxable_amount;
    $_SESSION['work_order_data']['tax_amount'] = $tax_amount;
    $_SESSION['work_order_data']['total'] = $total;
    
    // Redirect to receipt page
    header('Location: receipt.php');
    exit;
} else {
    // If someone tries to access this page directly, redirect to registration
    header('Location: Registration.html');
    exit;
}
?>
