// Work Order Form JavaScript
document.addEventListener('DOMContentLoaded', function() {
    let itemCounter = 1;
    const taxRate = 0.12; // 12% tax rate

    // Initialize form
    initializeForm();
    
    // Event Listeners
    setupEventListeners();

    function initializeForm() {
        // Set current date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('order_date').value = today;
        
        // Initial calculation
        calculateTotals();
        
        // Setup first item row calculations
        setupItemCalculations();
    }

    function setupEventListeners() {
        // Add item button
        document.querySelector('.add-item').addEventListener('click', addItem);
        
        // Same as contact checkbox
        document.getElementById('same_as_contact').addEventListener('change', copyContactToBilling);
        
        // Real-time sync when "same as contact" is checked
        ['contact_name', 'street_address', 'city_zip', 'phone'].forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('input', function() {
                if (document.getElementById('same_as_contact').checked) {
                    copyContactToBilling();
                }
            });
        });
        
        // Form submission
        document.getElementById('workOrderForm').addEventListener('submit', handleFormSubmit);
    }

    function addItem() {
        itemCounter++;
        const itemsContainer = document.querySelector('.items-container');
        
        const newItemRow = document.createElement('div');
        newItemRow.className = 'item-row';
        newItemRow.setAttribute('data-item', itemCounter);
        
        newItemRow.innerHTML = `
            <div class="row align-items-end">
                <div class="col-md-2 mb-3">
                    <label class="form-label">Qty</label>
                    <input type="number" class="form-control item-qty" name="items[${itemCounter}][qty]" min="1" value="1">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control item-description" name="items[${itemCounter}][description]">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Taxed</label>
                    <select class="form-control item-taxed" name="items[${itemCounter}][taxed]">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Unit Price</label>
                    <input type="number" class="form-control item-unit-price" name="items[${itemCounter}][unit_price]" 
                           step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Total Price</label>
                    <input type="number" class="form-control item-total-price" name="items[${itemCounter}][total_price]" 
                           step="0.01" readonly placeholder="0.00">
                </div>
                <div class="col-md-1 mb-3">
                    <button type="button" class="btn btn-danger btn-sm remove-item">×</button>
                </div>
            </div>
        `;
        
        // Insert before the add button
        itemsContainer.appendChild(newItemRow);
        
        // Setup event listeners for new item
        setupItemCalculations();
        updateRemoveButtons();
    }

    function setupItemCalculations() {
        // Remove existing event listeners and add new ones
        document.querySelectorAll('.item-qty, .item-unit-price, .item-taxed').forEach(input => {
            // Remove existing listeners
            input.removeEventListener('input', calculateItemTotal);
            input.removeEventListener('change', calculateItemTotal);
            
            // Add new listeners
            input.addEventListener('input', calculateItemTotal);
            input.addEventListener('change', calculateItemTotal);
        });

        // Remove item buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.removeEventListener('click', removeItem);
            button.addEventListener('click', removeItem);
        });
    }

    function calculateItemTotal(event) {
        const itemRow = event.target.closest('.item-row');
        const qty = parseFloat(itemRow.querySelector('.item-qty').value) || 0;
        const unitPrice = parseFloat(itemRow.querySelector('.item-unit-price').value) || 0;
        const totalPriceField = itemRow.querySelector('.item-total-price');
        
        const totalPrice = qty * unitPrice;
        totalPriceField.value = totalPrice.toFixed(2);
        
        // Recalculate overall totals
        calculateTotals();
    }

    function removeItem(event) {
        const itemRow = event.target.closest('.item-row');
        itemRow.remove();
        calculateTotals();
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        const itemRows = document.querySelectorAll('.item-row');
        const removeButtons = document.querySelectorAll('.remove-item');
        
        removeButtons.forEach((button, index) => {
            button.disabled = itemRows.length <= 1;
        });
    }

    function calculateTotals() {
        let subtotal = 0;
        let taxableAmount = 0;
        
        document.querySelectorAll('.item-row').forEach(row => {
            const totalPrice = parseFloat(row.querySelector('.item-total-price').value) || 0;
            const isTaxed = row.querySelector('.item-taxed').value === '1';
            
            subtotal += totalPrice;
            
            if (isTaxed) {
                taxableAmount += totalPrice;
            }
        });
        
        const taxAmount = taxableAmount * taxRate;
        const total = subtotal + taxAmount;
        
        // Update display
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('taxable_amount').textContent = taxableAmount.toFixed(2);
        document.getElementById('tax_amount').textContent = taxAmount.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
        
        // Update hidden fields
        document.getElementById('subtotal_hidden').value = subtotal.toFixed(2);
        document.getElementById('taxable_amount_hidden').value = taxableAmount.toFixed(2);
        document.getElementById('tax_amount_hidden').value = taxAmount.toFixed(2);
        document.getElementById('total_hidden').value = total.toFixed(2);
    }

    function copyContactToBilling() {
        const checkbox = document.getElementById('same_as_contact');
        
        if (checkbox.checked) {
            // Copy contact info to billing info
            document.getElementById('bill_name').value = document.getElementById('contact_name').value;
            document.getElementById('bill_street_address').value = document.getElementById('street_address').value;
            document.getElementById('bill_city_zip').value = document.getElementById('city_zip').value;
            document.getElementById('bill_phone').value = document.getElementById('phone').value;
            
            // Remove required attribute and disable billing fields
            document.getElementById('bill_name').removeAttribute('required');
            document.getElementById('bill_street_address').removeAttribute('required');
            document.getElementById('bill_city_zip').removeAttribute('required');
            
            // Disable billing fields
            document.getElementById('bill_name').disabled = true;
            document.getElementById('bill_street_address').disabled = true;
            document.getElementById('bill_city_zip').disabled = true;
            document.getElementById('bill_phone').disabled = true;
        } else {
            // Re-enable billing fields and add required attributes
            document.getElementById('bill_name').disabled = false;
            document.getElementById('bill_street_address').disabled = false;
            document.getElementById('bill_city_zip').disabled = false;
            document.getElementById('bill_phone').disabled = false;
            
            // Add required attributes back
            document.getElementById('bill_name').setAttribute('required', 'required');
            document.getElementById('bill_street_address').setAttribute('required', 'required');
            document.getElementById('bill_city_zip').setAttribute('required', 'required');
        }
    }

    function handleFormSubmit(event) {
        console.log("Form submission handler called");
        
        // Re-enable disabled fields before submission so their values are sent
        const sameAsContactChecked = document.getElementById('same_as_contact').checked;
        
        if (sameAsContactChecked) {
            // Temporarily enable disabled fields for form submission
            document.getElementById('bill_name').disabled = false;
            document.getElementById('bill_street_address').disabled = false;
            document.getElementById('bill_city_zip').disabled = false;
            document.getElementById('bill_phone').disabled = false;
            
            // Make sure values are copied one more time
            document.getElementById('bill_name').value = document.getElementById('contact_name').value;
            document.getElementById('bill_street_address').value = document.getElementById('street_address').value;
            document.getElementById('bill_city_zip').value = document.getElementById('city_zip').value;
            document.getElementById('bill_phone').value = document.getElementById('phone').value;
        }
        
        // Validate that at least one item exists
        const itemRows = document.querySelectorAll('.item-row');
        if (itemRows.length === 0) {
            event.preventDefault();
            alert('Please add at least one work order item.');
            return false;
        }
        
        // Validate that items have descriptions and prices
        let hasValidItems = false;
        itemRows.forEach(row => {
            const description = row.querySelector('.item-description').value.trim();
            const unitPrice = parseFloat(row.querySelector('.item-unit-price').value) || 0;
            
            if (description && unitPrice > 0) {
                hasValidItems = true;
            }
        });
        
        if (!hasValidItems) {
            event.preventDefault();
            alert('Please ensure at least one item has a description and unit price greater than 0.');
            return false;
        }
        
        // Show loading state
        const submitButton = document.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.classList.add('loading');
        submitButton.textContent = 'Creating Work Order...';
        
        // Allow form to submit normally
        console.log("Form validation passed, allowing submission");
        return true;
    }

    // Auto-save functionality (optional)
    function autoSave() {
        const formData = new FormData(document.getElementById('workOrderForm'));
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        localStorage.setItem('workOrderDraft', JSON.stringify(data));
    }

    function loadDraft() {
        const draft = localStorage.getItem('workOrderDraft');
        if (draft) {
            const data = JSON.parse(draft);
            
            // Populate form fields with draft data
            Object.keys(data).forEach(key => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = data[key];
                }
            });
            
            calculateTotals();
        }
    }

    // Auto-save every 30 seconds
    setInterval(autoSave, 30000);

    // Format currency inputs
    function formatCurrency(input) {
        let value = parseFloat(input.value) || 0;
        input.value = value.toFixed(2);
    }

    // Add currency formatting to price inputs
    document.querySelectorAll('.item-unit-price').forEach(input => {
        input.addEventListener('blur', function() {
            formatCurrency(this);
        });
    });

    // Phone number formatting
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.length >= 10) {
            if (value.startsWith('63')) {
                // Philippine format: +63 XXX XXX XXXX
                value = '+63 ' + value.substring(2, 5) + ' ' + value.substring(5, 8) + ' ' + value.substring(8, 12);
            } else {
                // Generic format: (XXX) XXX-XXXX
                value = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
            }
        }
        
        input.value = value;
    }

    // Add phone formatting
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('blur', function() {
            formatPhoneNumber(this);
        });
    });

    // Real-time validation feedback
    function addValidationFeedback() {
        document.querySelectorAll('input[required], textarea[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid') && this.value.trim() !== '') {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });
    }

    // Initialize validation feedback
    addValidationFeedback();

    // Print functionality
    function printWorkOrder() {
        window.print();
    }

    // Add print button (optional)
    function addPrintButton() {
        const printButton = document.createElement('button');
        printButton.type = 'button';
        printButton.className = 'btn btn-secondary me-2';
        printButton.innerHTML = '🖨️ Print Preview';
        printButton.addEventListener('click', printWorkOrder);
        
        const submitButton = document.querySelector('button[type="submit"]');
        submitButton.parentNode.insertBefore(printButton, submitButton);
    }

    // Uncomment to add print button
    // addPrintButton();

    // Initial setup
    updateRemoveButtons();
    
    // Load draft if exists (optional)
    // loadDraft();
    
    // Initialize phone number formatting
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    });
    
    // Initialize currency formatting
    document.querySelectorAll('.item-unit-price').forEach(input => {
        input.addEventListener('blur', function() {
            formatCurrency(this);
        });
    });
    
    // Initialize validation feedback
    addValidationFeedback();
});