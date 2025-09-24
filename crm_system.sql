-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2025 at 09:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(4, 'myke', 'myke@gmail.com', '1235145612131', 'San Nicolas\r\n', '2025-09-06 05:58:44');

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
(2, 2, 'Task Due Soon', 'Task \"Follow up with John Smith\" is due tomorrow', 'warning', 1, 1, 'task', '2025-09-06 07:50:12'),
(12, 2, 'Task Progress Update', 'Juan Tamad updated task status to \'in-progress\': \"dad\"', '', 1, 38, 'task', '2025-09-24 16:32:23'),
(13, 2, 'Task Progress Update', 'User User updated task status to \'in-progress\': \"dad\"', '', 1, 38, 'task', '2025-09-24 16:43:22'),
(14, 2, 'Task Progress Update', 'User User updated task status to \'in-progress\': \"dad\"', '', 1, 38, 'task', '2025-09-24 16:43:25'),
(15, 2, 'Task Progress Update', 'User User updated task status to \'completed\': \"dad\"', '', 1, 38, 'task', '2025-09-24 16:43:28'),
(17, 2, 'Task Progress Update', 'User User updated task status to \'in-progress\': \"adad\"', '', 1, 39, 'task', '2025-09-24 17:15:08'),
(18, 2, 'Task Progress Update', 'User User updated task status to \'completed\': \"adad\"', '', 1, 39, 'task', '2025-09-24 17:15:36'),
(20, 12, 'New Task Assigned', 'You have been assigned a new task: \"DADAWEWAE\"', '', 1, 41, 'task', '2025-09-24 18:02:20'),
(21, 2, 'Task Progress Update', 'User User updated task status to \'in-progress\': \"DADAWEWAE\"', '', 1, 41, 'task', '2025-09-24 18:02:50'),
(22, 2, 'Task Progress Update', 'User User updated task status to \'completed\': \"DADAWEWAE\"', '', 1, 41, 'task', '2025-09-24 18:02:52'),
(23, 12, 'New Task Assigned', 'You have been assigned a new task: \"dawd\"', '', 1, 42, 'task', '2025-09-24 18:14:34'),
(24, 2, 'Task Progress Update', 'User User updated task status to \'completed\': \"dawd\"', '', 1, 42, 'task', '2025-09-24 18:14:57'),
(25, 12, 'New Task Assigned', 'You have been assigned a new task: \"Stock\"', '', 1, 43, 'task', '2025-09-24 18:18:23'),
(26, 2, 'Task Progress Update', 'User User updated task status to \'completed\': \"Stock\"', '', 1, 43, 'task', '2025-09-24 18:19:04'),
(27, 12, 'New Task Assigned', 'You have been assigned a new task: \"qeqe\"', '', 1, 44, 'task', '2025-09-24 18:21:37'),
(28, 2, 'Task Progress Update', 'User User updated task status to \'completed\': \"qeqe\"', '', 1, 44, 'task', '2025-09-24 18:23:51'),
(29, 12, 'New Task Assigned', 'You have been assigned a new task: \"F\"', '', 1, 45, 'task', '2025-09-24 18:39:28'),
(30, 12, 'Task Updated', 'Your assigned task has been updated: \"F\"', '', 1, 45, 'task', '2025-09-24 18:40:08'),
(31, 12, 'Task Deleted', 'A task assigned to you was deleted: \"F\"', '', 1, 45, 'task', '2025-09-24 18:40:34'),
(32, 12, 'New Task Assigned', 'You have been assigned a new task: \"fsaf\"', '', 1, 46, 'task', '2025-09-24 18:48:43'),
(33, 12, 'Task Updated', 'Your assigned task has been updated: \"fsaf\"', '', 1, 46, 'task', '2025-09-24 18:49:14'),
(34, 12, 'New Task Assigned', 'You have been assigned a new task: \"a\"', '', 1, 47, 'task', '2025-09-24 18:49:23'),
(35, 12, 'Task Deleted', 'A task assigned to you was deleted: \"a\"', '', 1, 47, 'task', '2025-09-24 18:49:40'),
(36, 12, 'Task Deleted', 'A task assigned to you was deleted: \"fsaf\"', '', 1, 46, 'task', '2025-09-24 18:49:42'),
(37, 12, 'New Task Assigned', 'You have been assigned a new task: \"dad\"', '', 1, 48, 'task', '2025-09-24 18:55:28'),
(38, 12, 'Task Deleted', 'A task assigned to you was deleted: \"dad\"', '', 1, 48, 'task', '2025-09-24 18:55:32');

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
(42, 'dawd', 'dawd', '2025-09-25', 'high', 'completed', 12, 2, '2025-09-24 18:14:34', '2025-09-24 18:14:57'),
(43, 'Stock', 'need to stock', '2025-09-25', 'high', 'completed', 12, 2, '2025-09-24 18:18:23', '2025-09-24 18:19:04'),
(44, 'qeqe', 'eqe', '2025-09-25', 'medium', 'completed', 12, 2, '2025-09-24 18:21:37', '2025-09-24 18:23:51');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(2, 'Admin Admin', 'admin@gmail.com', '$2y$10$12eaVk51tyeWfHaOcZiTrObIji1XK78HiMLVtr3XyIBIumJG/Ttmq', 'admin', '2025-08-20 08:38:05', '2025-09-24 16:42:14'),
(12, 'User User', 'user@gmail.com', '$2y$10$4V3txzQcnjB5927JYJyNY.QPTmCW9q3Be0GIv5zQcMUflcIyh3Jki', 'user', '2025-09-24 17:54:38', '2025-09-24 17:54:38');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

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
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
