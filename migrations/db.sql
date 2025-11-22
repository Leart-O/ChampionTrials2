-- CityCare Database Schema
-- Import this file into phpMyAdmin

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS `citycare` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `citycare`;

-- Table: user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `role_id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: authorities
CREATE TABLE IF NOT EXISTS `authorities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `contact_email` VARCHAR(255) NOT NULL,
  `notes` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: report_status
CREATE TABLE IF NOT EXISTS `report_status` (
  `status_id` INT(11) NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reports
CREATE TABLE IF NOT EXISTS `reports` (
  `report_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `image` LONGBLOB,
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `category` VARCHAR(64) DEFAULT NULL,
  `status_id` INT(11) NOT NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `action_due` DATETIME DEFAULT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `user_id` (`user_id`),
  KEY `status_id` (`status_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `category` (`category`),
  KEY `latitude` (`latitude`),
  KEY `longitude` (`longitude`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `report_status` (`status_id`),
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `authorities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: comments
CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `report_id` (`report_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ai_logs
CREATE TABLE IF NOT EXISTS `ai_logs` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) NOT NULL,
  `priority` INT(11) DEFAULT NULL,
  `reason` TEXT,
  `raw_response` LONGTEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `report_id` (`report_id`),
  KEY `priority` (`priority`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `ai_logs_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_trail
CREATE TABLE IF NOT EXISTS `audit_trail` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `actor_user_id` INT(11) NOT NULL,
  `note` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `actor_user_id` (`actor_user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE,
  CONSTRAINT `audit_trail_ibfk_2` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial data for roles
INSERT INTO `user_roles` (`role_id`, `role_name`) VALUES
(1, 'Civilian'),
(2, 'Municipality Head'),
(3, 'Admin');

-- Insert initial data for statuses
INSERT INTO `report_status` (`status_id`, `status_name`) VALUES
(1, 'Pending'),
(2, 'In-Progress'),
(3, 'Fixed'),
(4, 'Rejected');

COMMIT;

