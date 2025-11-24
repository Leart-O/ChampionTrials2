-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 06:28 PM
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
-- Database: `citycare`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_logs`
--

CREATE TABLE `ai_logs` (
  `log_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `priority` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `raw_response` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_logs`
--

INSERT INTO `ai_logs` (`log_id`, `report_id`, `priority`, `reason`, `raw_response`, `created_at`) VALUES
(1, 103, 4, 'Large pothole in high-traffic area poses safety hazard to vehicles and could cause accidents or damage.', '{\"priority\": 4, \"reason\": \"Large pothole in high-traffic area poses safety hazard to vehicles and could cause accidents or damage.\"}', '2025-11-24 17:25:47'),
(2, 104, 3, 'Non-functional street lighting reduces nighttime visibility and safety, affecting pedestrians and drivers near a public park. While not an immediate hazard, it poses safety concerns for park users and nearby residents.', '{\"priority\": 3, \"reason\": \"Non-functional street lighting reduces nighttime visibility and safety, affecting pedestrians and drivers near a public park. While not an immediate hazard, it poses safety concerns for park users and nearby residents.\"}', '2025-11-24 17:26:09'),
(3, 105, 2, 'Graffiti on a school wall is a low to moderate priority issue. While it affects the appearance of a public educational facility and should be addressed, it does not pose an immediate safety hazard or impact essential services. Cleaning and inspection are recommended to maintain the school environment and prevent future incidents.', '{\n  \"priority\": 2,\n  \"reason\": \"Graffiti on a school wall is a low to moderate priority issue. While it affects the appearance of a public educational facility and should be addressed, it does not pose an immediate safety hazard or impact essential services. Cleaning and inspection are recommended to maintain the school environment and prevent future incidents.\"\n}', '2025-11-24 17:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `actor_user_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`id`, `report_id`, `action`, `actor_user_id`, `note`, `created_at`) VALUES
(1, 103, 'created', 7, 'Report created', '2025-11-24 17:25:45'),
(2, 104, 'created', 7, 'Report created', '2025-11-24 17:26:07'),
(3, 105, 'created', 7, 'Report created', '2025-11-24 17:26:31'),
(4, 103, 'assigned', 5, 'Assigned to: Road Side Assistance', '2025-11-24 17:27:08'),
(5, 104, 'status_changed', 5, 'Status changed to: In-Progress. ', '2025-11-24 17:27:28'),
(6, 105, 'assigned', 5, 'Assigned to: Vandalism Response Officer', '2025-11-24 17:28:04'),
(7, 105, 'status_changed', 5, 'Status changed to: In-Progress. ', '2025-11-24 17:28:14');

-- --------------------------------------------------------

--
-- Table structure for table `authorities`
--

CREATE TABLE `authorities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authorities`
--

INSERT INTO `authorities` (`id`, `user_id`, `name`, `type`, `contact_email`, `notes`) VALUES
(1, 2, 'Road Side Assistance', 'Road Maintenance', 'roadKs@ks.com', 'They manage roads'),
(2, 3, 'Public Lighting Inspector', 'Lighting Inspector', 'lightInspector@gmail.com', 'They inspect lights'),
(3, 4, 'Vandalism Response Officer', 'Vandalism Response', 'vandalismks@gmail.com', 'They inspect vandalism');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` longblob DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `category` varchar(64) DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `action_due` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `user_id`, `title`, `description`, `image`, `latitude`, `longitude`, `category`, `status_id`, `assigned_to`, `action_due`, `is_verified`, `created_at`) VALUES
(103, 7, 'Pothole on Main Street, Pristina', 'There is a large pothole on the main street near the city center that is dangerous for cars and motorcycles.', NULL, 42.3950655, 21.0278320, 'pothole', 1, 1, '2025-11-25 20:30:00', 0, '2025-11-24 17:25:45'),
(104, 7, 'Broken Street Light in Peja', 'The street light in front of the municipal park is not working for over a week, causing darkness at night.', NULL, 42.6425459, 20.2835083, 'lighting', 2, NULL, NULL, 0, '2025-11-24 17:26:07'),
(105, 7, 'Graffiti on School Wall, Gjakova', 'Someone painted graffiti on the wall of the local elementary school, needs cleaning and inspection.', NULL, 42.2794679, 20.9598541, 'other', 2, 3, '2025-12-01 12:00:00', 0, '2025-11-24 17:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `report_status`
--

CREATE TABLE `report_status` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_status`
--

INSERT INTO `report_status` (`status_id`, `status_name`) VALUES
(3, 'Fixed'),
(2, 'In-Progress'),
(1, 'Pending'),
(4, 'Rejected');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `role_id`, `created_at`) VALUES
(1, 'Admin', 'admin@admin.com', '$2y$10$t6tnmZg0Z.JziliOaJkanOJS8b8ODrYGZn7PRYkb3CEMpBq2laVY2', 3, '2025-11-24 00:00:01'),
(2, 'RoadKs', 'roadks@ks.com', '$2y$10$t6tnmZg0Z.JziliOaJkanOJS8b8ODrYGZn7PRYkb3CEMpBq2laVY2', 4, '2025-11-24 00:00:02'),
(3, 'LightInspector', 'lightInspector@gmail.com', '$2y$10$t6tnmZg0Z.JziliOaJkanOJS8b8ODrYGZn7PRYkb3CEMpBq2laVY2', 4, '2025-11-24 00:00:03'),
(4, 'VandalismKs', 'vandalismks@gmail.com', '$2y$10$t6tnmZg0Z.JziliOaJkanOJS8b8ODrYGZn7PRYkb3CEMpBq2laVY2', 4, '2025-11-24 00:00:04'),
(5, 'MunicipalityHead', 'municipality@city.com', '$2y$10$t6tnmZg0Z.JziliOaJkanOJS8b8ODrYGZn7PRYkb3CEMpBq2laVY2', 2, '2025-11-24 00:00:05'),
(7, 'user', 'user@user.com', '$2y$10$t6tnmZg0Z.JziliOaJkanOJS8b8ODrYGZn7PRYkb3CEMpBq2laVY2', 1, '2025-11-24 17:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`role_id`, `role_name`) VALUES
(3, 'Admin'),
(4, 'Authority'),
(1, 'Civilian'),
(2, 'Municipality Head');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `priority` (`priority`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `actor_user_id` (`actor_user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `authorities`
--
ALTER TABLE `authorities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `category` (`category`),
  ADD KEY `latitude` (`latitude`),
  ADD KEY `longitude` (`longitude`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `report_status`
--
ALTER TABLE `report_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_logs`
--
ALTER TABLE `ai_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `authorities`
--
ALTER TABLE `authorities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `report_status`
--
ALTER TABLE `report_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD CONSTRAINT `ai_logs_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_trail_ibfk_2` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `authorities`
--
ALTER TABLE `authorities`
  ADD CONSTRAINT `authorities_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `report_status` (`status_id`),
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `authorities` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
