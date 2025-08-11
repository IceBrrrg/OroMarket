-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2025 at 03:22 PM
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
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `target_audience` enum('all','sellers','customers','admins') NOT NULL DEFAULT 'all',
  `expiry_date` datetime DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `priority`, `target_audience`, `expiry_date`, `is_pinned`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'holiday', 'way klase', 'medium', 'sellers', '2025-08-13 20:51:00', 0, 1, 20, '2025-08-11 12:48:11', '2025-08-11 12:48:11'),
(2, 'gwapo ko', 'werty', 'medium', 'sellers', '2025-08-20 12:52:00', 0, 1, 20, '2025-08-11 12:48:53', '2025-08-11 12:49:03'),
(3, 'Consumers Protection', 'Dear consumers, please ayaw mo pauwat sa scam, report to admin if ever naay mag scam!', 'high', 'customers', '2025-09-12 00:00:00', 0, 1, 4, '2025-08-11 13:04:04', '2025-08-11 13:04:04');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) DEFAULT NULL COMMENT 'Emoji or icon character',
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `description`, `parent_id`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Fruits', NULL, 'Fresh and organic fruits', NULL, NULL, 1, '2025-08-06 07:23:51', '2025-08-06 07:23:51'),
(2, 'Bread', NULL, 'Fresh baked bread and bakery items', NULL, NULL, 1, '2025-08-06 07:23:51', '2025-08-06 07:23:51'),
(3, 'Vegetable', NULL, 'Fresh vegetables and greens', NULL, NULL, 1, '2025-08-06 07:23:51', '2025-08-06 07:23:51'),
(4, 'Fish', NULL, 'Fresh fish and seafood', NULL, NULL, 1, '2025-08-06 07:23:51', '2025-08-06 07:23:51'),
(5, 'Meat', NULL, 'Quality meat products', NULL, NULL, 1, '2025-08-06 07:23:51', '2025-08-06 07:23:51'),
(6, 'Drinks', NULL, 'Beverages and refreshments', NULL, NULL, 1, '2025-08-06 07:23:51', '2025-08-06 07:23:51'),
(7, 'Sea Food', NULL, 'Fresh seafood and marine products', NULL, NULL, 1, '2025-08-06 07:23:51', '2025-08-06 07:23:51');

-- --------------------------------------------------------

--
-- Table structure for table `chat_blocks`
--

