-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2025 at 05:29 PM
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
(8, 'CSS ADMIN', 'Important Announcement We are excited to announce the launch of our new website! 🎉 Explore our latest products and services now!', '2025-03-04 15:11:20', 'admin'),
(9, ' CSS ADMIN', 'Important Announcement We are excited to announce the launch of our new website! 🎉 Explore our latest products and services now!', '2025-03-04 15:11:29', 'admin'),
(10, 'CCS ADMIN', 'Important Announcement We are excited to announce the launch of our new website! 🎉 Explore our latest products and services now!', '2025-03-04 15:11:41', 'admin');

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
(3, '524', 1, 'in-use', '2025-03-04 12:58:57'),
(4, '526', 2, 'in-use', '2025-03-04 12:58:59'),
(5, '528', 3, 'in-use', '2025-03-04 12:59:01'),
(6, '542', 4, 'in-use', '2025-03-04 12:59:03'),
(7, '524', 9, 'in-use', '2025-03-04 13:08:54'),
(8, '524', 16, 'in-use', '2025-03-04 13:08:55'),
(9, '524', 20, 'in-use', '2025-03-04 13:08:56'),
(10, '526', 33, 'in-use', '2025-03-04 13:09:01'),
(11, '526', 26, 'in-use', '2025-03-04 13:09:02'),
(12, '526', 30, 'in-use', '2025-03-04 13:09:02'),
(13, '528', 11, 'in-use', '2025-03-04 13:09:05'),
(14, '528', 23, 'in-use', '2025-03-04 13:09:05'),
(15, '528', 26, 'in-use', '2025-03-04 13:09:06'),
(16, '528', 38, 'in-use', '2025-03-04 13:09:08'),
(17, '528', 30, 'in-use', '2025-03-04 13:09:09'),
(18, '530', 15, 'in-use', '2025-03-04 13:09:11'),
(19, '530', 18, 'in-use', '2025-03-04 13:09:11'),
(20, '530', 16, 'in-use', '2025-03-04 13:09:12'),
(21, '530', 33, 'in-use', '2025-03-04 13:09:13'),
(22, '530', 31, 'in-use', '2025-03-04 13:09:13'),
(23, '542', 25, 'in-use', '2025-03-04 13:09:15'),
(24, '542', 21, 'in-use', '2025-03-04 13:09:16'),
(25, '542', 27, 'in-use', '2025-03-04 13:09:16'),
(26, '542', 34, 'in-use', '2025-03-04 13:09:17'),
(27, '542', 36, 'in-use', '2025-03-04 13:09:17'),
(28, '526', 10, 'in-use', '2025-03-04 14:28:57'),
(29, '530', 1, 'in-use', '2025-03-04 14:48:05'),
(30, '524', 2, 'in-use', '2025-03-04 15:04:33'),
(31, '526', 12, 'in-use', '2025-03-04 15:35:14'),
(32, '524', 3, 'in-use', '2025-03-04 15:46:46'),
(33, '524', 7, 'in-use', '2025-03-04 16:10:00'),
(34, '530', 2, 'in-use', '2025-03-04 16:12:03'),
(35, '526', 1, 'in-use', '2025-03-04 16:24:03');

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
('2025-03-04', 4);

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

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `idno`, `fullname`, `purpose`, `laboratory`, `pc_number`, `time_in`, `time_out`, `date`, `created_at`, `status`) VALUES
(1, '20952503', 'Cabunilas, Vince Bryant N.', 'java_programming', '524', 0, '18:55:00', NULL, '2025-03-17', '2025-03-03 08:55:16', 'approved'),
(2, '20952503', 'Cabunilas, Vince Bryant N.', 'php', '524', 2, '15:00:00', NULL, '2025-03-05', '2025-03-04 13:40:46', 'rejected'),
(3, '20952503', 'Cabunilas, Vince Bryant N.', 'csharp', '526', 10, '10:00:00', NULL, '2025-03-06', '2025-03-04 14:22:11', 'approved'),
(4, '20952503', 'Cabunilas, Vince Bryant N.', 'aspnet', '530', 1, '15:00:00', NULL, '2025-03-15', '2025-03-04 14:47:43', 'approved'),
(5, '20983134', 'Tormis, Francine', 'c_programming', '524', 2, '16:04:00', '23:05:49', '2025-03-04', '2025-03-04 15:04:06', 'completed'),
(6, '20983134', 'Tormis, Francine', 'php', '526', 12, '15:00:00', '23:37:17', '2025-03-04', '2025-03-04 15:34:49', 'completed'),
(7, '20983134', 'Tormis, Francine', 'c_programming', '524', 3, '08:49:00', '23:48:40', '2025-03-04', '2025-03-04 15:46:19', 'completed'),
(8, '20983134', 'Tormis, Francine', 'aspnet', '524', 7, '13:08:00', '00:10:37', '2025-03-05', '2025-03-04 16:08:55', 'completed'),
(9, '20010012', 'Tudtud, Daphne', 'csharp', '530', 2, '16:11:00', NULL, '2025-03-05', '2025-03-04 16:11:42', 'approved'),
(10, '20952503', 'Cabunilas, Vince Bryant N.', 'c_programming', '526', 1, '15:22:00', NULL, '2025-03-05', '2025-03-04 16:22:46', 'approved');

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
(2, '20952503', 'Cabunilas', 'Vince Bryant', 'N.', 'BS-Computer Science', 3, 'Vince', '$2y$10$twPu7cUcwWDXkPE3SlQR6.NLRybSZvvfEo2nBG6mGXx9NqxaXmmR6', 'vincebryant42@gmail.com', 'Cebu, City', 30, '', '../uploads/profile_67c6ec0d2164c.png', '2025-02-13 04:14:38'),
(3, '20934721', 'Escoton', 'Julius', '', 'BS-Information Techn', 2, 'Joboy', '$2y$10$Ebzkn9jgH7A70vxKyJyyjuSkRgyHc49YsRl0AQaZinQCDmMnnWfLW', NULL, NULL, 30, '', 'uploads/profile_67ad7301f18d0.jpg', '2025-02-13 04:20:03'),
(4, '20983134', 'Tormis', 'Francine', '', 'SJH', 4, 'pransin_noob', '$2y$10$hkZsRiyxNGlIGIbjTVNfVO6be.T1LStWe1qlOgvVWxasbTLHqvREm', 'pransin@gmail.com', 'Digos noob', 30, '', '../uploads/profile_67c6e9a9e2535.jpg', '2025-02-13 14:08:20'),
(5, '20010012', 'Tudtud', 'Daphne', '', 'BS-Computer Science', 1, 'Sashimi', '$2y$10$Jad4spx3QyWBnw2WaeICReNb1ERgN9xC2qIDPV7ZtgVeQZkt7iDnW', 'sashimi@gmail.com', 'Tisa noob', 30, '', '../uploads/profile_67c7261dbfbda.jpg', '2025-02-14 02:44:15'),
(6, '20914241', 'McArthur', 'Newbie', '', 'College of Education', 4, 'Newbie', '$2y$10$TZyAGn9J4LT1hQRFkhMaCueHcRkgqu2nD6K0y9pK5peHVqasjS1VG', '', '', 30, '', 'uploads/profile_67b602449f3d5.jpg', '2025-02-19 16:09:03'),
(7, '20019922', 'Stalin', 'Joseph', 'R.', 'College of Criminal ', 4, 'MotherRussia', '$2y$10$2ilyiu9/P94FeowL12mia.jQAv/BGDaeSvGihD6XGMzOON6zYtWc2', NULL, NULL, 30, '', NULL, '2025-03-03 10:01:52'),
(8, '11111111', 'Putin', 'Vladimir', '', 'BS-Computer Science', 4, 'Vodka', '$2y$10$unQMQb8AwVxwBnl92N1eU.WVpKCGimKEPothWhQWkP/BrFFshQqVy', '', '', 30, '', 'uploads/profile_67c65840c9f6c.png', '2025-03-04 01:12:13'),
(9, '20873192', 'userx', 'userp', '', 'CCJ', 1, 'Littlenoob', '$2y$10$/SRO5uuRCEXe0SbQ0xLfbOdZRNT5BNcmfz9Rx5pYiS0Cg9ZXnWIdu', 'noob@gmail.com', 'newbville', 30, '', NULL, '2025-03-04 11:57:23');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `computer_status`
--
ALTER TABLE `computer_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
