-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2025 at 12:09 AM
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
-- Database: `habeshaequb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `phone`, `password`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'Abeldemssie', NULL, NULL, '$2y$12$t42lLluGvefREVG44PN20.Ar4fdU8aEzadsvzV7BYn/gVM3zzArjW', 1, '2025-07-22 08:19:10', '2025-07-22 08:19:24'),
(4, 'abela', NULL, NULL, '$2y$12$KJjtNQ0EBbCS8x7sp77eJuzgBjDzTseNZoD6Mk5XGSgM39hfHODFy', 1, '2025-07-24 21:19:50', '2025-07-24 21:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `equb_rules`
--

CREATE TABLE `equb_rules` (
  `id` int(11) NOT NULL,
  `rule_number` int(11) NOT NULL,
  `rule_en` text NOT NULL,
  `rule_am` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equb_rules`
--

INSERT INTO `equb_rules` (`id`, `rule_number`, `rule_en`, `rule_am`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Monthly payments are due on the 1st day of each month.', 'ሁሉም አባላት እቁባቸውን በወሩ የመጀመሪያ ቀን መክፈል አለባቸው', 1, '2025-07-22 21:51:08', '2025-07-22 21:59:41'),
(2, 2, 'If you are unable to pay on time due to an emergency, you must notify the admin as soon as possible. An extension of up to two additional days may be granted.', 'አባላቶች ከአቅም በላይ በሆነ ጉዳይ በሰዓቱ መክፈል ካልቻሉ ለሰብሳቢው ቀድመው ማሳወቅ አለባቸው፣ ይሄም እቁቡን ለመክፈል ተጨማሪ 2 ቀናትን እንዲያገኙ ያስችልዎታል', 1, '2025-07-22 22:22:15', '2025-07-22 22:22:15'),
(3, 3, 'If payment is not received within this grace period, a late fee of £20 will be charged automatically.', 'እቁቡን በሰአቱ ካልከፈሉ ተጨማሪ £20 ቅጣት ይከፍላሉ', 1, '2025-07-22 22:23:35', '2025-07-22 22:23:35'),
(4, 4, 'Each member receives their full payout on the 5th day of the month.', 'አባላቶች ወር በገባ በአምስተኛው ቀን እቁባቸውን የሚወስዱ ይሆናል', 1, '2025-07-22 22:24:32', '2025-07-22 22:24:32'),
(5, 5, 'A £10 service fee will be deducted from each payout.', 'ሁሉም አባል ተራው ደርሶ እቁብ ሲወስድ ለእቁብ ስራ ማስኬጃ የሚውል £10 ይቀነስበታል', 1, '2025-07-22 22:26:27', '2025-07-22 22:26:27'),
(6, 6, 'Once your payout turn is assigned, it cannot be changed.\r\nIf you must request a change, you must notify the admin at least 3 weeks in advance.', 'አንዴ እቁብ የሚወስዱበት ቀን ከታወቀ በኋላ መቀየር አይቻልም፣ ግዴታ መቀየር አስፈላጊ ሆኖ ከተገኘ ለሰብሳቢው ቢያንስ ከ 3 ሳምንት በፊት ማሳወቅ ይኖርብዎታል', 1, '2025-07-22 22:28:18', '2025-07-22 22:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_id` varchar(20) NOT NULL COMMENT 'Auto-generated: HEM-AG1, HEM-AM2, etc.',
  `username` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT '6 digit alphanumeric',
  `status` varchar(20) DEFAULT 'active',
  `monthly_payment` decimal(10,2) NOT NULL COMMENT 'Monthly contribution amount',
  `payout_position` int(3) NOT NULL COMMENT 'Position in payout rotation (1,2,3...)',
  `payout_month` date DEFAULT NULL COMMENT 'Month when member receives payout',
  `total_contributed` decimal(10,2) DEFAULT 0.00 COMMENT 'Total amount contributed so far',
  `has_received_payout` tinyint(1) DEFAULT 0 COMMENT '1 if already received payout',
  `guarantor_first_name` varchar(50) NOT NULL,
  `guarantor_last_name` varchar(50) NOT NULL,
  `guarantor_phone` varchar(20) NOT NULL,
  `guarantor_email` varchar(100) DEFAULT NULL,
  `guarantor_relationship` varchar(50) DEFAULT NULL COMMENT 'Relationship to member',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_approved` tinyint(1) DEFAULT 0 COMMENT 'Admin approval status',
  `email_verified` tinyint(1) DEFAULT 0,
  `join_date` date NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `notification_preferences` set('email','sms','both') DEFAULT 'both',
  `go_public` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Public visibility: 1=Yes (public), 0=No (private)',
  `language_preference` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Web language: 0=English, 1=Amharic',
  `notes` text DEFAULT NULL COMMENT 'Admin notes about member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_id`, `username`, `first_name`, `last_name`, `full_name`, `email`, `phone`, `password`, `status`, `monthly_payment`, `payout_position`, `payout_month`, `total_contributed`, `has_received_payout`, `guarantor_first_name`, `guarantor_last_name`, `guarantor_phone`, `guarantor_email`, `guarantor_relationship`, `is_active`, `is_approved`, `email_verified`, `join_date`, `last_login`, `notification_preferences`, `go_public`, `language_preference`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'HEM-MW1', 'michael', 'Michael', 'Werkneh', 'Michael Werkneh', 'michael.werkneh@email.com', '+447123456789', '$2y$12$kdwSiI7P37OpM7OVFH4rMOHH2W7Yywf08O3DItvLGCVH6ZKqI/qBi', 'active', 1000.00, 1, '2025-07-05', 6000.00, 1, 'Sarah', 'Werkneh', '+447123456790', 'sarah.werkneh@email.com', 'Wife', 1, 1, 1, '2024-05-15', '2025-07-23 20:42:30', 'email,sms', 1, 1, 'First member - received June payout', '2025-07-22 07:24:42', '2025-07-24 02:43:20'),
(2, 'HEM-MN2', NULL, 'Maeruf', 'Nasir', NULL, 'maeruf.nasir@email.com', '+447234567890', 'MN456B', 'active', 1000.00, 2, '2025-08-05', 1000.00, 0, 'Ahmed', 'Nasir', '+447234567891', 'ahmed.nasir@email.com', 'Brother', 1, 1, 1, '2024-05-15', '2024-06-18 13:15:00', 'email', 1, 1, 'Active member - good payment record', '2025-07-22 07:24:42', '2025-07-24 02:43:23'),
(3, 'HEM-TE3', NULL, 'Teddy', 'Elias', NULL, 'teddy.elias@email.com', '+447345678901', 'TE789C', 'active', 500.00, 3, '2025-09-05', 1500.00, 0, 'Helen', 'Elias', '+447345678902', 'helen.elias@email.com', 'Mother', 1, 1, 1, '2024-05-15', '2024-06-19 15:45:00', 'email,sms', 1, 1, 'Reliable member', '2025-07-22 07:24:42', '2025-07-24 02:43:26'),
(4, 'HEM-KG4', NULL, 'Kokit', 'Gormesa', NULL, 'kokit.gormesa@email.com', '+447456789012', 'KG012D', 'active', 1000.00, 4, '2025-10-05', 1000.00, 0, 'Dawit', 'Gormesa', '+447456789013', 'dawit.gormesa@email.com', 'Husband', 1, 1, 1, '2024-05-15', '2024-06-17 11:20:00', 'sms', 1, 1, 'New member - very enthusiastic', '2025-07-22 07:24:42', '2025-07-24 02:43:30'),
(5, 'HEM-MA5', NULL, 'Mahlet', 'Ayalew', NULL, 'mahlet.ayalew@email.com', '+447567890123', 'MA345E', 'active', 1000.00, 5, '2025-11-05', 1000.00, 0, 'Bereket', 'Ayalew', '+447567890124', 'bereket.ayalew@email.com', 'Father', 1, 1, 1, '2024-05-15', '2024-06-21 08:10:00', 'email,sms', 1, 1, 'Last position - patient member', '2025-07-22 07:24:42', '2025-07-24 02:43:33');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `notification_id` varchar(20) NOT NULL COMMENT 'Auto-generated: NOT-202401-001',
  `recipient_type` enum('member','admin','all_members','all_admins') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL COMMENT 'Member or Admin ID (NULL for broadcast)',
  `recipient_email` varchar(100) DEFAULT NULL,
  `recipient_phone` varchar(20) DEFAULT NULL,
  `type` enum('payment_reminder','payout_alert','welcome','approval','general','emergency') NOT NULL,
  `channel` enum('email','sms','both') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `language` enum('en','am') DEFAULT 'en',
  `status` enum('pending','sent','delivered','failed','cancelled') NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL COMMENT 'Email opened timestamp',
  `clicked_at` timestamp NULL DEFAULT NULL COMMENT 'Link clicked timestamp',
  `sent_by_admin_id` int(11) DEFAULT NULL,
  `email_provider_response` varchar(500) DEFAULT NULL COMMENT 'Email service response',
  `sms_provider_response` varchar(500) DEFAULT NULL COMMENT 'SMS service response',
  `retry_count` int(2) DEFAULT 0,
  `scheduled_for` timestamp NULL DEFAULT NULL COMMENT 'Scheduled sending time',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `notes` text DEFAULT NULL COMMENT 'Admin notes about notification',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_id` varchar(20) NOT NULL COMMENT 'Auto-generated: PAY-HEM-AG1-202401',
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_month` date NOT NULL COMMENT 'Which month this payment is for (YYYY-MM-01)',
  `payment_date` date DEFAULT NULL COMMENT 'Actual date payment was made',
  `status` enum('pending','paid','late','missed') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','bank_transfer','mobile_money') DEFAULT 'cash',
  `verified_by_admin` tinyint(1) DEFAULT 0,
  `verified_by_admin_id` int(11) DEFAULT NULL,
  `verification_date` timestamp NULL DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Payment notes/comments',
  `late_fee` decimal(8,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_id`, `member_id`, `amount`, `payment_month`, `payment_date`, `status`, `payment_method`, `verified_by_admin`, `verified_by_admin_id`, `verification_date`, `receipt_number`, `notes`, `late_fee`, `created_at`, `updated_at`) VALUES
(1, 'PAY-MW1-062024', 1, 1000.00, '0000-00-00', '2024-06-01', 'paid', 'cash', 1, 3, '2025-07-22 17:31:06', 'RCP-MW1-001', 'June payment - on time', 0.00, '2025-07-22 07:24:42', '2025-07-22 16:31:06'),
(3, 'PAY-MW1-122024', 1, 500.00, '2024-12-01', '2024-12-01', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(4, 'PAY-MW2-122024', 2, 500.00, '2024-12-01', '2024-12-01', 'paid', 'cash', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(5, 'PAY-MW3-122024', 3, 500.00, '0000-00-00', '2025-07-22', 'paid', 'bank_transfer', 1, 3, '2025-07-22 19:33:09', '', '', 0.00, '2025-07-22 17:36:50', '2025-07-22 18:33:09'),
(6, 'PAY-MW1-112024', 1, 500.00, '2024-11-01', '2024-11-01', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(7, 'PAY-MW2-112024', 2, 500.00, '2024-11-01', '2024-11-01', 'paid', 'mobile_money', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(8, 'PAY-MW3-112024', 3, 500.00, '2024-11-01', '2024-11-03', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(9, 'PAY-MW1-102024', 1, 500.00, '2024-10-01', '2024-10-01', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(10, 'PAY-MW2-102024', 2, 500.00, '2024-10-01', '2024-10-01', 'paid', 'cash', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(11, 'PAY-MW3-102024', 3, 500.00, '2024-10-01', '2024-10-02', 'paid', 'bank_transfer', 1, 3, '2025-07-22 19:43:11', NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 19:43:11'),
(12, 'PAY-MW1-092024', 1, 500.00, '2024-09-01', '2024-09-01', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(13, 'PAY-MW2-092024', 2, 500.00, '2024-09-01', '2024-09-01', 'paid', 'cash', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(14, 'PAY-MW3-092024', 3, 500.00, '2024-09-01', '2024-09-03', 'paid', 'mobile_money', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(15, 'PAY-MW1-082024', 1, 500.00, '2024-08-01', '2024-08-01', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(16, 'PAY-MW2-082024', 2, 500.00, '2024-08-01', '2024-08-01', 'paid', 'cash', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(17, 'PAY-MW3-082024', 3, 500.00, '2024-08-01', '2024-08-02', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(18, 'PAY-MW1-072024', 1, 500.00, '2024-07-01', '2024-07-01', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(19, 'PAY-MW2-072024', 2, 500.00, '2024-07-01', '2024-07-01', 'paid', 'mobile_money', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50'),
(20, 'PAY-MW3-072024', 3, 500.00, '2024-07-01', '2024-07-03', 'paid', 'bank_transfer', 0, NULL, NULL, NULL, NULL, 0.00, '2025-07-22 17:36:50', '2025-07-22 17:36:50');

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `id` int(11) NOT NULL,
  `payout_id` varchar(20) NOT NULL COMMENT 'Auto-generated: PO-HEM-AG1-202401',
  `member_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL COMMENT 'Total payout amount',
  `scheduled_date` date NOT NULL COMMENT 'When payout was scheduled',
  `actual_payout_date` date DEFAULT NULL COMMENT 'When actually paid out',
  `status` enum('scheduled','processing','completed','cancelled','on_hold') NOT NULL DEFAULT 'scheduled',
  `payout_method` enum('cash','bank_transfer','mobile_money','mixed') DEFAULT 'cash',
  `processed_by_admin_id` int(11) DEFAULT NULL,
  `admin_fee` decimal(8,2) DEFAULT 0.00 COMMENT 'Admin service fee',
  `net_amount` decimal(12,2) NOT NULL COMMENT 'Amount after fees',
  `transaction_reference` varchar(100) DEFAULT NULL COMMENT 'Bank/payment reference',
  `receipt_issued` tinyint(1) DEFAULT 0,
  `member_signature` tinyint(1) DEFAULT 0 COMMENT 'Member confirmed receipt',
  `payout_notes` text DEFAULT NULL COMMENT 'DETAILED NOTES: Cash+transfer combinations, issues, special circumstances, delays, member requests, etc.',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payouts`
--

INSERT INTO `payouts` (`id`, `payout_id`, `member_id`, `total_amount`, `scheduled_date`, `actual_payout_date`, `status`, `payout_method`, `processed_by_admin_id`, `admin_fee`, `net_amount`, `transaction_reference`, `receipt_issued`, `member_signature`, `payout_notes`, `created_at`, `updated_at`) VALUES
(1, 'PAYOUT-MW1-062024', 1, 5000.00, '2024-06-15', '2024-06-15', 'completed', 'bank_transfer', 3, 50.00, 4950.00, 'TXN-MW1-20240615', 1, 0, 'First equib payout - Michael Werkneh - June 2024. Total collected: £5000, Admin fee: £50, Net payout: £4950', '2025-07-22 07:24:42', '2025-07-22 16:06:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `equb_rules`
--
ALTER TABLE `equb_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rule_number` (`rule_number`),
  ADD KEY `idx_rule_number` (`rule_number`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_id` (`member_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_member_id` (`member_id`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_approved` (`is_approved`),
  ADD KEY `idx_payout_position` (`payout_position`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notification_id` (`notification_id`),
  ADD UNIQUE KEY `idx_notification_id` (`notification_id`),
  ADD KEY `idx_recipient` (`recipient_type`,`recipient_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_channel` (`channel`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_scheduled` (`scheduled_for`),
  ADD KEY `sent_by_admin_id` (`sent_by_admin_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_id` (`payment_id`),
  ADD UNIQUE KEY `idx_payment_id` (`payment_id`),
  ADD UNIQUE KEY `idx_member_month` (`member_id`,`payment_month`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_month` (`payment_month`),
  ADD KEY `idx_verified` (`verified_by_admin`),
  ADD KEY `verified_by_admin_id` (`verified_by_admin_id`);

--
-- Indexes for table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payout_id` (`payout_id`),
  ADD UNIQUE KEY `idx_payout_id` (`payout_id`),
  ADD KEY `idx_member_id` (`member_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_actual_date` (`actual_payout_date`),
  ADD KEY `processed_by_admin_id` (`processed_by_admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `equb_rules`
--
ALTER TABLE `equb_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`sent_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `payouts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payouts_ibfk_2` FOREIGN KEY (`processed_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
