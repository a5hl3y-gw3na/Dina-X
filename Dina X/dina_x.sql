-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 16, 2025 at 10:27 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dina_x`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `quantity` int NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `reorder_level` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `name`, `quantity`, `unit`, `supplier_id`, `reorder_level`) VALUES
(1, 'Mealie meal', 5, 'kg', 5, 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `meals_enjoyed`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `meals_enjoyed`;
CREATE TABLE IF NOT EXISTS `meals_enjoyed` (
`id` int
,`user_id` int
,`menu_item_id` int
,`rating` int
,`review_text` text
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

DROP TABLE IF EXISTS `menu_categories`;
CREATE TABLE IF NOT EXISTS `menu_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`id`, `category_name`) VALUES
(1, 'Breakfast'),
(2, 'Lunch'),
(3, 'Supper'),
(4, 'Fast-Food'),
(5, 'traditional');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `category_id` int DEFAULT NULL,
  `availability` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `calories` int DEFAULT NULL,
  `protein` decimal(5,2) DEFAULT NULL,
  `fat` decimal(5,2) DEFAULT NULL,
  `carbs` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `availability`, `created_at`, `calories`, `protein`, `fat`, `carbs`) VALUES
(1, 'Sadza and Vegetables', '', 1.00, 3, 1, '2025-05-28 21:46:11', NULL, NULL, NULL, NULL),
(3, 'Porridge', 'mealie meal porridge ( zviyo and mhunga)', 1.50, 1, 1, '2025-05-28 21:55:30', NULL, NULL, NULL, NULL),
(4, 'Pan fried beef polony', 'taken straight from the buse farm', 2.00, 1, 1, '2025-05-28 21:56:33', NULL, NULL, NULL, NULL),
(5, 'Rice and Chicken', 'with salad.....and manusa chicken stew', 2.50, 3, 1, '2025-05-28 21:57:27', NULL, NULL, NULL, NULL),
(6, 'Sadza, Vegetables and Chicken', 'home sweet home', 1.50, 3, 1, '2025-05-28 21:58:09', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 'New menu item \'Sadza and Vegetables\' has been added.', 0, '2025-05-28 21:46:11'),
(2, 4, 'New menu item \'Sadza and Vegetables\' has been added.', 0, '2025-05-28 21:46:11'),
(3, 3, 'New menu item \'Burger and Chips\' has been added.', 0, '2025-05-28 21:46:51'),
(4, 4, 'New menu item \'Burger and Chips\' has been added.', 0, '2025-05-28 21:46:51'),
(5, 3, 'New menu item \'Porridge\' has been added.', 0, '2025-05-28 21:55:30'),
(6, 4, 'New menu item \'Porridge\' has been added.', 0, '2025-05-28 21:55:30'),
(7, 3, 'New menu item \'Pan fried beef polony\' has been added.', 0, '2025-05-28 21:56:33'),
(8, 4, 'New menu item \'Pan fried beef polony\' has been added.', 0, '2025-05-28 21:56:33'),
(9, 3, 'New menu item \'Rice and Chicken\' has been added.', 0, '2025-05-28 21:57:27'),
(10, 4, 'New menu item \'Rice and Chicken\' has been added.', 0, '2025-05-28 21:57:27'),
(11, 3, 'New menu item \'Sadza, Vegetables and Chicken\' has been added.', 0, '2025-05-28 21:58:09'),
(12, 4, 'New menu item \'Sadza, Vegetables and Chicken\' has been added.', 0, '2025-05-28 21:58:09'),
(13, 3, 'Menu item \'Burger and Chips\' has been removed.', 0, '2025-05-31 17:58:17'),
(14, 7, 'Menu item \'Burger and Chips\' has been removed.', 0, '2025-05-31 17:58:17');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `client_id` varchar(10) NOT NULL,
  `status` enum('Preparing','Ready','Delivered') DEFAULT 'Preparing',
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `scheduled_time` datetime DEFAULT NULL,
  `dining_hall` varchar(255) DEFAULT NULL,
  `order_notes` text,
  `price` decimal(10,2) DEFAULT NULL,
  `custom_order` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `client_id`, `status`, `order_date`, `total_amount`, `scheduled_time`, `dining_hall`, `order_notes`, `price`, `custom_order`) VALUES
