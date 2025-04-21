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
        function sanitize($data) {
            return htmlspecialchars(stripslashes(trim($data)));
        }

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
</body>
</html>
