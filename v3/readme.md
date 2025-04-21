Here's what you need to run the Work Order Management System:

# Required Setup and Files

## Server Requirements
- XAMPP 8.0 or higher with:
  - Apache Server
  - PHP 7.4 or higher
  - SQL Server (for database)

## Required Files Structure
```
c:\xampp\htdocs\v3\
├── Registration.html     # Main entry form
├── receipt.php          # Receipt generation
├── process.php          # Data processing
├── receiptstyle.css     # Receipt styling
├── tailwind.config.js   # Tailwind configuration
├── signatures/          # Directory for signature storage
└── README.md           # Documentation
```

## Installation Steps

1. **Install XAMPP**
   ```bash
   # Download and install XAMPP from
   https://www.apachefriends.org/download.html
   ```

2. **Create Project Directory**
   ```bash
   mkdir c:\xampp\htdocs\v3
   ```

3. **Create Signatures Directory**
   ```bash
   mkdir c:\xampp\htdocs\v3\signatures
   chmod 777 c:\xampp\htdocs\v3\signatures
   ```

4. **SQL Server Setup**
   ```sql
   CREATE DATABASE cpesfd;
   USE cpesfd;

   CREATE TABLE workorderinfo (
       work_order_id VARCHAR(50) PRIMARY KEY,
       date DATE,
       requested_by VARCHAR(100),
       customer_id VARCHAR(50),
       department VARCHAR(100)
   );

   CREATE TABLE billto (
       id INT IDENTITY(1,1) PRIMARY KEY,
       bill_to_name VARCHAR(100),
       bill_to_company VARCHAR(100),
       bill_to_address VARCHAR(200),
       bill_to_city_zip VARCHAR(100),
       bill_to_phone VARCHAR(50)
   );

   CREATE TABLE jobdetails (
       id INT IDENTITY(1,1) PRIMARY KEY,
       job_details TEXT
   );

   CREATE TABLE workorderitems (
       id INT IDENTITY(1,1) PRIMARY KEY,
       work_order_id VARCHAR(50),
       qty INT,
       description VARCHAR(200),
       taxed BIT,
       unit_price DECIMAL(10,2),
       total_price DECIMAL(10,2),
       subtotal DECIMAL(10,2),
       taxable_amount DECIMAL(10,2),
       tax_rate DECIMAL(5,2),
       tax_amount DECIMAL(10,2),
       total DECIMAL(10,2)
   );

   CREATE TABLE contact_info (
       id INT IDENTITY(1,1) PRIMARY KEY,
       work_order_id VARCHAR(50),
       contact_name VARCHAR(100),
       contact_phone VARCHAR(50),
       contact_email VARCHAR(100)
   );
   ```

5. **Required CDN Dependencies**
   Add these to your HTML files:
   ```html
   <script src="https://cdn.tailwindcss.com"></script>
   <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
   ```

## Configuration

1. **Configure SQL Server Connection**
   In process.php, update the connection details:
   ```php
   $serverName = "YOUR_SERVER_NAME\SQLEXPRESS";
   $connectionInfo = array(
       "Database" => "cpesfd",
       "TrustServerCertificate" => true
   );
   ```

2. **Set Directory Permissions**
   ```bash
   # For Windows XAMPP
   icacls "c:\xampp\htdocs\v3\signatures" /grant Everyone:(OI)(CI)F
   ```

## Running the Application

1. Start XAMPP Control Panel
2. Start Apache and SQL Server services
3. Open your browser and navigate to:
   ```
   http://localhost/v3/Registration.html
   ```

## Features Enabled
- Work order creation
- Digital signature capture
- Dark/Light mode toggle
- Dynamic item addition
- Automatic calculations
- PDF receipt generation
- Database storage
- Signature image storage

## Troubleshooting

If you encounter issues:

1. **Signature Not Saving**
   - Check signatures directory permissions
   - Verify PHP has write access
   - Check error logs in `C:\xampp\php\logs`

2. **Database Connection Fails**
   - Verify SQL Server is running
   - Check connection credentials
   - Ensure database exists

3. **Styling Issues**
   - Clear browser cache
   - Verify CDN links are accessible
   - Check console for JavaScript errors

## Security Notes

- Set proper file permissions
- Implement input validation
- Use prepared statements for queries
- Regular cleanup of signature files
- Secure database credentials

Remember to regularly backup your database and signature files.