(53, 3, '', 'Ready', '2025-06-16 22:19:34', 3.50, NULL, 'Mt Darwin', NULL, 3.50, NULL),
(27, 0, '1', '', '2025-06-02 22:45:29', 8.00, NULL, 'main campus', 'changed meat and dining hall', NULL, NULL),
(26, 0, '02312320', '', '2025-06-02 22:35:57', 6.00, NULL, '', '', NULL, NULL),
(29, 0, '02312320', '', '2025-06-02 23:20:35', 5.99, NULL, 'main', 'Beef', NULL, NULL),
(30, 0, '02312320', '', '2025-06-02 23:30:43', 4.00, NULL, 'new dining hall', '', NULL, NULL),
(31, 0, '02312320', '', '2025-06-02 23:41:42', 11.00, NULL, 'new dini', 'beef jurkey', NULL, NULL),
(32, 0, '02312320', '', '2025-06-02 23:42:54', 3.99, NULL, 'new dini', 'chips plain', NULL, NULL),
(33, 0, '02312320', '', '2025-06-02 23:50:18', 4.50, NULL, 'new dini', 'chips plain', NULL, NULL),
(52, 3, '', 'Preparing', '2025-06-16 22:12:07', 2.50, NULL, 'Main Campus', NULL, 2.50, NULL),
(40, 3, '', 'Ready', '2025-06-04 00:18:35', 3.50, NULL, 'New Dining Hall', NULL, NULL, ''),
(39, 3, '', 'Preparing', '2025-06-04 00:10:55', 5.00, NULL, 'Main Campus', NULL, 5.00, NULL),
(54, 3, '', 'Preparing', '2025-06-16 22:23:03', 4.25, NULL, 'Main Campus', NULL, 4.25, NULL),
(44, 3, '', '', '2025-06-16 21:06:06', 2.25, NULL, 'Mt Darwin', NULL, 2.25, NULL),
(51, 3, '', 'Ready', '2025-06-16 21:59:09', 10.00, NULL, 'Main Campus', NULL, 10.00, NULL),
(49, 3, '', 'Ready', '2025-06-16 21:43:19', 3.50, NULL, 'Main Campus', NULL, 3.50, NULL),
(47, 3, '', 'Ready', '2025-06-16 21:17:26', 3.75, NULL, 'Mt Darwin', NULL, 3.75, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 1.00),
(2, 1, 2, 1, 2.50),
(3, 2, 1, 1, 1.00),
(4, 2, 2, 1, 2.50),
(5, 2, 4, 3, 2.00),
(6, 3, 1, 1, 1.00),
(7, 3, 5, 1, 2.50),
(8, 3, 6, 2, 1.50),
(16, 10, 4, 2, 2.00),
(10, 5, 4, 1, 2.00),
(15, 10, 1, 2, 1.00),
(12, 7, 6, 2, 1.50),
(13, 8, 6, 2, 1.50),
(17, 11, 1, 2, 1.00),
(18, 11, 0, 1, 2.50),
(19, 12, 4, 3, 2.00),
(20, 13, 4, 3, 2.00),
(21, 14, 4, 3, 2.00),
(22, 15, 6, 1, 1.50),
(23, 15, 0, 1, 4.00),
(29, 20, 1, 1, 1.00),
(26, 17, 4, 2, 2.00),
(28, 19, 4, 2, 2.00),
(30, 20, 0, 1, 3.99),
(31, 21, 1, 1, 1.00),
(32, 21, 0, 1, 3.99),
(33, 22, 1, 1, 1.00),
(34, 22, 0, 1, 3.99),
(35, 23, 1, 1, 1.00),
(36, 23, 0, 1, 4.00),
(37, 24, 0, 1, 5.00),
(57, 36, 3, 1, 1.50),
(39, 26, 3, 4, 1.50),
(40, 27, 3, 2, 1.50),
(41, 27, 0, 1, 5.01),
(42, 28, 3, 2, 1.50),
(43, 28, 0, 1, 10.00),
(44, 29, 3, 2, 1.50),
(45, 29, 0, 1, 2.99),
(46, 30, 1, 4, 1.00),
(47, 31, 1, 1, 1.00),
(48, 31, 0, 1, 10.00),
(49, 32, 1, 2, 1.00),
(50, 32, 0, 1, 1.99),
(51, 33, 1, 2, 1.00),
(52, 33, 0, 1, 2.50),
(53, 34, 1, 2, 1.00),
(54, 34, 0, 1, 2.50),
(55, 35, 1, 2, 1.00),
(56, 35, 0, 1, 2.39),
(58, 36, 4, 1, 2.00),
(59, 40, 3, 1, 1.50),
(60, 40, 4, 1, 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Completed','Failed') DEFAULT 'Pending',
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `change_given` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cash_received` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `user_id`, `amount`, `payment_method`, `status`, `payment_date`, `change_given`, `cash_received`) VALUES
(1, 1, 3, 3.50, 'ecocash', 'Completed', '2025-05-28 21:59:11', 0.00, 0.00),
(2, 2, 5, 9.50, '0771212148', 'Pending', '2025-05-29 10:56:13', 0.00, 0.00),
(3, 3, 6, 6.50, 'card', 'Completed', '2025-05-29 11:19:48', 0.00, 0.00),
(52, 54, 3, 0.00, 'wallet', 'Completed', '2025-06-16 22:23:03', 0.00, 0.00),
(33, 47, 3, 0.00, 'wallet', 'Completed', '2025-06-16 21:17:26', 0.00, 0.00),
(50, 53, 0, 3.50, 'wallet', 'Completed', '2025-06-16 22:20:15', 0.00, 0.00),
(49, 53, 3, 0.00, 'wallet', 'Completed', '2025-06-16 22:19:34', 0.00, 0.00),
(37, 48, 3, 0.00, 'wallet', 'Completed', '2025-06-16 21:29:46', 0.00, 0.00),
(53, 54, 3, 4.25, 'wallet', '', '2025-06-16 22:23:16', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_name` (`permission_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `rating` int NOT NULL,
  `review_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `menu_item_id` (`menu_item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `menu_item_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 7, 5, 5, 'chicken has that umph in it', '2025-06-01 00:22:15');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`) VALUES
(1, 'general_admin', 'General admin with limited access'),
(2, 'superior_admin', 'Superior admin with full access including inventory and reporting'),
(3, 'client', 'Client user');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_info`) VALUES
(1, 'Gwena Farms Madziva.', '0777367773.'),
(2, 'Irvines', '+263 8782 3232323'),
(6, 'Makwaiba Pigs', '0776963900'),
(5, 'Bindura University Shamva farm', '0773434242');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `residence` varchar(20) DEFAULT NULL,
  `level` varchar(3) DEFAULT NULL,
  `client_id` varchar(8) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'icons/download.jpeg',
  `total_spend` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `fk_client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role_id`, `created_at`, `wallet_balance`, `phone`, `address`, `residence`, `level`, `client_id`, `profile_pic`, `total_spend`) VALUES
