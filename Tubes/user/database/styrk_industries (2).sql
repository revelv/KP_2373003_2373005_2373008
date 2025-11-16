-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 15, 2025 at 04:37 PM
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
  `provinsi` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kota` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kecamatan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `alamat` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `provinsi`, `kota`, `kecamatan`, `alamat`) VALUES
(101, 'admin', '$2y$10$fbdJi7jdC0xfeKCuZSDaG.Fv6TM7Hiuway3HYMDNnwqKziU9TsOUy', '', '', '', '');

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
(3, 16, 'KB019', 'CannonKeys Savage65', '65% keyboard kit with a CNC aluminum case.', 'https://i.postimg.cc/6QCJFYkj/42.jpg', 1000000.00, 1100000.00, 16, '2025-11-15 09:49:41', '2025-11-17 09:42:00', 'active'),
(4, 16, 'KB017', 'Akko MOD 007 V2', 'A premium 75% keyboard with gasket mount design.', 'https://i.postimg.cc/QCfr0C2j/40.jpg', 2000000.00, 2000007.00, 18, '2025-11-15 10:03:48', '2025-11-17 10:03:00', 'active'),
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
(6, 4, 18, 2000007.00, '2025-11-15 14:20:35');

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
(34, 10, 'KB013', 2, '2025-11-15 13:47:29', NULL),
(67, 18, 'SW001', 1, '2025-11-13 08:31:09', NULL),
(70, 10, 'KB018', 1, '2025-11-15 13:47:33', NULL),
(71, 16, 'KB018', 1, '2025-11-15 14:04:16', NULL);

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
  `code_courier` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_kurir` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `kecamatan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_reengagement_sent` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `nama`, `password`, `email`, `no_telepon`, `provinsi`, `kota`, `kecamatan`, `alamat`, `last_login`, `last_reengagement_sent`) VALUES
(5, 'Jeremia', '$2y$10$fwucJezb3RQTmc5Gz2a56uyAjvXYyMdS/V38v913qqxpJtpMF5sRS', 'jeremiadylan80@gmail.com', '081312663058', '', '', '', 'Jalan kebenara 169', NULL, NULL),
(6, 'Jeremia', '$2y$10$JK.Mo5XVtj0TCs2ikcXCBevbN5By6w9KYArlaRgMFBXFlpJK5gmFO', 'jeremiaethan05@gmail.com', '081312663058', '2', '25', '', 'Jalan asmi no 123', '2025-11-13 08:31:33', NULL),
(7, 'Aldy Taher', '$2y$10$EiyoeSPMZt2kKDkP5bB0h.7ae7fA5dvhz4uJpgwSFup5viqMXjIlK', 'guaganteng@123.com', '123456', '', '', '', 'jalan tuhan kebenaran no. 100', NULL, NULL),
(10, 'Tuyul Ganteng', '$2y$10$ERjVD1oOnWRikvY297secepKphheTL5UKAYmWeCtxMVO4wru7N2OG', '2373003@maranatha.ac.id', '298483924', '', '', '', 'rumahsammy 123', '2025-11-15 13:36:31', NULL),
(16, 'Doni Salmanan', '$2y$10$7uuw.sFubujIPGy2KANG4.s20CN.w7uznjQxwWPtwfTCJ7zieh./C', 'styrk_industries@gmail.com', '08124272849', '4', '462', '', 'gunung gede 123', '2025-11-15 14:02:53', '2025-10-26 08:32:45'),
(17, 'Aldy Taher', '$2y$10$FTxIp34ew5uky05iP7JtzuWmB.KHyTkJnOZaRkn0ze4yO9B6Pia56', 'kink.konk169@gmail.com', '081223830598', '3', '36', '', 'banjaran 120', NULL, NULL),
(18, 'JRMIA', '$2y$12$bl8jij7L3oJrrqI6cyDgWeEAFhTB/v7gH2.8dOIgFp/3ynWNt0ZQG', 'jeremiadylan15@gmail.com', '081312663058', '6', '64', '626', 'Taman Kopo Indah 69 Blok S', '2025-11-13 08:30:22', NULL),
(19, 'jeremiadylan', '$2y$12$iOWWC85uO2u7dOJuDEGG7eZiL.jRv2Or5fSYRgWAoLELkZMqHyS6a', 'jeremiadylan115@gmail.com', '081312663058', '5', '55', '', 'Taman Kopo Indah 69 Blok S', '2025-10-28 15:41:29', NULL);

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
  `alamat` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code_courier` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ongkos_kirim` int NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `status` enum('pending','proses','selesai','batal') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `tgl_order`, `provinsi`, `kota`, `alamat`, `code_courier`, `ongkos_kirim`, `total_harga`, `status`) VALUES
