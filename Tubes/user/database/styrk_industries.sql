-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
<<<<<<< HEAD
-- Generation Time: Oct 28, 2025 at 07:38 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10
=======
-- Waktu pembuatan: 26 Okt 2025 pada 18.02
-- Versi server: 8.4.3
-- Versi PHP: 8.3.16
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

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
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`) VALUES
(101, 'admin', '$2y$10$fbdJi7jdC0xfeKCuZSDaG.Fv6TM7Hiuway3HYMDNnwqKziU9TsOUy');

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
(34, 10, 'KB013', 1, '2025-10-19 12:07:07', NULL),
<<<<<<< HEAD
(55, 18, 'KB012', 1, '2025-10-26 13:26:32', NULL),
(56, 18, 'KB006', 1, '2025-10-26 13:28:10', NULL);
=======
(55, 16, 'KB012', 2, '2025-10-26 17:29:55', NULL),
(56, 16, 'KB013', 1, '2025-10-26 17:29:57', NULL),
(57, 16, 'KB006', 1, '2025-10-26 17:29:59', NULL);
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

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
-- Table structure for table `courier`
--

CREATE TABLE `courier` (
  `code_courier` varchar(99) NOT NULL,
  `nama_kurir` varchar(99) NOT NULL,
  `avaibility` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `courier`
--

INSERT INTO `courier` (`code_courier`, `nama_kurir`, `avaibility`) VALUES
('ide', 'IDExpress', 1),
('jne', 'JNE', 1),
('jnt', 'J&T Express', 1),
('lion', 'Lion Parcel', 1),
('ninja', 'Ninja', 1),
('pos', 'POS Indonesia', 1),
('rex', 'Royal Express Asia', 1),
('sap', 'SAP Express', 1),
('sentral', 'Sentral Cargo', 1),
('sicepat', 'SiCepat', 1),
('tiki', 'TIKI', 1),
('wahana', 'Wahana Express', 1);

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
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_reengagement_sent` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `nama`, `password`, `email`, `no_telepon`, `provinsi`, `kota`, `alamat`, `last_login`, `last_reengagement_sent`) VALUES
(5, 'Jeremia', '$2y$10$fwucJezb3RQTmc5Gz2a56uyAjvXYyMdS/V38v913qqxpJtpMF5sRS', 'jeremiadylan80@gmail.com', '081312663058', '', '', 'Jalan kebenara 169', NULL, NULL),
(6, 'Jeremia', '$2y$10$JK.Mo5XVtj0TCs2ikcXCBevbN5By6w9KYArlaRgMFBXFlpJK5gmFO', 'jeremiaethan05@gmail.com', '081312663058', '', '', 'Jalan asmi no 123', NULL, NULL),
(7, 'Aldy Taher', '$2y$10$EiyoeSPMZt2kKDkP5bB0h.7ae7fA5dvhz4uJpgwSFup5viqMXjIlK', 'guaganteng@123.com', '123456', '', '', 'jalan tuhan kebenaran no. 100', NULL, NULL),
(10, 'Tuyul Ganteng', '$2y$10$ERjVD1oOnWRikvY297secepKphheTL5UKAYmWeCtxMVO4wru7N2OG', '2373003@maranatha.ac.id', '298483924', '', '', 'rumahsammy 123', NULL, NULL),
<<<<<<< HEAD
(16, 'Doni Salmanan', '$2y$10$7uuw.sFubujIPGy2KANG4.s20CN.w7uznjQxwWPtwfTCJ7zieh./C', 'styrk_industries@gmail.com', '08124272849', '4', '462', 'gunung gede 123', '2025-06-26 08:30:06', '2025-10-26 08:32:45'),
(17, 'Aldy Taher', '$2y$10$FTxIp34ew5uky05iP7JtzuWmB.KHyTkJnOZaRkn0ze4yO9B6Pia56', 'kink.konk169@gmail.com', '081223830598', '3', '36', 'banjaran 120', NULL, NULL),
(18, 'JRMIA', '$2y$12$bl8jij7L3oJrrqI6cyDgWeEAFhTB/v7gH2.8dOIgFp/3ynWNt0ZQG', 'jeremiadylan15@gmail.com', '081312663058', '5', '55', 'Taman Kopo Indah 69 Blok S', '2025-10-27 15:38:06', NULL);
=======
(12, 'JRMIA', '$2y$12$tzAx012j9sKDVpzX/JjkY.IIeryx45XdJJO7NGy6ZpivAxkn0wn4C', 'jeremiadylan15@gmail.com', '081312663058', '18', '531', 'Taman Kopo Indah 69 Blok S', NULL, NULL),
(16, 'Doni Salmanan', '$2y$10$7uuw.sFubujIPGy2KANG4.s20CN.w7uznjQxwWPtwfTCJ7zieh./C', 'styrk_industries@gmail.com', '08124272849', '4', '462', 'gunung gede 123', '2025-10-26 13:26:46', '2025-10-26 08:32:45'),
(17, 'Aldy Taher', '$2y$10$FTxIp34ew5uky05iP7JtzuWmB.KHyTkJnOZaRkn0ze4yO9B6Pia56', 'kink.konk169@gmail.com', '081223830598', '3', '36', 'banjaran 120', NULL, NULL);
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `tgl_order` datetime NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `status` enum('pending','proses','selesai','batal') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `tgl_order`, `total_harga`, `status`) VALUES
(18, 7, '2025-06-24 23:40:55', 90.90, 'batal'),
(19, 7, '2025-06-24 23:58:37', 663.87, 'batal'),
(20, 7, '2025-06-24 23:59:39', 272.70, 'batal'),
(21, 10, '2025-10-19 08:04:46', 2929000.00, 'proses'),
(22, 10, '2025-10-19 10:59:04', 2424000.00, 'proses'),
(23, 10, '2025-10-19 12:17:02', 1899132.00, 'proses'),
(26, 16, '2025-10-25 12:31:49', 6110500.00, 'proses'),
(27, 16, '2025-10-25 13:34:14', 7070000.00, 'proses'),
(28, 16, '2025-10-25 14:14:13', 3838000.00, 'proses');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `detail_id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah` int NOT NULL,
  `harga_satuan` int NOT NULL,
  `subtotal` int NOT NULL,
  `ongkos_kirim` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`detail_id`, `order_id`, `product_id`, `jumlah`, `harga_satuan`, `subtotal`, `ongkos_kirim`) VALUES
