-- IT Support Management System Database Schema
-- Created: 2024
-- Description: Full IT Support CRUD application with audit logs, roles, and reporting

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+06:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `it_support` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `it_support`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','pc','ups','cctv','printer','ipphone','hardware') NOT NULL DEFAULT 'hardware',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: branches
-- --------------------------------------------------------
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_code` varchar(20) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_type` enum('Branch','Sub-Branch','MBO','FT','Division') NOT NULL DEFAULT 'Branch',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: issues
-- --------------------------------------------------------
CREATE TABLE `issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('PC','UPS','CCTV','Printer','IP Phone','Hardware','Others') NOT NULL,
  `issue_date` date NOT NULL,
  `vendor_name` varchar(100) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `branch_officer` varchar(100) DEFAULT NULL,
  `contact_ipphone` varchar(50) DEFAULT NULL,
  `problem_description` text NOT NULL,
  `remarks` text DEFAULT NULL,
  `officer_it` varchar(100) DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `solved` tinyint(1) NOT NULL DEFAULT 0,
  `manager_name` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `last_updated_by` int(11) DEFAULT NULL,
  `last_updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `created_by` (`created_by`),
  KEY `last_updated_by` (`last_updated_by`),
  KEY `category` (`category`),
  KEY `issue_date` (`issue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: audit_logs
-- --------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `action` enum('created','updated','status_changed','solved') NOT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_by_name` varchar(100) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  KEY `updated_by` (`updated_by`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Foreign Keys
-- --------------------------------------------------------
ALTER TABLE `issues`
  ADD CONSTRAINT `fk_issues_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_issues_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_issues_updated_by` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Default Admin User (password: admin123)
-- --------------------------------------------------------
-- Default admin password: admin123
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('admin', '$2y$10$gRErETZTgYtPijBNL0FJduHjxgc0K4y/Jbn6t.o0WaGtjY5QKc1A.', 'System Administrator', 'admin@itsupport.local', 'admin');

-- --------------------------------------------------------
-- Sample Branches
-- --------------------------------------------------------
INSERT INTO `branches` (`branch_code`, `branch_name`, `branch_type`) VALUES
('HO', 'Head Office', 'Division'),
('BR001', 'Dhaka Main Branch', 'Branch'),
('BR002', 'Chittagong Branch', 'Branch'),
('SB001', 'Mirpur Sub-Branch', 'Sub-Branch'),
('SB002', 'Gulshan Sub-Branch', 'Sub-Branch'),
('MBO001', 'Uttara MBO', 'MBO'),
('FT001', 'Narayanganj FT', 'FT'),
('DIV001', 'IT Division', 'Division');

COMMIT;
