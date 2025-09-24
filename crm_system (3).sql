-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 04:22 PM
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
-- Database: `crm_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'User logged in successfully', '192.168.1.100', NULL, '2025-09-06 07:50:12'),
(2, 1, 'task_create', 'Created new task: Follow up with John Smith', '192.168.1.100', NULL, '2025-09-06 07:50:12'),
(3, 2, 'customer_update', 'Updated customer details: ABC Company', '192.168.1.101', NULL, '2025-09-06 07:50:12'),
(4, 2, 'schedule', 'Scheduled task: sa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-19 05:15:23');

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `location` enum('local','cloud') NOT NULL DEFAULT 'local',
  `size` varchar(50) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `created_at`) VALUES
(2, 'Sample Customer', 'samplecustomer@gamail.com', '09123456789', '123,Sta Lucia, Sta Ana Pampanga\r\n', '2025-08-20 08:12:05'),
(4, 'myke', 'myke@gmail.com', '1235145612131', 'San Nicolas', '2025-09-06 05:58:44');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','danger') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `related_type` enum('task','customer','user') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `related_id`, `related_type`, `created_at`) VALUES
(1, 1, 'New Task Assigned', 'You have been assigned a new task: Prepare Q3 sales report', 'info', 0, 2, 'task', '2025-09-06 07:50:12'),
(2, 2, 'Task Due Soon', 'Task \"Follow up with John Smith\" is due tomorrow', 'warning', 0, 1, 'task', '2025-09-06 07:50:12'),
(3, 3, 'Welcome to CRM', 'Your account has been created successfully', 'success', 0, 3, 'user', '2025-09-06 07:50:12'),
(4, 1, 'New Scheduled Task', 'You\'ve been assigned: sa', '', 0, 4, 'task', '2025-09-19 05:15:23'),
(5, 5, 'New Task Assigned', 'You\'ve been assigned a new task: addfadf', '', 0, 29, 'task', '2025-09-19 08:19:03'),
(6, 7, 'New Task Assigned', 'You\'ve been assigned a new task: atasss', '', 0, 30, 'task', '2025-09-23 07:10:52'),
(7, 5, 'New Task Assigned', 'You\'ve been assigned a new task: asdsdfa', '', 0, 31, 'task', '2025-09-23 13:28:15');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` enum('sales','customers','tasks','users') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_range` enum('7','30','90','365','custom') NOT NULL DEFAULT '30',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `report_type`, `title`, `description`, `date_range`, `start_date`, `end_date`, `created_by`, `created_at`, `filters`) VALUES
(1, 'sales', 'Q3 Sales Performance', 'Quarterly sales performance report for Q3 2023', '90', '2023-07-01', '2023-09-30', 1, '2025-09-06 07:50:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_data`
--

CREATE TABLE `report_data` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `data_date` date NOT NULL,
  `new_customers` int(11) DEFAULT 0,
  `tasks_created` int(11) DEFAULT 0,
  `tasks_completed` int(11) DEFAULT 0,
  `conversion_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_data`
--

INSERT INTO `report_data` (`id`, `report_id`, `data_date`, `new_customers`, `tasks_created`, `tasks_completed`, `conversion_rate`, `created_at`) VALUES
(1, 1, '2023-10-01', 5, 12, 8, 15.20, '2025-09-06 07:50:12'),
(2, 1, '2023-09-30', 3, 10, 7, 12.80, '2025-09-06 07:50:12'),
(3, 1, '2023-09-29', 7, 15, 12, 18.50, '2025-09-06 07:50:12'),
(4, 1, '2023-09-28', 4, 8, 6, 10.40, '2025-09-06 07:50:12'),
(5, 1, '2023-09-27', 6, 14, 10, 16.70, '2025-09-06 07:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL DEFAULT 'CRM System',
  `auto_backup` tinyint(1) NOT NULL DEFAULT 1,
  `backup_frequency` enum('daily','weekly','monthly') NOT NULL DEFAULT 'weekly',
  `backup_location` enum('local','cloud','both') NOT NULL DEFAULT 'local',
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `date_format` enum('mm/dd/yyyy','dd/mm/yyyy','yyyy-mm-dd') NOT NULL DEFAULT 'mm/dd/yyyy',
  `time_format` enum('12','24') NOT NULL DEFAULT '12',
  `items_per_page` enum('10','25','50','100') NOT NULL DEFAULT '25',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `company_name`, `auto_backup`, `backup_frequency`, `backup_location`, `email_notifications`, `timezone`, `date_format`, `time_format`, `items_per_page`, `created_at`, `updated_at`) VALUES
