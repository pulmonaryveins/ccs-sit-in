-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2025 at 11:17 AM
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
-- Database: `monitor`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$YourHashedPasswordHere', '2025-03-03 09:43:54');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`, `created_by`) VALUES
(4, 'CCS Admin', 'Lab Opened', '2025-03-04 01:43:45', 'admin'),
(5, 'CCS Admin', 'Lab Maintenance', '2025-03-04 01:44:19', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `idno` varchar(20) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `purpose` varchar(50) NOT NULL,
  `laboratory` varchar(10) NOT NULL,
  `time_in` time NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `idno`, `fullname`, `purpose`, `laboratory`, `time_in`, `date`, `created_at`) VALUES
(1, '20952503', 'Cabunilas, Vince Bryant N.', 'java_programming', '524', '18:55:00', '2025-03-17', '2025-03-03 08:55:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(20) NOT NULL,
  `idno` varchar(20) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `course` varchar(20) NOT NULL,
  `year` int(1) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `lastname`, `firstname`, `middlename`, `course`, `year`, `username`, `password`, `email`, `address`, `role`, `profile_image`, `created_at`) VALUES
(1, '20183201', 'Monreal', 'Jeff', '', 'BS-Information Techn', 3, 'Jeffrey', '$2y$10$lBOdbK0MqdTvgzVKMUnSEOdzCo7Ai8fmIr8IyE76OJ8pe5coHy8J.', NULL, NULL, '', 'uploads/profile_67ad71220ee12.jpg', '2025-02-13 04:10:38'),
(2, '20952503', 'Cabunilas', 'Vince Bryant', 'N.', 'BS-Computer Science', 3, 'Vince', '$2y$10$twPu7cUcwWDXkPE3SlQR6.NLRybSZvvfEo2nBG6mGXx9NqxaXmmR6', 'vincebryant42@gmail.com', 'Cebu, City', '', 'uploads/profile_67bc96b0862f0.png', '2025-02-13 04:14:38'),
(3, '20934721', 'Escoton', 'Julius', '', 'BS-Information Techn', 2, 'Joboy', '$2y$10$Ebzkn9jgH7A70vxKyJyyjuSkRgyHc49YsRl0AQaZinQCDmMnnWfLW', NULL, NULL, '', 'uploads/profile_67ad7301f18d0.jpg', '2025-02-13 04:20:03'),
(4, '20983134', 'Tormis', 'Francine', '', 'SJH', 4, 'pransin_noob', '$2y$10$hkZsRiyxNGlIGIbjTVNfVO6be.T1LStWe1qlOgvVWxasbTLHqvREm', 'pransin@gmail.com', 'Digos noob', '', 'uploads/profile_67adfce5dae8f.jpg', '2025-02-13 14:08:20'),
(5, '20010012', 'Tudtud', 'Daphne', '', 'BS-Computer Science', 1, 'Sashimi', '$2y$10$Jad4spx3QyWBnw2WaeICReNb1ERgN9xC2qIDPV7ZtgVeQZkt7iDnW', 'sashimi@gmail.com', 'Tisa noob', '', 'uploads/profile_67bc9b639ac57.jpg', '2025-02-14 02:44:15'),
(6, '20914241', 'McArthur', 'Newbie', '', 'College of Education', 4, 'Newbie', '$2y$10$TZyAGn9J4LT1hQRFkhMaCueHcRkgqu2nD6K0y9pK5peHVqasjS1VG', '', '', '', 'uploads/profile_67b602449f3d5.jpg', '2025-02-19 16:09:03'),
(7, '20019922', 'Stalin', 'Joseph', 'R.', 'College of Criminal ', 4, 'MotherRussia', '$2y$10$2ilyiu9/P94FeowL12mia.jQAv/BGDaeSvGihD6XGMzOON6zYtWc2', NULL, NULL, '', NULL, '2025-03-03 10:01:52'),
(8, '11111111', 'Putin', 'Vladimir', '', 'BS-Computer Science', 4, 'Vodka', '$2y$10$unQMQb8AwVxwBnl92N1eU.WVpKCGimKEPothWhQWkP/BrFFshQqVy', '', '', '', 'uploads/profile_67c65840c9f6c.png', '2025-03-04 01:12:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
