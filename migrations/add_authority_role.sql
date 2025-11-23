-- Add Authority role and link authorities to users
-- Run this after the main db.sql migration

-- Add Authority role
INSERT INTO `user_roles` (`role_id`, `role_name`) VALUES (4, 'Authority') 
ON DUPLICATE KEY UPDATE role_name = 'Authority';

-- Add user_id column to authorities table to link authority records to user accounts
ALTER TABLE `authorities` 
ADD COLUMN `user_id` INT(11) DEFAULT NULL AFTER `id`,
ADD KEY `user_id` (`user_id`),
ADD CONSTRAINT `authorities_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

