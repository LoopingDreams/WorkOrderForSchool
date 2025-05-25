<?php 
include 'includes/db_connect.php';
include 'includes/functions.php';
include 'includes/work_order_functions.php';
session_start();

// Get work order details from session
$work_order_number = $_SESSION['work_order_number'] ?? '';
$work_order_id = $_SESSION['work_order_id'] ?? 0;

// If no work order data in session, redirect to form
if (empty($work_order_number) || empty($work_order_id)) {
    redirect_with_message('index.php', 'No work order data found.', 'error');
}

// Get work order details
$work_order = get_work_order($conn, $work_order_id);
if (!$work_order) {
    redirect_with_message('index.php', 'Work order not found.', 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Created Successfully</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <!-- Additional styles for success page -->
    <style>
        .success-icon {
            animation: scaleIn 0.5s ease-in-out;
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .success-container {
            text-align: center;
            padding: 40px 30px;
        }
        
        .work-order-summary {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            border: 2px solid #28a745;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.1);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        
        .summary-row:not(:last-child) {
            border-bottom: 1px solid #f1f3f4;
        }
        
        .summary-label {
            font-weight: 500;
            color: #666;
        }
        
        .summary-value {
            font-weight: 600;
            color: #333;
        }
        
        .work-order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
            margin: 15px 0;
        }
        
        .btn-group-custom {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn-outline-primary {
            border: 2px solid #6c63ff;
            color: #6c63ff;
            background: transparent;
            border-radius: 8px;
            padding: 10px 25px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .btn-outline-primary:hover {
            background: #6c63ff;
            color: white;
            transform: translateY(-1px);
        }
        
        .next-steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .next-steps h4 {
            color: #6c63ff;
            margin-bottom: 15px;
        }
        
        .next-steps ul {
            margin-bottom: 0;
        }
        
        .contact-info {
            background: linear-gradient(135deg, #6c63ff 0%, #5a52d5 100%);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        @media (max-width: 576px) {
            .btn-group-custom {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-group-custom .btn {
                width: 100%;
                max-width: 250px;
            }
            
            .summary-row {
                flex-direction: column;
                text-align: center;
            }
            
            .summary-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <!-- Navigation Links -->
            <div class="nav-links">
                <a href="#">dashboard</a>
                <a href="#">work orders</a>
                <a href="#">customers</a>
                <a href="index.php">new order</a>
                <a href="#">reports</a>
                <a href="#">settings</a>
            </div>
            
            <div class="success-container">
                <!-- Success Icon -->
                <div class="success-icon mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#28a745" class="bi bi-check-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                    </svg>
                </div>
                
                <!-- Header -->
                <div class="site-header">
                    <h1>Work Order Created!</h1>
                    <p>Your work order has been successfully submitted and is now in our system.</p>
                </div>
                
                <!-- Display flash message if exists -->
                <?php echo display_flash_message(); ?>
                
                <!-- Work Order Number -->
                <div class="work-order-number">
                    Work Order #: <?php echo htmlspecialchars($work_order_number); ?>
                </div>
                
                <!-- Work Order Summary -->
                <div class="work-order-summary">
                    <h4 style="color: #28a745; margin-bottom: 20px;">Order Summary</h4>
                    
                    <div class="summary-row">
                        <span class="summary-label">Date:</span>
                        <span class="summary-value"><?php echo date('F j, Y', strtotime($work_order['order_date'])); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Requested By:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($work_order['requested_by']); ?></span>
                    </div>
                    
                    <?php if (!empty($work_order['customer_id'])): ?>
                    <div class="summary-row">
                        <span class="summary-label">Customer ID:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($work_order['customer_id']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($work_order['department'])): ?>
                    <div class="summary-row">
                        <span class="summary-label">Department:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($work_order['department']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span class="summary-label">Contact:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($work_order['contact_name']); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Email:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($work_order['contact_email']); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Phone:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($work_order['phone']); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Items:</span>
                        <span class="summary-value"><?php echo count($work_order['items']); ?> item(s)</span>
                    </div>
                    
                    <div class="summary-row" style="border-top: 2px solid #28a745; margin-top: 15px; padding-top: 15px;">
                        <span class="summary-label" style="font-size: 1.1rem;"><strong>Total Amount:</strong></span>
                        <span class="summary-value" style="font-size: 1.2rem; color: #28a745;"><strong>₱<?php echo number_format($work_order['total'], 2); ?></strong></span>
                    </div>
                </div>
                
                <!-- Next Steps -->
                <div class="next-steps">
                    <h4>What Happens Next?</h4>
                    <ul>
                        <li><strong>Review Process:</strong> Our team will review your work order within 24-48 hours</li>
                        <li><strong>Confirmation Call:</strong> We'll contact you to confirm details and discuss timeline</li>
                        <li><strong>Schedule Work:</strong> Once approved, we'll schedule the work at your convenience</li>
                        <li><strong>Email Updates:</strong> You'll receive email notifications about status changes</li>
                        <li><strong>Billing:</strong> Invoice will be sent to the billing address provided upon completion</li>
                    </ul>
                </div>
                
                <!-- Action Buttons -->
                <div class="btn-group-custom">
                    <a href="index.php" class="btn btn-primary">Create Another Work Order</a>
                    <a href="#" class="btn-outline-primary" onclick="window.print()">Print Work Order</a>
                    <a href="#" class="btn-outline-primary">Track Status</a>
                </div>
                
                <!-- Contact Information -->
                <div class="contact-info">
                    <h5 style="margin-bottom: 15px;">Need Help or Have Questions?</h5>
                    <p style="margin-bottom: 10px;">
                        <strong>Email:</strong> <?php echo get_work_order_setting($conn, 'company_email', 'info@yourcompany.com'); ?><br>
                        <strong>Phone:</strong> <?php echo get_work_order_setting($conn, 'company_phone', '(555) 123-4567'); ?><br>
                        <strong>Reference:</strong> Work Order #<?php echo htmlspecialchars($work_order_number); ?>
                    </p>
                    <small>Please have your work order number ready when contacting us.</small>
                </div>
                
                <!-- Additional Information -->
                <div class="mt-4">
                    <small class="text-muted">
                        <strong>Important:</strong> This work order is now in our system and cannot be modified online. 
                        If you need to make changes, please contact us immediately using the information above.<br><br>
                        <strong>Confirmation Email:</strong> A detailed confirmation email has been sent to 
                        <?php echo htmlspecialchars($work_order['contact_email']); ?>. Please check your spam folder if you don't see it in your inbox.
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-clear session data after page load to prevent refresh issues
        window.addEventListener('load', function() {
            // Optional: Add tracking/analytics code here
            
            // Clear work order session data after 5 minutes to prevent stale data
            setTimeout(function() {
                fetch('clear_work_order_session.php', { method: 'POST' })
                    .catch(function(error) {
                        console.log('Session cleanup failed:', error);
                    });
            }, 300000); // 5 minutes
        });
        
        // Print functionality
        function printWorkOrder() {
            window.print();
        }
        
        // Track status functionality (placeholder)
        function trackStatus() {
            alert('Status tracking feature coming soon! For now, please contact us directly for updates.');
        }
        
        // Prevent back button issues
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    
    <!-- Print Styles -->
    <style>
        @media print {
            .nav-links,
            .btn-group-custom,
            .btn,
            .contact-info {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .form-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 20px !important;
            }
            
            .work-order-summary {
                border: 2px solid #333 !important;
                page-break-inside: avoid;
            }
            
            .site-header h1 {
                color: #333 !important;
            }
            
            .work-order-number {
                color: #333 !important;
                font-size: 1.8rem !important;
            }
            
            .next-steps {
                page-break-inside: avoid;
            }
        }
    </style>
</body>
</html>

<?php
// Clear work order session data to prevent refresh issues
unset($_SESSION['work_order_number']);
unset($_SESSION['work_order_id']);
?>