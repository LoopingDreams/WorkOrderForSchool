<?php
// All files use sanitization
function sanitize_output($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Order Submission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 mb-5">
    <h2 class="mb-4">Dwayne & Friends Co.</h2>
    <form method="POST" action="receipt.php">
        <!-- Work Order Info -->
        <div class="card mb-4">
            <div class="card-header">Work Order Info</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label for="work_order_id" class="form-label">Work Order #</label>
                    <input type="text" class="form-control" id="work_order_id" name="work_order_id" required>
                </div>
                <div class="col-md-4">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" required>
                </div>
                <div class="col-md-4">
                    <label for="requested_by" class="form-label">Requested By</label>
                    <input type="text" class="form-control" id="requested_by" name="requested_by" required>
                </div>
                <div class="col-md-6">
                    <label for="customer_id" class="form-label">Customer ID</label>
                    <input type="text" class="form-control" id="customer_id" name="customer_id" required>
                </div>
                <div class="col-md-6">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" required>
                </div>
            </div>
        </div>

        <!-- Bill To -->
        <div class="card mb-4">
            <div class="card-header">Bill To</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="bill_to_name" class="form-label">Name</label>
                    <input type="text" class="form-control" name="bill_to_name" required>
                </div>
                <div class="col-md-6">
                    <label for="bill_to_company" class="form-label">Company Name</label>
                    <input type="text" class="form-control" name="bill_to_company">
                </div>
                <div class="col-md-6">
                    <label for="bill_to_address" class="form-label">Street Address</label>
                    <input type="text" class="form-control" name="bill_to_address">
                </div>
                <div class="col-md-6">
                    <label for="bill_to_city_zip" class="form-label">City/ZIP</label>
                    <input type="text" class="form-control" name="bill_to_city_zip">
                </div>
                <div class="col-md-6">
                    <label for="bill_to_phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" name="bill_to_phone">
                   </div> 
				</div>
		</div>
				
			    <!-- Job Details -->
        <div class="card mb-4">
            <div class="card-header">Job Details</div>
            <div class="card-body row g-3">
                <div class="col-md-6">	  
                <div class="col-12">
                    <label for="job_details" class="form-label">Description of Work</label>
                    <textarea class="form-control" name="job_details" rows="3"></textarea>
                      </div>
					</div> 
				</div>
        </div>

        <!-- Work Order Items -->
        <div class="card mb-4">
            <div class="card-header">Work Order Items</div>
            <div class="card-body">
                <div id="items-container">
                    <div class="row g-3 item-row mb-3">
                        <div class="col-md-1">
                            <label class="form-label">Qty</label>
                            <input type="number" class="form-control" name="items[0][qty]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="items[0][description]" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="items[0][taxed]" value="1">
                                <label class="form-check-label">Taxed</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" step="0.01" class="form-control" name="items[0][unit_price]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Price</label>
                            <input type="number" step="0.01" class="form-control" name="items[0][total_price]" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addItem()">Add Item</button>
            </div>
        </div>
		
		<!-- Below  -->
				<div class="card mb-4">
            <div class="card-header">Others</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="subtotal" class="form-label">Subtotal</label>
                    <input type="number" step="0.01" class="form-control" name="subtotal" readonly>
                </div>
                <div class="col-md-4">
                    <label for="taxable_amount" class="form-label">Taxable Amount</label>
                    <input type="number" step="0.01" class="form-control" name="taxable_amount" readonly>
                </div>
                <div class="col-md-4">
                    <label for="tax_rate" class="form-label">Tax Rate</label>
                    <input type="text" class="form-control" name="tax_rate" value="12%" readonly>
                </div>
                <div class="col-md-4">
                    <label for="tax_amount" class="form-label">Tax Amount</label>
                    <input type="number" step="0.01" class="form-control" name="tax_amount" readonly>
                </div>
                <div class="col-md-4">
                    <label for="total" class="form-label">Total</label>
                    <input type="number" step="0.01" class="form-control" name="total" readonly>
                </div>
                <div class="col-md-4">
                    <label for="payable_to" class="form-label">Payable To</label>
                    <input type="text" class="form-control" name="payable_to">
                </div>
                <div class="col-md-6">
                    <label for="signature" class="form-label">Signature</label>
                    <input type="text" class="form-control" name="signature">
                </div>
                <div class="col-md-3">
                    <label for="completed_date" class="form-label">Completed Date</label>
                    <input type="date" class="form-control" name="completed_date">
                </div>
                <div class="col-md-3">
                    <label for="date_signed" class="form-label">Date Signed</label>
                    <input type="date" class="form-control" name="date_signed">
                </div>
            </div>
        </div>	

        <!-- Submit -->
        <button type="submit" class="btn btn-primary">Submit Work Order</button>
    </form>
</div>

<script>
    let itemIndex = 1;

    // Add event listener to calculate totals when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        setupCalculations(document.querySelector('.item-row'));
        calculateAllTotals();
    });

    function calculateTotal(row) {
        const qty = parseFloat(row.querySelector('[name$="[qty]"]').value) || 0;
        const unitPrice = parseFloat(row.querySelector('[name$="[unit_price]"]').value) || 0;
        const totalPriceInput = row.querySelector('[name$="[total_price]"]');
        const total = qty * unitPrice;
        totalPriceInput.value = total.toFixed(2);
    }

    function calculateAllTotals() {
        let subtotal = 0;
        let taxableAmount = 0;
        
        // Get all item rows
        const rows = document.querySelectorAll('.item-row');
        
        rows.forEach(row => {
            const total = parseFloat(row.querySelector('[name$="[total_price]"]').value) || 0;
            const isTaxed = row.querySelector('[name$="[taxed]"]').checked;
            
            subtotal += total;
            if (isTaxed) {
                taxableAmount += total;
            }
        });

        // Update subtotal
        document.querySelector('[name="subtotal"]').value = subtotal.toFixed(2);
        
        // Update taxable amount
        document.querySelector('[name="taxable_amount"]').value = taxableAmount.toFixed(2);
        
        // Calculate tax (12%)
        const taxRate = 0.12;
        const taxAmount = taxableAmount * taxRate;
        document.querySelector('[name="tax_amount"]').value = taxAmount.toFixed(2);
        
        // Calculate total
        const total = subtotal + taxAmount;
        document.querySelector('[name="total"]').value = total.toFixed(2);
    }

    function setupCalculations(row) {
        const qtyInput = row.querySelector('[name$="[qty]"]');
        const unitPriceInput = row.querySelector('[name$="[unit_price]"]');
        const totalPriceInput = row.querySelector('[name$="[total_price]"]');
        const taxedCheckbox = row.querySelector('[name$="[taxed]"]');

        // Make total price readonly
        totalPriceInput.readOnly = true;

        // Add event listeners
        qtyInput.addEventListener('input', () => {
            calculateTotal(row);
            calculateAllTotals();
        });
        
        unitPriceInput.addEventListener('input', () => {
            calculateTotal(row);
            calculateAllTotals();
        });
        
        taxedCheckbox.addEventListener('change', () => {
            calculateAllTotals();
        });
    }

    function addItem() {
        const container = document.getElementById("items-container");
        const row = document.createElement("div");
        row.className = "row g-3 item-row mb-3";
        row.innerHTML = `
            <div class="col-md-1">
                <label class="form-label">Qty</label>
                <input type="number" class="form-control" name="items[${itemIndex}][qty]" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="items[${itemIndex}][description]" required>
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="items[${itemIndex}][taxed]" value="1">
                    <label class="form-check-label">Taxed</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Unit Price</label>
                <input type="number" step="0.01" class="form-control" name="items[${itemIndex}][unit_price]" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Total Price</label>
                <input type="number" step="0.01" class="form-control" name="items[${itemIndex}][total_price]" readonly>
            </div>
        `;
        container.appendChild(row);
        setupCalculations(row);
        itemIndex++;
    }
</script>

</body>
</html>
<?php
// In receipt.php - Receives and displays data
$post_data = $_POST;  // Gets form data
// Displays formatted receipt

// In data.php - Stores in database
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Inserts data into multiple tables
}
?>
