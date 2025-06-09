-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2025 at 05:51 PM
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
-- Database: `todo_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `status` enum('Pending','In Progress','Completed','Overdue') NOT NULL DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tags` varchar(255) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `estimated_time` int(11) DEFAULT NULL,
  `actual_time` int(11) DEFAULT NULL,
  `recurrence` varchar(50) DEFAULT NULL,
  `parent_task_id` int(11) DEFAULT NULL,
  `completion_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `task_name`, `description`, `assigned_user_id`, `due_date`, `priority`, `status`, `created_at`, `updated_at`, `tags`, `comments`, `attachments`, `estimated_time`, `actual_time`, `recurrence`, `parent_task_id`, `completion_date`) VALUES
(3, 3, 'uap web', 'harus cepet', NULL, '2025-06-11', 'High', 'In Progress', '2025-06-09 22:18:38', '2025-06-09 22:18:38', NULL, NULL, NULL, NULL, 1, 'daily', NULL, NULL),
(4, 3, 'afa', '', 3, '2025-06-11', 'High', 'In Progress', '2025-06-09 22:28:00', '2025-06-09 22:28:00', NULL, NULL, NULL, 600, NULL, 'daily', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `profile_pic` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `profile_pic`, `phone`) VALUES
(1, 'Kiyokou', '$2y$10$TSCjK8a6KU.83red2Nj.tOccK092FqZ0UnqsmJ24XH9LQjgY.hIQS', 'admin', NULL, NULL),
(2, 'user1', '$2y$10$IW/HOAgbQsGmouaghne/GeJngQr7LHkxrA433HpnZapzS2y6YkKn2', 'user', NULL, NULL),
(3, 'ghalib725', '$2y$10$oZOhYQckQHjAiFJxR0uDdOgElgPg3rNd5OGMJFeJhRfusxjZQ3wMG', 'user', 'profile_6846ead5a59c5.jpeg', '083147158593');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_user_id` (`assigned_user_id`),
  ADD KEY `parent_task_id` (`parent_task_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`parent_task_id`) REFERENCES `tasks` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