(22, 18, 'KB008', 1, 90, 90, 0),
(23, 19, 'KB010', 1, 299, 299, 0),
(24, 19, 'KB006', 1, 358, 358, 0),
(25, 20, 'KB008', 3, 90, 270, 0),
(26, 21, 'KB008', 2, 1450000, 2900000, 0),
(27, 22, 'SW005', 1, 650000, 650000, 0),
(28, 22, 'KC006', 1, 1750000, 1750000, 0),
(29, 23, 'KB009', 1, 1900000, 1900000, 0),
(33, 26, 'KB010', 1, 4800000, 4800000, 0),
(34, 26, 'KB004', 1, 1250000, 1250000, 0),
(35, 27, 'KC004', 1, 4200000, 4200000, 0),
(36, 27, 'KB011', 1, 2800000, 2800000, 0),
(37, 28, 'KK003', 1, 3800000, 3800000, 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `tracking_id` int NOT NULL,
  `order_id` int NOT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`tracking_id`, `order_id`, `status`, `description`, `timestamp`) VALUES
(1, 18, 'batal', 'Payment rejected, order canceled and stock restored', '2025-06-25 06:52:23'),
(2, 19, 'batal', 'Pembayaran ditolak, silahkan belanja kembali.', '2025-06-25 06:59:07'),
(3, 20, 'batal', 'Pembayaran ditolak, silahkan belanja kembali.', '2025-06-25 07:00:13');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `order_id` int NOT NULL,
  `metode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_dibayar` decimal(12,2) NOT NULL,
  `tanggal_bayar` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_proof` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `metode`, `jumlah_dibayar`, `tanggal_bayar`, `payment_proof`, `payment_status`) VALUES
(8, 18, 'Transfer Bank', 90.90, '2025-06-24 23:40:55', '../payment_proofs/proof_18_1750808455.png', 'rejected'),
(9, 19, 'QRIS', 663.87, '2025-06-24 23:58:37', 'STYRK_ORDER19_811', 'rejected'),
(10, 20, 'QRIS', 272.70, '2025-06-24 23:59:39', 'STYRK_ORDER20_747', 'rejected'),
(11, 21, 'QRIS', 2929000.00, '2025-10-19 08:04:46', 'STYRK_ORDER21_121', 'proses'),
(12, 22, 'QRIS', 2424000.00, '2025-10-19 10:59:04', 'STYRK_ORDER22_959', 'proses'),
(13, 23, 'QRIS', 1899132.00, '2025-10-19 12:17:02', 'STYRK_ORDER23_252', 'proses'),
(16, 26, 'QRIS', 6110500.00, '2025-10-25 12:31:49', 'STYRK_ORDER24_791', 'proses'),
(17, 27, 'QRIS', 7070000.00, '2025-10-25 13:34:14', 'STYRK_ORDER27_451', 'proses'),
(18, 28, 'QRIS', 3838000.00, '2025-10-25 14:14:13', 'STYRK_ORDER28_456', 'proses');

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
<<<<<<< HEAD
(2, 2, 18, 'Genta ganteng banget', '2025-10-26 13:24:47'),
(3, 2, 18, 'sekya', '2025-10-26 15:20:59'),
(4, 1, 18, 'keren banget kamu kak', '2025-10-26 15:21:28');
=======
(2, 1, 16, 'jelek', '2025-10-26 14:09:29'),
(3, 1, 16, 'ganteng', '2025-10-26 14:33:21');
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

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
  `weight` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `nama_produk`, `deskripsi_produk`, `harga`, `stok`, `link_gambar`, `category_id`, `weight`) VALUES
('CS001', 'Tofu60 Redux Case', 'An upgraded version of the classic Tofu60 case, offering improved materials, finish, and design features. Compatible with a wide range of 60% PCBs and plates.', 1850000.00, 8, 'https://i.postimg.cc/T1D3LRzQ/11.jpg', 1, 500),
('KB001', 'Sirius Manta', 'A premium mechanical keyboard known for its elegant design and smooth typing experience. The Sirius Manta blends aesthetics with functionality, making it a favorite among hobbyists.', 3200000.00, 0, 'https://i.postimg.cc/zfxB42ww/10.jpg', 2, 1000),
('KB002', 'Snake60 R2', 'A high-end 60% keyboard kit with sleek lines and robust build quality. The Snake60 R2 delivers a refined typing experience and top-tier customization options at a heavily discounted price.', 7500000.00, 0, 'https://i.postimg.cc/L5chNqtr/2.jpg', 2, 1000),
('KB003', 'KBD8X MKIII Keyboard', 'A beloved full-sized mechanical keyboard featuring top mount design and premium aluminum construction. Now at half price, it\'s a steal for serious keyboard builders.', 7800000.00, 9, 'https://i.postimg.cc/JnhynC7d/4.jpg', 2, 1000),
('KB004', 'Magnum65', 'A 65% layout keyboard with a bold design and exceptional build quality. The Magnum65 is for those who want a compact form factor without compromising on performance.', 1250000.00, 5, 'https://i.postimg.cc/sfqBVLkw/5.jpg', 2, 1000),
('KB005', 'Quartz Stone Wrist Rest', 'A solid quartz wrist rest designed to offer comfort and elegance. Its cool, stone finish adds a premium touch to your keyboard setup.', 650000.00, 6, 'https://i.postimg.cc/jSQC4SLF/7.jpg', 2, 1000),
('KB006', 'Odin 75 Hot-swap Keyboard with PBTfans Courage red', 'A ready-to-use Odin 75 keyboard with bold Courage Red keycaps. Hot-swap sockets make switch swapping easy without soldering.', 5750000.00, 10, 'https://i.postimg.cc/bwH9Mn60/17.jpg', 2, 1000),
('KB007', 'Keychron K8 Wireless', 'A tenkeyless wireless mechanical keyboard compatible with Mac and Windows.', 1300000.00, 9, 'https://i.postimg.cc/mrPhMfFc/21.jpg', 2, 1000),
('KB008', 'Akko 3068B Plus', 'A compact 65% keyboard with wireless connectivity and hot-swappable switches.', 1450000.00, 8, 'https://i.postimg.cc/0Nhj0WpV/22.png', 2, 1000),
('KB009', 'Ducky One 3 Mini', 'A 60% keyboard known for vibrant colors and premium build.', 1900000.00, 8, 'https://i.postimg.cc/vB9Bqrhb/23.jpg', 2, 1000),
('KB010', 'Mode Sonnet Keyboard', 'A custom keyboard with a sleek design and premium materials.', 4800000.00, 9, 'https://i.postimg.cc/XqbvTr1F/25.jpg', 2, 1000),
('KB011', 'Keychron Q1 V2', 'A customizable 75% keyboard with QMK/VIA support.', 2800000.00, 9, 'https://i.postimg.cc/KjDYFmCW/26.jpg', 2, 1000),
('KB012', 'Ikki68 Aurora', 'A popular entry-level custom keyboard kit.', 2150000.00, 10, 'https://i.postimg.cc/J7N0jQtQ/27.jpg', 2, 1000),
('KB013', 'MelGeek Mojo68', 'A semi-transparent wireless keyboard with customizable layout.', 3600000.00, 10, 'https://i.postimg.cc/X7NjdSRV/33.jpg', 2, 1000),
('KB014', 'NK65 Entry Edition', 'A budget-friendly 65% mechanical keyboard with a polycarbonate case.', 1500000.00, 10, 'https://i.postimg.cc/ydzBNwhC/36.jpg', 2, 1000),
('KB015', 'Keychron V4', 'A budget 60% keyboard with QMK/VIA support.', 1100000.00, 10, 'https://i.postimg.cc/43cssJ91/37.jpg', 2, 1000),
('KB016', 'Ajazz AK966', '96% layout wireless mechanical keyboard with knob.', 1950000.00, 10, 'https://i.postimg.cc/ZRFmvVjB/38.jpg\r\n', 2, 1000),
('KB017', 'Akko MOD 007 V2', 'A premium 75% keyboard with gasket mount design.', 2300000.00, 10, 'https://i.postimg.cc/QCfr0C2j/40.jpg', 2, 1000),
('KB018', 'Rama M65-B', 'High-end aluminum 65% keyboard with elegant design.', 5800000.00, 10, 'https://i.postimg.cc/Qd2Z6RyK/41.jpg', 2, 1000),
('KB019', 'CannonKeys Savage65', '65% keyboard kit with a CNC aluminum case.', 3700000.00, 10, 'https://i.postimg.cc/6QCJFYkj/42.jpg', 2, 1000),
('KB020', 'Drop ALT Keyboard', 'Compact mechanical keyboard with hot-swap sockets.', 2900000.00, 10, 'https://i.postimg.cc/bNFhnG0T/43.jpg', 2, 1000),
('KB021', 'Varmilo VA87M', 'Tenkeyless keyboard with a variety of themes and switches.', 2250000.00, 10, 'https://i.postimg.cc/vZgdtQ3d/44.jpg', 2, 1000),
('KB022', 'Zoom65 V2', 'A versatile 65% keyboard with wireless features.', 2850000.00, 10, 'https://i.postimg.cc/63wJKCw9/45.jpg', 2, 1000),
('KB023', 'MelGeek Pixel Keyboard', 'A customizable Lego-style keyboard.', 4200000.00, 10, 'https://i.postimg.cc/Jnqwzt4j/46.jpg', 2, 1000),
('KB024', 'IDOBAO ID80', 'Gasket-mounted keyboard with a unique acrylic body.', 3000000.00, 10, 'https://i.postimg.cc/Zn716gNR/47.jpg', 2, 1000),
('KB025', 'EPOMAKER TH96', '96% keyboard with hot-swap, wireless and knob features.', 2100000.00, 10, 'https://i.postimg.cc/jdCVRxVJ/50.jpg', 2, 1000),
('KB026', 'Royal Kludge RK61', 'Budget 60% wireless mechanical keyboard.', 800000.00, 10, 'https://i.postimg.cc/RCg5r3YB/51.jpg', 2, 1000),
('KC001', 'Circus PGA Profile Keycaps', 'A vibrant and playful set of PGA profile keycaps inspired by circus aesthetics. Perfect for mechanical keyboard enthusiasts looking to add a burst of color and uniqueness to their setup.', 1250000.00, 10, 'https://i.postimg.cc/zBPyH2VD/1.jpg', 3, 1000),
('KC002', 'Dusk 67 with PBTfans Inkdrop', 'A beautifully themed 65% keyboard featuring the Dusk 67 case and PBTfans Inkdrop keycaps. This bundle is perfect for those who want a cohesive, stunning setup.', 3300000.00, 10, 'https://i.postimg.cc/WpC8JTFZ/13.jpg', 3, 1000),
('KC003', 'TET Keyboard With PBTfans Count Dracula', 'A spooky and stylish keyboard pairing the TET layout with the popular PBTfans Count Dracula keycaps. Eye-catching and great for Halloween or gothic setups.', 4000000.00, 10, 'https://i.postimg.cc/7ZyNm9NY/16.jpg', 3, 1000),
('KC004', 'KBD8X MKIII HE Gaming Keyboard with PBTfans Blush', 'A performance-focused version of the KBD8X MKIII featuring Hall Effect switches for rapid input and PBTfans Blush keycaps for soft, pastel aesthetics.', 4200000.00, 9, 'https://i.postimg.cc/HWXcBgvX/18.jpg', 3, 1000),
('KC005', 'GMK Red Samurai Keycap Set', 'A premium GMK keycap set inspired by traditional samurai aesthetics.', 2400000.00, 9, 'https://i.postimg.cc/SKSfpK5B/19.jpg', 3, 1000),
('KC006', 'KAT Milkshake Keycap Set', 'A colorful pastel keycap set with a unique KAT profile.', 1750000.00, 9, 'https://i.postimg.cc/c4DsckWf/34.jpg', 3, 1000),
('KC007', 'SA Bliss Keycap Set', 'A vibrant SA profile keycap set inspired by serene aesthetics.', 2100000.00, 10, 'https://i.postimg.cc/0yFPT6bQ/35.jpg', 3, 1000),
('KC008', 'GMK Olivia Keycap Set', 'Elegant pink and black themed GMK keycap set.', 2400000.00, 10, 'https://i.postimg.cc/zXQsBs52/49.png', 3, 1000),
('KK001', 'Tofu60 Redux Plate', 'A compatible plate for the Tofu60 Redux case, offering improved rigidity and mounting flexibility. Great for customizing your typing feel.', 320000.00, 10, 'https://i.postimg.cc/L6PJhTRR/6.jpg', 4, 200),
('KK002', 'KBD67 Lite R4 Mechanical Keyboard Kit', 'A budget-friendly yet high-performing 65% keyboard kit. Ideal for newcomers and veterans alike, with hot-swap functionality and great acoustics.', 1900000.00, 10, 'https://i.postimg.cc/2SfVWZ8W/8.jpg', 4, 200),
('KK003', 'KBDfans Odin 75 Mechanical Keyboard Kit', 'A compact 75% layout keyboard with a stylish and functional design. The Odin 75 offers great balance between form and usability.', 3800000.00, 9, 'https://i.postimg.cc/mrLkXW92/9.jpg', 4, 200),
('KK004', 'Sebas Keyboard kit', 'A stylish and sturdy keyboard kit designed with premium materials. Its layout and build make it suitable for both work and play.', 3450000.00, 10, 'https://i.postimg.cc/2jVT6V6m/12.jpg', 4, 200),
('KK005', 'KBDfans GT-80 Case', 'A durable and elegant keyboard case designed for the GT-80 layout. Built with anodized aluminum and precision machining.', 1900000.00, 10, 'https://i.postimg.cc/pyMXKwxv/14.jpg', 4, 200),
('KK006', 'Margo Case', 'A uniquely designed keyboard case with gentle curves and premium anodization. A great choice for custom keyboard builds looking to stand out.', 2150000.00, 10, 'https://i.postimg.cc/9F9pdKZn/15.jpg', 4, 200),
('KK007', 'GMMK Pro Barebone', 'A 75% layout mechanical keyboard with a rotary knob and aluminum body.', 2750000.00, 10, 'https://i.postimg.cc/0NVd5s1b/20.jpg', 4, 200),
('KK008', 'Tofu65 Kit', 'Aluminum 65% DIY keyboard kit with customizable options.', 2700000.00, 10, 'https://i.postimg.cc/m2Vr52Yz/28.jpg', 4, 200),
('KK009', 'KBD75 V3 Kit', '75% aluminum keyboard with refined layout and features.', 2950000.00, 10, 'https://i.postimg.cc/Kjv6kFRw/48.jpg', 4, 200),
('KP001', 'Taco Pad', 'A novelty macropad shaped like a taco. Fun, quirky, and useful for macros, shortcuts, or artisan display. A must-have desk companion for enthusiasts.', 1450000.00, 10, 'https://i.postimg.cc/C5BzGCqG/3.jpg', 5, 400),
('ST001', 'Durock V2 Stabilizers', 'Premium screw-in stabilizers for mechanical keyboards.', 350000.00, 10, 'https://i.postimg.cc/g2nGtycQ/31.jpg', 6, 20),
('SW001', 'Leopold FC660C', 'Topre electro-capacitive switches in a 65% layout.', 3700000.00, 10, 'https://i.postimg.cc/pLGppXyb/24.jpg', 7, 100),
('SW002', 'NovelKeys Cream Switches (70 pcs)', 'Smooth linear switches with self-lubricating POM housing.', 900000.00, 10, 'https://i.postimg.cc/jS5j00c8/29.jpg', 7, 100),
('SW003', 'Akko CS Jelly Purple (45 pcs)', 'Tactile mechanical switches with a unique jelly-like stem.', 320000.00, 10, 'https://i.postimg.cc/SKYNR6wC/30.jpg', 7, 100),
('SW004', 'Glorious Panda Switches (36 pcs)', 'Tactile switches with a strong bump and satisfying sound.', 550000.00, 10, 'https://i.postimg.cc/DfPfSyYj/32.jpg', 7, 100),
('SW005', 'Gateron Oil King Switches (70 pcs)', 'Smooth linear switches with a deep, satisfying sound.', 650000.00, 9, 'https://i.postimg.cc/zvzrCKPd/39.jpg', 7, 100),
('SW006', 'GATERON BLUE G PRO 3.0 (LUBED) Mechanical Keyboard Switch', 'Gateron Blue G Pro 3.0 (Lubed) は、クリッキータイプのメカニカルスイッチで、押すたびに明確なタクタイルフィードバックと爽快なクリック音を提供します。  工場出荷時から潤滑済み（factory-lubed）のため、キーストロークがスムーズで、スプリングの雑音も軽減されています。  ナイロンハウジングとPOMステムを採用しており、耐久性に優れ、安定した動作を実現します。', 400000.00, 5, 'https://postimg.cc/56jr57D1', 7, 100);

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
  `tipe` enum('rupiah','persen') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'rupiah'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

<<<<<<< HEAD
INSERT INTO `vouchers` (`voucher_id`, `customer_id`, `kode_voucher`, `nilai_rupiah`, `tgl_dibuat`, `tgl_kadaluarsa`, `status`, `keterangan`) VALUES
(1, 10, 'STYRKF4087D', 19868, '2025-10-18 12:41:44', '2025-11-01 05:41:44', 'terpakai', 'Voucher Selamat Datang'),
(2, 11, 'STYRKBA5480', 10000, '2025-10-19 12:35:45', '2025-11-02 05:35:45', 'terpakai', 'Voucher Selamat Datang'),
(3, 12, 'STYRK65D3ED', 20000, '2025-10-22 14:14:30', '2025-11-05 07:14:30', 'aktif', 'Voucher Selamat Datang'),
(4, 13, 'STYRK610940', 15000, '2025-10-22 15:11:35', '2025-11-05 08:11:35', 'aktif', 'Voucher Selamat Datang'),
(5, 14, 'STYRK40CC72', 10000, '2025-10-25 05:49:41', '2025-11-07 22:49:41', 'aktif', 'Voucher Selamat Datang'),
(6, 15, 'STYRK86A756', 10000, '2025-10-25 08:52:00', '2025-11-08 01:52:00', 'aktif', 'Voucher Selamat Datang'),
(7, 16, 'STYRK3CF97A', 10000, '2025-10-25 08:59:15', '2025-11-08 01:59:15', 'aktif', 'Voucher Selamat Datang'),
(8, 16, 'STYRK7F7EBF', 30000, '2025-10-26 08:32:40', '2025-11-02 01:32:40', 'aktif', 'Voucher Comeback!'),
(9, 17, 'STYRK14614D', 10000, '2025-10-26 09:41:20', '2025-11-09 02:41:20', 'aktif', 'Voucher Selamat Datang'),
(10, 18, 'STYRK0748FB', 10000, '2025-10-26 13:23:30', '2025-11-09 06:23:30', 'aktif', 'Voucher Selamat Datang');
=======
INSERT INTO `vouchers` (`voucher_id`, `customer_id`, `kode_voucher`, `nilai_rupiah`, `nilai_persen`, `tgl_dibuat`, `tgl_kadaluarsa`, `status`, `keterangan`, `tipe`) VALUES
(6, 15, 'STYRK86A756', 10000, NULL, '2025-10-25 08:52:00', '2025-11-08 01:52:00', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(7, 16, 'STYRK3CF97A', 10000, NULL, '2025-10-25 08:59:15', '2025-11-08 01:59:15', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(8, 16, 'STYRK7F7EBF', 30000, NULL, '2025-10-26 08:32:40', '2025-11-02 01:32:40', 'aktif', 'Voucher Comeback!', 'rupiah'),
(9, 17, 'STYRK14614D', 10000, NULL, '2025-10-26 09:41:20', '2025-11-09 02:41:20', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(10, 19, 'STYRK448348', 10000, NULL, '2025-10-26 13:17:56', '2025-11-09 06:17:56', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(11, 20, 'STYRK58BD69', 10000, NULL, '2025-10-26 13:20:03', '2025-11-09 06:20:03', 'aktif', 'Voucher Selamat Datang', 'rupiah'),
(12, 0, 'STYRKIKUZO', 0, 10, '2025-10-26 17:29:13', '2026-10-26 10:29:13', 'aktif', 'Voucher global diskon 10% - STYRKIKUZO', 'persen');
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

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
-- Indexes for table `courier`
--
ALTER TABLE `courier`
  ADD PRIMARY KEY (`code_courier`);

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
  ADD KEY `FK_customer_id` (`customer_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `FK_order_id` (`order_id`),
  ADD KEY `FK_product_id` (`product_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `FK_ORDER_ID_TRACK` (`order_id`);

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
  ADD PRIMARY KEY (`voucher_id`),
  ADD UNIQUE KEY `kode_voucher` (`kode_voucher`),
  ADD KEY `customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
<<<<<<< HEAD
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;
=======
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
<<<<<<< HEAD
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
=======
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `tracking_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
<<<<<<< HEAD
  MODIFY `post_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
=======
  MODIFY `post_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `thread_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
<<<<<<< HEAD
  MODIFY `voucher_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
=======
  MODIFY `voucher_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
>>>>>>> f56171cc7fe3b94ce37fb32765b3e529fa3a696c

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `FK_customer_id_carts` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_product_id_carts` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `FK_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `FK_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `FK_ORDER_ID_TRACK` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `FK_order_id_payment` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
