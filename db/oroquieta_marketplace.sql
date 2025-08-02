-- 4th edit by EARL
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 02, 2025 at 04:56 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `oroquieta_marketplace`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `profile_image`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'admin', '', '$2y$10$WqS4wckzX8YBNJlKNGtXyukb8nfDzIWgVRgQ15lVak4KOezHyy076', NULL, NULL, NULL, NULL, 1, '2025-04-25 08:31:00', '2025-04-25 08:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_type` enum('admin','seller') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_type`, `recipient_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 'seller', 1, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php', 0, '2025-08-01 12:18:18'),
(2, 'seller', 4, 'Application Status Update', 'Your seller application has been reviewed. Please check for more details.', 'application_status.php', 0, '2025-08-01 12:19:40'),
(3, 'seller', 20, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php', 0, '2025-08-01 15:20:38'),
(4, 'seller', 21, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php', 0, '2025-08-02 13:42:53'),
(5, 'seller', 22, 'Application Rejected', 'Your seller application has been reviewed and rejected. Please contact support for more details.', 'application_status.php', 0, '2025-08-02 14:13:34');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` text NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash_on_delivery','gcash','bank_transfer') DEFAULT 'cash_on_delivery',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('pending','approved','rejected','suspended') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `profile_image`, `facebook_url`, `is_active`, `status`, `created_at`, `updated_at`) VALUES
(1, 'seller', 'adrenalinepop301@gmail.com', '$2y$10$gZyvlOsdCVMX4EGDABjkhOhfh6DkxLH9u.sXsSaCRsJu93pcdHm1e', 'Mike', 'Will', '0912345678', NULL, NULL, '', 1, 'approved', '2025-04-25 06:29:15', '2025-08-01 12:53:10'),
(3, 'COMLAB', 'banawaearllawrence83@gmail.com', '$2y$10$NJH4mE9bgwmMN50tlDmsvekeyGDQyK67LzaMtyDdCn1r/jrmPDAW6', 'ea', 'dwad', '341231', NULL, NULL, '', 1, 'approved', '2025-07-31 11:33:39', '2025-08-01 12:53:10'),
(4, 'COMLABb', 'banawaearllawrence@gmail.com', '$2y$10$qTEjzdWc.W1nUWAXpirhf.RFmK8ptNsbo6FseYpDHOOwV83qhYirG', 'qwerty', 'awds', '123456789', NULL, NULL, '', 1, 'approved', '2025-07-31 11:35:56', '2025-08-01 12:53:10'),
(5, 'earl lawrence', 'user1@user.com', '$2y$10$u4EOkLfPSlAS/osK6wPhtew5M0ux6bObhwh2NbMuc4SvaSK5feZ4m', 'qwerty', 'dwad', '341231', NULL, NULL, '', 1, 'approved', '2025-08-01 11:31:15', '2025-08-01 12:53:10'),
(6, 'bxpmco', 'margie.callenero40@gmail.com', '$2y$10$Iqj/FwhwAgf5Nz.sKdniFuAI8bmE.wxs0zYdZpaDMgTh5fXKwA.cy', 'qwerty', 'qwerty', '123456789', NULL, NULL, '', 1, 'approved', '2025-08-01 11:33:06', '2025-08-01 12:53:10'),
(19, 'COMLABb5', 'banawaearllawwwrence8333@gmail.com', '$2y$10$gzz5nIAdj0wdR04sQd8YX.h.ClGByiubXdsle1VMXatxvXELJrKBC', 'qwerty', 'dwad', '123456789', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee', 1, 'pending', '2025-08-01 14:05:45', '2025-08-01 14:05:45'),
(20, 'batman', 'dc@comics.com', '$2y$10$UvxiJfeBnp82.UFez7dWU.lQzuPk80SpbuegKOtzpYF2AfFW5xGXS', 'batman', 'dc@comics.com', '123456', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee/', 1, 'approved', '2025-08-01 15:08:04', '2025-08-01 15:20:38'),
(21, 'superman', 'superdc@comics.com', '$2y$10$Ja88JUjliZ0WjRfKfyAEkuHkMsPyjMl4alB342ZaUjCHUGNcFYRMe', 'superman', 'superman', '123456789', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee/', 1, 'approved', '2025-08-02 13:42:09', '2025-08-02 13:42:53'),
(22, 'cyborg', 'cyborgdc@comics.com', '$2y$10$IlM6bRw04Pp9uWl/K6AwgOl9yBqiNmq52TOxZ0h38oFpOv5CSo4aS', 'batman', 'superman', '123123123', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee/', 0, 'rejected', '2025-08-02 14:13:15', '2025-08-02 14:13:34');

-- --------------------------------------------------------

--
-- Table structure for table `seller_applications`
--

CREATE TABLE `seller_applications` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_phone` varchar(20) NOT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `documents_submitted` text DEFAULT NULL,
  `selected_stall` varchar(10) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_applications`
--

INSERT INTO `seller_applications` (`id`, `seller_id`, `business_name`, `business_phone`, `tax_id`, `documents_submitted`, `selected_stall`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Fish Fish', '0912345678', '123456789', '[\"uploads\\/seller_documents\\/680b2bbbb6ef7.jpg\"]', NULL, 'approved', '', '2025-04-25 06:29:15', '2025-08-01 12:18:18'),
(2, 3, '1231', '123123', '123', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688b4ee236dae.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688b4ee237f01.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688b4ee2391e8.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688b4ee23963d.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688b4ee239959.jpeg\"}', 'B9', 'rejected', NULL, '2025-07-31 11:33:39', '2025-08-01 13:46:48'),
(3, 4, 'qwert', '13212313', '123131', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688b55188ee4b.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688b55188f892.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688b5518904ab.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688b551890fa0.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688b551891624.jpeg\"}', 'M9', 'rejected', '', '2025-07-31 11:35:56', '2025-08-01 12:19:40'),
(4, 5, 'adwaw', '23123', '123123123', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688ca57e1d8f3.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688ca57e1ebc6.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688ca57e1f10a.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688ca57e1f8a7.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688ca57e1fc5c.jpeg\"}', 'T5', 'rejected', NULL, '2025-08-01 11:31:15', '2025-08-01 12:04:40'),
(5, 6, '1231456', '12345678', '12345', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688ca5ef1fc06.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688ca5ef200d8.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688ca5ef209d8.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688ca5ef20f45.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688ca5ef272b7.jpeg\"}', 'M11', 'rejected', NULL, '2025-08-01 11:33:06', '2025-08-01 12:04:36'),
(7, 19, 'comics', '13212313', '12312313', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688cc9b740c91.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688cc9b741353.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688cc9b741832.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688cc9b741dee.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688cc9b742210.jpeg\"}', 'T2', 'approved', NULL, '2025-08-01 14:05:45', '2025-08-01 14:07:16'),
(8, 20, 'dc', '1234566', '12343432', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688cd84f09114.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688cd84f09f5a.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688cd84f0a75f.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688cd84f0ab31.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688cd84f0ae81.jpeg\"}', 'T8', 'approved', NULL, '2025-08-01 15:08:04', '2025-08-01 15:20:38'),
(9, 21, 'dc', '1234566', '12343432', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688e15ae3e4f4.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688e15ae3f533.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688e15ae3fc85.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688e15ae40564.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688e15ae40bf3.jpeg\"}', 'R3', 'approved', NULL, '2025-08-02 13:42:09', '2025-08-02 13:42:53'),
(10, 22, 'cyborg meatshop', '1234566', '12343432', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688e1cf8952e2.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688e1cf89615c.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688e1cf8968a5.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688e1cf896c59.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688e1cf897c21.jpeg\"}', 'M6', 'rejected', '', '2025-08-02 14:13:15', '2025-08-02 14:13:34');

-- --------------------------------------------------------

--
-- Table structure for table `stalls`
--

CREATE TABLE `stalls` (
  `id` int(11) NOT NULL,
  `stall_number` varchar(20) NOT NULL,
  `floor_number` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `size` decimal(10,2) NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `current_seller_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stalls`
--

INSERT INTO `stalls` (`id`, `stall_number`, `floor_number`, `section`, `size`, `monthly_rent`, `status`, `current_seller_id`, `description`, `amenities`, `created_at`, `updated_at`) VALUES
(1, 'T1', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(2, 'T2', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-08-02 14:15:15'),
(3, 'T3', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(4, 'T4', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(5, 'T5', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-08-02 14:17:00'),
(6, 'T6', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(7, 'T7', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(8, 'T8', 1, 'Top Row', 12.00, 2500.00, 'occupied', 20, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-08-01 15:20:38'),
(9, 'T9', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(10, 'T10', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(11, 'T11', 1, 'Top Row', 12.00, 2500.00, 'available', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(12, 'B1', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(13, 'B2', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(14, 'B3', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(15, 'B4', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(16, 'B5', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(17, 'B6', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-08-02 14:17:00'),
(18, 'B7', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(19, 'B8', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(20, 'B9', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-08-02 14:17:00'),
(21, 'B10', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(22, 'B11', 1, 'Bottom Row', 12.00, 2500.00, 'available', NULL, 'Bottom row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(23, 'L1', 1, 'Left Column', 10.00, 2200.00, 'available', NULL, 'Left column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(24, 'L2', 1, 'Left Column', 10.00, 2200.00, 'available', NULL, 'Left column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(25, 'L3', 1, 'Left Column', 10.00, 2200.00, 'available', NULL, 'Left column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(26, 'L4', 1, 'Left Column', 10.00, 2200.00, 'available', NULL, 'Left column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(27, 'L5', 1, 'Left Column', 10.00, 2200.00, 'available', NULL, 'Left column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(28, 'L6', 1, 'Left Column', 10.00, 2200.00, 'available', NULL, 'Left column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(29, 'R1', 1, 'Right Column', 10.00, 2200.00, 'available', NULL, 'Right column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(30, 'R2', 1, 'Right Column', 10.00, 2200.00, 'available', NULL, 'Right column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(31, 'R3', 1, 'Right Column', 10.00, 2200.00, 'occupied', 21, 'Right column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-08-02 13:42:53'),
(32, 'R4', 1, 'Right Column', 10.00, 2200.00, 'available', NULL, 'Right column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(33, 'R5', 1, 'Right Column', 10.00, 2200.00, 'available', NULL, 'Right column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(34, 'R6', 1, 'Right Column', 10.00, 2200.00, 'available', NULL, 'Right column general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(35, 'F1', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(36, 'F2', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(37, 'F3', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(38, 'F4', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(39, 'F5', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(40, 'F6', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(41, 'F7', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(42, 'F8', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(43, 'F9', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(44, 'F10', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(45, 'F11', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(46, 'F12', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(47, 'F13', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(48, 'F14', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(49, 'F15', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(50, 'F16', 1, 'Fish Section', 8.00, 2800.00, 'available', NULL, 'Fish vendor stall with drainage', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(51, 'M1', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(52, 'M2', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(53, 'M3', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(54, 'M4', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(55, 'M5', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(56, 'M6', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-08-02 14:13:34'),
(57, 'M7', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(58, 'M8', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(59, 'M9', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-08-02 14:17:00'),
(60, 'M10', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(61, 'M11', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-08-02 14:17:00'),
(62, 'M12', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(63, 'M13', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(64, 'M14', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(65, 'M15', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53'),
(66, 'M16', 1, 'Meat Section', 8.00, 3000.00, 'available', NULL, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-07-31 11:31:53');

-- --------------------------------------------------------

--
-- Table structure for table `stall_applications`
--

CREATE TABLE `stall_applications` (
  `id` int(11) NOT NULL,
  `stall_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stall_applications`
--

INSERT INTO `stall_applications` (`id`, `stall_id`, `seller_id`, `application_date`, `status`, `start_date`, `end_date`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 20, 3, '2025-07-31 11:33:39', 'rejected', NULL, NULL, NULL, '2025-07-31 11:33:39', '2025-08-02 14:14:28'),
(2, 59, 4, '2025-07-31 11:35:56', 'pending', NULL, NULL, NULL, '2025-07-31 11:35:56', '2025-07-31 11:35:56'),
(3, 5, 5, '2025-08-01 11:31:15', 'pending', NULL, NULL, NULL, '2025-08-01 11:31:15', '2025-08-01 11:31:15'),
(4, 61, 6, '2025-08-01 11:33:06', 'pending', NULL, NULL, NULL, '2025-08-01 11:33:06', '2025-08-01 11:33:06'),
(6, 2, 19, '2025-08-01 14:05:45', 'pending', NULL, NULL, NULL, '2025-08-01 14:05:45', '2025-08-01 14:05:45'),
(7, 8, 20, '2025-08-01 15:08:04', 'approved', NULL, NULL, NULL, '2025-08-01 15:08:04', '2025-08-01 15:20:38'),
(8, 31, 21, '2025-08-02 13:42:09', 'approved', NULL, NULL, NULL, '2025-08-02 13:42:09', '2025-08-02 13:42:53'),
(9, 56, 22, '2025-08-02 14:13:15', 'rejected', NULL, NULL, NULL, '2025-08-02 14:13:15', '2025-08-02 14:13:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_recipient` (`recipient_type`,`recipient_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_products_seller_id` (`seller_id`),
  ADD KEY `idx_products_category_id` (`category_id`);

--
-- Indexes for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_sellers_status` (`status`);

--
-- Indexes for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `idx_seller_applications_status` (`status`);

--
-- Indexes for table `stalls`
--
ALTER TABLE `stalls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stall_number` (`stall_number`),
  ADD KEY `current_seller_id` (`current_seller_id`),
  ADD KEY `idx_stalls_status` (`status`),
  ADD KEY `idx_stalls_floor` (`floor_number`);

--
-- Indexes for table `stall_applications`
--
ALTER TABLE `stall_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stall_id` (`stall_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `idx_stall_applications_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `seller_applications`
--
ALTER TABLE `seller_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stalls`
--
ALTER TABLE `stalls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `stall_applications`
--
ALTER TABLE `stall_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD CONSTRAINT `seller_applications_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stalls`
--
ALTER TABLE `stalls`
  ADD CONSTRAINT `stalls_ibfk_1` FOREIGN KEY (`current_seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stall_applications`
--
ALTER TABLE `stall_applications`
  ADD CONSTRAINT `stall_applications_ibfk_1` FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stall_applications_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