('STYRK176303123943954', 6, '2025-11-13 10:53:59', '2', '25', 'Jalan asmi no 123', 'jne', 14000, 3714000.00, 'proses'),
('STYRK176303199680232', 6, '2025-11-13 11:06:36', '2', '25', 'Jalan asmi no 123', 'jne', 14000, 2084000.00, 'proses');

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
(49, 'STYRK176303123943954', 'CS001', 2, 1850000, 3700000),
(50, 'STYRK176303199680232', 'KB017', 1, 2300000, 2300000);

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `tracking_id` int NOT NULL,
  `order_id` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `metode`, `jumlah_dibayar`, `tanggal_bayar`, `payment_proof`, `payment_status`) VALUES
(28, 'STYRK176303123943954', 'QRIS', 3714000.00, '2025-11-13 10:53:59', 'STYRK_QRIS_1763031236970_979', 'proses'),
(29, 'STYRK176303199680232', 'QRIS', 2084000.00, '2025-11-13 11:06:36', 'STYRK_QRIS_1763031991688_340', 'proses');

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
(4, 1, 18, 'keren banget kamu kak', '2025-10-26 15:21:28');

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
('CS001', 'Tofu60 Redux Case', 'An upgraded version of the classic Tofu60 case, offering improved materials, finish, and design features. Compatible with a wide range of 60% PCBs and plates.', 1850000.00, 4, 'https://i.postimg.cc/T1D3LRzQ/11.jpg', 1, 500, 'dijual'),
('KB001', 'Sirius Manta', 'A premium mechanical keyboard known for its elegant design and smooth typing experience. The Sirius Manta blends aesthetics with functionality, making it a favorite among hobbyists.', 3200000.00, 0, 'https://i.postimg.cc/zfxB42ww/10.jpg', 2, 1000, 'dijual'),
('KB002', 'Snake60 R2', 'A high-end 60% keyboard kit with sleek lines and robust build quality. The Snake60 R2 delivers a refined typing experience and top-tier customization options at a heavily discounted price.', 7500000.00, 0, 'https://i.postimg.cc/L5chNqtr/2.jpg', 2, 1000, 'dijual'),
('KB003', 'KBD8X MKIII Keyboard', 'A beloved full-sized mechanical keyboard featuring top mount design and premium aluminum construction. Now at half price, it\'s a steal for serious keyboard builders.', 7800000.00, 9, 'https://i.postimg.cc/JnhynC7d/4.jpg', 2, 1000, 'dijual'),
('KB004', 'Magnum65', 'A 65% layout keyboard with a bold design and exceptional build quality. The Magnum65 is for those who want a compact form factor without compromising on performance.', 1250000.00, 5, 'https://i.postimg.cc/sfqBVLkw/5.jpg', 2, 1000, 'dijual'),
('KB005', 'Quartz Stone Wrist Rest', 'A solid quartz wrist rest designed to offer comfort and elegance. Its cool, stone finish adds a premium touch to your keyboard setup.', 650000.00, 6, 'https://i.postimg.cc/jSQC4SLF/7.jpg', 2, 1000, 'dijual'),
('KB006', 'Odin 75 Hot-swap Keyboard with PBTfans Courage red', 'A ready-to-use Odin 75 keyboard with bold Courage Red keycaps. Hot-swap sockets make switch swapping easy without soldering.', 5750000.00, 9, 'https://i.postimg.cc/bwH9Mn60/17.jpg', 2, 1000, 'dijual'),
('KB007', 'Keychron K8 Wireless', 'A tenkeyless wireless mechanical keyboard compatible with Mac and Windows.', 1300000.00, 9, 'https://i.postimg.cc/mrPhMfFc/21.jpg', 2, 1000, 'dijual'),
('KB008', 'Akko 3068B Plus', 'A compact 65% keyboard with wireless connectivity and hot-swappable switches.', 1450000.00, 8, 'https://i.postimg.cc/0Nhj0WpV/22.png', 2, 1000, 'dijual'),
('KB009', 'Ducky One 3 Mini', 'A 60% keyboard known for vibrant colors and premium build.', 1900000.00, 8, 'https://i.postimg.cc/vB9Bqrhb/23.jpg', 2, 1000, 'dijual'),
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
(12, 0, 'STYRKIKUZO', 0, 10, '2025-10-26 10:29:13', '2025-12-31 13:36:28', 'terpakai', 'Voucher global diskon 10% - STYRKIKUZO', 'persen');

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
-- Indexes for table `courier`
--
ALTER TABLE `courier`
  ADD PRIMARY KEY (`code_courier`),
  ADD UNIQUE KEY `code_courier` (`code_courier`);

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
  MODIFY `bid_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `tracking_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `thread_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `FK_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_KURIR` FOREIGN KEY (`code_courier`) REFERENCES `courier` (`code_courier`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `FK_ORDER_ID` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `FK_ODER_ID_TRACK` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
