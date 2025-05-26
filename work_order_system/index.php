<?php 
// Start session FIRST - before any output
session_start();

// Include files in the correct order
require_once 'config.php';
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';

// Generate Work Order Number (only after all includes are loaded)
$work_order_number = '';
try {
    if (isset($conn) && $conn instanceof mysqli) {
        $work_order_number = generate_work_order_number($conn);
    } else {
        $work_order_number = 'WO' . date('Y') . '0001'; // Fallback
    }
} catch (Exception $e) {
    $work_order_number = 'WO' . date('Y') . '0001'; // Fallback
    error_log("Error generating work order number: " . $e->getMessage());
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Form</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- React Enhancement Styles -->
    <style>
        .react-enhanced {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        
        .react-enhanced::before {
            content: '⚛️ Hi :3';
            position: absolute;
            top: -10px;
            right: 20px;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 0 0 8px 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .vat-info-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .item-row-react {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            animation: slideInUp 0.3s ease-out;
        }
        
        .item-row-react:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideOutDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }
        
        .removing {
            animation: slideOutDown 0.3s ease-out forwards;
        }
        
        .add-item-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .add-item-btn:hover {
            background: linear-gradient(135deg, #5a52d5 0%, #6c5ce7 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .remove-item-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .remove-item-btn:hover {
            background: linear-gradient(135deg, #ff5252 0%, #e53e3e 100%);
            transform: scale(1.1);
            color: white;
        }
        
        .remove-item-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .totals-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .totals-row:last-child {
            border-bottom: none;
            font-size: 1.2rem;
            font-weight: 700;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid rgba(255,255,255,0.3);
        }
        
        .vat-breakdown {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
        }
        
        .vat-breakdown h6 {
            margin-bottom: 10px;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .react-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }
        
        .vat-inclusive-label {
            background: #10b981;
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container" style="max-width: 900px;">
            <!-- Navigation Links -->
            <div class="nav-links">
                <a href="dashboard.php">dashboard</a>
                <a href="work_orders.php">work orders</a>
                <a href="customers.php">customers</a>
                <a href="index.php" class="active">new order</a>
                <a href="reports.php">reports</a>
                <a href="settings.php">settings</a>
            </div>
            
            <!-- Header -->
            <div class="site-header">
                <h1>Work Order Form</h1>
                <p>Create a new work order for tracking and billing purposes.</p>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Hi :3 </strong> Enjoy real-time calculations with Philippines VAT (12%) system.
                </div>
            </div>
            
            <!-- Display flash messages -->
            <?php echo display_flash_message(); ?>
            
            <!-- Work Order Form -->
            <form action="process_work_order.php" method="post" id="workOrderForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- Contact Information Section -->
                <div class="form-section">
                    <h3 class="section-title">Contact Information</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="street_address" class="form-label">Street Address *</label>
                        <input type="text" class="form-control" id="street_address" name="street_address" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city_zip" class="form-label">City/ZIP *</label>
                            <input type="text" class="form-control" id="city_zip" name="city_zip" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>
                </div>

                <!-- Work Order Info Section -->
                <div class="form-section">
                    <h3 class="section-title">Work Order Information</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="work_order_number" class="form-label">Work Order #</label>
                            <input type="text" class="form-control" id="work_order_number" name="work_order_number" 
                                   value="<?php echo $work_order_number; ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="order_date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="requested_by" class="form-label">Requested By *</label>
                            <input type="text" class="form-control" id="requested_by" name="requested_by" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="customer_id" class="form-label">Customer ID</label>
                            <input type="text" class="form-control" id="customer_id" name="customer_id">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department">
                        </div>
                    </div>
                </div>

                <!-- Bill To Section -->
                <div class="form-section">
                    <h3 class="section-title">Bill To</h3>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="same_as_contact" name="same_as_contact">
                        <label class="form-check-label" for="same_as_contact">
                            Same as contact information
                        </label>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bill_name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="bill_name" name="bill_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bill_company" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="bill_company" name="bill_company">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="bill_street_address" class="form-label">Street Address *</label>
                        <input type="text" class="form-control" id="bill_street_address" name="bill_street_address" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bill_city_zip" class="form-label">City/ZIP *</label>
                            <input type="text" class="form-control" id="bill_city_zip" name="bill_city_zip" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bill_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="bill_phone" name="bill_phone">
                        </div>
                    </div>
                </div>

                <!-- Job Details Section -->
                <div class="form-section">
                    <h3 class="section-title">Job Details</h3>
                    <div class="mb-3">
                        <label for="description_of_work" class="form-label">Description of Work *</label>
                        <textarea class="form-control" id="description_of_work" name="description_of_work" 
                                  rows="4" required placeholder="Provide detailed description of the work to be performed..."></textarea>
                    </div>
                </div>

                <!-- React-Enhanced Work Order Items Section -->
                <div class="form-section">
                    <h3 class="section-title">Work Order Items</h3>
                    <div class="Owo whats this">
                        <div class="vat-info-badge">
                            <i class="bi bi-info-circle"></i>
                            Philippines VAT System - Enter prices including 12% VAT
                        </div>
                        <div id="react-work-order-items">
                            <!-- React component will render here -->
                            <div class="text-center">
                                <div class="loading-spinner"></div>
                                Loading React component...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payable To Section -->
                <div class="form-section">
                    <h3 class="section-title">Payment Information</h3>
                    <div class="mb-3">
                        <label for="payable_to" class="form-label">Payable To</label>
                        <input type="text" class="form-control" id="payable_to" name="payable_to" 
                               value="Your Company Name" placeholder="Company or individual name">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="bi bi-check-lg me-2"></i>Create Work Order
                    </button>
                </div>
                
                <div class="form-footer mt-3">
                    <small class="text-muted">* Required fields</small>
                </div>
            </form>
        </div>
    </div>
    
    <!-- React Status Indicator -->
    <div class="react-status" id="reactStatus" style="display: none;">
        <i class="bi bi-check-circle me-2"></i>React + PH VAT Active
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- React CDN -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    
    <!-- React Work Order Items Component with Philippines VAT -->
    <script type="text/babel">
        const { useState, useEffect } = React;
        
        const WorkOrderItems = () => {
            const [items, setItems] = useState([
                { 
                    id: 1, 
                    quantity: 1, 
                    description: '', 
                    taxed: true, 
                    unitPriceInclusive: 0,
                    totalPriceInclusive: 0
                }
            ]);
            
            const [totals, setTotals] = useState({
                subtotalExclusive: 0,
                totalVAT: 0,
                totalInclusive: 0
            });
            
            const [isCalculating, setIsCalculating] = useState(false);
            
            // Philippines VAT constants
            const VAT_RATE = 0.12; // 12%
            const VAT_MULTIPLIER = 1 + VAT_RATE; // 1.12
            
            // Calculate totals whenever items change
            useEffect(() => {
                setIsCalculating(true);
                const timer = setTimeout(() => {
                    calculateTotals();
                    setIsCalculating(false);
                }, 100);
                
                return () => clearTimeout(timer);
            }, [items]);
            
            const calculateTotals = () => {
                let subtotalInclusive = 0;
                let subtotalExclusive = 0;
                let totalVATAmount = 0;
                
                items.forEach(item => {
                    const itemTotalInclusive = item.quantity * item.unitPriceInclusive;
                    subtotalInclusive += itemTotalInclusive;
                    
                    if (item.taxed && itemTotalInclusive > 0) {
                        // Extract VAT from tax-inclusive price
                        const vatAmount = itemTotalInclusive * (VAT_RATE / VAT_MULTIPLIER);
                        const exclusivePrice = itemTotalInclusive - vatAmount;
                        
                        subtotalExclusive += exclusivePrice;
                        totalVATAmount += vatAmount;
                    } else {
                        // Non-taxed items
                        subtotalExclusive += itemTotalInclusive;
                    }
                });
                
                setTotals({
                    subtotalExclusive: subtotalExclusive,
                    totalVAT: totalVATAmount,
                    totalInclusive: subtotalInclusive
                });
            };
            
            const addItem = () => {
                const newItem = {
                    id: Math.max(...items.map(i => i.id), 0) + 1,
                    quantity: 1,
                    description: '',
                    taxed: true,
                    unitPriceInclusive: 0,
                    totalPriceInclusive: 0
                };
                setItems([...items, newItem]);
            };
            
            const removeItem = (id) => {
                if (items.length > 1) {
                    // Add removing animation class
                    const itemElement = document.querySelector(`[data-item-id="${id}"]`);
                    if (itemElement) {
                        itemElement.classList.add('removing');
                        setTimeout(() => {
                            setItems(items.filter(item => item.id !== id));
                        }, 300);
                    } else {
                        setItems(items.filter(item => item.id !== id));
                    }
                }
            };
            
            const updateItem = (id, field, value) => {
                setItems(items.map(item => {
                    if (item.id === id) {
                        const updatedItem = { ...item, [field]: value };
                        // Calculate total price for this item
                        updatedItem.totalPriceInclusive = updatedItem.quantity * updatedItem.unitPriceInclusive;
                        return updatedItem;
                    }
                    return item;
                }));
            };
            
            const formatCurrency = (amount) => {
                return new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                    minimumFractionDigits: 2
                }).format(amount);
            };
            
            const getVATBreakdown = (priceInclusive, isTaxed) => {
                if (!isTaxed || priceInclusive <= 0) {
                    return {
                        exclusive: priceInclusive,
                        vat: 0,
                        inclusive: priceInclusive
                    };
                }
                
                const vatAmount = priceInclusive * (VAT_RATE / VAT_MULTIPLIER);
                const exclusivePrice = priceInclusive - vatAmount;
                
                return {
                    exclusive: exclusivePrice,
                    vat: vatAmount,
                    inclusive: priceInclusive
                };
            };
            
            return (
                <div className="work-order-items-react">
                    <div className="mb-4">
                        <h5 className="mb-3">
                            <i className="bi bi-list-ul me-2"></i>
                            Line Items
                            <span className="badge bg-primary ms-2">{items.length}</span>
                        </h5>
                        
                        {items.map((item, index) => {
                            const breakdown = getVATBreakdown(item.totalPriceInclusive, item.taxed);
                            
                            return (
                                <div 
                                    key={item.id} 
                                    className="item-row-react"
                                    data-item-id={item.id}
                                >
                                    <div className="row align-items-end">
                                        <div className="col-md-2 mb-3">
                                            <label className="form-label fw-semibold">Quantity</label>
                                            <input 
                                                type="number" 
                                                className="form-control"
                                                value={item.quantity}
                                                onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 0)}
                                                min="1"
                                                placeholder="1"
                                            />
                                        </div>
                                        
                                        <div className="col-md-3 mb-3">
                                            <label className="form-label fw-semibold">Description</label>
                                            <input 
                                                type="text" 
                                                className="form-control"
                                                value={item.description}
                                                onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                placeholder="Item description..."
                                            />
                                        </div>
                                        
                                        <div className="col-md-1 mb-3">
                                            <label className="form-label fw-semibold">VAT</label>
                                            <select 
                                                className="form-control"
                                                value={item.taxed ? '1' : '0'}
                                                onChange={(e) => updateItem(item.id, 'taxed', e.target.value === '1')}
                                            >
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                        
                                        <div className="col-md-2 mb-3">
                                            <label className="form-label fw-semibold">
                                                Unit Price
                                                <span className="vat-inclusive-label">VAT Inc.</span>
                                            </label>
                                            <div className="input-group">
                                                <span className="input-group-text">₱</span>
                                                <input 
                                                    type="number" 
                                                    className="form-control"
                                                    value={item.unitPriceInclusive}
                                                    onChange={(e) => updateItem(item.id, 'unitPriceInclusive', parseFloat(e.target.value) || 0)}
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    title="Enter price including 12% VAT"
                                                />
                                            </div>
                                        </div>
                                        
                                        <div className="col-md-3 mb-3">
                                            <label className="form-label fw-semibold">Total Price</label>
                                            <div className="input-group">
                                                <span className="input-group-text">₱</span>
                                                <input 
                                                    type="text" 
                                                    className="form-control fw-bold"
                                                    value={breakdown.inclusive.toFixed(2)}
                                                    readOnly
                                                    style={{backgroundColor: '#f8f9fa'}}
                                                />
                                            </div>
                                            {item.taxed && breakdown.vat > 0 && (
                                                <small className="text-muted">
                                                    VAT: ₱{breakdown.vat.toFixed(2)} | Ex-VAT: ₱{breakdown.exclusive.toFixed(2)}
                                                </small>
                                            )}
                                        </div>
                                        
                                        <div className="col-md-1 mb-3 text-center">
                                            <label className="form-label">&nbsp;</label>
                                            <div>
                                                <button 
                                                    type="button" 
                                                    className="remove-item-btn"
                                                    onClick={() => removeItem(item.id)}
                                                    disabled={items.length === 1}
                                                    title={items.length === 1 ? "Cannot remove the last item" : "Remove item"}
                                                >
                                                    <i className="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                        
                        <div className="text-center mt-3">
                            <button 
                                type="button" 
                                className="add-item-btn"
                                onClick={addItem}
                            >
                                <i className="bi bi-plus-circle"></i>
                                Add New Item
                            </button>
                        </div>
                    </div>
                    
                    {/* Philippines VAT Totals Summary */}
                    <div className="totals-card">
                        <h5 className="mb-3">
                            <i className="bi bi-calculator me-2"></i>
                            Philippines VAT Summary
                            {isCalculating && <span className="loading-spinner ms-2"></span>}
                        </h5>
                        
                        <div className="vat-breakdown">
                            <h6><i className="bi bi-info-circle me-1"></i>VAT Breakdown (12%)</h6>
                            <div className="totals-row" style={{borderBottom: 'none', padding: '4px 0'}}>
                                <span>Subtotal (VAT Exclusive):</span>
                                <span>{formatCurrency(totals.subtotalExclusive)}</span>
                            </div>
                            <div className="totals-row" style={{borderBottom: 'none', padding: '4px 0'}}>
                                <span>VAT Amount (12%):</span>
                                <span>{formatCurrency(totals.totalVAT)}</span>
                            </div>
                        </div>
                        
                        <div className="totals-row">
                            <span><strong>Total Amount (VAT Inclusive):</strong></span>
                            <span><strong>{formatCurrency(totals.totalInclusive)}</strong></span>
                        </div>
                        
                        <div className="mt-3">
                            <small style={{opacity: 0.9}}>
                                <i className="bi bi-lightbulb me-1"></i>
                                All prices entered include 12% VAT. VAT is calculated automatically.
                            </small>
                        </div>
                    </div>
                    
                    {/* Hidden inputs for PHP form submission */}
                    <div style={{display: 'none'}}>
                        {items.map((item, index) => {
                            const breakdown = getVATBreakdown(item.totalPriceInclusive, item.taxed);
                            return (
                                <div key={item.id}>
                                    <input name={`items[${index + 1}][qty]`} value={item.quantity} readOnly />
                                    <input name={`items[${index + 1}][description]`} value={item.description} readOnly />
                                    <input name={`items[${index + 1}][taxed]`} value={item.taxed ? '1' : '0'} readOnly />
                                    <input name={`items[${index + 1}][unit_price]`} value={item.unitPriceInclusive.toFixed(2)} readOnly />
                                    <input name={`items[${index + 1}][total_price]`} value={item.totalPriceInclusive.toFixed(2)} readOnly />
                                </div>
                            );
                        })}
                        <input name="subtotal" value={totals.subtotalExclusive.toFixed(2)} readOnly />
                        <input name="taxable_amount" value={totals.totalVAT.toFixed(2)} readOnly />
                        <input name="tax_amount" value={totals.totalVAT.toFixed(2)} readOnly />
                        <input name="total" value={totals.totalInclusive.toFixed(2)} readOnly />
                    </div>
                </div>
            );
        };
        
        // Render the React component
        ReactDOM.render(<WorkOrderItems />, document.getElementById('react-work-order-items'));
        
        // Show React status indicator
        setTimeout(() => {
            document.getElementById('reactStatus').style.display = 'block';
        }, 1000);
    </script>
    
    <!-- Enhanced Form Handling -->
    <script>
        // Same as contact functionality (enhanced)
        document.getElementById('same_as_contact').addEventListener('change', function() {
            const isChecked = this.checked;
            const fields = [
                ['contact_name', 'bill_name'],
                ['street_address', 'bill_street_address'],
                ['city_zip', 'bill_city_zip'],
                ['phone', 'bill_phone']
            ];
            
            fields.forEach(([source, target]) => {
                const sourceField = document.getElementById(source);
                const targetField = document.getElementById(target);
                
                if (isChecked) {
                    targetField.value = sourceField.value;
                    targetField.style.backgroundColor = '#f8f9fa';
                    targetField.setAttribute('readonly', true);
                } else {
                    targetField.style.backgroundColor = '';
                    targetField.removeAttribute('readonly');
                }
            });
            
            // Real-time sync when checkbox is checked
            if (isChecked) {
                fields.forEach(([source, target]) => {
                    const sourceField = document.getElementById(source);
                    const targetField = document.getElementById(target);
                    
                    sourceField.addEventListener('input', function() {
                        if (document.getElementById('same_as_contact').checked) {
                            targetField.value = this.value;
                        }
                    });
                });
            }
        });
        
        // Enhanced form submission
        document.getElementById('workOrderForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            
            // Add loading state
            submitBtn.innerHTML = '<span class="loading-spinner"></span>Processing...';
            submitBtn.disabled = true;
            
            // Form validation can be added here
            // For now, we'll let it submit normally
            
            // Re-enable button after 5 seconds (in case of errors)
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Create Work Order';
                submitBtn.disabled = false;
            }, 5000);
        });
        
        // Add some polish animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form sections on load
            const sections = document.querySelectorAll('.form-section');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    section.style.transition = 'all 0.6s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
        
        // Hide React status after 5 seconds
        setTimeout(() => {
            const reactStatus = document.getElementById('reactStatus');
            if (reactStatus) {
                reactStatus.style.animation = 'slideInRight 0.5s ease-out reverse';
                setTimeout(() => {
                    reactStatus.style.display = 'none';
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>