CREATE TABLE `chat_blocks` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `blocked_name` varchar(255) NOT NULL,
  `blocked_contact` varchar(255) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_settings`
--

CREATE TABLE `chat_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_settings`
--

INSERT INTO `chat_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'chat_enabled', '1', '2025-08-09 07:53:17', '2025-08-09 07:53:17'),
(2, 'max_message_length', '1000', '2025-08-09 07:53:17', '2025-08-09 07:53:17'),
(3, 'auto_archive_days', '30', '2025-08-09 07:53:17', '2025-08-09 07:53:17'),
(4, 'welcome_message', 'Hello! How can I help you today?', '2025-08-09 07:53:17', '2025-08-09 07:53:17');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `guest_name` varchar(255) NOT NULL,
  `guest_contact` varchar(255) DEFAULT NULL,
  `seller_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','archived','blocked') DEFAULT 'active',
  `last_message_preview` varchar(100) DEFAULT NULL,
  `guest_ip_address` varchar(45) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `guest_name`, `guest_contact`, `seller_id`, `created_at`, `updated_at`, `status`, `last_message_preview`, `guest_ip_address`, `is_archived`) VALUES
(1, 'earl', '09509720086', 20, '2025-08-09 07:56:46', '2025-08-09 08:19:23', 'active', 'ulol', NULL, 0),
(2, 'yami', '09123456', 23, '2025-08-09 08:19:55', '2025-08-09 08:28:30', 'active', 'Hi! I\'m interested in your product: tuna', NULL, 0),
(3, 'yami', '09123456', 20, '2025-08-09 08:20:22', '2025-08-09 08:24:38', 'active', 'okay', NULL, 0),
(4, 'earl', '09123456', 24, '2025-08-09 11:47:34', '2025-08-09 11:59:44', 'active', 'klaro ana', NULL, 0),
(5, 'yami', '09509720086', 24, '2025-08-09 11:50:15', '2025-08-09 11:50:23', 'active', 'okay', NULL, 0),
(6, '0xletus', 'banaw@gmail.com', 24, '2025-08-09 12:01:43', '2025-08-09 12:03:48', 'active', 'klaro ana', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `template_type` varchar(50) DEFAULT 'default',
  `status` enum('pending','sent','failed','cancelled') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('guest','seller') NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `message_type` enum('text','image','file') DEFAULT 'text',
  `attachment_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_type`, `sender_name`, `message_text`, `sent_at`, `is_read`, `message_type`, `attachment_path`) VALUES
(1, 1, 'guest', 'earl', 'Hi! I\'m interested in your product: malunggay', '2025-08-09 07:56:46', 1, 'text', NULL),
(2, 1, 'guest', 'earl', 'naa oa moy malunggay?', '2025-08-09 07:57:18', 1, 'text', NULL),
(3, 1, 'guest', 'earl', 'Hi! I\'m interested in your product: malunggay', '2025-08-09 08:01:52', 1, 'text', NULL),
(4, 1, 'guest', 'earl', 'woi', '2025-08-09 08:01:57', 1, 'text', NULL),
(5, 1, 'guest', 'earl', 'Hi! I\'m interested in your product: malunggay', '2025-08-09 08:12:08', 1, 'text', NULL),
(6, 1, 'seller', 'batman dc@comics.com', 'wala na yamo choi!', '2025-08-09 08:17:30', 1, 'text', NULL),
(7, 1, 'guest', 'earl', 'Hi! I\'m interested in your product: malunggay', '2025-08-09 08:18:27', 1, 'text', NULL),
(8, 1, 'seller', 'batman dc@comics.com', 'sumo oi', '2025-08-09 08:19:09', 1, 'text', NULL),
(9, 1, 'guest', 'earl', 'hahahahh', '2025-08-09 08:19:19', 1, 'text', NULL),
(10, 1, 'seller', 'batman dc@comics.com', 'ulol', '2025-08-09 08:19:23', 1, 'text', NULL),
(11, 2, 'guest', 'yami', 'Hi! I\'m interested in your product: tuna', '2025-08-09 08:19:55', 0, 'text', NULL),
(12, 3, 'guest', 'yami', 'Hi! I\'m interested in your product: calamansi', '2025-08-09 08:20:22', 1, 'text', NULL),
(13, 3, 'seller', 'batman dc@comics.com', 'pila kabouk?', '2025-08-09 08:20:44', 1, 'text', NULL),
(14, 3, 'guest', 'yami', 'isa', '2025-08-09 08:20:52', 1, 'text', NULL),
(15, 3, 'seller', 'batman dc@comics.com', 'pauli', '2025-08-09 08:20:59', 1, 'text', NULL),
(16, 3, 'guest', 'yami', 'Hi! I\'m interested in your product: calamansi', '2025-08-09 08:24:28', 1, 'text', NULL),
(17, 3, 'seller', 'batman dc@comics.com', 'okay', '2025-08-09 08:24:38', 1, 'text', NULL),
(18, 2, 'guest', 'yami', 'Hi! I\'m interested in your product: tuna', '2025-08-09 08:28:30', 0, 'text', NULL),
(19, 4, 'guest', 'earl', 'Hi! I\'m interested in your product: Lumyagan', '2025-08-09 11:47:34', 1, 'text', NULL),
(20, 4, 'guest', 'earl', 'buhi paka?', '2025-08-09 11:47:38', 1, 'text', NULL),
(21, 5, 'guest', 'yami', 'Hi! I\'m interested in your product: Lumyagan', '2025-08-09 11:50:15', 0, 'text', NULL),
(22, 5, 'guest', 'yami', 'okay', '2025-08-09 11:50:23', 0, 'text', NULL),
(23, 4, 'guest', 'Earl', 'Hi! I\'m interested in your product: Lumyagan', '2025-08-09 11:58:37', 1, 'text', NULL),
(24, 4, 'guest', 'Earl', 'okay', '2025-08-09 11:58:59', 1, 'text', NULL),
(25, 4, 'seller', 'Kurt Cobain', 'klaro ana', '2025-08-09 11:59:44', 1, 'text', NULL),
(26, 6, 'guest', '0xletus', 'Hi! I\'m interested in your product: Lumyagan', '2025-08-09 12:01:43', 1, 'text', NULL),
(27, 6, 'guest', '0xletus', 'luh', '2025-08-09 12:01:56', 1, 'text', NULL),
(28, 6, 'guest', '0xletus', 'si Kurt Cobain', '2025-08-09 12:02:04', 1, 'text', NULL),
(29, 6, 'guest', '0xletus', 'online ampt, kusog signal langit?', '2025-08-09 12:02:44', 1, 'text', NULL),
(30, 6, 'seller', 'Kurt Cobain', 'klaro ana', '2025-08-09 12:03:48', 1, 'text', NULL);

--
-- Triggers `messages`
--
DELIMITER $$
CREATE TRIGGER `update_conversation_preview` AFTER INSERT ON `messages` FOR EACH ROW BEGIN
    UPDATE conversations 
    SET last_message_preview = LEFT(NEW.message_text, 100),
        updated_at = NOW()
    WHERE id = NEW.conversation_id;
END
$$
DELIMITER ;

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
(5, 'seller', 22, 'Application Rejected', 'Your seller application has been reviewed and rejected. Please contact support for more details.', 'application_status.php', 0, '2025-08-02 14:13:34'),
(6, 'seller', 23, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php', 0, '2025-08-06 12:28:36'),
(7, 'seller', 19, 'Application Rejected', 'Your seller application has been reviewed and rejected. Please contact support for more details.', 'application_status.php', 0, '2025-08-09 11:08:41'),
(8, 'seller', 24, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php', 0, '2025-08-09 11:45:11');

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
  `weight` decimal(10,2) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `name`, `description`, `price`, `stock_quantity`, `weight`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES
(5, 20, NULL, 'tenderloin', 'fresh beefds', 369.00, 45, 50.00, 0, 1, '2025-08-04 12:33:06', '2025-08-04 12:44:00'),
(8, 23, 4, 'tuna', 'dlil dubok nga tuna', 240.00, 5, 50.00, 0, 1, '2025-08-06 12:30:43', '2025-08-06 12:30:43'),
(9, 20, 3, 'malunggay', '\"Ang Ultimate Pinoy Superfood nga Dili Ka Makalingaw\" - Malunggay ba, ang dahon nga makahimo nimog healthy pero ang lami sama sa kinang-kinang na papel!\r\nUsahay gitawag sab nila og \"Popeye\'s Jealousy Plant\" kay mas healthy pa ni sa spinach pero ang problema lang, murag nag-kaon ka og grass sa bukid!\r\nO kaha \"Ang Plant nga Gi-bless sa Lola Nimo\" - pirmi jud na isulti sa mga lola nga \"Kaon ana nak, healthy na!\" Pero ikaw naman, \"Ay Lola, bitter man!\"\r\n\"Ang Green Medicina nga Murag Laway sa Iro\" kung lutoon nimo siya sa sabaw - slimy kaayo pero healthy daw!\r\nPero seriously though, grabe ka-healthy ani. Daghan vitamins ug minerals. Maong daghan mga Pinoy nagtanom ani sa likod sa balay - \"Ang Backyard Pharmacy\" ba!\r\nNindot sab ibutang sa tinola, monggo, o kaha sa mga soup. Basta timan-i lang, dili ni lami kung raw - kinahanglan lutoon jud!', 5.00, 20, 0.00, 0, 1, '2025-08-08 14:08:38', '2025-08-08 14:08:38'),
(10, 20, 3, 'calamansi', 'calamansi', 5.00, 120000, 20.00, 0, 1, '2025-08-09 11:36:05', '2025-08-09 11:36:05'),
(11, 20, 2, 'pandesal ni aling nina', 'init pa ang pan tehh!', 5.00, 50, 0.00, 0, 1, '2025-08-09 11:38:29', '2025-08-09 11:38:29'),
(12, 24, 7, 'Lumyagan', 'Lumyagan sa Oroquieta', 130.00, 69, 500.00, 0, 1, '2025-08-09 11:47:13', '2025-08-09 11:47:13');

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

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `display_order`, `created_at`) VALUES
(1, 5, 'uploads/products/product_5_1754310786_0.jpeg', 1, 0, '2025-08-04 12:33:06'),
(4, 8, 'uploads/products/product_8_1754483443_0.jpeg', 1, 0, '2025-08-06 12:30:43'),
(5, 9, 'uploads/products/product_9_1754662118_0.jpeg', 1, 0, '2025-08-08 14:08:38'),
(6, 10, 'uploads/products/product_10_1754739365_0.jpg', 1, 0, '2025-08-09 11:36:05'),
(7, 11, 'uploads/products/product_11_1754739509_0.jpg', 1, 0, '2025-08-09 11:38:29'),
(8, 12, 'uploads/products/product_12_1754740033_0.jpg', 1, 0, '2025-08-09 11:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `product_views`
--

CREATE TABLE `product_views` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `view_count` int(11) DEFAULT 0,
  `last_viewed` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_views`
