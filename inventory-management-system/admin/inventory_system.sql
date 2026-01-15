-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 15, 2026 at 11:26 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_quantity` int(11) DEFAULT 10,
  `qr_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `description`, `category`, `price`, `quantity`, `min_quantity`, `qr_code`, `created_at`, `updated_at`) VALUES
(1, 'prod767', 'acer mouse', 'smooth scrolling', 'Electronics', 100.00, 208, 10, '0', '2026-01-10 13:34:36', '2026-01-12 05:23:19'),
(2, 'PROD001', 'acer123', 'good for office', 'Laptop', 50000.00, 18, 10, '', '2026-01-10 13:35:30', '2026-01-10 13:58:58'),
(3, 'prod 111', 'Dell', 'good for students', 'Laptop', 50000.00, 100, 10, '', '2026-01-10 13:36:54', '2026-01-10 13:36:54'),
(4, 'PROD0010', 'Lenovo t460', 'good for house using', 'Laptop', 5000.00, 70, 10, '', '2026-01-10 13:39:28', '2026-01-10 13:39:28'),
(5, 'LAP001', 'Dell Inspiron 15 3000', '15.6\" FHD, i5-1135G7, 8GB RAM, 512GB SSD', 'Laptop', 45000.00, 200, 5, 'QR_LAP001', '2026-01-10 13:58:17', '2026-01-15 10:20:42'),
(6, 'LAP002', 'HP Pavilion 14', '14\" FHD, Ryzen 5 5500U, 8GB RAM, 512GB SSD', 'Laptop', 48000.00, 20, 5, 'QR_LAP002', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(7, 'LAP003', 'Lenovo IdeaPad Slim 3', '15.6\" FHD, i3-1115G4, 8GB RAM, 256GB SSD', 'Laptop', 35000.00, 30, 5, 'QR_LAP003', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(8, 'LAP004', 'Asus VivoBook 15', '15.6\" FHD, Ryzen 7 5700U, 16GB RAM, 512GB SSD', 'Laptop', 55000.00, 15, 5, 'QR_LAP004', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(10, 'LAP006', 'MSI Modern 14', '14\" FHD, i5-1135G7, 8GB RAM, 512GB SSD', 'Laptop', 52000.00, 12, 3, 'QR_LAP006', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(11, 'LAP007', 'Dell Vostro 15', '15.6\" FHD, i7-1165G7, 16GB RAM, 512GB SSD', 'Laptop', 68000.00, 10, 3, 'QR_LAP007', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(12, 'LAP008', 'HP 15s', '15.6\" HD, Ryzen 3 5300U, 8GB RAM, 512GB SSD', 'Laptop', 38000.00, 22, 5, 'QR_LAP008', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(13, 'LAP009', 'Lenovo ThinkPad E14', '14\" FHD, i5-1135G7, 8GB RAM, 512GB SSD', 'Laptop', 58000.00, 14, 3, 'QR_LAP009', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(14, 'LAP010', 'Asus TUF Gaming F15', '15.6\" FHD 144Hz, i5-11400H, RTX 3050, 8GB, 512GB', 'Laptop', 72000.00, 8, 3, 'QR_LAP010', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(15, 'DSK001', 'Dell Optiplex 3080 MT', 'i5-10500, 8GB RAM, 1TB HDD, Win 11 Pro', 'Desktop', 42000.00, 15, 3, 'QR_DSK001', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(16, 'DSK002', 'HP ProDesk 400 G7', 'i3-10100, 8GB RAM, 256GB SSD, Win 11', 'Desktop', 35000.00, 18, 3, 'QR_DSK002', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(17, 'DSK003', 'Lenovo ThinkCentre M70s', 'i5-10400, 8GB RAM, 512GB SSD, Win 11 Pro', 'Desktop', 48000.00, 12, 3, 'QR_DSK003', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(18, 'DSK004', 'Asus ExpertCenter D500MA', 'i3-10100, 4GB RAM, 1TB HDD', 'Desktop', 32000.00, 19, 5, 'QR_DSK004', '2026-01-10 13:58:17', '2026-01-11 05:44:48'),
(19, 'DSK005', 'Acer Veriton Essential', 'i5-10400, 8GB RAM, 512GB SSD', 'Desktop', 45000.00, 13, 3, 'QR_DSK005', '2026-01-10 13:58:17', '2026-01-11 05:40:07'),
(20, 'DSK006', 'Custom Gaming PC Ryzen 5', 'Ryzen 5 5600, RTX 3060, 16GB, 512GB SSD', 'Desktop', 85000.00, 0, 2, 'QR_DSK006', '2026-01-10 13:58:17', '2026-01-11 13:32:14'),
(21, 'DSK007', 'Custom Gaming PC i7', 'i7-12700, RTX 3070, 16GB, 1TB SSD', 'Desktop', 125000.00, 5, 2, 'QR_DSK007', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(22, 'DSK008', 'HP All-in-One 24', '23.8\" FHD, i5-1135G7, 8GB, 512GB SSD', 'Desktop', 58000.00, 10, 3, 'QR_DSK008', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(23, 'MOU001', 'Logitech M185 Wireless', '2.4GHz wireless, 1000 DPI, Battery life 12 months', 'Mouse', 500.00, 100, 20, 'QR_MOU001', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(24, 'MOU002', 'HP X3000 Wireless', 'Blue LED, 1200 DPI, Ambidextrous', 'Mouse', 450.00, 80, 20, 'QR_MOU002', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(25, 'MOU003', 'Dell MS116 Wired', 'Optical, 1000 DPI, USB', 'Mouse', 250.00, 120, 30, 'QR_MOU003', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(26, 'MOU004', 'Logitech M331 Silent', 'Wireless, Silent clicks, 1000 DPI', 'Mouse', 850.00, 60, 15, 'QR_MOU004', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(27, 'MOU005', 'Razer DeathAdder V2', 'Gaming, 20000 DPI, RGB, 8 buttons', 'Mouse', 3500.00, 34, 10, 'QR_MOU005', '2026-01-10 13:58:17', '2026-01-10 14:31:27'),
(28, 'MOU006', 'Logitech MX Master 3', 'Wireless, Rechargeable, 4000 DPI, Ergonomic', 'Mouse', 8500.00, 25, 5, 'QR_MOU006', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(29, 'MOU007', 'Microsoft Basic Optical', 'Wired, 800 DPI, Ambidextrous', 'Mouse', 350.00, 90, 20, 'QR_MOU007', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(30, 'MOU008', 'Corsair Harpoon RGB', 'Gaming, 12000 DPI, RGB, 6 buttons', 'Mouse', 2200.00, 40, 10, 'QR_MOU008', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(31, 'MOU009', 'HP Z3700 Wireless', 'Slim design, 1600 DPI, Multiple colors', 'Mouse', 750.00, 55, 15, 'QR_MOU009', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(32, 'MOU010', 'Logitech G502 Hero', 'Gaming, 25600 DPI, 11 buttons, RGB', 'Mouse', 4500.00, 30, 8, 'QR_MOU010', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(33, 'KEY001', 'Logitech K120 Wired', 'Standard layout, Spill-resistant, USB', 'Keyboard', 600.00, 80, 20, 'QR_KEY001', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(34, 'KEY002', 'HP K300 Wired', 'Slim design, Quiet keys, USB', 'Keyboard', 550.00, 75, 20, 'QR_KEY002', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(35, 'KEY003', 'Dell KB216 Wired', 'Full-size, Multimedia keys, USB', 'Keyboard', 650.00, 50, 15, 'QR_KEY003', '2026-01-10 13:58:17', '2026-01-12 05:18:44'),
(36, 'KEY004', 'Logitech K230 Wireless', '2.4GHz, Compact, Battery life 24 months', 'Keyboard', 1200.00, 50, 15, 'QR_KEY004', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(37, 'KEY005', 'Razer BlackWidow V3', 'Mechanical, Green switches, RGB, Wired', 'Keyboard', 8500.00, 25, 5, 'QR_KEY005', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(38, 'KEY006', 'Corsair K55 RGB', 'Gaming, Membrane, RGB, Programmable', 'Keyboard', 3500.00, 33, 10, 'QR_KEY006', '2026-01-10 13:58:17', '2026-01-13 10:59:57'),
(39, 'KEY007', 'Logitech MK270 Combo', 'Wireless keyboard + mouse combo', 'Keyboard', 1800.00, 45, 12, 'QR_KEY007', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(40, 'KEY008', 'HyperX Alloy FPS Pro', 'Mechanical, Cherry MX Red, Compact, RGB', 'Keyboard', 6500.00, 20, 5, 'QR_KEY008', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(41, 'KEY009', 'Microsoft Wired 600', 'Standard, Quiet keys, Spill-resistant', 'Keyboard', 700.00, 65, 15, 'QR_KEY009', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(42, 'KEY010', 'Zebronics Zeb-Transformer', 'Gaming, RGB, Membrane, Wired', 'Keyboard', 1500.00, 55, 15, 'QR_KEY010', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(43, 'PRT001', 'HP DeskJet 2331', 'Color inkjet, Print/Scan/Copy, USB', 'Printer', 3500.00, 20, 5, 'QR_PRT001', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(44, 'PRT002', 'Canon PIXMA E477', 'Color inkjet, WiFi, Print/Scan/Copy', 'Printer', 5500.00, 15, 5, 'QR_PRT002', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(45, 'PRT003', 'Epson EcoTank L3150', 'Color inkjet, WiFi, Tank printer', 'Printer', 14500.00, 12, 3, 'QR_PRT003', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(46, 'PRT004', 'HP LaserJet Pro M126nw', 'Monochrome laser, Print/Scan/Copy, Network', 'Printer', 15000.00, 10, 3, 'QR_PRT004', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(47, 'PRT005', 'Brother DCP-T420W', 'Color inkjet, WiFi, Tank printer', 'Printer', 13500.00, 14, 3, 'QR_PRT005', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(48, 'PRT006', 'Canon imageCLASS LBP2900B', 'Monochrome laser, Print only, USB', 'Printer', 6500.00, 4, 5, 'QR_PRT006', '2026-01-10 13:58:17', '2026-01-15 10:18:56'),
(49, 'PRT007', 'Epson L805', 'Color photo printer, WiFi, 6-color', 'Printer', 20000.00, 1, 2, 'QR_PRT007', '2026-01-10 13:58:17', '2026-01-10 14:33:06'),
(50, 'PRT008', 'HP Smart Tank 529', 'Color inkjet, WiFi, Print/Scan/Copy, Tank', 'Printer', 16500.00, 10, 3, 'QR_PRT008', '2026-01-10 13:58:17', '2026-01-12 05:17:16'),
(51, 'PEN001', 'SanDisk Cruzer Blade 16GB', 'USB 2.0, Black', 'Pendrive', 250.00, 200, 40, 'QR_PEN001', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(52, 'PEN002', 'SanDisk Cruzer Blade 32GB', 'USB 2.0, Black', 'Pendrive', 400.00, 180, 40, 'QR_PEN002', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(53, 'PEN003', 'SanDisk Ultra 64GB', 'USB 3.0, Up to 130MB/s', 'Pendrive', 750.00, 150, 30, 'QR_PEN003', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(54, 'PEN004', 'HP V236W 16GB', 'USB 2.0, Metal body', 'Pendrive', 280.00, 160, 35, 'QR_PEN004', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(55, 'PEN005', 'HP V236W 32GB', 'USB 2.0, Metal body', 'Pendrive', 450.00, 140, 30, 'QR_PEN005', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(56, 'PEN006', 'Kingston DataTraveler 64GB', 'USB 3.0, Up to 100MB/s', 'Pendrive', 800.00, 120, 25, 'QR_PEN006', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(57, 'PEN007', 'SanDisk Ultra Dual 32GB', 'USB-C + USB-A, USB 3.1', 'Pendrive', 650.00, 100, 20, 'QR_PEN007', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(58, 'PEN008', 'SanDisk Ultra Dual 64GB', 'USB-C + USB-A, USB 3.1', 'Pendrive', 950.00, 85, 20, 'QR_PEN008', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(59, 'PEN009', 'Transcend JetFlash 128GB', 'USB 3.1, Up to 90MB/s', 'Pendrive', 1400.00, 70, 15, 'QR_PEN009', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(60, 'PEN010', 'SanDisk Extreme Pro 128GB', 'USB 3.2, Up to 420MB/s', 'Pendrive', 2800.00, 35, 10, 'QR_PEN010', '2026-01-10 13:58:17', '2026-01-15 10:18:56'),
(61, 'PEN011', 'Kingston DataTraveler 16GB', 'USB 3.0, Capless design', 'Pendrive', 300.00, 175, 35, 'QR_PEN011', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(62, 'PEN012', 'HP X750W 32GB', 'USB 3.0, Metal, Up to 120MB/s', 'Pendrive', 550.00, 110, 25, 'QR_PEN012', '2026-01-10 13:58:17', '2026-01-10 13:58:17'),
(63, 'prod00100', 'Dell charger', 'universal charger', 'Electronics', 2500.00, 100, 10, '', '2026-01-12 05:20:02', '2026-01-12 05:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','upi') DEFAULT 'cash',
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `cashier_id`, `total_amount`, `discount`, `tax`, `final_amount`, `payment_method`, `sale_date`) VALUES
(1, 'INV202601108482', 2, 6900.00, 0.00, 0.00, 6900.00, 'cash', '2026-01-10 13:43:35'),
(2, 'INV202601107704', 2, 100000.00, 0.00, 0.00, 100000.00, 'cash', '2026-01-10 13:44:01'),
(3, 'INV202601101555', 2, 5000.00, 0.00, 0.00, 5000.00, 'cash', '2026-01-10 13:48:18'),
(4, 'INV202601107059', 2, 7000.00, 200.00, 0.00, 6800.00, 'cash', '2026-01-10 13:52:49'),
(5, 'INV202601109752', 2, 2000000.00, 0.00, 0.00, 2000000.00, 'cash', '2026-01-10 13:58:58'),
(6, 'INV202601102460', 2, 3600.00, 0.00, 0.00, 3600.00, 'cash', '2026-01-10 14:31:27'),
(7, 'INV202601105249', 2, 1080000.00, 98.77, 0.00, 1079901.23, 'cash', '2026-01-10 14:31:53'),
(8, 'INV202601104217', 2, 140000.00, 0.00, 0.00, 140000.00, 'cash', '2026-01-10 14:33:06'),
(9, 'INV202601115244', 2, 45000.00, 139.80, 0.00, 44860.20, 'cash', '2026-01-11 05:40:07'),
(10, 'INV202601118101', 2, 32000.00, 0.00, 0.00, 32000.00, 'cash', '2026-01-11 05:44:48'),
(11, 'INV202601114111', 2, 680000.00, 200.00, 0.00, 679800.00, 'card', '2026-01-11 13:32:14'),
(12, 'INV202601123725', 2, 16500.00, 0.00, 0.00, 16500.00, 'cash', '2026-01-12 05:17:16'),
(13, 'INV202601126166', 2, 13000.00, 0.00, 0.00, 13000.00, 'card', '2026-01-12 05:18:44'),
(14, 'INV202601124341', 2, 45000.00, 0.00, 0.00, 45000.00, 'cash', '2026-01-12 05:22:48'),
(15, 'INV202601136593', 2, 7000.00, 100.00, 0.00, 6900.00, 'card', '2026-01-13 10:59:57'),
(16, 'INV202601151087', 2, 133000.00, 3000.00, 0.00, 130000.00, 'card', '2026-01-15 10:18:56');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 'acer mouse', 69, 100.00, 6900.00),
(2, 2, 2, 'acer123', 2, 50000.00, 100000.00),
(3, 3, 1, 'acer mouse', 50, 100.00, 5000.00),
(4, 4, 1, 'acer mouse', 70, 100.00, 7000.00),
(5, 5, 2, 'acer123', 40, 50000.00, 2000000.00),
(6, 6, 1, 'acer mouse', 1, 100.00, 100.00),
(7, 6, 27, 'Razer DeathAdder V2', 1, 3500.00, 3500.00),
(8, 7, 5, 'Dell Inspiron 15 3000', 24, 45000.00, 1080000.00),
(9, 8, 49, 'Epson L805', 7, 20000.00, 140000.00),
(10, 9, 19, 'Acer Veriton Essential', 1, 45000.00, 45000.00),
(11, 10, 18, 'Asus ExpertCenter D500MA', 1, 32000.00, 32000.00),
(12, 11, 20, 'Custom Gaming PC Ryzen 5', 8, 85000.00, 680000.00),
(13, 12, 50, 'HP Smart Tank 529', 1, 16500.00, 16500.00),
(14, 13, 35, 'Dell KB216 Wired', 20, 650.00, 13000.00),
(15, 14, 5, 'Dell Inspiron 15 3000', 1, 45000.00, 45000.00),
(16, 15, 38, 'Corsair K55 RGB', 2, 3500.00, 7000.00),
(17, 16, 60, 'SanDisk Extreme Pro 128GB', 15, 2800.00, 42000.00),
(18, 16, 48, 'Canon imageCLASS LBP2900B', 14, 6500.00, 91000.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `action_type` enum('add','sale','adjustment') NOT NULL,
  `user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `product_id`, `quantity_before`, `quantity_change`, `quantity_after`, `action_type`, `user_id`, `notes`, `created_at`) VALUES
(1, 1, 50, -69, -19, 'sale', 2, 'Sale - Invoice #INV202601108482', '2026-01-10 13:43:35'),
(2, 2, 60, -2, 58, 'sale', 2, 'Sale - Invoice #INV202601107704', '2026-01-10 13:44:01'),
(3, 1, 50, -50, 0, 'sale', 2, 'Sale - Invoice #INV202601101555', '2026-01-10 13:48:18'),
(4, 1, 0, 89, 89, 'add', 1, 'low stock', '2026-01-10 13:49:12'),
(5, 1, 89, 90, 179, 'add', 1, 'low stock', '2026-01-10 13:51:55'),
(6, 1, 179, -70, 109, 'sale', 2, 'Sale - Invoice #INV202601107059', '2026-01-10 13:52:49'),
(7, 2, 58, -40, 18, 'sale', 2, 'Sale - Invoice #INV202601109752', '2026-01-10 13:58:58'),
(8, 1, 109, -1, 108, 'sale', 2, 'Sale - Invoice #INV202601102460', '2026-01-10 14:31:27'),
(9, 27, 35, -1, 34, 'sale', 2, 'Sale - Invoice #INV202601102460', '2026-01-10 14:31:27'),
(10, 5, 25, -24, 1, 'sale', 2, 'Sale - Invoice #INV202601105249', '2026-01-10 14:31:53'),
(11, 49, 8, -7, 1, 'sale', 2, 'Sale - Invoice #INV202601104217', '2026-01-10 14:33:06'),
(12, 19, 14, -1, 13, 'sale', 2, 'Sale - Invoice #INV202601115244', '2026-01-11 05:40:07'),
(13, 18, 20, -1, 19, 'sale', 2, 'Sale - Invoice #INV202601118101', '2026-01-11 05:44:48'),
(14, 20, 8, -8, 0, 'sale', 2, 'Sale - Invoice #INV202601114111', '2026-01-11 13:32:14'),
(15, 50, 11, -1, 10, 'sale', 2, 'Sale - Invoice #INV202601123725', '2026-01-12 05:17:16'),
(16, 35, 70, -20, 50, 'sale', 2, 'Sale - Invoice #INV202601126166', '2026-01-12 05:18:44'),
(17, 5, 1, -1, 0, 'sale', 2, 'Sale - Invoice #INV202601124341', '2026-01-12 05:22:48'),
(18, 1, 108, 100, 208, 'add', 1, 'restocking', '2026-01-12 05:23:19'),
(19, 38, 35, -2, 33, 'sale', 2, 'Sale - Invoice #INV202601136593', '2026-01-13 10:59:57'),
(20, 60, 50, -15, 35, 'sale', 2, 'Sale - Invoice #INV202601151087', '2026-01-15 10:18:56'),
(21, 48, 18, -14, 4, 'sale', 2, 'Sale - Invoice #INV202601151087', '2026-01-15 10:18:56'),
(22, 5, 0, 200, 200, 'add', 1, 'low stock', '2026-01-15 10:20:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', '2026-01-10 11:06:48'),
(2, 'cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 'Cashier User', '2026-01-10 11:06:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
