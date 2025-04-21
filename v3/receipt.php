<?php
session_start();

// Check if we have POST data or session data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store POST data in session and use it
    $_SESSION['work_order_data'] = $_POST;
} else if (!isset($_SESSION['work_order_data'])) {
    // If no POST or session data, redirect to registration
    header('Location: Registration.html');
    exit;
}

// Get data from session
$post_data = array_merge([
    'bill_to_address' => '',
    'bill_to_city_zip' => '',
    'bill_to_phone' => '',
    'work_order_id' => '',
    'date' => date('m/d/Y'),
    'requested_by' => '',
    'customer_id' => '',
    'department' => '',
    'bill_to_name' => '',
    'bill_to_company' => '',
    'job_details' => '',
    'items' => array(),
    'subtotal' => 0,
    'taxable_amount' => 0,
    'tax_rate' => '12%',
    'tax_amount' => 0,
    'total' => 0,
    'payable_to' => 'Company Name',
], $_SESSION['work_order_data']);

// Calculate totals if not already calculated
$subtotal = 0;
$taxable_amount = 0;

if (!empty($post_data['items'])) {
    foreach ($post_data['items'] as $item) {
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

$post_data['subtotal'] = $subtotal;
$post_data['taxable_amount'] = $taxable_amount;
$post_data['tax_amount'] = $tax_amount;
$post_data['total'] = $total;

// Clear the session data after retrieving it
unset($_SESSION['work_order_data']);

// Sanitize data function
function sanitize_output($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px auto;
            max-width: 800px;
            color: #333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .company-info {
            color: #4771b2;
        }
        .company-info h2 {
            margin: 0 0 10px 0;
        }
        .company-details p {
            margin: 3px 0;
            color: #666;
        }
        .work-order-box {
            text-align: right;
        }
        .work-order-title {
            color: #666;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .blue-header {
            background: #4771b2;
            color: white;
            padding: 5px;
            text-align: center;
            margin-bottom: 2px;
        }
        .gray-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            background: #e0e0e0;
            margin: 20px 0;
        }
        .gray-section div {
            padding: 10px;
            border-right: 1px solid #fff;
        }
        .gray-section div:last-child {
            border-right: none;
        }
        .section-box {
            display: grid;
            grid-template-columns: 250px 1fr;
            border: 1px solid #999;
            margin: 20px 0;
        }
        .section-title {
            background: #4771b2;
            color: white;
            padding: 8px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #4771b2;
            color: white;
            padding: 8px;
            text-align: left;
        }
        .items-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .comments {
            background: #e0e0e0;
            padding: 10px;
            float: left;
            width: 60%;
        }
        .totals {
            float: right;
            width: 35%;
        }
        .totals-table {
            width: 100%;
        }
        .totals-table td {
            padding: 5px;
        }
        .total-row {
            background: #4771b2;
            color: white;
        }
        .signature-section {
            clear: both;
            margin-top: 40px;
            text-align: center;
        }
        .signature-line {
            display: inline-block;
            width: 200px;
            margin: 20px 40px;
            border-bottom: 1px solid #000;
        }
        .work-order-header {
            text-align: right;
            color: #666;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .bill-to-box, .job-details-box {
            background: white;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
        
        .blue-title {
            background: #4771b2;
            color: white;
            padding: 8px 15px;
        }
        
        .box-content {
            padding: 15px;
        }
        
        .items-table {
            border: 1px solid #ccc;
            width: 100%;
            margin: 20px 0;
        }
        
        .items-table th {
            background: #4771b2;
            color: white;
            padding: 8px 15px;
            text-align: left;
        }
        
        .items-table td {
            padding: 8px 15px;
            border: 1px solid #ddd;
        }
        
        .other-comments {
            background: #e9ecef;
            padding: 15px;
            width: 60%;
            float: left;
        }
        
        .totals-section {
            width: 35%;
            float: right;
        }
        
        .total-row {
            background: #4771b2;
            color: white;
            font-weight: bold;
        }
        
        .print-button {
            background: #4771b2;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            body {
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h2><?php echo sanitize_output($post_data['payable_to']); ?></h2>
            <p><?php echo sanitize_output($post_data['bill_to_address']); ?></p>
            <p><?php echo sanitize_output($post_data['bill_to_city_zip']); ?></p>
            <p>Phone: <?php echo sanitize_output($post_data['bill_to_phone']); ?></p>
            <p>Fax: (000) 000-0000</p>
            <p>www.companyname.com</p>
        </div>
        <div>
            <div class="work-order-header">WORK ORDER</div>
            <div class="blue-header">
                WORK ORDER #<br>
                <?php echo sanitize_output($post_data['work_order_id']); ?>
            </div>
            <div class="blue-header">
                DATE<br>
                <?php echo sanitize_output($post_data['date']); ?>
            </div>
        </div>
    </div>

    <div class="gray-section">
        <div>
            <strong>REQUESTED BY</strong><br>
            <?php echo sanitize_output($post_data['requested_by']); ?>
        </div>
        <div>
            <strong>CUSTOMER ID</strong><br>
            <?php echo sanitize_output($post_data['customer_id']); ?>
        </div>
        <div>
            <strong>DEPARTMENT</strong><br>
            <?php echo sanitize_output($post_data['department']); ?>
        </div>
    </div>

    <div class="bill-to-box">
        <div class="blue-title">BILL TO</div>
        <div class="box-content">
            <p><?php echo sanitize_output($post_data['bill_to_name']); ?></p>
            <p><?php echo sanitize_output($post_data['bill_to_company']); ?></p>
            <p><?php echo sanitize_output($post_data['bill_to_address']); ?></p>
            <p><?php echo sanitize_output($post_data['bill_to_city_zip']); ?></p>
            <p><?php echo sanitize_output($post_data['bill_to_phone']); ?></p>
        </div>
    </div>

    <div class="job-details-box">
        <div class="blue-title">JOB DETAILS</div>
        <div class="box-content">
            <p><?php echo nl2br(sanitize_output($post_data['job_details'])); ?></p>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>QTY</th>
                <th>DESCRIPTION</th>
                <th>TAXED</th>
                <th>UNIT PRICE</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($post_data['items'] as $item): ?>
            <tr>
                <td><?php echo sanitize_output($item['qty']); ?></td>
                <td><?php echo sanitize_output($item['description']); ?></td>
                <td style="text-align: center"><?php echo isset($item['taxed']) && $item['taxed'] ? 'X' : ''; ?></td>
                <td>$<?php echo number_format(floatval($item['unit_price']), 2); ?></td>
                <td>$<?php echo number_format(floatval($item['qty']) * floatval($item['unit_price']), 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for($i = 0; $i < 8; $i++): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>-</td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="other-comments">
        <strong>OTHER COMMENTS</strong>
        <ol>
            <li>Total payment due 30 days after completion of work</li>
            <li>Please refer to the W/O # in all your correspondence</li>
            <li>Please send correspondence regarding this work order to:<br>
            [Name, Phone #, Email]</li>
        </ol>
    </div>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>SUBTOTAL</td>
                <td>$<?php echo number_format($post_data['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td>TAXABLE</td>
                <td>$<?php echo number_format($post_data['taxable_amount'], 2); ?></td>
            </tr>
            <tr>
                <td>TAX RATE</td>
                <td><?php echo sanitize_output($post_data['tax_rate']); ?></td>
            </tr>
            <tr>
                <td>TAX</td>
                <td>$<?php echo number_format($post_data['tax_amount'], 2); ?></td>
            </tr>
            <tr>
                <td>S & H</td>
                <td>$ -</td>
            </tr>
            <tr>
                <td>OTHER</td>
                <td>$ -</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL</td>
                <td>$<?php echo number_format($post_data['total'], 2); ?></td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="signature-section">
        <p>Make checks payable to: <?php echo sanitize_output($post_data['payable_to']); ?></p>
        <p>I agree that all work has been performed to my satisfaction.</p>
        <div style="margin: 30px 0;">
            <div class="signature-line">Signature</div>
            <div class="signature-line">Date</div>
        </div>
        <p style="font-style: italic;">Thank You For Your Business!</p>
    </div>

    <button onclick="window.print()" class="print-button">Print Work Order</button>
</body>
</html>