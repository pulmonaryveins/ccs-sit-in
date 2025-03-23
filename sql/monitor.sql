-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2025 at 06:03 AM
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
  `remaining_sessions` int(11) NOT NULL DEFAULT 30,
  `role` varchar(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `lastname`, `firstname`, `middlename`, `course`, `year`, `username`, `password`, `email`, `address`, `remaining_sessions`, `role`, `profile_image`, `created_at`) VALUES
(1, '20183201', 'Monreal', 'Jeff', '', 'BS-Information Techn', 3, 'Jeffrey', '$2y$10$lBOdbK0MqdTvgzVKMUnSEOdzCo7Ai8fmIr8IyE76OJ8pe5coHy8J.', NULL, NULL, 30, '', 'uploads/profile_67ad71220ee12.jpg', '2025-02-13 04:10:38'),
(2, '20952503', 'Cabunilas', 'Vince Bryant', 'N.', 'BS-Information Techn', 3, 'Vince', '$2y$10$twPu7cUcwWDXkPE3SlQR6.NLRybSZvvfEo2nBG6mGXx9NqxaXmmR6', 'vincebryant42@gmail.com', 'Cebu, City', 28, '', '../uploads/profile_67c6ec0d2164c.png', '2025-02-13 04:14:38'),
(3, '20934721', 'Escoton', 'Julius', '', 'BS-Information Techn', 2, 'Joboy', '$2y$10$Ebzkn9jgH7A70vxKyJyyjuSkRgyHc49YsRl0AQaZinQCDmMnnWfLW', NULL, NULL, 29, '', 'uploads/profile_67ad7301f18d0.jpg', '2025-02-13 04:20:03'),
(4, '20983134', 'Tormis', 'Francine', '', 'SJH', 4, 'pransin_noob', '$2y$10$hkZsRiyxNGlIGIbjTVNfVO6be.T1LStWe1qlOgvVWxasbTLHqvREm', 'pransin@gmail.com', 'Digos noob', 30, '', '../uploads/profile_67c6e9a9e2535.jpg', '2025-02-13 14:08:20'),
(5, '20010012', 'Tudtud', 'Daphne', '', 'BS-Computer Science', 1, 'Sashimi', '$2y$10$Jad4spx3QyWBnw2WaeICReNb1ERgN9xC2qIDPV7ZtgVeQZkt7iDnW', 'sashimi@gmail.com', 'Tisa noob', 30, '', '../uploads/profile_67c7261dbfbda.jpg', '2025-02-14 02:44:15'),
(6, '20914241', 'McArthur', 'Newbie', '', 'College of Education', 4, 'Newbie', '$2y$10$TZyAGn9J4LT1hQRFkhMaCueHcRkgqu2nD6K0y9pK5peHVqasjS1VG', '', '', 29, '', 'uploads/profile_67b602449f3d5.jpg', '2025-02-19 16:09:03'),
(7, '20019922', 'Stalin', 'Joseph', 'R.', 'College of Criminal ', 4, 'MotherRussia', '$2y$10$2ilyiu9/P94FeowL12mia.jQAv/BGDaeSvGihD6XGMzOON6zYtWc2', NULL, NULL, 30, '', NULL, '2025-03-03 10:01:52'),
(8, '11111111', 'Putin', 'Vladimir', '', 'BS-Computer Science', 4, 'Vodka', '$2y$10$unQMQb8AwVxwBnl92N1eU.WVpKCGimKEPothWhQWkP/BrFFshQqVy', '', '', 30, '', 'uploads/profile_67c65840c9f6c.png', '2025-03-04 01:12:13'),
(10, '29892812', 'Musk', 'Elon', '', 'BS-Computer Science', 2, 'tesla', '$2y$10$mPVy4vWCndHRim5pLLgWz.qIi/DUfIrlaalhvSDKlR9gqQNUtZK7i', NULL, NULL, 29, '', NULL, '2025-03-09 08:59:45'),
(11, '28617809', 'Graham', 'Micro', NULL, 'CCJ', 4, 'tidet', '$2y$10$KFBy5hvEaRYRrkfSux3vGeD3fGaA0yoZAwDzrL53keO.H2HdvUAHu', '', '', 29, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 12:49:15'),
(12, '08726712', 'Turner', 'Jimmy', NULL, 'COE', 3, 'minecraft', '$2y$10$hPeFf6qgU0dImsMz5B856.l6yojAnltVWNCrscyW.Cc9j3XGN0KMS', NULL, NULL, 29, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 13:28:16'),
(13, '92863763', 'Bean', 'Mr', NULL, 'CBA', 3, 'beans', '$2y$10$6D/wMUExrTwLja/QHQr3UeaEZxXKSWt1rAetQWB.Em83RJX4zhBS.', NULL, NULL, 29, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 14:00:22'),
(14, '21387321', 'Chill', 'Mc', NULL, 'BS-Computer Science', 2, 'epic', '$2y$10$GcSZYlVRYMy2DiYpfiUGA.KMzbhpcNv5n2Q.5v8ddKHXLQFWKYvS.', NULL, NULL, 29, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 14:26:33'),
(15, '28763712', 'Cat', 'Meow', NULL, 'CAS', 2, 'memo', '$2y$10$nWTI3PEUv4lvVZhY/B0q/eTrqAhkZyRyx4JS55WViI5FWWXcKriry', NULL, NULL, 29, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 14:36:31'),
(16, '09827897', 'McFlurry', 'Julio', NULL, 'COE', 2, 'Julio', '$2y$10$dSzolT5ls7M9ohCa/VLd.ez8CZEfTJvFyM4Fis1X3jazRWtOKzThq', NULL, NULL, 30, 'student', '../assets/images/logo/AVATAR.png', '2025-03-23 03:34:06'),
(17, '98767229', 'WashingMachine', 'George', NULL, 'CCJ', 3, 'Washing', '$2y$10$ogV4YvE75ZlOm6OKyDmhcu9B4GbMtIdrxBoZatO8q4S0lZuTSeHWa', NULL, NULL, 29, 'student', '../assets/images/logo/AVATAR.png', '2025-03-23 03:39:33');

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
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
