-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 11:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `work_order_db`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `generate_work_order_number` () RETURNS VARCHAR(50) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DETERMINISTIC READS SQL DATA BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE next_number INT;
    DECLARE year_suffix VARCHAR(4);
    DECLARE result VARCHAR(50);
    
    -- Get prefix from settings
    SELECT setting_value INTO prefix FROM work_order_settings WHERE setting_name = 'work_order_prefix';
    IF prefix IS NULL THEN
        SET prefix = 'WO';
    END IF;
    
    -- Get current year
    SET year_suffix = YEAR(NOW());
    
    -- Get next number for this year
    SELECT COALESCE(MAX(CAST(SUBSTRING(work_order_number, LENGTH(prefix) + 1) AS UNSIGNED)), 0) + 1 
    INTO next_number
    FROM work_orders 
    WHERE work_order_number LIKE CONCAT(prefix, year_suffix, '%');
    
    -- Format the result
    SET result = CONCAT(prefix, year_suffix, LPAD(next_number, 4, '0'));
    
    RETURN result;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city_zip` varchar(100) DEFAULT NULL,
  `total_orders` int(6) DEFAULT 0,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `last_order_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_id`, `name`, `company`, `email`, `phone`, `address`, `city_zip`, `total_orders`, `total_amount`, `last_order_date`, `status`, `created_at`, `updated_at`) VALUES
(1, '49596', 'Adrian', NULL, 'asuplico@gmail.com', '+63 933 810 9601', NULL, NULL, 2, 369.60, '2025-05-25', 'active', '2025-05-25 08:57:47', '2025-05-25 08:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `work_orders`
--

CREATE TABLE `work_orders` (
  `id` int(11) NOT NULL,
  `work_order_number` varchar(50) NOT NULL,
  `order_date` date NOT NULL,
  `requested_by` varchar(100) NOT NULL,
  `customer_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `contact_name` varchar(100) NOT NULL,
  `contact_email` varchar(150) NOT NULL,
  `street_address` text NOT NULL,
  `city_zip` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `bill_name` varchar(100) NOT NULL,
  `bill_company` varchar(150) DEFAULT NULL,
  `bill_street_address` text NOT NULL,
  `bill_city_zip` varchar(100) NOT NULL,
  `bill_phone` varchar(20) DEFAULT NULL,
  `description_of_work` text NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payable_to` varchar(150) DEFAULT NULL,
  `status` enum('draft','pending','in_progress','completed','cancelled','billed') DEFAULT 'draft',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_orders`
--

