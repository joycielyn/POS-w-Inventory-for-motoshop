-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 04:35 AM
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
-- Database: `pos_inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_category`
--

CREATE TABLE `tbl_category` (
  `catid` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `vat_percent` int(10) NOT NULL,
  `vat` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbl_category`
--

INSERT INTO `tbl_category` (`catid`, `category`, `vat_percent`, `vat`) VALUES
(1, 'parts', 0, NULL),
(2, 'accessories', 0, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice`
--

CREATE TABLE `tbl_invoice` (
  `invoice_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `due` decimal(10,2) DEFAULT NULL,
  `paid` decimal(10,2) DEFAULT NULL,
  `vat_percent` decimal(5,2) DEFAULT 12.00,
  `vat_amount` decimal(10,2) DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbl_invoice`
--

INSERT INTO `tbl_invoice` (`invoice_id`, `order_date`, `subtotal`, `discount`, `total`, `payment_type`, `due`, `paid`, `vat_percent`, `vat_amount`, `vat`) VALUES
(10, '2026-03-04 00:00:00', 2500.00, 10.00, 2250.00, 'Cash', -750.00, 3000.00, 12.00, 0.00, 0.00),
(11, '2026-03-06 00:00:00', 5000.00, 10.00, 4500.00, 'Cash', -500.00, 5000.00, 12.00, 0.00, 0.00),
(12, '2026-03-06 00:00:00', 40.00, 12.00, 35.20, 'Cash', -14.80, 50.00, 12.00, 0.00, 0.00),
(13, '2026-03-06 00:00:00', 10000.00, 10.00, 9000.00, 'Cash', -1000.00, 10000.00, 12.00, 0.00, 0.00),
(14, '2026-03-07 00:00:00', 40.00, 0.00, 48.00, 'Cash', -2.00, 50.00, 12.00, 0.00, 8.00),
(15, '2026-03-07 00:00:00', 2500.00, 0.00, 2500.00, 'Cash', -500.00, 3000.00, 12.00, 0.00, 576.92),
(16, '2026-03-11 00:00:00', 600.00, 0.00, 600.00, 'Cash', 0.00, 600.00, 12.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice_details`
--

CREATE TABLE `tbl_invoice_details` (
  `invoice_details_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `saleprice` decimal(10,2) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbl_invoice_details`
--

INSERT INTO `tbl_invoice_details` (`invoice_details_id`, `invoice_id`, `barcode`, `product_id`, `product_name`, `qty`, `rate`, `saleprice`, `order_date`) VALUES
(13, 10, '4800417003764', 4, 'side mirror', 1, 2500.00, 2500.00, '2026-03-04 00:00:00'),
(14, 11, '4800417003764', 4, 'side mirror', 2, 2500.00, 5000.00, '2026-03-06 00:00:00'),
(15, 12, '5062715', 5, 'Break Pad', 1, 40.00, 40.00, '2026-03-06 00:00:00'),
(16, 13, '4800417003764', 4, 'side mirror', 4, 2500.00, 10000.00, '2026-03-06 00:00:00'),
(17, 14, '5062715', 5, 'Break Pad', 1, 40.00, 40.00, '2026-03-07 00:00:00'),
(18, 15, '4800417003764', 4, 'side mirror', 1, 2500.00, 2500.00, '2026-03-07 00:00:00'),
(19, 16, '567890796', 6, 'Sprockets', 2, 300.00, 600.00, '2026-03-11 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product`
--

CREATE TABLE `tbl_product` (
  `pid` int(11) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `product` varchar(255) NOT NULL,
  `product_unit` varchar(20) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `purchaseprice` decimal(10,2) DEFAULT NULL,
  `saleprice` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbl_product`
--

INSERT INTO `tbl_product` (`pid`, `barcode`, `product`, `product_unit`, `category`, `description`, `stock`, `purchaseprice`, `saleprice`, `image`, `is_archived`, `archived_at`) VALUES
(4, '4800417003764', 'side mirror', 'set', 'parts', 'smooth', 7, 1500.00, 2500.00, '6995715eca786.jpg', 0, NULL),
(5, '5062715', 'Break Pad', 'pcs', 'parts', 'dfsdf', 8, 25.00, 40.00, '69aa65b3321b5.png', 0, NULL),
(6, '567890796', 'Sprockets', 'pcs', 'parts', 'dfghjdklsx', 8, 250.00, 300.00, '69b155693a93b.png', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_supplier`
--

CREATE TABLE `tbl_supplier` (
  `supid` int(11) NOT NULL,
  `supplier` varchar(255) NOT NULL,
  `contact` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_taxdis`
--

CREATE TABLE `tbl_taxdis` (
  `taxdis_id` int(11) NOT NULL,
  `discount` decimal(5,2) DEFAULT NULL,
  `tax` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbl_taxdis`
--

INSERT INTO `tbl_taxdis` (`taxdis_id`, `discount`, `tax`) VALUES
(3, 10.00, 4.00),
(4, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `userid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `useremail` varchar(255) NOT NULL,
  `userpassword` varchar(255) NOT NULL,
  `useraddress` text DEFAULT NULL,
  `userage` int(11) DEFAULT NULL,
  `usercontact` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `userimage` varchar(200) NOT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_logout_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`userid`, `username`, `useremail`, `userpassword`, `useraddress`, `userage`, `usercontact`, `role`, `userimage`, `last_login_at`, `last_logout_at`) VALUES
(4, 'joy jimenez', 'jjimenez@gmail.com', 'joy123', 'Sitio Cabitaugan Cawag Subic Zambales', 20, '09701806956', 'Admin', '1773201428_Screenshot_2025-08-18_203207.png', '2026-03-11 12:23:10', '2026-03-11 12:10:02'),
(5, 'rona', 'rmsicat@gmail.com', 'mamamo', 'pilar', 21, '09276567890', 'User', '1772271635_Screenshot 2025-09-20 095547.png', '2026-03-11 13:14:38', '2026-03-11 13:14:24'),
(7, 'Bart Javillonar', 'bartjavillonar@gmail.com', 'maverick79', 'Calapacuan Subic Zambales', 46, '09304871699', 'Admin', '1772895506_Screenshot 2025-08-22 190103.png', NULL, '2026-03-11 11:55:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_category`
--
ALTER TABLE `tbl_category`
  ADD PRIMARY KEY (`catid`);

--
-- Indexes for table `tbl_invoice`
--
ALTER TABLE `tbl_invoice`
  ADD PRIMARY KEY (`invoice_id`);

--
-- Indexes for table `tbl_invoice_details`
--
ALTER TABLE `tbl_invoice_details`
  ADD PRIMARY KEY (`invoice_details_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_product`
--
ALTER TABLE `tbl_product`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `tbl_supplier`
--
ALTER TABLE `tbl_supplier`
  ADD PRIMARY KEY (`supid`);

--
-- Indexes for table `tbl_taxdis`
--
ALTER TABLE `tbl_taxdis`
  ADD PRIMARY KEY (`taxdis_id`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `useremail` (`useremail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_category`
--
ALTER TABLE `tbl_category`
  MODIFY `catid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_invoice`
--
ALTER TABLE `tbl_invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_invoice_details`
--
ALTER TABLE `tbl_invoice_details`
  MODIFY `invoice_details_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `tbl_product`
--
ALTER TABLE `tbl_product`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_supplier`
--
ALTER TABLE `tbl_supplier`
  MODIFY `supid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_taxdis`
--
ALTER TABLE `tbl_taxdis`
  MODIFY `taxdis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_invoice_details`
--
ALTER TABLE `tbl_invoice_details`
  ADD CONSTRAINT `tbl_invoice_details_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_invoice` (`invoice_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_invoice_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`pid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
