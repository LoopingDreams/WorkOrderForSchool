<?php
/**
 * Print Work Order
 * Print-friendly view of work order
 */

include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid work order ID');
}

$work_order_id = (int)$_GET['id'];

// Get work order details
$work_order = get_work_order($conn, $work_order_id);

if (!$work_order) {
    die('Work order not found');
}

// Get company settings
$company_name = get_work_order_setting($conn, 'company_name', 'Your Company Name');
$company_address = get_work_order_setting($conn, 'company_address', '');
$company_phone = get_work_order_setting($conn, 'company_phone', '');
$company_email = get_work_order_setting($conn, 'company_email', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order <?php echo htmlspecialchars($work_order['work_order_number']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .company-info {
            font-size: 12px;
            color: #666;
        }
        
        .work-order-title {
            font-size: 28px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .work-order-number {
            font-size: 18px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .two-column {
            display: flex;
            gap: 30px;
        }
        
        .column {
            flex: 1;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        
        .value {
            flex: 1;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .totals-section {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
        }
        
        .totals-table .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .status-badge {
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .section {
                page-break-inside: avoid;
            }
            
            .totals-section {
                page-break-inside: avoid;
            }
        }
        
        @media screen {
            .print-button {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
            }
            
            .print-button:hover {
                background: #0056b3;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>
    
    <!-- Header -->
    <div class="header">
        <div class="company-name"><?php echo htmlspecialchars($company_name); ?></div>
        <div class="company-info">
            <?php if (!empty($company_address)): ?>
                <?php echo htmlspecialchars($company_address); ?><br>
            <?php endif; ?>
            <?php if (!empty($company_phone)): ?>
                Phone: <?php echo htmlspecialchars($company_phone); ?>
            <?php endif; ?>
            <?php if (!empty($company_email)): ?>
                | Email: <?php echo htmlspecialchars($company_email); ?>
            <?php endif; ?>
        </div>
        
        <div class="work-order-title">WORK ORDER</div>
        <div class="work-order-number">
            #<?php echo htmlspecialchars($work_order['work_order_number']); ?>
        </div>
    </div>
    
    <!-- Work Order Information -->
    <div class="section">
        <div class="section-title">Work Order Information</div>
        <div class="two-column">
            <div class="column">
                <div class="info-row">
                    <span class="label">Date:</span>
                    <span class="value"><?php echo date('F j, Y', strtotime($work_order['order_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Requested By:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['requested_by']); ?></span>
                </div>
                <?php if (!empty($work_order['customer_id'])): ?>
                <div class="info-row">
                    <span class="label">Customer ID:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['customer_id']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="column">
                <div class="info-row">
                    <span class="label">Status:</span>
                    <span class="value">
                        <span class="status-badge"><?php echo ucfirst(str_replace('_', ' ', $work_order['status'])); ?></span>
                    </span>
                </div>
                <?php if (!empty($work_order['department'])): ?>
                <div class="info-row">
                    <span class="label">Department:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['department']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="label">Created:</span>
                    <span class="value"><?php echo date('F j, Y g:i A', strtotime($work_order['created_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Information -->
    <div class="section">
        <div class="section-title">Contact Information</div>
        <div class="two-column">
            <div class="column">
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['contact_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['contact_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['phone']); ?></span>
                </div>
            </div>
            <div class="column">
                <div class="info-row">
                    <span class="label">Address:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($work_order['street_address']); ?><br>
                        <?php echo htmlspecialchars($work_order['city_zip']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bill To Information -->
    <div class="section">
        <div class="section-title">Bill To</div>
        <div class="two-column">
            <div class="column">
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['bill_name']); ?></span>
                </div>
                <?php if (!empty($work_order['bill_company'])): ?>
                <div class="info-row">
                    <span class="label">Company:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['bill_company']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($work_order['bill_phone'])): ?>
                <div class="info-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($work_order['bill_phone']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="column">
                <div class="info-row">
                    <span class="label">Address:</span>
                    <span class="value">
                        <?php echo htmlspecialchars($work_order['bill_street_address']); ?><br>
                        <?php echo htmlspecialchars($work_order['bill_city_zip']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Job Description -->
    <div class="section">
        <div class="section-title">Job Description</div>
        <p><?php echo nl2br(htmlspecialchars($work_order['description_of_work'])); ?></p>
    </div>
    
    <!-- Work Order Items -->
    <?php if (!empty($work_order['items'])): ?>
    <div class="section">
        <div class="section-title">Work Order Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Taxed</th>
                    <th class="text-right">Total Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($work_order['items'] as $item): ?>
                <tr>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td><?php echo $item['is_taxed'] ? 'Yes' : 'No'; ?></td>
                    <td class="text-right">₱<?php echo number_format($item['total_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Totals -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">₱<?php echo number_format($work_order['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td>Taxable Amount:</td>
                <td class="text-right">₱<?php echo number_format($work_order['taxable_amount'], 2); ?></td>
            </tr>
            <tr>
                <td>Tax (12%):</td>
                <td class="text-right">₱<?php echo number_format($work_order['tax_amount'], 2); ?></td>
            </tr>
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right">₱<?php echo number_format($work_order['total'], 2); ?></td>
            </tr>
        </table>
    </div>
    
    <div style="clear: both;"></div>
    
    <!-- Payment Information -->
    <div class="section" style="margin-top: 40px;">
        <div class="section-title">Payment Information</div>
        <div class="info-row">
            <span class="label">Payable To:</span>
            <span class="value"><?php echo htmlspecialchars($work_order['payable_to'] ?: $company_name); ?></span>
        </div>
    </div>
    
    <!-- Terms and Conditions -->
    <div class="section">
        <div class="section-title">Terms and Conditions</div>
        <ul style="font-size: 12px; color: #666;">
            <li>Payment is due within 30 days of completion unless otherwise specified.</li>
            <li>Any changes to the scope of work must be approved in writing and may result in additional charges.</li>
            <li>Materials and labor are guaranteed for 90 days from completion date.</li>
            <li>Customer is responsible for providing access to work areas and ensuring utilities are available.</li>
            <li>Additional charges may apply for work performed outside normal business hours.</li>
        </ul>
    </div>
    
    <!-- Signatures -->
    <div class="section" style="margin-top: 50px;">
        <div class="two-column">
            <div class="column">
                <div style="border-bottom: 1px solid #333; margin-bottom: 5px; height: 40px;"></div>
                <div style="text-align: center; font-size: 12px;">
                    <strong>Customer Signature</strong><br>
                    Date: _______________
                </div>
            </div>
            <div class="column">
                <div style="border-bottom: 1px solid #333; margin-bottom: 5px; height: 40px;"></div>
                <div style="text-align: center; font-size: 12px;">
                    <strong>Authorized Representative</strong><br>
                    Date: _______________
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>For questions about this work order, please contact us at <?php echo htmlspecialchars($company_phone); ?> or <?php echo htmlspecialchars($company_email); ?></p>
        <p style="margin-top: 20px; font-size: 10px;">
            Work Order #<?php echo htmlspecialchars($work_order['work_order_number']); ?> - 
            Generated on <?php echo date('F j, Y \a\t g:i A'); ?>
        </p>
    </div>
    
    <script>
        // Auto-print when opened in new window
        if (window.location !== window.parent.location) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>