INSERT INTO `work_orders` (`id`, `work_order_number`, `order_date`, `requested_by`, `customer_id`, `department`, `contact_name`, `contact_email`, `street_address`, `city_zip`, `phone`, `bill_name`, `bill_company`, `bill_street_address`, `bill_city_zip`, `bill_phone`, `description_of_work`, `subtotal`, `taxable_amount`, `tax_amount`, `total`, `payable_to`, `status`, `priority`, `ip_address`, `user_agent`, `created_at`, `updated_at`, `completed_at`) VALUES
(2, 'WO20250001', '2025-05-25', 'me', '49596', 'Chemistry', 'Adrian', 'asuplico@gmail.com', 'Bacolod City Mansiligan', '6100', '+63 933 810 9601', 'Adrian', 'Dwaf Co', 'Bacolod City Mansiligan', '6100', '+63 933 810 9601', 'Yes', 275.00, 275.00, 33.00, 308.00, 'dada', 'draft', 'medium', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-25 08:58:36', '2025-05-25 08:58:36', NULL);

--
-- Triggers `work_orders`
--
DELIMITER $$
CREATE TRIGGER `update_customer_stats_after_insert` AFTER INSERT ON `work_orders` FOR EACH ROW BEGIN
    IF NEW.customer_id IS NOT NULL THEN
        INSERT INTO customers (customer_id, name, email, phone, total_orders, total_amount, last_order_date)
        VALUES (NEW.customer_id, NEW.contact_name, NEW.contact_email, NEW.phone, 1, NEW.total, NEW.order_date)
        ON DUPLICATE KEY UPDATE
            total_orders = total_orders + 1,
            total_amount = total_amount + NEW.total,
            last_order_date = NEW.order_date,
            updated_at = NOW();
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_customer_stats_after_update` AFTER UPDATE ON `work_orders` FOR EACH ROW BEGIN
    IF NEW.customer_id IS NOT NULL AND OLD.total != NEW.total THEN
        UPDATE customers 
        SET total_amount = total_amount - OLD.total + NEW.total,
            updated_at = NOW()
        WHERE customer_id = NEW.customer_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `work_order_attachments`
--

CREATE TABLE `work_order_attachments` (
  `id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_order_comments`
--

CREATE TABLE `work_order_comments` (
  `id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `comment_type` enum('note','status_change','customer_communication') DEFAULT 'note',
  `comment` text NOT NULL,
  `added_by` varchar(100) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_order_items`
--

CREATE TABLE `work_order_items` (
  `id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `item_order` int(3) NOT NULL DEFAULT 1,
  `quantity` int(6) NOT NULL DEFAULT 1,
  `description` text NOT NULL,
  `is_taxed` tinyint(1) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_order_items`
--

INSERT INTO `work_order_items` (`id`, `work_order_id`, `item_order`, `quantity`, `description`, `is_taxed`, `unit_price`, `total_price`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 5, 'banana', 1, 55.00, 275.00, '2025-05-25 08:58:36', '2025-05-25 08:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `work_order_settings`
--

CREATE TABLE `work_order_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_order_settings`
--

INSERT INTO `work_order_settings` (`id`, `setting_name`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'Your Company Name', 'Company name for work orders', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(2, 'company_address', '123 Business St, City, State 12345', 'Company address', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(3, 'company_phone', '(555) 123-4567', 'Company phone number', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(4, 'company_email', 'info@yourcompany.com', 'Company email address', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(5, 'tax_rate', '0.12', 'Tax rate (12%)', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(6, 'work_order_prefix', 'WO', 'Prefix for work order numbers', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(7, 'auto_generate_customer_id', '1', 'Auto-generate customer IDs (1=yes, 0=no)', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(8, 'default_payable_to', 'Your Company Name', 'Default payable to field', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(9, 'email_notifications', '1', 'Send email notifications (1=yes, 0=no)', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(10, 'admin_email', 'admin@yourcompany.com', 'Admin email for notifications', '2025-05-25 07:48:51', '2025-05-25 07:48:51'),
(11, 'system_initialized', '1', 'System setup completed', '2025-05-25 07:48:51', '2025-05-25 07:48:51');

-- --------------------------------------------------------

--
-- Stand-in structure for view `work_order_summary`
-- (See below for the actual view)
--
CREATE TABLE `work_order_summary` (
`id` int(11)
,`work_order_number` varchar(50)
,`order_date` date
,`contact_name` varchar(100)
,`contact_email` varchar(150)
,`requested_by` varchar(100)
,`department` varchar(100)
,`status` enum('draft','pending','in_progress','completed','cancelled','billed')
,`priority` enum('low','medium','high','urgent')
,`total` decimal(10,2)
,`created_at` timestamp
,`item_count` bigint(21)
,`customer_company` varchar(150)
);

-- --------------------------------------------------------

--
-- Structure for view `work_order_summary`
--
DROP TABLE IF EXISTS `work_order_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `work_order_summary`  AS SELECT `wo`.`id` AS `id`, `wo`.`work_order_number` AS `work_order_number`, `wo`.`order_date` AS `order_date`, `wo`.`contact_name` AS `contact_name`, `wo`.`contact_email` AS `contact_email`, `wo`.`requested_by` AS `requested_by`, `wo`.`department` AS `department`, `wo`.`status` AS `status`, `wo`.`priority` AS `priority`, `wo`.`total` AS `total`, `wo`.`created_at` AS `created_at`, count(`woi`.`id`) AS `item_count`, `c`.`company` AS `customer_company` FROM ((`work_orders` `wo` left join `work_order_items` `woi` on(`wo`.`id` = `woi`.`work_order_id`)) left join `customers` `c` on(`wo`.`customer_id` = `c`.`customer_id`)) GROUP BY `wo`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_company` (`company`);

--
-- Indexes for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_order_number` (`work_order_number`),
  ADD KEY `idx_work_order_number` (`work_order_number`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_contact_email` (`contact_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `work_order_attachments`
--
ALTER TABLE `work_order_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_work_order_id` (`work_order_id`),
  ADD KEY `idx_filename` (`filename`);

--
-- Indexes for table `work_order_comments`
--
ALTER TABLE `work_order_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_work_order_id` (`work_order_id`),
  ADD KEY `idx_comment_type` (`comment_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `work_order_items`
--
ALTER TABLE `work_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_work_order_id` (`work_order_id`),
  ADD KEY `idx_item_order` (`item_order`);

--
-- Indexes for table `work_order_settings`
--
ALTER TABLE `work_order_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`),
  ADD KEY `idx_setting_name` (`setting_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `work_orders`
--
ALTER TABLE `work_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `work_order_attachments`
--
ALTER TABLE `work_order_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_order_comments`
--
ALTER TABLE `work_order_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_order_items`
--
ALTER TABLE `work_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `work_order_settings`
--
ALTER TABLE `work_order_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `work_order_attachments`
--
ALTER TABLE `work_order_attachments`
  ADD CONSTRAINT `work_order_attachments_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_order_comments`
--
ALTER TABLE `work_order_comments`
  ADD CONSTRAINT `work_order_comments_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_order_items`
--
ALTER TABLE `work_order_items`
  ADD CONSTRAINT `work_order_items_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
