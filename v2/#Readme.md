# Work Order Management System

A PHP-based work order management system with dynamic calculations and receipt generation.

## File Structure

```
v2/
├── registration.php    # Main form for work order entry
├── receipt.php        # Receipt generation and display
├── process.php        # Order confirmation display
├── data.php          # Database operations
└── README.md         # This file
```

## File Descriptions

### registration.php
- Main entry point for work order creation
- Features:
  - Work order information input
  - Customer billing details
  - Job details section
  - Dynamic item addition with calculations
  - Automatic tax calculations (12% fixed rate)
  - Real-time total calculations
- JavaScript Functions:
  - `calculateTotal()`: Calculates individual item totals
  - `calculateAllTotals()`: Updates subtotal, tax, and final total
  - `setupCalculations()`: Sets up event listeners for calculations
  - `addItem()`: Adds new item rows dynamically

### receipt.php
- Generates printable receipt from form data
- Features:
  - Company header
  - Work order details
  - Customer information
  - Item listing with totals
  - Tax calculations display
  - Signature section
  - Print functionality
- Security:
  - Input sanitization
  - Default value handling
  - Error checking for missing data

### process.php
- Confirmation page after form submission
- Features:
  - Success notification
  - Order summary display
  - Styled confirmation layout
  - Print option
  - Option to submit new order
- Styling:
  - Bootstrap-based layout
  - Custom CSS for professional appearance
  - Responsive design

### data.php
- Handles database operations
- Features:
  - MySQL database connection
  - Data sanitization
  - Multiple table insertions:
    - Work order information
    - Billing details
    - Job details
    - Order items
- Tables:
  - workorderinfo
  - billto
  - jobdetails
  - workorderitems

## Setup Requirements
1. XAMPP or similar PHP environment
2. MySQL database
3. Web browser with JavaScript enabled

## Database Configuration
```sql
CREATE DATABASE IF NOT EXISTS cpesfd;
USE cpesfd;

CREATE TABLE workorderinfo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id VARCHAR(50),
    date DATE,
    requested_by VARCHAR(100),
    customer_id VARCHAR(50),
    department VARCHAR(100)
);

-- Additional table creation statements...
```

## Usage
1. Start XAMPP (Apache and MySQL)
2. Place files in htdocs directory
3. Access via: `http://localhost/v2/registration.php`
4. Fill out work order form
5. Submit to generate receipt
6. Data is stored in database

## Security Notes
- All input is sanitized
- Form validation implemented
- SQL injection prevention
- XSS protection