(1, 'Your Company Inc.', 1, 'weekly', 'local', 1, 'UTC', 'mm/dd/yyyy', '12', '25', '2025-09-06 07:50:12', '2025-09-06 07:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in-progress','completed') NOT NULL DEFAULT 'pending',
  `assigned_to` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `due_date`, `priority`, `status`, `assigned_to`, `created_by`, `created_at`, `updated_at`) VALUES
(30, 'atasss', 'asdfa', '2025-10-01', 'high', 'completed', 7, 2, '2025-09-23 07:10:52', '2025-09-23 07:11:29'),
(31, 'asdsdfa', 'fdfadad', '2025-09-24', 'low', 'pending', 5, 2, '2025-09-23 13:28:15', '2025-09-23 13:28:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `position`, `bio`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'systemadmin@example.com', '$2y$10$R0r3LPR2PnXVmadxme3SHuTcwpEZHRS2p.vPet/H73W0aR1OQ8Hjm', 'admin', NULL, NULL, NULL, '2025-09-06 07:48:22', '2025-09-23 06:48:34'),
(2, 'Juan Dela Cruz', 'admin@gmail.com', '$2y$10$fP4qOnNeofCJAERX80jySuy5nv6M3e1oqAUFgt4iT5l9M7audb2me', 'admin', '+63-912-345-6789', 'Senior Administrator', 'Manages customer relationships and team coordination.', '2025-08-20 08:38:05', '2025-09-23 06:49:43'),
(3, 'Manager User', 'user@gmail.com', '$2y$10$uBXudqproXdZ0KC2/nhtAedy5oEmD4VlPA7Sf.7KrfRqL62bE/Wbu', 'user', '+63-917-890-1234', 'Sales Manager', 'Handles sales operations and client management.', '2025-08-20 08:55:13', '2025-09-23 06:50:14'),
(5, 'user1', 'user1@gmail.com', '$2y$10$0YkGsv2gxJhWWb0I39PAW.133iq7vZgSnnme/Jn5M1CaSBBuQHFNO', 'user', '+63-918-567-8901', 'Sales Representative', 'Assists customers and manages sales inquiries.', '2025-09-19 07:39:10', '2025-09-23 06:50:46'),
(6, 'sample', 'sample@gmai.com', '$2y$10$Qdp2/DjOKximx6C1HbCXgO3TckiyGIXNwtZpXcFVjiSiSAHYZlMv6', 'user', '12312412415151', 'Accounting', NULL, '2025-09-23 06:53:40', '2025-09-23 06:53:40'),
(7, 'asa', 'asa@gmail.com', '$2y$10$NpC6F/C3c5ygqbpbZHCeEuo.VNpmpa0EEV.PVQfkf40yzc6pEM7P2', 'user', '1111111111111', 'Accounting', NULL, '2025-09-23 07:09:07', '2025-09-23 07:09:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `push_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `task_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `weekly_reports` tinyint(1) NOT NULL DEFAULT 0,
  `theme` enum('light','dark','auto') NOT NULL DEFAULT 'light',
  `language` enum('en','es','fr','de') NOT NULL DEFAULT 'en',
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `date_format` enum('mm/dd/yyyy','dd/mm/yyyy','yyyy-mm-dd') NOT NULL DEFAULT 'mm/dd/yyyy',
  `time_format` enum('12','24') NOT NULL DEFAULT '12',
  `items_per_page` enum('10','25','50','100') NOT NULL DEFAULT '25',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `email_notifications`, `push_notifications`, `task_reminders`, `weekly_reports`, `theme`, `language`, `timezone`, `date_format`, `time_format`, `items_per_page`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 0, 'light', 'en', 'UTC', 'mm/dd/yyyy', '12', '25', '2025-09-06 07:50:12', '2025-09-06 07:50:12'),
(2, 2, 1, 1, 1, 1, 'dark', 'en', 'UTC', 'mm/dd/yyyy', '12', '', '2025-09-06 07:50:12', '2025-09-23 07:10:10'),
(3, 3, 0, 1, 0, 0, 'auto', 'es', 'UTC', 'mm/dd/yyyy', '12', '25', '2025-09-06 07:50:12', '2025-09-06 07:50:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `report_data`
--
ALTER TABLE `report_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report_data`
--
ALTER TABLE `report_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_data`
--
ALTER TABLE `report_data`
  ADD CONSTRAINT `report_data_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
