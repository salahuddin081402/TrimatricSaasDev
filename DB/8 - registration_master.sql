-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2025 at 07:46 AM
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
-- Database: `trimatricsaas`
--

-- --------------------------------------------------------

--
-- Table structure for table `registration_master`
--

CREATE TABLE `registration_master` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Registration ID',
  `company_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK companies.id (tenant)',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK users.id (1:1 user ↔ registration)',
  `Employee_ID` varchar(60) DEFAULT NULL,
  `Reg_Key` varchar(255) DEFAULT NULL,
  `registration_type` enum('client','company_officer','professional') NOT NULL COMMENT 'Set at start from UI selection',
  `full_name` varchar(150) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `division_id` bigint(20) UNSIGNED NOT NULL,
  `district_id` bigint(20) UNSIGNED NOT NULL,
  `upazila_id` bigint(20) UNSIGNED NOT NULL,
  `person_type` enum('J','B','H','S','P','O') NOT NULL COMMENT 'J=Service Holder, B=Business Man, H=House Wife, S=Student, P=Professional, O=Other',
  `present_address` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `Profession` bigint(20) UNSIGNED DEFAULT NULL,
  `NID` varchar(17) DEFAULT NULL,
  `NID_Photo_Front_Page` varchar(255) DEFAULT NULL,
  `NID_Photo_Back_Page` varchar(255) DEFAULT NULL,
  `Birth_Certificate_Photo` varchar(255) DEFAULT NULL COMMENT 'Image path of birth certificate (required if NID not provided)',
  `approval_status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'users.id who approved/declined',
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0 -> Pending, 1 -> Approved/Active, 2-> Declined , 3-> Inactive',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `registration_master`
--

