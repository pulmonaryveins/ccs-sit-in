-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2025 at 03:39 AM
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
(10, 'CCS ADMIN', 'Important Announcement We are excited to announce the launch of our new website! 🎉 Explore our latest products and services now!', '2025-03-04 15:11:41', 'admin'),
(11, 'CSS ADMIN', 'CAT CAT CAT MIMING', '2025-03-05 14:32:16', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `computer_status`
--

CREATE TABLE `computer_status` (
  `id` int(11) NOT NULL,
  `laboratory` varchar(10) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `computer_status`
--

INSERT INTO `computer_status` (`id`, `laboratory`, `pc_number`, `status`, `last_updated`) VALUES
(1, '524', 15, 'available', '2025-03-04 12:58:51'),
(3, '524', 1, 'in-use', '2025-03-09 13:19:36'),
(4, '526', 2, 'in-use', '2025-03-09 13:19:43'),
(5, '528', 3, 'in-use', '2025-03-09 13:22:20'),
(6, '542', 4, 'in-use', '2025-03-09 13:19:55'),
(7, '524', 9, 'in-use', '2025-03-09 13:19:39'),
(8, '524', 16, 'available', '2025-03-09 09:44:38'),
(9, '524', 20, 'available', '2025-03-09 09:44:37'),
(10, '526', 33, 'available', '2025-03-09 09:44:47'),
(11, '526', 26, 'available', '2025-03-09 09:44:46'),
(12, '526', 30, 'available', '2025-03-09 09:44:47'),
(13, '528', 11, 'in-use', '2025-03-09 13:22:23'),
(14, '528', 23, 'available', '2025-03-09 09:44:52'),
(15, '528', 26, 'available', '2025-03-09 09:44:52'),
(16, '528', 38, 'available', '2025-03-09 09:44:53'),
(17, '528', 30, 'available', '2025-03-09 09:44:53'),
(18, '530', 15, 'available', '2025-03-09 09:44:56'),
(19, '530', 18, 'available', '2025-03-09 09:44:57'),
(20, '530', 16, 'available', '2025-03-09 09:44:57'),
(21, '530', 33, 'available', '2025-03-09 09:44:58'),
(22, '530', 31, 'available', '2025-03-09 09:44:59'),
(23, '542', 25, 'available', '2025-03-09 09:45:04'),
(24, '542', 21, 'available', '2025-03-09 09:45:04'),
(25, '542', 27, 'available', '2025-03-09 09:45:04'),
(26, '542', 34, 'available', '2025-03-09 09:45:05'),
(27, '542', 36, 'available', '2025-03-09 09:45:06'),
(28, '526', 10, 'in-use', '2025-03-09 13:19:45'),
(29, '530', 1, 'in-use', '2025-03-09 13:19:48'),
(30, '524', 2, 'in-use', '2025-03-09 13:19:36'),
(31, '526', 12, 'available', '2025-03-09 09:44:45'),
(32, '524', 3, 'in-use', '2025-03-09 13:19:36'),
(33, '524', 7, 'in-use', '2025-03-09 13:19:38'),
(34, '530', 2, 'in-use', '2025-03-09 13:19:50'),
(35, '526', 1, 'in-use', '2025-03-09 13:19:43'),
(36, '530', 10, 'in-use', '2025-03-09 13:19:49'),
(71, '524', 13, 'available', '2025-03-09 11:09:50'),
(72, '524', 11, 'in-use', '2025-03-09 16:01:46'),
(73, '528', 28, 'available', '2025-03-09 11:21:37'),
(75, '530', 8, 'in-use', '2025-03-09 13:19:52'),
(77, '542', 5, 'in-use', '2025-03-09 13:19:54'),
(83, '524', 40, 'available', '2025-03-09 13:19:34'),
(84, '524', 39, 'available', '2025-03-09 13:19:34'),
(90, '524', 4, 'in-use', '2025-03-09 13:19:37'),
(93, '524', 5, 'in-use', '2025-03-09 13:19:38'),
(94, '524', 6, 'in-use', '2025-03-09 13:19:38'),
(96, '524', 8, 'in-use', '2025-03-09 13:19:39'),
(98, '524', 10, 'in-use', '2025-03-09 13:19:39'),
(101, '526', 3, 'in-use', '2025-03-09 13:19:44'),
(102, '526', 4, 'in-use', '2025-03-09 13:19:44'),
(103, '526', 5, 'in-use', '2025-03-09 13:19:44'),
(105, '526', 9, 'in-use', '2025-03-09 13:19:45'),
(106, '526', 8, 'in-use', '2025-03-09 13:19:45'),
(107, '526', 6, 'in-use', '2025-03-09 13:19:46'),
(108, '526', 7, 'in-use', '2025-03-09 13:19:46'),
(112, '530', 3, 'in-use', '2025-03-09 13:19:50'),
(113, '530', 5, 'in-use', '2025-03-09 13:19:51'),
(114, '530', 4, 'in-use', '2025-03-09 13:19:51'),
(115, '530', 9, 'in-use', '2025-03-09 13:19:52'),
(117, '530', 7, 'in-use', '2025-03-09 13:19:52'),
(118, '530', 6, 'in-use', '2025-03-09 13:19:52'),
(121, '542', 3, 'in-use', '2025-03-09 13:19:55'),
(122, '542', 2, 'in-use', '2025-03-09 13:19:56'),
(123, '542', 1, 'in-use', '2025-03-09 13:19:56'),
(124, '542', 6, 'in-use', '2025-03-09 13:19:56'),
(125, '542', 10, 'in-use', '2025-03-09 13:19:57'),
(126, '542', 9, 'in-use', '2025-03-09 13:19:57'),
(127, '542', 7, 'in-use', '2025-03-09 13:19:58'),
(128, '528', 1, 'in-use', '2025-03-09 13:22:19'),
(129, '528', 2, 'in-use', '2025-03-09 13:22:20'),
(131, '528', 4, 'in-use', '2025-03-09 13:22:20'),
(132, '528', 5, 'in-use', '2025-03-09 13:22:21'),
(133, '528', 15, 'in-use', '2025-03-09 13:22:21'),
(134, '528', 14, 'in-use', '2025-03-09 13:22:21'),
(135, '528', 13, 'in-use', '2025-03-09 13:22:22'),
(136, '528', 12, 'in-use', '2025-03-09 13:22:22'),
(138, '524', 25, 'in-use', '2025-03-09 14:41:14'),
(139, '528', 7, 'in-use', '2025-03-13 09:27:20'),
(140, '530', 12, 'in-use', '2025-03-13 09:28:34');

-- --------------------------------------------------------

--
-- Table structure for table `current_sessions`
--

CREATE TABLE `current_sessions` (
  `date` date NOT NULL,
  `count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `current_sessions`
--

INSERT INTO `current_sessions` (`date`, `count`) VALUES
('2025-03-04', 4),
('2025-03-05', 0),
('2025-03-09', 13),
('2025-03-13', 2);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `sit_in_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ;

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
  `pc_number` int(11) NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sit_ins`
--

CREATE TABLE `sit_ins` (
  `id` int(11) NOT NULL,
  `idno` varchar(20) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `purpose` varchar(50) NOT NULL,
  `laboratory` varchar(10) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, '20952503', 'Cabunilas', 'Vince Bryant', 'N.', 'BS-Information Techn', 3, 'Vince', '$2y$10$twPu7cUcwWDXkPE3SlQR6.NLRybSZvvfEo2nBG6mGXx9NqxaXmmR6', 'vincebryant42@gmail.com', 'Cebu, City', 30, '', '../uploads/profile_67c6ec0d2164c.png', '2025-02-13 04:14:38'),
(3, '20934721', 'Escoton', 'Julius', '', 'BS-Information Techn', 2, 'Joboy', '$2y$10$Ebzkn9jgH7A70vxKyJyyjuSkRgyHc49YsRl0AQaZinQCDmMnnWfLW', NULL, NULL, 30, '', 'uploads/profile_67ad7301f18d0.jpg', '2025-02-13 04:20:03'),
(4, '20983134', 'Tormis', 'Francine', '', 'SJH', 4, 'pransin_noob', '$2y$10$hkZsRiyxNGlIGIbjTVNfVO6be.T1LStWe1qlOgvVWxasbTLHqvREm', 'pransin@gmail.com', 'Digos noob', 30, '', '../uploads/profile_67c6e9a9e2535.jpg', '2025-02-13 14:08:20'),
(5, '20010012', 'Tudtud', 'Daphne', '', 'BS-Computer Science', 1, 'Sashimi', '$2y$10$Jad4spx3QyWBnw2WaeICReNb1ERgN9xC2qIDPV7ZtgVeQZkt7iDnW', 'sashimi@gmail.com', 'Tisa noob', 30, '', '../uploads/profile_67c7261dbfbda.jpg', '2025-02-14 02:44:15'),
(6, '20914241', 'McArthur', 'Newbie', '', 'College of Education', 4, 'Newbie', '$2y$10$TZyAGn9J4LT1hQRFkhMaCueHcRkgqu2nD6K0y9pK5peHVqasjS1VG', '', '', 30, '', 'uploads/profile_67b602449f3d5.jpg', '2025-02-19 16:09:03'),
(7, '20019922', 'Stalin', 'Joseph', 'R.', 'College of Criminal ', 4, 'MotherRussia', '$2y$10$2ilyiu9/P94FeowL12mia.jQAv/BGDaeSvGihD6XGMzOON6zYtWc2', NULL, NULL, 30, '', NULL, '2025-03-03 10:01:52'),
(8, '11111111', 'Putin', 'Vladimir', '', 'BS-Computer Science', 4, 'Vodka', '$2y$10$unQMQb8AwVxwBnl92N1eU.WVpKCGimKEPothWhQWkP/BrFFshQqVy', '', '', 30, '', 'uploads/profile_67c65840c9f6c.png', '2025-03-04 01:12:13'),
(9, '20873192', 'userx', 'userp', '', 'CCJ', 1, 'Littlenoob', '$2y$10$/SRO5uuRCEXe0SbQ0xLfbOdZRNT5BNcmfz9Rx5pYiS0Cg9ZXnWIdu', 'noob@gmail.com', 'newbville', 30, '', NULL, '2025-03-04 11:57:23'),
(10, '29892812', 'Musk', 'Elon', '', 'BS-Computer Science', 2, 'tesla', '$2y$10$mPVy4vWCndHRim5pLLgWz.qIi/DUfIrlaalhvSDKlR9gqQNUtZK7i', NULL, NULL, 30, '', NULL, '2025-03-09 08:59:45');

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
-- Indexes for table `computer_status`
--
ALTER TABLE `computer_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_pc` (`laboratory`,`pc_number`);

--
-- Indexes for table `current_sessions`
--
ALTER TABLE `current_sessions`
  ADD PRIMARY KEY (`date`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `sit_in_id` (`sit_in_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sit_ins`
--
ALTER TABLE `sit_ins`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `computer_status`
--
ALTER TABLE `computer_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sit_ins`
--
ALTER TABLE `sit_ins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`sit_in_id`) REFERENCES `sit_ins` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
