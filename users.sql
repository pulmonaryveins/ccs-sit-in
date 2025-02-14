-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2025 at 05:53 AM
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
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `lastname`, `firstname`, `middlename`, `course`, `year`, `username`, `password`, `email`, `address`, `profile_image`, `created_at`) VALUES
(1, '20183201', 'Monreal', 'Jeff', '', 'BS-Information Techn', 3, 'Jeffrey', '$2y$10$lBOdbK0MqdTvgzVKMUnSEOdzCo7Ai8fmIr8IyE76OJ8pe5coHy8J.', NULL, NULL, 'uploads/profile_67ad71220ee12.jpg', '2025-02-13 04:10:38'),
(2, '20952503', 'Cabunilas', 'Vince Bryant', 'N.', 'BS-Information Techn', 0, 'Vince', '$2y$10$twPu7cUcwWDXkPE3SlQR6.NLRybSZvvfEo2nBG6mGXx9NqxaXmmR6', 'vincebryant42@gmail.com', '100 Tres de Abril Street. CC.', 'uploads/profile_67ad71b655739.jpg', '2025-02-13 04:14:38'),
(3, '20934721', 'Escoton', 'Julius', '', 'BS-Information Techn', 2, 'Joboy', '$2y$10$Ebzkn9jgH7A70vxKyJyyjuSkRgyHc49YsRl0AQaZinQCDmMnnWfLW', NULL, NULL, 'uploads/profile_67ad7301f18d0.jpg', '2025-02-13 04:20:03');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