INSERT INTO `registration_master` (`id`, `company_id`, `user_id`, `Employee_ID`, `Reg_Key`, `registration_type`, `full_name`, `gender`, `date_of_birth`, `phone`, `email`, `division_id`, `district_id`, `upazila_id`, `person_type`, `present_address`, `notes`, `Photo`, `Profession`, `NID`, `NID_Photo_Front_Page`, `NID_Photo_Back_Page`, `Birth_Certificate_Photo`, `approval_status`, `approved_by`, `approved_at`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 1, 5, NULL, NULL, 'company_officer', 'Pending Officer', 'male', NULL, '+8801700000002', 'pending.officer@example.com', 3, 18, 1, 'P', 'Test pending address', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, 0, 1, 1, '2025-09-14 03:20:39', '2025-09-14 03:20:39', NULL),
(3, 1, 1, NULL, NULL, 'company_officer', 'Super Admin', 'male', NULL, '+8801700000001', 'admin@example.com', 3, 18, 1, 'J', 'Head Office', 'Bootstrap approved record for Super Admin', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 1, '2025-09-14 03:27:45', 1, 1, 1, '2025-09-14 03:27:45', '2025-09-14 03:27:45', NULL),
(4, 1, 6, NULL, NULL, 'company_officer', 'CEO Test', 'male', NULL, '+8801701000001', 'ceo.test@example.com', 3, 18, 1, 'P', 'Head Office', 'CEO seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 7, '2025-09-19 07:39:32', 1, 7, 7, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(5, 1, 7, NULL, NULL, 'company_officer', 'HO Admin Test', 'male', NULL, '+8801701000002', 'ho.admin.test@example.com', 3, 18, 2, 'P', 'Head Office', 'HO Admin seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 6, '2025-09-19 07:39:32', 1, 6, 6, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(6, 1, 8, NULL, NULL, 'company_officer', 'HOPM Test', 'male', NULL, '+8801701000003', 'hopm.test@example.com', 3, 18, 3, 'P', 'Head Office', 'HOPM seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 7, '2025-09-19 07:39:32', 1, 7, 7, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(7, 1, 9, NULL, NULL, 'company_officer', 'Division Admin Dhaka', 'male', NULL, '+8801701000004', 'div.admin.dhk@example.com', 3, 18, 1, 'P', 'Dhaka Division', 'Div Admin seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 8, '2025-09-19 07:39:32', 1, 8, 8, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(8, 1, 10, NULL, NULL, 'company_officer', 'District Admin D1', 'male', NULL, '+8801701000005', 'dist.admin.dhk1@example.com', 3, 18, 2, 'P', 'Dhaka District 1', 'Dist Admin seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 9, '2025-09-19 07:39:32', 1, 9, 9, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(9, 1, 11, NULL, NULL, 'company_officer', 'Cluster Admin C1', 'male', NULL, '+8801701000006', 'cl.admin.c1@example.com', 3, 18, 3, 'P', 'Cluster HQ', 'Cluster Admin seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 10, '2025-09-19 07:39:32', 1, 10, 10, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(10, 1, 12, NULL, NULL, 'company_officer', 'Cluster Member A', 'male', NULL, '+8801701000007', 'cm.a@example.com', 3, 18, 1, 'P', 'Cluster Area', 'Member A seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 11, '2025-09-19 07:39:32', 1, 11, 11, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(11, 1, 13, NULL, NULL, 'company_officer', 'Cluster Member B', 'male', NULL, '+8801701000008', 'cm.b@example.com', 3, 18, 2, 'P', 'Cluster Area', 'Member B seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 11, '2025-09-19 07:39:32', 1, 11, 11, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(12, 1, 14, NULL, NULL, 'company_officer', 'Cluster Member C', 'male', NULL, '+8801701000009', 'cm.c@example.com', 3, 18, 3, 'P', 'Cluster Area', 'Member C seed', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 11, '2025-09-19 07:39:32', 1, 11, 11, '2025-09-19 07:39:32', '2025-09-19 07:39:32', NULL),
(13, 1, 2, NULL, NULL, 'client', 'Salahuddin(Client-Reg-Done)', 'male', NULL, '01859227761', 'guest_noreg@example.com', 3, 18, 1, 'B', '28/8, Prominent Housing, Shekertek RD#03, Adabar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', NULL, NULL, 1, 2, 2, '2025-09-21 22:40:09', '2025-09-21 22:56:20', NULL),
(15, 1, 15, NULL, NULL, 'client', 'Guest 2 (reg done)', 'male', '1986-01-31', '01859227762', NULL, 3, 18, 1, 'J', 'Moghbazar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', NULL, NULL, 1, 15, 15, '2025-09-23 13:35:16', '2025-09-23 14:31:56', NULL),
(16, 1, 16, NULL, NULL, 'client', 'Client (Reg Done)', 'male', '1969-09-19', '01859227763', 'guest3.noreg@example.com', 3, 18, 1, 'J', '28/8, Prominent Housing, Shekertek RD#03, Adabar', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', NULL, NULL, 1, 16, 16, '2025-09-24 02:28:12', '2025-09-24 03:40:09', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `registration_master`
--
ALTER TABLE `registration_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reg_user` (`user_id`),
  ADD UNIQUE KEY `uq_reg_company_phone` (`company_id`,`phone`),
  ADD UNIQUE KEY `uq_reg_company_email` (`company_id`,`email`),
  ADD UNIQUE KEY `uq_reg_company_nid` (`company_id`,`NID`),
  ADD KEY `idx_reg_company_id` (`company_id`),
  ADD KEY `idx_reg_user_id` (`user_id`),
  ADD KEY `idx_reg_reg_type` (`registration_type`),
  ADD KEY `idx_reg_division_id` (`division_id`),
  ADD KEY `idx_reg_district_id` (`district_id`),
  ADD KEY `idx_reg_upazila_id` (`upazila_id`),
  ADD KEY `idx_reg_person_type` (`person_type`),
  ADD KEY `idx_reg_approval_status` (`approval_status`),
  ADD KEY `idx_reg_status` (`status`),
  ADD KEY `idx_reg_deleted_at` (`deleted_at`),
  ADD KEY `idx_reg_created_by` (`created_by`),
  ADD KEY `idx_reg_updated_by` (`updated_by`),
  ADD KEY `idx_reg_approved_by` (`approved_by`),
  ADD KEY `idx_reg_employee_id` (`Employee_ID`),
  ADD KEY `idx_reg_reg_key` (`Reg_Key`),
  ADD KEY `idx_reg_profession` (`Profession`),
  ADD KEY `fk_reg_company_regkey` (`company_id`,`Reg_Key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `registration_master`
--
ALTER TABLE `registration_master`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Registration ID';

--
-- Constraints for dumped tables
--

--
-- Constraints for table `registration_master`
--
ALTER TABLE `registration_master`
  ADD CONSTRAINT `fk_reg_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reg_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reg_company_regkey` FOREIGN KEY (`company_id`,`Reg_Key`) REFERENCES `company_reg_keys` (`Company_id`, `reg_key`),
  ADD CONSTRAINT `fk_reg_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  ADD CONSTRAINT `fk_reg_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  ADD CONSTRAINT `fk_reg_profession` FOREIGN KEY (`Profession`) REFERENCES `professions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reg_upazila` FOREIGN KEY (`upazila_id`) REFERENCES `upazilas` (`id`),
  ADD CONSTRAINT `fk_reg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
