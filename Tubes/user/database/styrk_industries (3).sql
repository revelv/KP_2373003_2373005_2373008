-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 24, 2025 at 06:03 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `styrk_industries`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int NOT NULL,
  `username` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `provinsi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kota` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kecamatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kelurahan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `postal_code` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `provinsi`, `kota`, `kecamatan`, `kelurahan`, `postal_code`) VALUES
(101, 'admin', '$2y$10$fbdJi7jdC0xfeKCuZSDaG.Fv6TM7Hiuway3HYMDNnwqKziU9TsOUy', '', '', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `auctions`
--

CREATE TABLE `auctions` (
  `auction_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `product_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_price` decimal(12,2) NOT NULL,
  `current_bid` decimal(12,2) NOT NULL,
  `current_winner_id` int DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL,
  `status` enum('active','ended','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auctions`
--

INSERT INTO `auctions` (`auction_id`, `customer_id`, `product_id`, `title`, `description`, `image_url`, `start_price`, `current_bid`, `current_winner_id`, `start_time`, `end_time`, `status`) VALUES
(3, 16, 'KB019', 'CannonKeys Savage65', '65% keyboard kit with a CNC aluminum case.', 'https://i.postimg.cc/6QCJFYkj/42.jpg', 1000000.00, 9999999.00, 18, '2025-11-15 09:49:41', '2025-11-17 09:42:00', 'active'),
(4, 16, 'KB017', 'Akko MOD 007 V2', 'A premium 75% keyboard with gasket mount design.', 'https://i.postimg.cc/QCfr0C2j/40.jpg', 2000000.00, 2000008.00, 18, '2025-11-15 10:03:48', '2025-11-17 10:03:00', 'active'),
(6, 16, 'KB025', 'EPOMAKER TH96', '96% keyboard with hot-swap, wireless and knob features.', 'https://i.postimg.cc/jdCVRxVJ/50.jpg', 1000000.00, 1152000.00, 16, '2025-11-15 13:36:01', '2025-11-15 14:10:00', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `bids`
--

CREATE TABLE `bids` (
  `bid_id` int NOT NULL,
  `auction_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `bid_amount` decimal(12,2) NOT NULL,
  `bid_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bids`
--

INSERT INTO `bids` (`bid_id`, `auction_id`, `customer_id`, `bid_amount`, `bid_time`) VALUES
(1, 3, 16, 1100000.00, '2025-11-15 13:30:09'),
(2, 6, 16, 1100000.00, '2025-11-15 13:36:13'),
(3, 6, 10, 1150000.00, '2025-11-15 13:36:46'),
(4, 6, 16, 1152000.00, '2025-11-15 14:07:42'),
(5, 4, 18, 2000002.00, '2025-11-15 14:20:27'),
(6, 4, 18, 2000007.00, '2025-11-15 14:20:35'),
(7, 4, 18, 2000008.00, '2025-11-17 06:11:11'),
(8, 3, 18, 9999999.00, '2025-11-17 06:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `product_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_barang` int NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `customer_id`, `product_id`, `jumlah_barang`, `updated_at`, `notified_at`) VALUES
(34, 10, 'KB013', 2, '2025-11-18 17:23:43', '2025-11-18 17:23:43'),
(70, 10, 'KB018', 1, '2025-11-18 17:23:43', '2025-11-18 17:23:43'),
(79, 16, 'COMM-2', 1, '2025-11-18 17:23:47', '2025-11-18 17:23:47'),
(92, 18, 'KB004', 1, '2025-11-19 15:57:33', '2025-11-19 15:57:33'),
(118, 21, 'KB003', 1, '2025-11-24 03:38:21', '2025-11-24 03:38:21');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category`) VALUES
(1, 'Case'),
(2, 'Keyboard'),
(3, 'Keycaps'),
(4, 'Keyboard_Kit'),
(5, 'Keypad'),
(6, 'Stabilizers'),
(7, 'Switch_kit');

-- --------------------------------------------------------

--
-- Table structure for table `community_articles`
--

CREATE TABLE `community_articles` (
  `article_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `product_price` int NOT NULL DEFAULT '0',
  `product_id` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_published` tinyint(1) DEFAULT '1',
  `linked_product_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `community_articles`
--

INSERT INTO `community_articles` (`article_id`, `title`, `content`, `image_url`, `product_price`, `product_id`, `created_at`, `updated_at`, `is_published`, `linked_product_id`) VALUES
(1, 'Noir Timeless82 v2 x Nevertoolavish \"Type Out Loud\" - NTL Edition Wireless Mechanical Keyboard Gasket Mount', 'Noir Timeless82 v2 × Nevertoolavish “Type Out Loud” – NTL Edition\r\nKolaborasi eksklusif antara Noir Gear dan Nevertoolavish, hadir dengan desain unik dan estetika premium yang memadukan gaya, fungsi, dan pengalaman mengetik kelas atas.\r\n\r\nFitur Utama:\r\nWireless Tri-Mode: Bluetooth, 2.4GHz, dan USB-C kabel\r\nGasket Mount System dengan 3-layer foam + poron untuk suara halus & feel empuk\r\nHot-swappable (3-pin & 5-pin) – bebas ganti switch tanpa solder\r\nLayout 75% (81 tombol) compact & fungsional\r\nPlate Polycarbonate, PCB flex-cut untuk fleksibilitas mengetik lebih nyaman\r\nKeycaps PBT dye-sub, tahan aus dan tidak mudah mengilap', 'https://i.postimg.cc/RFVNNY11/4e30c0a6026d46a093d0aa226f5d24d1.jpg', 3500000, NULL, '2025-11-17 21:24:16', '2025-11-17 21:55:39', 1, NULL),
(2, '[Press Play x Demon Slayer] ESSENTIAL75 HE RENGOKU Edition 75% Rapid Trigger Hall Effect Keyboard', 'ESSENTIAL75 HE RENGOKU Edition 75% Rapid Trigger Hall Effect Keyboard\r\n\r\n\"I will fulfill my duty! I won\'t allow anyone here to die!\"\r\nESSENTIAL75 HE, hadir dengan tampilan dan keycaps eksklusif bertema Rengoku dari Demon Slayer.\r\n\r\nOnline software available here: ess75.pressplayid.com\r\n\r\n©Koyoharu Gotoge / SHUEISHA, Aniplex, ufotable', 'https://i.postimg.cc/Y0LscYqt/8abcdbe1-6c7c-442b-b9ca-81c3e7f04c9b.jpg', 4200000, NULL, '2025-11-18 00:12:05', '2025-11-18 00:12:05', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `community_article_comments`
--

CREATE TABLE `community_article_comments` (
  `comment_id` int NOT NULL,
  `article_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `no_telepon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `provinsi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kota` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kecamatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kelurahan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `profile_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postal_code` int DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_reengagement_sent` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `nama`, `password`, `email`, `no_telepon`, `provinsi`, `kota`, `kecamatan`, `kelurahan`, `profile_image`, `postal_code`, `alamat`, `last_login`, `last_reengagement_sent`) VALUES
(6, 'Jeremia', '$2y$10$JK.Mo5XVtj0TCs2ikcXCBevbN5By6w9KYArlaRgMFBXFlpJK5gmFO', 'jeremiaethan05@gmail.com', '081312663058', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', NULL, 40218, 'Taman Kopo Indah 3 C2 no 4', '2025-11-24 03:39:45', NULL),
(7, 'Aldy Taher', '$2y$10$EiyoeSPMZt2kKDkP5bB0h.7ae7fA5dvhz4uJpgwSFup5viqMXjIlK', 'guaganteng@123.com', '123456', '', '', '', '', NULL, 0, 'jalan tuhan kebenaran no. 100', NULL, NULL),
(10, 'Tuyul Ganteng', '$2y$10$ERjVD1oOnWRikvY297secepKphheTL5UKAYmWeCtxMVO4wru7N2OG', '2373003@maranatha.ac.id', '298483924', '', '', '', '', NULL, 0, 'rumahsammy 123', '2025-11-15 13:36:31', NULL),
(16, 'Doni Salmanan', '$2y$10$7uuw.sFubujIPGy2KANG4.s20CN.w7uznjQxwWPtwfTCJ7zieh./C', 'styrk_industries@gmail.com', '08124272849', 'JAWA BARAT', 'BANDUNG', 'BOJONGSOANG', 'LENGKONG', '../uploads/profile/profile_16_1763491478.jpg', 0, 'gunung gede 123', '2025-11-18 16:26:34', '2025-10-26 08:32:45'),
(17, 'Aldy Taher', '$2y$10$FTxIp34ew5uky05iP7JtzuWmB.KHyTkJnOZaRkn0ze4yO9B6Pia56', 'kink.konk169@gmail.com', '081223830598', '3', '36', '', '', NULL, 0, 'banjaran 120', NULL, NULL),
(18, 'JRMIA', '$2y$12$bl8jij7L3oJrrqI6cyDgWeEAFhTB/v7gH2.8dOIgFp/3ynWNt0ZQG', 'jeremiadylan15@gmail.com', '081312663058', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '', NULL, 0, 'Taman Kopo Indah 69 Blok S', '2025-11-18 14:24:00', NULL),
(20, 'JRMI4A', '$2y$10$iQo8aKUB1k/Wqf5ffP3wm.8lujrZZXVXvpTpiYqJJOQjThxcySQBi', 'jeremiadylan115@gmail.com', '0813213123', 'JAWA BARAT', 'BANDUNG', 'ANDIR', '', NULL, 0, 'Jalan andir no 15', '2025-11-17 11:36:01', NULL),
(21, 'Jeremia', '$2y$12$NxiD4m53ua0WUI6QirVhV.QsmqhqO4he5ivJVJeogGPfrl2Fm2eQO', 'jeremiadylan80@gmail.com', '081312663058', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '../uploads/profile/profile_21_1763822038.png', 40218, 'Taman Kopo Indah 69 Blok S', '2025-11-22 16:51:28', NULL),
(22, 'JRMIA', '$2y$12$CNS9SxZLlWo2JvZr8z2sruJQ2OODx3cW54iToRtfXCZv4vTzTEJUu', 'willykurniawan456@gmail.com', '081312663058', 'JAWA BARAT', 'BANDUNG', 'ANDIR', 'CIROYOM', NULL, NULL, 'Jalan andir no 15', '2025-11-22 05:14:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `customer_id` int NOT NULL,
  `tgl_order` datetime NOT NULL,
  `provinsi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kota` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kecamatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kelurahan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `postal_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `shipping_provider_order_id` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_tracking_code` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_last_sync` datetime DEFAULT NULL,
  `code_courier` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `shipping_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ongkos_kirim` int NOT NULL,
  `total_harga` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `tgl_order`, `provinsi`, `kota`, `kecamatan`, `kelurahan`, `postal_code`, `alamat`, `shipping_provider_order_id`, `shipping_tracking_code`, `shipping_status`, `shipping_last_sync`, `code_courier`, `shipping_type`, `ongkos_kirim`, `total_harga`) VALUES
('ORD-20251117050246-469', 18, '2025-11-17 12:02:46', '6', '64', '', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'ERROR_HTTP_404', '2025-11-17 00:00:00', 'jne', '', 7000, 3707000.00),
('ORD-20251117050534-242', 18, '2025-11-17 12:05:34', '6', '64', '', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'ERROR_HTTP_404', '2025-11-17 00:00:00', 'jne', '', 7000, 2707000.00),
('ORD-20251117051054-966', 18, '2025-11-17 12:10:54', '6', '64', '', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'ERROR_HTTP_404', '2025-11-17 00:00:00', 'jne', '', 7000, 357000.00),
('ORD-20251117065225-781', 18, '2025-11-17 13:52:25', '5', '55', '', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'ERROR_HTTP_422', '2025-11-17 00:00:00', 'pos', '', 9000, 2759000.00),
('ORD-20251117065836-905', 18, '2025-11-17 13:58:36', '5', '55', '', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'ERROR_HTTP_422 Create Order failed', '2025-11-17 00:00:00', 'pos', '', 9000, 329000.00),
('ORD-20251117080623-619', 18, '2025-11-17 15:06:23', '5', '55', '', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, NULL, NULL, 'pos', '', 9000, 559000.00),
('ORD-20251117081052-599', 18, '2025-11-17 15:10:52', '5', '55', '', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'ERROR_HTTP_422', '2025-11-17 00:00:00', 'pos', '', 9000, 559000.00),
('ORD-20251118113958-116', 6, '2025-11-18 18:39:58', 'JAWA BARAT', 'BEKASI', '', '', NULL, 'Jalan asmi no 123', NULL, NULL, 'ERROR_HTTP_404', '2025-11-18 00:00:00', 'jne', '', 9500, 1759500.00),
('ORD-20251118124139-307', 6, '2025-11-18 19:41:39', 'JAWA BARAT', 'BEKASI', 'BABELAN', '', NULL, 'Jalan asmi no 123', NULL, NULL, NULL, NULL, 'jne', '', 9500, 4659500.00),
('ORD-20251118130310-367', 6, '2025-11-18 20:03:10', 'JAWA BARAT', 'BEKASI', 'BABELAN', '', NULL, 'Jalan asmi no 123', NULL, NULL, NULL, NULL, 'jnt', 'EZ', 11000, 2111000.00),
('ORD-20251118131034-127', 6, '2025-11-18 20:10:34', 'JAWA BARAT', 'BEKASI', 'BABELAN', '', NULL, 'Jalan asmi no 123', NULL, NULL, NULL, NULL, 'ninja', 'Standard', 11000, 361000.00),
('ORD-20251118131201-556', 6, '2025-11-18 20:12:01', 'JAWA BARAT', 'BEKASI', 'BABELAN', '', NULL, 'Jalan asmi no 123', NULL, NULL, NULL, NULL, 'ninja', 'Standard', 11000, 1461000.00),
('ORD-20251118134721-120', 6, '2025-11-18 20:47:21', 'JAWA BARAT', 'BEKASI', 'BABELAN', '', NULL, 'Jalan asmi no 123', NULL, NULL, 'pending', NULL, 'jne', '0', 9500, 4209500.00),
('ORD-20251118142423-111', 18, '2025-11-18 21:24:23', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '', '0', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'ninja', '0', 8000, 2958000.00),
('ORD-20251118142837-726', 18, '2025-11-18 21:28:37', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '', '0', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'lion', 'REGPACK', 7000, 1857000.00),
('ORD-20251118143330-454', 18, '2025-11-18 21:33:30', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '', '0', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jnt', 'EZ', 8000, 1258000.00),
('ORD-20251118143953-269', 18, '2025-11-18 21:39:53', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '', '0', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', '0', 9500, 1909500.00),
('ORD-20251118145554-579', 18, '2025-11-18 21:55:54', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '', NULL, 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', '0', 9500, 3309500.00),
('ORD-20251118151141-961', 18, '2025-11-18 22:11:41', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '', '0', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', '0', 9500, 7809500.00),
('ORD-20251119144410-583', 21, '2025-11-19 21:44:10', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 1859500.00),
('ORD-20251119144528-260', 21, '2025-11-19 21:45:28', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 7809500.00),
('ORD-20251119145554-294', 21, '2025-11-19 21:55:54', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'ninja', 'Standard', 8000, 1308000.00),
('ORD-20251119151428-755', 21, '2025-11-19 22:14:28', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 659500.00),
('ORD-20251119152858-103', 21, '2025-11-19 22:28:58', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 3609500.00),
('ORD-20251119153025-557', 21, '2025-11-19 22:30:25', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', '', NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 1959500.00),
('ORD-20251119153118-132', 21, '2025-11-19 22:31:18', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 2259500.00),
('ORD-20251119155749-810', 21, '2025-11-19 22:57:49', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 1259500.00),
('ORD-20251119160357-489', 21, '2025-11-19 23:03:57', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 4209500.00),
('ORD-20251119161111-830', 21, '2025-11-19 23:11:11', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 3009500.00),
('ORD-20251119161619-830', 21, '2025-11-19 23:16:19', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 7809500.00),
('ORD-20251119162347-733', 21, '2025-11-19 23:23:47', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'JNEFlat', 9500, 1959500.00),
('ORD-20251119163445-501', 21, '2025-11-19 23:34:45', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'error: Create Order failed', '2025-11-19 00:00:00', 'jne', 'JNEFlat', 9500, 659500.00),
('ORD-20251119170708-787', 21, '2025-11-20 00:07:08', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', 'KOM79935202511200007', NULL, 'created', '2025-11-20 00:00:00', 'jne', 'JNEFlat', 9500, 1909500.00),
('ORD-20251119171921-243', 21, '2025-11-20 00:19:21', 'JAWA BARAT', 'BANDUNG', 'SUKAJADI', 'PASTEUR', '448', 'Jl. Prof. drg. Surya Sumantri, M.P.H. No. 65, Bandung.', 'KOM94453202511200019', NULL, 'created', '2025-11-20 00:00:00', 'jne', 'JNEFlat', 9500, 659500.00),
('ORD-20251119172443-164', 21, '2025-11-20 00:24:43', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', 'KOM77918202511200024', NULL, 'created', '2025-11-20 00:00:00', 'jne', 'JNEFlat', 9500, 2159500.00),
('ORD-20251119173845-706', 21, '2025-11-20 00:38:45', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', 'KOM12714202511200038', '', 'Diajukan', '2025-11-20 00:00:00', 'jne', 'JNEFlat', 19000, 1919000.00),
('ORD-20251119175057-997', 21, '2025-11-20 00:50:57', 'JAWA BARAT', 'BANDUNG', 'SUKAJADI', 'PASTEUR', '448', 'da', 'KOM71875202511200051', '', 'Diajukan', '2025-11-20 00:00:00', 'ninja', 'Standard', 8000, 4008000.00),
('ORD-20251119180234-328', 21, '2025-11-20 01:02:34', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'error: maximum cod value is 5000000', '2025-11-20 00:00:00', 'lion', 'REGPACK', 7000, 7807000.00),
('ORD-20251119180311-111', 21, '2025-11-20 01:03:11', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', 'KOM49363202511200103', '', 'Diajukan', '2025-11-20 00:00:00', 'jne', 'JNEFlat', 9500, 1909500.00),
('ORD-20251121133657-752', 21, '2025-11-21 20:36:57', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'error: maximum cod value is 5000000', '2025-11-21 00:00:00', 'jne', 'JNEFlat', 9500, 7809500.00),
('ORD-20251121133727-293', 21, '2025-11-21 20:37:27', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', 'KOM51252202511212037', NULL, 'created', '2025-11-21 00:00:00', 'jne', 'JNEFlat', 9500, 3609500.00),
('ORD-20251121134644-493', 21, '2025-11-21 20:46:44', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '5143', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'error: Create Order failed', '2025-11-21 00:00:00', 'jne', 'JNEFlat', 9500, 1309500.00),
('ORD-20251122145838-651', 21, '2025-11-22 21:58:38', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '0', 'Taman Kopo Indah 69 Blok S', NULL, NULL, 'pending', NULL, 'jne', 'Reguler', 8000, 1858000.00),
('ORD-20251124034140-472', 6, '2025-11-24 10:41:40', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', '-', '40164', 'Taman Kopo Indah 3 C2 no 4', NULL, NULL, NULL, NULL, 'jne', 'reguler', 8000, 658000.00),
('ORD-20251124043043-382', 6, '2025-11-24 11:30:43', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '40218', 'Taman Kopo Indah 3 C2 no 4', NULL, NULL, NULL, NULL, 'sap', 'regular', 10000, 1910000.00),
('ORD-20251124054310-973', 6, '2025-11-24 12:43:10', 'JAWA BARAT', 'BANDUNG', 'MARGAASIH', 'RAHAYU', '40218', 'Taman Kopo Indah 3 C2 no 4', '6923f06fd58cd645b2973b34', 'WYB-1763962991033', 'confirmed', '2025-11-24 13:03:28', 'jne', 'reg', 8000, 1858000.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `detail_id` int NOT NULL,
  `order_id` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `product_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah` int NOT NULL,
  `harga_satuan` int NOT NULL,
  `subtotal` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`detail_id`, `order_id`, `product_id`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(52, 'ORD-20251117081052-599', 'SW004', 1, 550000, 550000),
(53, 'ORD-20251118124139-307', 'KK005', 1, 1900000, 1900000),
(54, 'ORD-20251118124139-307', 'KK007', 1, 2750000, 2750000),
(55, 'ORD-20251118130310-367', 'KC007', 1, 2100000, 2100000),
(56, 'ORD-20251118131034-127', 'ST001', 1, 350000, 350000),
(57, 'ORD-20251118131201-556', 'KP001', 1, 1450000, 1450000),
(58, 'ORD-20251118134721-120', 'COMM-2', 1, 4200000, 4200000),
(59, 'ORD-20251118142423-111', 'KK009', 1, 2950000, 2950000),
(60, 'ORD-20251118142837-726', 'CS001', 1, 1850000, 1850000),
(61, 'ORD-20251118143330-454', 'KB004', 1, 1250000, 1250000),
(62, 'ORD-20251118143953-269', 'KB009', 1, 1900000, 1900000),
(63, 'ORD-20251118145554-579', 'KC002', 1, 3300000, 3300000),
(64, 'ORD-20251118151141-961', 'KB003', 1, 7800000, 7800000),
(65, 'ORD-20251119144410-583', 'CS001', 1, 1850000, 1850000),
(66, 'ORD-20251119144528-260', 'KB003', 1, 7800000, 7800000),
(67, 'ORD-20251119145554-294', 'KB007', 1, 1300000, 1300000),
(68, 'ORD-20251119151428-755', 'KB005', 1, 650000, 650000),
(69, 'ORD-20251119152858-103', 'KB013', 1, 3600000, 3600000),
(70, 'ORD-20251119153025-557', 'KB016', 1, 1950000, 1950000),
(71, 'ORD-20251119153118-132', 'KB021', 1, 2250000, 2250000),
(72, 'ORD-20251119155749-810', 'KB004', 1, 1250000, 1250000),
(73, 'ORD-20251119160357-489', 'COMM-2', 1, 4200000, 4200000),
(74, 'ORD-20251119161111-830', 'KB024', 1, 3000000, 3000000),
(75, 'ORD-20251119161619-830', 'KB003', 1, 7800000, 7800000),
(76, 'ORD-20251119162347-733', 'KB016', 1, 1950000, 1950000),
(77, 'ORD-20251119163445-501', 'SW005', 1, 650000, 650000),
(78, 'ORD-20251119170708-787', 'KK002', 1, 1900000, 1900000),
(79, 'ORD-20251119171921-243', 'KB005', 1, 650000, 650000),
(80, 'ORD-20251119172443-164', 'KK006', 1, 2150000, 2150000),
(81, 'ORD-20251119173845-706', 'KB004', 1, 1250000, 1250000),
(82, 'ORD-20251119173845-706', 'KB005', 1, 650000, 650000),
(83, 'ORD-20251119175057-997', 'KC003', 1, 4000000, 4000000),
(84, 'ORD-20251119180234-328', 'KB003', 1, 7800000, 7800000),
(85, 'ORD-20251119180311-111', 'KB009', 1, 1900000, 1900000),
(86, 'ORD-20251121133657-752', 'KB003', 1, 7800000, 7800000),
(87, 'ORD-20251121133727-293', 'KB013', 1, 3600000, 3600000),
(88, 'ORD-20251121134644-493', 'KB007', 1, 1300000, 1300000),
(89, 'ORD-20251122145838-651', 'CS001', 1, 1850000, 1850000),
(91, 'ORD-20251124034140-472', 'KB005', 1, 650000, 650000),
(92, 'ORD-20251124043043-382', 'KB009', 1, 1900000, 1900000),
(93, 'ORD-20251124054310-973', 'CS001', 1, 1850000, 1850000);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `order_id` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `metode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_dibayar` decimal(12,2) NOT NULL,
  `tanggal_bayar` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_proof` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int NOT NULL,
  `thread_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `thread_id`, `customer_id`, `content`, `created_at`) VALUES
(1, 1, 16, 'Brandnya bagus sih tapi kalau claim garansi nya gimana ya kak? kok susah banget', '2025-10-26 09:01:33'),
(2, 2, 18, 'Genta ganteng banget', '2025-10-26 13:24:47'),
(3, 2, 18, 'sekya', '2025-10-26 15:20:59'),
(4, 1, 18, 'keren banget kamu kak', '2025-10-26 15:21:28'),
(5, 1, 16, 'dd', '2025-11-18 18:40:11');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_produk` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_produk` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `stok` int NOT NULL,
  `link_gambar` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `weight` int NOT NULL,
  `status_jual` enum('dijual','dilelang') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dijual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `nama_produk`, `deskripsi_produk`, `harga`, `stok`, `link_gambar`, `category_id`, `weight`, `status_jual`) VALUES
('COMM-2', '[Press Play x Demon Slayer] ESSENTIAL75 HE RENGOKU Edition 75% Rapid Trigger Hal', 'ESSENTIAL75 HE RENGOKU Edition 75% Rapid Trigger Hall Effect Keyboard\r\n\r\n\"I will fulfill my duty! I won\'t allow anyone here to die!\"\r\nESSENTIAL75 HE, hadir dengan tampilan dan keycaps eksklusif bertema Rengoku dari Demon Slayer.\r\n\r\nOnline software available here: ess75.pressplayid.com\r\n\r\n©Koyoharu Gotoge / SHUEISHA, Aniplex, ufotable', 4200000.00, 1, 'https://i.postimg.cc/Y0LscYqt/8abcdbe1-6c7c-442b-b9ca-81c3e7f04c9b.jpg', 2, 1000, 'dijual'),
('CS001', 'Tofu60 Redux Case', 'An upgraded version of the classic Tofu60 case, offering improved materials, finish, and design features. Compatible with a wide range of 60% PCBs and plates.', 1850000.00, 3, 'https://i.postimg.cc/T1D3LRzQ/11.jpg', 1, 500, 'dijual'),
('KB001', 'Sirius Manta', 'A premium mechanical keyboard known for its elegant design and smooth typing experience. The Sirius Manta blends aesthetics with functionality, making it a favorite among hobbyists.', 3200000.00, 0, 'https://i.postimg.cc/zfxB42ww/10.jpg', 2, 1000, 'dijual'),
('KB002', 'Snake60 R2', 'A high-end 60% keyboard kit with sleek lines and robust build quality. The Snake60 R2 delivers a refined typing experience and top-tier customization options at a heavily discounted price.', 7500000.00, 0, 'https://i.postimg.cc/L5chNqtr/2.jpg', 2, 1000, 'dijual'),
('KB003', 'KBD8X MKIII Keyboard', 'A beloved full-sized mechanical keyboard featuring top mount design and premium aluminum construction. Now at half price, it\'s a steal for serious keyboard builders.', 7800000.00, 9, 'https://i.postimg.cc/JnhynC7d/4.jpg', 2, 1000, 'dijual'),
('KB004', 'Magnum65', 'A 65% layout keyboard with a bold design and exceptional build quality. The Magnum65 is for those who want a compact form factor without compromising on performance.', 1250000.00, 5, 'https://i.postimg.cc/sfqBVLkw/5.jpg', 2, 1000, 'dijual'),
('KB005', 'Quartz Stone Wrist Rest', 'A solid quartz wrist rest designed to offer comfort and elegance. Its cool, stone finish adds a premium touch to your keyboard setup.', 650000.00, 5, 'https://i.postimg.cc/jSQC4SLF/7.jpg', 2, 1000, 'dijual'),
('KB006', 'Odin 75 Hot-swap Keyboard with PBTfans Courage red', 'A ready-to-use Odin 75 keyboard with bold Courage Red keycaps. Hot-swap sockets make switch swapping easy without soldering.', 5750000.00, 9, 'https://i.postimg.cc/bwH9Mn60/17.jpg', 2, 1000, 'dijual'),
('KB007', 'Keychron K8 Wireless', 'A tenkeyless wireless mechanical keyboard compatible with Mac and Windows.', 1300000.00, 9, 'https://i.postimg.cc/mrPhMfFc/21.jpg', 2, 1000, 'dijual'),
('KB008', 'Akko 3068B Plus', 'A compact 65% keyboard with wireless connectivity and hot-swappable switches.', 1450000.00, 8, 'https://i.postimg.cc/0Nhj0WpV/22.png', 2, 1000, 'dijual'),
('KB009', 'Ducky One 3 Mini', 'A 60% keyboard known for vibrant colors and premium build.', 1900000.00, 7, 'https://i.postimg.cc/vB9Bqrhb/23.jpg', 2, 1000, 'dijual'),
('KB010', 'Mode Sonnet Keyboard', 'A custom keyboard with a sleek design and premium materials.', 4800000.00, 9, 'https://i.postimg.cc/XqbvTr1F/25.jpg', 2, 1000, 'dijual'),
('KB011', 'Keychron Q1 V2', 'A customizable 75% keyboard with QMK/VIA support.', 2800000.00, 9, 'https://i.postimg.cc/KjDYFmCW/26.jpg', 2, 1000, 'dijual'),
('KB012', 'Ikki68 Aurora', 'A popular entry-level custom keyboard kit.', 2150000.00, 9, 'https://i.postimg.cc/J7N0jQtQ/27.jpg', 2, 1000, 'dijual'),
('KB013', 'MelGeek Mojo68', 'A semi-transparent wireless keyboard with customizable layout.', 3600000.00, 10, 'https://i.postimg.cc/X7NjdSRV/33.jpg', 2, 1000, 'dijual'),
('KB014', 'NK65 Entry Edition', 'A budget-friendly 65% mechanical keyboard with a polycarbonate case.', 1500000.00, 9, 'https://i.postimg.cc/ydzBNwhC/36.jpg', 2, 1000, 'dijual'),
('KB015', 'Keychron V4', 'A budget 60% keyboard with QMK/VIA support.', 1100000.00, 9, 'https://i.postimg.cc/43cssJ91/37.jpg', 2, 1000, 'dijual'),
('KB016', 'Ajazz AK966', '96% layout wireless mechanical keyboard with knob.', 1950000.00, 10, 'https://i.postimg.cc/ZRFmvVjB/38.jpg\r\n', 2, 1000, 'dijual'),
('KB017', 'Akko MOD 007 V2', 'A premium 75% keyboard with gasket mount design.', 2300000.00, 9, 'https://i.postimg.cc/QCfr0C2j/40.jpg', 2, 1000, 'dilelang'),
('KB018', 'Rama M65-B', 'High-end aluminum 65% keyboard with elegant design.', 5800000.00, 10, 'https://i.postimg.cc/Qd2Z6RyK/41.jpg', 2, 1000, 'dijual'),
('KB019', 'CannonKeys Savage65', '65% keyboard kit with a CNC aluminum case.', 3700000.00, 10, 'https://i.postimg.cc/6QCJFYkj/42.jpg', 2, 1000, 'dilelang'),
('KB020', 'Drop ALT Keyboard', 'Compact mechanical keyboard with hot-swap sockets.', 2900000.00, 10, 'https://i.postimg.cc/bNFhnG0T/43.jpg', 2, 1000, 'dijual'),
('KB021', 'Varmilo VA87M', 'Tenkeyless keyboard with a variety of themes and switches.', 2250000.00, 10, 'https://i.postimg.cc/vZgdtQ3d/44.jpg', 2, 1000, 'dijual'),
('KB022', 'Zoom65 V2', 'A versatile 65% keyboard with wireless features.', 2850000.00, 10, 'https://i.postimg.cc/63wJKCw9/45.jpg', 2, 1000, 'dijual'),
('KB023', 'MelGeek Pixel Keyboard', 'A customizable Lego-style keyboard.', 4200000.00, 10, 'https://i.postimg.cc/Jnqwzt4j/46.jpg', 2, 1000, 'dijual'),
('KB024', 'IDOBAO ID80', 'Gasket-mounted keyboard with a unique acrylic body.', 3000000.00, 10, 'https://i.postimg.cc/Zn716gNR/47.jpg', 2, 1000, 'dijual'),
('KB025', 'EPOMAKER TH96', '96% keyboard with hot-swap, wireless and knob features.', 2100000.00, 10, 'https://i.postimg.cc/jdCVRxVJ/50.jpg', 2, 1000, 'dilelang'),
('KB026', 'Royal Kludge RK61', 'Budget 60% wireless mechanical keyboard.', 800000.00, 10, 'https://i.postimg.cc/RCg5r3YB/51.jpg', 2, 1000, 'dijual'),
('KC001', 'Circus PGA Profile Keycaps', 'A vibrant and playful set of PGA profile keycaps inspired by circus aesthetics. Perfect for mechanical keyboard enthusiasts looking to add a burst of color and uniqueness to their setup.', 1250000.00, 10, 'https://i.postimg.cc/zBPyH2VD/1.jpg', 3, 1000, 'dijual'),
('KC002', 'Dusk 67 with PBTfans Inkdrop', 'A beautifully themed 65% keyboard featuring the Dusk 67 case and PBTfans Inkdrop keycaps. This bundle is perfect for those who want a cohesive, stunning setup.', 3300000.00, 10, 'https://i.postimg.cc/WpC8JTFZ/13.jpg', 3, 1000, 'dijual'),
('KC003', 'TET Keyboard With PBTfans Count Dracula', 'A spooky and stylish keyboard pairing the TET layout with the popular PBTfans Count Dracula keycaps. Eye-catching and great for Halloween or gothic setups.', 4000000.00, 10, 'https://i.postimg.cc/7ZyNm9NY/16.jpg', 3, 1000, 'dijual'),
('KC004', 'KBD8X MKIII HE Gaming Keyboard with PBTfans Blush', 'A performance-focused version of the KBD8X MKIII featuring Hall Effect switches for rapid input and PBTfans Blush keycaps for soft, pastel aesthetics.', 4200000.00, 9, 'https://i.postimg.cc/HWXcBgvX/18.jpg', 3, 1000, 'dijual'),
('KC005', 'GMK Red Samurai Keycap Set', 'A premium GMK keycap set inspired by traditional samurai aesthetics.', 2400000.00, 9, 'https://i.postimg.cc/SKSfpK5B/19.jpg', 3, 1000, 'dijual'),
('KC006', 'KAT Milkshake Keycap Set', 'A colorful pastel keycap set with a unique KAT profile.', 1750000.00, 9, 'https://i.postimg.cc/c4DsckWf/34.jpg', 3, 1000, 'dijual'),
('KC007', 'SA Bliss Keycap Set', 'A vibrant SA profile keycap set inspired by serene aesthetics.', 2100000.00, 10, 'https://i.postimg.cc/0yFPT6bQ/35.jpg', 3, 1000, 'dijual'),
('KC008', 'GMK Olivia Keycap Set', 'Elegant pink and black themed GMK keycap set.', 2400000.00, 10, 'https://i.postimg.cc/zXQsBs52/49.png', 3, 1000, 'dijual'),
('KK001', 'Tofu60 Redux Plate', 'A compatible plate for the Tofu60 Redux case, offering improved rigidity and mounting flexibility. Great for customizing your typing feel.', 320000.00, 10, 'https://i.postimg.cc/L6PJhTRR/6.jpg', 4, 200, 'dijual'),
('KK002', 'KBD67 Lite R4 Mechanical Keyboard Kit', 'A budget-friendly yet high-performing 65% keyboard kit. Ideal for newcomers and veterans alike, with hot-swap functionality and great acoustics.', 1900000.00, 10, 'https://i.postimg.cc/2SfVWZ8W/8.jpg', 4, 200, 'dijual'),
('KK003', 'KBDfans Odin 75 Mechanical Keyboard Kit', 'A compact 75% layout keyboard with a stylish and functional design. The Odin 75 offers great balance between form and usability.', 3800000.00, 9, 'https://i.postimg.cc/mrLkXW92/9.jpg', 4, 200, 'dijual'),
('KK004', 'Sebas Keyboard kit', 'A stylish and sturdy keyboard kit designed with premium materials. Its layout and build make it suitable for both work and play.', 3450000.00, 8, 'https://i.postimg.cc/2jVT6V6m/12.jpg', 4, 200, 'dijual'),
('KK005', 'KBDfans GT-80 Case', 'A durable and elegant keyboard case designed for the GT-80 layout. Built with anodized aluminum and precision machining.', 1900000.00, 10, 'https://i.postimg.cc/pyMXKwxv/14.jpg', 4, 200, 'dijual'),
('KK006', 'Margo Case', 'A uniquely designed keyboard case with gentle curves and premium anodization. A great choice for custom keyboard builds looking to stand out.', 2150000.00, 10, 'https://i.postimg.cc/9F9pdKZn/15.jpg', 4, 200, 'dijual'),
('KK007', 'GMMK Pro Barebone', 'A 75% layout mechanical keyboard with a rotary knob and aluminum body.', 2750000.00, 10, 'https://i.postimg.cc/0NVd5s1b/20.jpg', 4, 200, 'dijual'),
('KK008', 'Tofu65 Kit', 'Aluminum 65% DIY keyboard kit with customizable options.', 2700000.00, 10, 'https://i.postimg.cc/m2Vr52Yz/28.jpg', 4, 200, 'dijual'),
('KK009', 'KBD75 V3 Kit', '75% aluminum keyboard with refined layout and features.', 2950000.00, 10, 'https://i.postimg.cc/Kjv6kFRw/48.jpg', 4, 200, 'dijual'),
('KP001', 'Taco Pad', 'A novelty macropad shaped like a taco. Fun, quirky, and useful for macros, shortcuts, or artisan display. A must-have desk companion for enthusiasts.', 1450000.00, 10, 'https://i.postimg.cc/C5BzGCqG/3.jpg', 5, 400, 'dijual'),
('ST001', 'Durock V2 Stabilizers', 'Premium screw-in stabilizers for mechanical keyboards.', 350000.00, 9, 'https://i.postimg.cc/g2nGtycQ/31.jpg', 6, 20, 'dijual'),
('SW001', 'Leopold FC660C', 'Topre electro-capacitive switches in a 65% layout.', 3700000.00, 10, 'https://i.postimg.cc/pLGppXyb/24.jpg', 7, 100, 'dijual'),
('SW002', 'NovelKeys Cream Switches (70 pcs)', 'Smooth linear switches with self-lubricating POM housing.', 900000.00, 10, 'https://i.postimg.cc/jS5j00c8/29.jpg', 7, 100, 'dijual'),
('SW003', 'Akko CS Jelly Purple (45 pcs)', 'Tactile mechanical switches with a unique jelly-like stem.', 320000.00, 10, 'https://i.postimg.cc/SKYNR6wC/30.jpg', 7, 100, 'dijual'),
('SW004', 'Glorious Panda Switches (36 pcs)', 'Tactile switches with a strong bump and satisfying sound.', 550000.00, 9, 'https://i.postimg.cc/DfPfSyYj/32.jpg', 7, 100, 'dijual'),
('SW005', 'Gateron Oil King Switches (70 pcs)', 'Smooth linear switches with a deep, satisfying sound.', 650000.00, 9, 'https://i.postimg.cc/zvzrCKPd/39.jpg', 7, 100, 'dijual'),
('SW006', 'GATERON BLUE G PRO 3.0 (LUBED) Mechanical Keyboard Switch', 'Gateron Blue G Pro 3.0 (Lubed) は、クリッキータイプのメカニカルスイッチで、押すたびに明確なタクタイルフィードバックと爽快なクリック音を提供します。  工場出荷時から潤滑済み（factory-lubed）のため、キーストロークがスムーズで、スプリングの雑音も軽減されています。  ナイロンハウジングとPOMステムを採用しており、耐久性に優れ、安定した動作を実現します。', 400000.00, 5, 'https://postimg.cc/56jr57D1', 7, 100, 'dijual');

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `thread_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `threads`
--

INSERT INTO `threads` (`thread_id`, `customer_id`, `title`, `created_at`) VALUES
(1, 16, 'Keyboard gaming Brand Genta', '2025-10-26 09:01:33'),
(2, 18, 'Sekya rabani', '2025-10-26 13:24:47');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `voucher_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `kode_voucher` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nilai_rupiah` int NOT NULL,
  `nilai_persen` int DEFAULT NULL,
  `tgl_dibuat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tgl_kadaluarsa` timestamp NOT NULL,
  `status` enum('aktif','terpakai') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aktif',
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipe` enum('rupiah','persen') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'rupiah'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`voucher_id`, `customer_id`, `kode_voucher`, `nilai_rupiah`, `nilai_persen`, `tgl_dibuat`, `tgl_kadaluarsa`, `status`, `keterangan`, `tipe`) VALUES
(6, 15, 'STYRK86A756', 10000, NULL, '2025-10-25 01:52:00', '2025-11-07 18:52:00', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(7, 16, 'STYRK3CF97A', 10000, NULL, '2025-10-25 01:59:15', '2025-11-07 18:59:15', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(8, 16, 'STYRK7F7EBF', 30000, NULL, '2025-10-26 01:32:40', '2025-11-01 18:32:40', 'aktif', 'Voucher Comeback!', 'rupiah'),
(9, 17, 'STYRK14614D', 10000, NULL, '2025-10-26 02:41:20', '2025-11-08 19:41:20', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(10, 19, 'STYRK448348', 10000, NULL, '2025-10-26 06:17:56', '2025-11-08 23:17:56', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(11, 20, 'STYRK58BD69', 10000, NULL, '2025-10-26 06:20:03', '2025-11-08 23:20:03', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(12, 0, 'STYRKIKUZO', 0, 10, '2025-10-26 10:29:13', '2025-12-31 13:36:28', 'terpakai', 'Voucher global diskon 10% - STYRKIKUZO', 'persen'),
(13, 21, 'STYRKD0DB69', 10000, NULL, '2025-11-18 16:49:43', '2025-12-02 09:49:43', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(14, 22, 'STYRK6E0BDE', 10000, NULL, '2025-11-22 05:14:09', '2025-12-05 22:14:09', 'aktif', 'Voucher Selamat Datang', 'rupiah');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `auctions`
--
ALTER TABLE `auctions`
  ADD PRIMARY KEY (`auction_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `current_winner_id` (`current_winner_id`),
  ADD KEY `fk_auction_product` (`product_id`);

--
-- Indexes for table `bids`
--
ALTER TABLE `bids`
  ADD PRIMARY KEY (`bid_id`),
  ADD KEY `auction_id` (`auction_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `FK_customer_id_carts` (`customer_id`),
  ADD KEY `FK_product_id_carts` (`product_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `community_articles`
--
ALTER TABLE `community_articles`
  ADD PRIMARY KEY (`article_id`);

--
-- Indexes for table `community_article_comments`
--
ALTER TABLE `community_article_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `article_id` (`article_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `FK_customer_id` (`customer_id`),
  ADD KEY `FK_KURIR` (`code_courier`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `FK_order_id` (`order_id`),
  ADD KEY `FK_product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `FK_order_id_payment` (`order_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `FK_category_id_products` (`category_id`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`thread_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`voucher_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `auctions`
--
ALTER TABLE `auctions`
  MODIFY `auction_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `bid_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `community_articles`
--
ALTER TABLE `community_articles`
  MODIFY `article_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `community_article_comments`
--
ALTER TABLE `community_article_comments`
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `thread_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `voucher_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auctions`
--
ALTER TABLE `auctions`
  ADD CONSTRAINT `fk_auction_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auction_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auction_winner` FOREIGN KEY (`current_winner_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `bids`
--
ALTER TABLE `bids`
  ADD CONSTRAINT `fk_bid_auction` FOREIGN KEY (`auction_id`) REFERENCES `auctions` (`auction_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bid_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `FK_customer_id_carts` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_product_id_carts` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `community_article_comments`
--
ALTER TABLE `community_article_comments`
  ADD CONSTRAINT `community_article_comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `community_articles` (`article_id`),
  ADD CONSTRAINT `community_article_comments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `FK_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `FK_ORDER_ID` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `FK_ORDER_ID_PAYMENT` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_post_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_post_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `FK_category_id_products` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `threads`
--
ALTER TABLE `threads`
  ADD CONSTRAINT `fk_thread_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
