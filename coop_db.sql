-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2026 at 06:28 AM
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
-- Database: `coop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `crop`
--

CREATE TABLE `crop` (
  `crop_id` int(11) NOT NULL,
  `crop_name` varchar(100) NOT NULL,
  `season` varchar(50) DEFAULT NULL,
  `price_per_kg` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crop`
--

-- Sample crop records for project demonstration.
-- Prices are illustrative sample values (₹ per kg), not live market rates.
INSERT INTO `crop` (`crop_id`, `crop_name`, `season`, `price_per_kg`) VALUES
(1, 'Rice', 'Kharif', 28.50),
(2, 'Banana', 'Annual', 35.00),
(3, 'Black Pepper', 'Rabi', 520.00),
(4, 'Coconut', 'Annual', 32.00),
(5, 'Arecanut', 'Annual', 380.00),
(6, 'Tapioca', 'Annual', 22.00),
(7, 'Ginger', 'Rabi', 95.00),
(8, 'Turmeric', 'Kharif', 85.00),
(9, 'Cardamom', 'Annual', 1800.00),
(10, 'Cashew', 'Summer', 140.00),
(11, 'Cocoa', 'Annual', 210.00),
(12, 'Coffee', 'Rabi', 260.00),
(13, 'Tea', 'Annual', 180.00),
(14, 'Maize', 'Kharif', 24.00),
(15, 'Groundnut', 'Kharif', 75.00),
(16, 'Sugarcane', 'Annual', 4.00),
(17, 'Tomato', 'Winter', 30.00),
(18, 'Onion', 'Rabi', 38.00),
(19, 'Okra (Lady Finger)', 'Summer', 42.00),
(20, 'Mango', 'Summer', 65.00);

-- --------------------------------------------------------

--
-- Table structure for table `farmer`
--

CREATE TABLE `farmer` (
  `farmer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `village` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer`
--

INSERT INTO `farmer` (`farmer_id`, `name`, `village`, `phone`) VALUES
(1, 'Ravi Kumar', 'Kakkanad', '9876543210'),
(2, 'Anitha Nair', 'Thrikkakara', '9847012345'),
(3, 'Joseph Mathew', 'Aluva', '9946098765');

-- --------------------------------------------------------

--
-- Table structure for table `procurement`
--

CREATE TABLE `procurement` (
  `proc_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `proc_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procurement`
--

INSERT INTO `procurement` (`proc_id`, `farmer_id`, `crop_id`, `quantity_kg`, `proc_date`) VALUES
(1, 2, 2, 6.00, '2026-11-11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crop`
--
ALTER TABLE `crop`
  ADD PRIMARY KEY (`crop_id`);

--
-- Indexes for table `farmer`
--
ALTER TABLE `farmer`
  ADD PRIMARY KEY (`farmer_id`);

--
-- Indexes for table `procurement`
--
ALTER TABLE `procurement`
  ADD PRIMARY KEY (`proc_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `procurement`
--
ALTER TABLE `procurement`
  MODIFY `proc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `procurement`
--
ALTER TABLE `procurement`
  ADD CONSTRAINT `procurement_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmer` (`farmer_id`),
  ADD CONSTRAINT `procurement_ibfk_2` FOREIGN KEY (`crop_id`) REFERENCES `crop` (`crop_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
