-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 08:17 PM
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
-- Database: `auth_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Company/Tenant ID',
  `country_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK to country',
  `name` varchar(150) NOT NULL COMMENT 'Company or tenant name',
  `slug` varchar(190) NOT NULL,
  `description` varchar(255) DEFAULT NULL COMMENT 'Details about the company',
  `address` varchar(255) DEFAULT NULL COMMENT 'Address of the company',
  `contact_no` varchar(255) DEFAULT NULL COMMENT 'Contact No of the company',
  `logo` varchar(255) DEFAULT NULL COMMENT 'Path or URL to company logo',
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who created this company',
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who last updated this company',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `country_id`, `name`, `slug`, `description`, `address`, `contact_no`, `logo`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Trimatric Global', 'trimatric-global', 'Primary tenant for Super Admin phase', 'Dhaka, Bangladesh', '+8801XXXXXXXXX', 'assets/images/bangladesh/trimatric-global/logo/trimatric-global-logo-1756278501.png', 1, 1, 1, '2025-08-11 13:13:45', '2025-08-27 01:08:21', NULL),
(2, 1, 'ABC Limited', 'abc-limited', NULL, NULL, NULL, 'assets/images/bangladesh/abc-limited/logo/abc-limited-logo-1756270821.png', 1, 1, 1, '2025-08-26 23:00:21', '2025-08-26 23:00:21', NULL),
(3, 1, 'ARC House Limited', 'arc-house-limited', 'Architecture & Interior Design Farm', 'Dhanmondi', '+8801804753698', 'assets/images/bangladesh/arc-house-limited/logo/arc-house-limited-logo-1756278827.png', 1, 1, 1, '2025-08-27 01:13:47', '2025-08-27 01:14:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Country ID',
  `name` varchar(150) NOT NULL COMMENT 'Country name',
  `short_code` varchar(10) DEFAULT NULL COMMENT 'Short Code. Say, BD for Bangladesh',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who created this Country',
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who last updated this Country',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`, `short_code`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Bangladesh', 'BD', 1, 1, '2025-08-11 13:13:45', '2025-08-11 13:13:45', NULL),
(2, 'India', 'IN', 1, 1, '2025-08-13 02:29:48', '2025-08-13 02:30:11', NULL),
(3, 'Pakistan', 'PAK', 1, 1, '2025-08-21 19:15:59', '2025-08-21 19:15:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK to companies',
  `role_type_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK to role_types',
  `name` varchar(100) NOT NULL COMMENT 'Role name (e.g., Zonal Admin)',
  `description` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `company_id`, `role_type_id`, `name`, `description`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'Super Admin', 'Super Admin for Trimatric Global', 1, 1, '2025-08-11 13:13:45', '2025-08-11 13:13:45', NULL),
(2, 1, 2, 'CEO', 'Chief Executive Officer', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(3, 1, 2, 'Head Office Admin', 'HR/Approvals & role assignments', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(4, 1, 2, 'Head Office Project Manager', 'PM overseeing all divisions', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(5, 1, 3, 'Division Admin', 'Admin for a single division', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(6, 1, 3, 'District Admin', 'Admin for a single district', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(7, 1, 3, 'Cluster Admin', 'Admin for a single cluster', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(8, 1, 3, 'Cluster Member', 'Member working inside a cluster', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(9, 1, 4, 'Client', 'Registered client', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(10, 1, 4, 'Guest', 'Unregistered visitor', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(11, 1, 5, 'Professional', 'Independent professional', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_types`
--

CREATE TABLE `role_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Role type/category name (e.g., Super Admin, Head Office, Vendor, Client, etc.)',
  `description` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_types`
--

INSERT INTO `role_types` (`id`, `name`, `description`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Super Admin', 'Global Super Admin role type', 1, 1, '2025-08-11 13:13:45', '2025-08-11 13:13:45', NULL),
(2, 'Head Office', 'Head Office leadership & HR functions', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(3, 'Business Officers', 'Division/District/Cluster management', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(4, 'Client', 'End customers / clients', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL),
(5, 'Professional', 'Independent professionals', 1, 1, '2025-09-12 14:35:17', '2025-09-12 14:35:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Ox6DqqKRxHa6kJYOLSp78x1UZVU7cvSOyspDUXOh', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoic1R0aUJiREJCbzVCYkVyaHhCTnRUV3hGZkJhWWhvRnE4WGFhWnJhOSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9ob21lIjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO319', 1765892704),
('XwQ6A0JwwYElXJgsDsv88SW1OLPm3rLZoo6avzwu', 54, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoib21vemZNeTdFdEF5MzdKZjBYQVczUzBBN00wbzhjOTI2bnl4eTB6RSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9iYWNrZW5kL2NvbXBhbnkvdHJpbWF0cmljLWdsb2JhbC9kYXNoYm9hcmQvcHVibGljIjtzOjU6InJvdXRlIjtzOjg6ImNvbXBhbnkuIjt9czoxOToic29jaWFsX2NvbXBhbnlfc2x1ZyI7czoxNjoidHJpbWF0cmljLWdsb2JhbCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTQ7fQ==', 1765893054);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK to companies',
  `role_id` bigint(20) UNSIGNED NOT NULL COMMENT 'FK to roles',
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `provider_id` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL COMMENT 'Token for Laravel remember me authentication',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=active, 0=inactive',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_id`, `role_id`, `name`, `email`, `phone`, `password`, `provider`, `provider_id`, `remember_token`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'Super Admin', 'admin@example.com', NULL, '$2y$10$wVnIhQ7bqgqfQkq3oF7mAui3pQ7R0Y0W2QeQ0g7M6bqf4e3H.0p8a', NULL, NULL, NULL, 1, 1, 1, '2025-08-11 13:13:45', '2025-10-25 07:26:03', NULL),
(2, 1, 9, 'Guest NoReg', 'guest_noreg@example.com', NULL, '$2y$12$wCqYz0x3rQvQqk3rIysb5uQe2T4S7WQJd0y6L9p9iZy1mM0QxJrme', NULL, NULL, NULL, 1, 1, 1, '2025-09-13 08:56:59', '2025-09-21 22:40:09', NULL),
(5, 1, 10, 'Pending Officer', 'pending.officer@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-09-14 03:20:39', '2025-10-30 16:35:01', NULL),
(6, 1, 10, 'CEO Test', 'ceo.test@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', '2025-10-30 17:45:22', NULL),
(7, 1, 10, 'HO Admin Test', 'ho.admin.test@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', '2025-10-30 16:35:33', NULL),
(8, 1, 10, 'HOPM Test', 'hopm.test@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', '2025-10-30 16:29:51', NULL),
(9, 1, 2, 'Division Admin Dhaka', 'div.admin.dhk@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', '2025-10-30 15:51:49', NULL),
(10, 1, 6, 'District Admin D1', 'dist.admin.dhk1@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', NULL, NULL),
(11, 1, 7, 'Cluster Admin C1', 'cl.admin.c1@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', '2025-11-02 06:07:52', NULL),
(12, 1, 8, 'Cluster Member A', 'cm.a@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', NULL, NULL),
(13, 1, 8, 'Cluster Member B', 'cm.b@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', NULL, NULL),
(14, 1, 4, 'Cluster Member C', 'cm.c@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-19 07:39:32', '2025-10-31 16:54:24', NULL),
(15, 1, 9, 'Guest 2 (reg done)', 'guest2-noreg@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, NULL, NULL, '2025-09-22 15:56:40', '2025-09-23 14:31:56', NULL),
(16, 1, 9, 'Client (Reg Done)', 'guest3.noreg@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-09-24 08:22:01', '2025-09-24 03:40:09', NULL),
(17, 1, 9, 'Guest User A', 'guest.a@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-06 07:19:18', '2025-10-06 01:27:56', NULL),
(18, 1, 9, 'Guest User B', 'guest.b@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-06 07:19:18', '2025-10-06 03:05:17', NULL),
(19, 1, 7, 'Guest User C', 'guest.c@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-06 07:19:18', '2025-10-25 00:39:58', NULL),
(20, 1, 10, 'Guest (Role10) 01', 'guest.role10.01@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-10 11:19:08', NULL),
(21, 1, 10, 'Guest (Role10) 02', 'guest.role10.02@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-10 11:19:08', NULL),
(22, 1, 10, 'Guest (Role10) 03', 'guest.role10.03@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-10 11:19:08', NULL),
(23, 1, 3, 'Guest (Role10) 04', 'guest.role10.04@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-31 16:55:38', NULL),
(24, 1, 10, 'Guest (Role10) 05', 'guest.role10.05@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-10 11:19:08', NULL),
(25, 1, 10, 'Guest (Role10) 06', 'guest.role10.06@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-10 11:19:08', NULL),
(26, 1, 6, 'Guest (Role10) 07', 'guest.role10.07@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-30 15:52:54', NULL),
(27, 1, 8, 'Guest (Role10) 08', 'guest.role10.08@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-25 10:11:57', NULL),
(28, 1, 5, 'Guest (Role10) 09', 'guest.role10.09@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-11-02 11:59:07', NULL),
(29, 1, 10, 'Guest (Role10) 10', 'guest.role10.10@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-10 11:19:08', '2025-10-30 16:31:30', NULL),
(30, 1, 3, 'Guest (Role10) 11', 'guest.role10.11@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-30 00:30:43', NULL),
(31, 1, 4, 'Guest (Role10) 12', 'guest.role10.12@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-24 23:20:08', NULL),
(32, 1, 5, 'Guest (Role10) 13', 'guest.role10.13@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-24 23:21:20', NULL),
(33, 1, 5, 'Guest (Role10) 14', 'guest.role10.14@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-24 23:21:23', NULL),
(34, 1, 10, 'Guest (Role10) 15', 'guest.role10.15@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(35, 1, 10, 'Guest (Role10) 16', 'guest.role10.16@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(36, 1, 10, 'Guest (Role10) 17', 'guest.role10.17@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(37, 1, 10, 'Guest (Role10) 18', 'guest.role10.18@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(38, 1, 10, 'Guest (Role10) 19', 'guest.role10.19@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(39, 1, 10, 'Guest (Role10) 20', 'guest.role10.20@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(40, 1, 10, 'Guest (Role10) 21', 'guest.role10.21@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(41, 1, 10, 'Guest (Role10) 22', 'guest.role10.22@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(42, 1, 10, 'Guest (Role10) 23', 'guest.role10.23@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(43, 1, 10, 'Guest (Role10) 24', 'guest.role10.24@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(44, 1, 10, 'Guest (Role10) 25', 'guest.role10.25@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1, 1, 1, '2025-10-14 13:35:23', '2025-10-14 13:35:23', NULL),
(47, 1, 10, 'tushar mon', 'montushar@gmail.com', NULL, '$2y$12$mrmml/t2SihGun9HTT.IG.ZmAd1VF5t1ZtNdKFOQl2hdP0lMT1Ake', 'google', '111622485696823104151', NULL, 1, NULL, NULL, '2025-12-16 03:08:49', '2025-12-16 03:08:49', NULL),
(48, 1, 10, 'Md. Nawshad Pervage', 'mdnawshadpervage@gmail.com', NULL, '$2y$12$PnC6wBV2DUYubLEAV7RrruX3ewW6ZXhTJZpFqCdnrEZy.abP/bQI2', 'google', '101648236039104588170', NULL, 1, NULL, NULL, '2025-12-16 03:11:08', '2025-12-16 03:11:08', NULL),
(52, 1, 10, 'Md. Nawshad Pervage', 'tushar@gmail.com', '01755352842', '$2y$12$MzVnTpc5lHBJ1CsgDyGLq.QHHSptxnnycH08u.g7xG6b2f2riMWC.', NULL, NULL, NULL, 1, NULL, NULL, '2025-12-16 06:59:30', '2025-12-16 07:36:32', NULL),
(53, 1, 10, 'নওশাদ পারভেজ', 'nawshadpervage@gmail.com', NULL, '$2y$12$oWglz0Xeeg4ce0TjZm1mz.6pBalLKhj8Ze9yJalI5lxkPDMtApwZG', 'facebook', '24674262018916640', NULL, 1, NULL, NULL, '2025-12-16 07:07:46', '2025-12-16 07:07:46', NULL),
(54, 1, 10, 'Dot Code Digital', 'dotcodedigital@gmail.com', NULL, '$2y$12$cLYdp1KPQtdyISCieb04G.saAQaBqa3nRwT6t5VdBQzYFW0Adsz6G', 'google', '100382161714195675816', NULL, 1, NULL, NULL, '2025-12-16 07:50:54', '2025-12-16 07:50:54', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_name` (`name`),
  ADD UNIQUE KEY `companies_name_unique` (`name`),
  ADD UNIQUE KEY `companies_slug_unique` (`slug`),
  ADD KEY `idx_company_deleted_at` (`deleted_at`),
  ADD KEY `idx_company_created_by` (`created_by`),
  ADD KEY `idx_company_updated_by` (`updated_by`),
  ADD KEY `companies_country_id_foreign` (`country_id`),
  ADD KEY `companies_status_index` (`status`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_name_unique` (`name`),
  ADD KEY `idx_country_deleted_at` (`deleted_at`),
  ADD KEY `idx_country_created_by` (`created_by`),
  ADD KEY `idx_country_updated_by` (`updated_by`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_company_name` (`company_id`,`name`),
  ADD KEY `idx_roles_company_id` (`company_id`),
  ADD KEY `idx_roles_role_type_id` (`role_type_id`),
  ADD KEY `idx_roles_deleted_at` (`deleted_at`),
  ADD KEY `idx_roles_created_by` (`created_by`),
  ADD KEY `idx_roles_updated_by` (`updated_by`);

--
-- Indexes for table `role_types`
--
ALTER TABLE `role_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_types_name_unique` (`name`),
  ADD KEY `idx_role_type_deleted_at` (`deleted_at`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_email` (`company_id`,`email`),
  ADD KEY `idx_users_company_id` (`company_id`),
  ADD KEY `idx_users_role_id` (`role_id`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_deleted_at` (`deleted_at`),
  ADD KEY `idx_users_created_by` (`created_by`),
  ADD KEY `idx_users_updated_by` (`updated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Company/Tenant ID', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Country ID', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `role_types`
--
ALTER TABLE `role_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_role_type_id_foreign` FOREIGN KEY (`role_type_id`) REFERENCES `role_types` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