--

INSERT INTO `product_views` (`id`, `product_id`, `view_count`, `last_viewed`, `created_at`, `updated_at`) VALUES
(1, 5, 4, '2025-08-09 18:54:32', '2025-08-08 13:14:28', '2025-08-09 10:54:32'),
(4, 8, 18, '2025-08-09 16:28:25', '2025-08-08 13:14:28', '2025-08-09 08:28:25'),
(25, 9, 11, '2025-08-09 19:00:23', '2025-08-08 14:08:52', '2025-08-09 11:00:23'),
(53, 12, 7, '2025-08-09 22:00:03', '2025-08-09 11:47:25', '2025-08-09 14:00:03'),
(58, 11, 3, '2025-08-11 20:33:48', '2025-08-09 13:43:32', '2025-08-11 12:33:48'),
(59, 10, 3, '2025-08-09 21:49:05', '2025-08-09 13:43:44', '2025-08-09 13:49:05');

-- --------------------------------------------------------

--
-- Table structure for table `product_view_logs`
--

CREATE TABLE `product_view_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_view_logs`
--

INSERT INTO `product_view_logs` (`id`, `product_id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `viewed_at`) VALUES
(1, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 13:49:34'),
(9, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 13:55:51'),
(15, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 13:56:10'),
(16, 5, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 13:56:19'),
(18, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:01:02'),
(20, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:01:15'),
(21, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:01:29'),
(24, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:03:50'),
(25, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:04:01'),
(26, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:04:38'),
(28, 9, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:08:52'),
(29, 8, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:09:43'),
(32, 5, NULL, 'ka0q7n3g93llbso58afqe40ifk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-08 14:30:54'),
(33, 8, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:42:15'),
(34, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:42:24'),
(35, 8, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:42:27'),
(36, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:42:29'),
(37, 8, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:42:54'),
(38, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:43:00'),
(39, 8, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:43:29'),
(40, 8, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:43:58'),
(41, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:56:25'),
(42, 8, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 07:58:03'),
(43, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 08:09:32'),
(44, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 08:09:46'),
(45, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 08:09:48'),
(46, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 08:12:04'),
(47, 9, NULL, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 08:18:21'),
(48, 8, 20, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 08:19:39'),
(51, 8, 4, 'j48gboff11m7sq7s35eki423un', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 08:28:25'),
(52, 5, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 10:54:32'),
(55, 9, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 11:00:23'),
(56, 12, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 11:47:25'),
(57, 12, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 11:50:10'),
(58, 12, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 11:58:12'),
(59, 12, 24, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 12:01:24'),
(60, 12, 24, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 13:43:13'),
(61, 11, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 13:43:32'),
(62, 10, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 13:43:44'),
(63, 10, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 13:44:42'),
(64, 12, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 13:44:54'),
(65, 10, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 13:49:05'),
(66, 12, NULL, '43olo9tsom772en7oce8i8bkvv', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-09 14:00:03'),
(67, 11, NULL, '500t5e4udusrb3bvaclnlrchfl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 12:33:37'),
(68, 11, NULL, '500t5e4udusrb3bvaclnlrchfl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 12:33:48');

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
(19, 'COMLABb5', 'banawaearllawwwrence8333@gmail.com', '$2y$10$gzz5nIAdj0wdR04sQd8YX.h.ClGByiubXdsle1VMXatxvXELJrKBC', 'qwerty', 'dwad', '123456789', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee', 1, 'rejected', '2025-08-01 14:05:45', '2025-08-09 11:08:41'),
(20, 'batman', 'dc@comics.com', '$2y$10$UvxiJfeBnp82.UFez7dWU.lQzuPk80SpbuegKOtzpYF2AfFW5xGXS', 'batman', 'dc@comics.com', '123456', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee/', 1, 'approved', '2025-08-01 15:08:04', '2025-08-01 15:20:38'),
(21, 'superman', 'superdc@comics.com', '$2y$10$Ja88JUjliZ0WjRfKfyAEkuHkMsPyjMl4alB342ZaUjCHUGNcFYRMe', 'superman', 'superman', '123456789', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee/', 1, 'approved', '2025-08-02 13:42:09', '2025-08-02 13:42:53'),
(22, 'cyborg', 'cyborgdc@comics.com', '$2y$10$IlM6bRw04Pp9uWl/K6AwgOl9yBqiNmq52TOxZ0h38oFpOv5CSo4aS', 'batman', 'superman', '123123123', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee/', 0, 'rejected', '2025-08-02 14:13:15', '2025-08-02 14:13:34'),
(23, '0xletuss', 'yamiyuhiko@gmail.com', '$2y$10$dk0N6PKA2IKV6BNJwt1dr.qftO/.GCXNPyF9UuZE2C.2XFhxv/XIO', 'letuss', 'smits', '12312312', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee', 1, 'approved', '2025-08-06 12:27:50', '2025-08-06 12:28:36'),
(24, 'the1975', 'seller@seller.com', '$2y$10$UIOt9evUXtoGZquOO/bwfeOl6HqMQ0a8aHykFHzfeDTIqnnVwdOdW', 'Kurt', 'Cobain', '09509720086', NULL, NULL, 'https://www.facebook.com/Eaarrllzzkiiee', 1, 'approved', '2025-08-09 11:44:17', '2025-08-09 11:45:11');

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
(10, 22, 'cyborg meatshop', '1234566', '12343432', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_688e1cf8952e2.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_688e1cf89615c.jpeg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_688e1cf8968a5.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_688e1cf896c59.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_688e1cf897c21.jpeg\"}', 'M6', 'rejected', '', '2025-08-02 14:13:15', '2025-08-02 14:13:34'),
(11, 23, 'fish mart', '123456', '123456', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_68934a42f0014.jpg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_68934a42f0dc3.jpg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_68934a42f1188.jpg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_68934a42f14a6.jpg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_68934a42f183f.jpg\"}', 'M7', 'approved', NULL, '2025-08-06 12:27:50', '2025-08-06 12:28:36'),
(12, 24, 'Seafood ni Kurt Cobain', '13212313', '123456', '{\"dti_document\":\"uploads\\/seller_documents\\/dti_document_6897348f617e5.jpeg\",\"business_permit_document\":\"uploads\\/seller_documents\\/business_permit_document_6897348f61c24.jpg\",\"barangay_clearance_document\":\"uploads\\/seller_documents\\/barangay_clearance_document_6897348f6237b.jpeg\",\"bir_tin_document\":\"uploads\\/seller_documents\\/bir_tin_document_6897348f62867.jpeg\",\"sanitary_permit_document\":\"uploads\\/seller_documents\\/sanitary_permit_document_6897348f62e1d.jpg\"}', 'T11', 'pending', NULL, '2025-08-09 11:44:17', '2025-08-09 11:44:17');

-- --------------------------------------------------------

--
-- Stand-in structure for view `seller_message_stats`
-- (See below for the actual view)
--
CREATE TABLE `seller_message_stats` (
`seller_id` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`total_conversations` bigint(21)
,`active_conversations` bigint(21)
,`unread_messages` bigint(21)
,`last_message_time` timestamp
);

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
(11, 'T11', 1, 'Top Row', 12.00, 2500.00, 'reserved', NULL, 'Top row general merchandise stall', NULL, '2025-07-31 11:31:53', '2025-08-09 11:44:17'),
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
(57, 'M7', 1, 'Meat Section', 8.00, 3000.00, 'occupied', 23, 'Meat vendor stall with refrigeration', NULL, '2025-07-31 11:31:53', '2025-08-06 12:28:36'),
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
(9, 56, 22, '2025-08-02 14:13:15', 'rejected', NULL, NULL, NULL, '2025-08-02 14:13:15', '2025-08-02 14:13:34'),
(10, 57, 23, '2025-08-06 12:27:50', 'approved', NULL, NULL, NULL, '2025-08-06 12:27:50', '2025-08-06 12:28:36'),
(11, 11, 24, '2025-08-09 11:44:17', 'pending', NULL, NULL, NULL, '2025-08-09 11:44:17', '2025-08-09 11:44:17');

-- --------------------------------------------------------

--
-- Structure for view `seller_message_stats`
--
DROP TABLE IF EXISTS `seller_message_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `seller_message_stats`  AS SELECT `s`.`id` AS `seller_id`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, count(distinct `c`.`id`) AS `total_conversations`, count(distinct case when `c`.`status` = 'active' then `c`.`id` end) AS `active_conversations`, count(distinct case when `m`.`sender_type` = 'guest' and `m`.`is_read` = 0 then `m`.`id` end) AS `unread_messages`, max(`m`.`sent_at`) AS `last_message_time` FROM ((`sellers` `s` left join `conversations` `c` on(`s`.`id` = `c`.`seller_id`)) left join `messages` `m` on(`c`.`id` = `m`.`conversation_id`)) WHERE `s`.`status` = 'approved' GROUP BY `s`.`id`, `s`.`first_name`, `s`.`last_name` ;

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
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `chat_blocks`
--
ALTER TABLE `chat_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_blocked` (`seller_id`,`blocked_name`);

--
-- Indexes for table `chat_settings`
--
ALTER TABLE `chat_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`guest_name`,`seller_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_updated_at` (`updated_at`),
  ADD KEY `idx_seller_status` (`seller_id`,`status`),
  ADD KEY `idx_guest_seller` (`guest_name`,`seller_id`),
  ADD KEY `idx_archived` (`is_archived`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_template_type` (`template_type`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_sender` (`sender_type`,`sender_name`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_conversation_sender` (`conversation_id`,`sender_type`),
  ADD KEY `idx_read_status` (`is_read`,`sender_type`);

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
-- Indexes for table `product_views`
--
ALTER TABLE `product_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product` (`product_id`),
  ADD KEY `idx_view_count` (`view_count`),
  ADD KEY `idx_last_viewed` (`last_viewed`);

--
-- Indexes for table `product_view_logs`
--
ALTER TABLE `product_view_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_date` (`product_id`,`viewed_at`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_viewed_at` (`viewed_at`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chat_blocks`
--
ALTER TABLE `chat_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_settings`
--
ALTER TABLE `chat_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_views`
--
ALTER TABLE `product_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `product_view_logs`
--
ALTER TABLE `product_view_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `seller_applications`
--
ALTER TABLE `seller_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stalls`
--
ALTER TABLE `stalls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `stall_applications`
--
ALTER TABLE `stall_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_blocks`
--
ALTER TABLE `chat_blocks`
  ADD CONSTRAINT `chat_blocks_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_convo_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `product_views`
--
ALTER TABLE `product_views`
  ADD CONSTRAINT `product_views_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_view_logs`
--
ALTER TABLE `product_view_logs`
  ADD CONSTRAINT `product_view_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