(1, 'generaladmin', 'generaladmin@buse.ac.zw', '$2y$10$h3PImvZ.cG5bBZGrozzI3OPFCLXPxCyU5oyUEy0iT5U4yzC/bv7aG', 1, '2025-05-28 13:31:44', 0.00, NULL, NULL, NULL, NULL, NULL, 'icons/download.jpeg', NULL),
(2, 'superioradmin', 'superioradmin@buse.ac.zw', '$2y$10$ULCVLjLhP646cJUH.OUpReDCkCFW/JZnGX5G9bAInzIcBK98f5dVq', 2, '2025-05-28 13:31:44', 0.00, NULL, NULL, NULL, NULL, NULL, 'icons/download.jpeg', NULL),
(3, 'ashy', 'ashy@k.com', '$2y$10$ob2g/GXvf7TVPKyZ5jT38epd4WzDknv5.QgGwVNMAf71AVescnwxq', 3, '2025-05-28 15:15:57', 44.00, '0718401502', NULL, NULL, NULL, '02332330', 'icons/download.jpeg', NULL),
(8, 'tadi', 'tadiashley2003@gmail.com', '$2y$10$KdHJZxkrynB/RUJODyka3uA/OrEcX8cMZs7E0i/xxmqH73KXv5XDW', 3, '2025-06-01 18:43:36', 55.00, '0777367773', 'stand number 8, cheetown', 'Shashi near Piki', '2.2', '02342560', '../uploads/683f64b562b71_signature.png', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('credit','debit') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `amount`, `transaction_type`, `description`, `created_at`) VALUES
(1, 3, 20.00, 'credit', 'Admin added funds', '2025-06-02 00:59:47'),
(2, 8, 45.00, 'credit', 'Admin added funds', '2025-06-02 23:35:24'),
(3, 3, 43.00, 'credit', 'Admin added funds', '2025-06-03 13:14:56'),
(4, 3, 43.00, 'credit', 'Admin added funds', '2025-06-03 13:15:01');

-- --------------------------------------------------------

--
-- Structure for view `meals_enjoyed`
--
DROP TABLE IF EXISTS `meals_enjoyed`;

DROP VIEW IF EXISTS `meals_enjoyed`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `meals_enjoyed`  AS SELECT `reviews`.`id` AS `id`, `reviews`.`user_id` AS `user_id`, `reviews`.`menu_item_id` AS `menu_item_id`, `reviews`.`rating` AS `rating`, `reviews`.`review_text` AS `review_text`, `reviews`.`created_at` AS `created_at` FROM `reviews` WHERE (`reviews`.`rating` >= 4.0) ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
