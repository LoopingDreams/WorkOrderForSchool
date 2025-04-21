<?php
// Initialize variables with default values
$post_data = array(
    'bill_to_address' => '',
    'bill_to_city_zip' => '',
    'bill_to_phone' => '',
    'work_order_id' => '',
    'date' => '',
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
    'payable_to' => '',
    'signature' => '',
    'date_signed' => ''
);

// Merge POST data with defaults
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($post_data as $key => $value) {
        if (isset($_POST[$key])) {
            $post_data[$key] = $_POST[$key];
        }
    }
}

// Sanitize data function
function sanitize_output($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Order Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 14px; }
        .work-order-header { background-color: #f8f9fa; padding: 20px; border-bottom: 2px solid #0d6efd; }
        .blue-header { background-color: #0d6efd; color: white; padding: 8px; }
        .gray-header { background-color: #f0f0f0; padding: 8px; }
        .border-bottom-gray { border-bottom: 1px solid #dee2e6; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="card">
            <div class="card-body">
                <!-- Company Header -->
                <div class="row work-order-header">
                    <div class="col-md-6">
                        <h4>Dwayne & Friends Co.</h4>
                        <p class="mb-0"><?php echo sanitize_output($post_data['bill_to_address']); ?></p>
                        <p class="mb-0"><?php echo sanitize_output($post_data['bill_to_city_zip']); ?></p>
                        <p class="mb-0">Phone: <?php echo sanitize_output($post_data['bill_to_phone']); ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="blue-header mb-2">
                            <strong>WORK ORDER #</strong><br>
                            <?php echo sanitize_output($post_data['work_order_id']); ?>
                        </div>
                        <div class="blue-header">
                            <strong>DATE</strong><br>
                            <?php echo sanitize_output($post_data['date']); ?>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="row mt-4">
                    <div class="col-md-4 gray-header">
                        <strong>REQUESTED BY</strong><br>
                        <?php echo sanitize_output($post_data['requested_by']); ?>
                    </div>
                    <div class="col-md-4 gray-header">
                        <strong>CUSTOMER ID</strong><br>
                        <?php echo sanitize_output($post_data['customer_id']); ?>
                    </div>
                    <div class="col-md-4 gray-header">
                        <strong>DEPARTMENT</strong><br>
                        <?php echo sanitize_output($post_data['department']); ?>
                    </div>
                </div>

                <!-- Bill To Section -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="blue-header">BILL TO</div>
                        <div class="p-2">
                            <p class="mb-1"><?php echo sanitize_output($post_data['bill_to_name']); ?></p>
                            <p class="mb-1"><?php echo sanitize_output($post_data['bill_to_company']); ?></p>
                            <p class="mb-1"><?php echo sanitize_output($post_data['bill_to_address']); ?></p>
                            <p class="mb-1"><?php echo sanitize_output($post_data['bill_to_city_zip']); ?></p>
                            <p class="mb-1"><?php echo sanitize_output($post_data['bill_to_phone']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="blue-header">JOB DETAILS</div>
                        <div class="p-2">
                            <?php echo nl2br(sanitize_output($post_data['job_details'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mt-4">
                    <table class="table">
                        <thead class="blue-header">
                            <tr>
                                <th>QTY</th>
                                <th>DESCRIPTION</th>
                                <th>TAXED</th>
                                <th>UNIT PRICE</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($post_data['items'])): ?>
                                <?php foreach ($post_data['items'] as $item): ?>
                                <tr>
                                    <td><?php echo sanitize_output($item['qty'] ?? ''); ?></td>
                                    <td><?php echo sanitize_output($item['description'] ?? ''); ?></td>
                                    <td><?php echo isset($item['taxed']) ? 'X' : ''; ?></td>
                                    <td>$<?php echo number_format(floatval($item['unit_price'] ?? 0), 2); ?></td>
                                    <td>$<?php echo number_format(floatval($item['total_price'] ?? 0), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-sm">
                            <tr>
                                <td>SUBTOTAL</td>
                                <td class="text-end">$<?php echo number_format(floatval($post_data['subtotal']), 2); ?></td>
                            </tr>
                            <tr>
                                <td>TAXABLE</td>
                                <td class="text-end">$<?php echo number_format(floatval($post_data['taxable_amount']), 2); ?></td>
                            </tr>
                            <tr>
                                <td>TAX RATE</td>
                                <td class="text-end"><?php echo sanitize_output($post_data['tax_rate']); ?></td>
                            </tr>
                            <tr>
                                <td>TAX</td>
                                <td class="text-end">$<?php echo number_format(floatval($post_data['tax_amount']), 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>TOTAL</strong></td>
                                <td class="text-end"><strong>$<?php echo number_format(floatval($post_data['total']), 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <p>Make checks payable to: <?php echo sanitize_output($post_data['payable_to']); ?></p>
                        <div class="mt-4">
                            <div class="border-bottom-gray"><?php echo sanitize_output($post_data['signature']); ?></div>
                            <div class="d-flex justify-content-between mt-2">
                                <span>Signature</span>
                                <span>Date: <?php echo sanitize_output($post_data['date_signed']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Print Button -->
                <div class="text-center mt-4">
                    <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>