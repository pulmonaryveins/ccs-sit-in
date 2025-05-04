-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2025 at 08:43 AM
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
(14, 'CCS ADMIN', 'Nice!', '2025-04-02 16:51:25', 'admin');

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
(3, '524', 1, 'in-use', '2025-04-27 07:39:15'),
(4, '526', 2, 'available', '2025-04-27 07:13:59'),
(5, '528', 3, 'available', '2025-04-27 07:14:03'),
(6, '542', 4, 'available', '2025-04-27 07:14:19'),
(7, '524', 9, 'available', '2025-04-27 07:13:45'),
(8, '524', 16, 'available', '2025-03-09 09:44:38'),
(9, '524', 20, 'available', '2025-03-09 09:44:37'),
(10, '526', 33, 'available', '2025-03-09 09:44:47'),
(11, '526', 26, 'available', '2025-03-09 09:44:46'),
(12, '526', 30, 'available', '2025-03-09 09:44:47'),
(13, '528', 11, 'available', '2025-04-27 07:14:05'),
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
(28, '526', 10, 'available', '2025-04-27 07:13:57'),
(29, '530', 1, 'available', '2025-04-27 07:14:12'),
(30, '524', 2, 'available', '2025-04-27 07:13:43'),
(31, '526', 12, 'available', '2025-03-09 09:44:45'),
(32, '524', 3, 'available', '2025-04-27 07:13:44'),
(33, '524', 7, 'available', '2025-04-27 07:13:44'),
(34, '530', 2, 'available', '2025-04-27 07:14:12'),
(35, '526', 1, 'available', '2025-04-27 07:13:59'),
(36, '530', 10, 'available', '2025-04-27 07:14:11'),
(71, '524', 13, 'available', '2025-03-09 11:09:50'),
(72, '524', 11, 'available', '2025-04-12 13:30:44'),
(73, '528', 28, 'available', '2025-03-09 11:21:37'),
(75, '530', 8, 'available', '2025-04-27 07:14:10'),
(77, '542', 5, 'available', '2025-04-27 07:14:18'),
(83, '524', 40, 'available', '2025-04-27 07:13:52'),
(84, '524', 39, 'available', '2025-04-27 07:13:52'),
(90, '524', 4, 'available', '2025-04-27 07:13:45'),
(93, '524', 5, 'available', '2025-04-27 07:13:45'),
(94, '524', 6, 'available', '2025-04-27 07:13:44'),
(96, '524', 8, 'available', '2025-04-27 07:13:45'),
(98, '524', 10, 'available', '2025-04-27 07:13:46'),
(101, '526', 3, 'available', '2025-04-27 07:13:58'),
(102, '526', 4, 'available', '2025-04-27 07:13:58'),
(103, '526', 5, 'available', '2025-04-27 07:13:57'),
(105, '526', 9, 'available', '2025-04-27 07:13:58'),
(106, '526', 8, 'available', '2025-04-27 07:13:58'),
(107, '526', 6, 'available', '2025-04-27 07:14:00'),
(108, '526', 7, 'available', '2025-04-27 07:13:59'),
(112, '530', 3, 'available', '2025-04-27 07:14:12'),
(113, '530', 5, 'available', '2025-04-27 07:14:13'),
(114, '530', 4, 'available', '2025-04-27 07:14:12'),
(115, '530', 9, 'available', '2025-04-27 07:14:10'),
(117, '530', 7, 'available', '2025-04-27 07:14:10'),
(118, '530', 6, 'available', '2025-04-27 07:14:10'),
(121, '542', 3, 'available', '2025-04-27 07:14:20'),
(122, '542', 2, 'available', '2025-04-27 07:14:20'),
(123, '542', 1, 'available', '2025-04-27 07:14:21'),
(124, '542', 6, 'available', '2025-04-27 07:14:22'),
(125, '542', 10, 'available', '2025-04-27 07:14:19'),
(126, '542', 9, 'available', '2025-04-27 07:14:19'),
(127, '542', 7, 'available', '2025-04-27 07:14:20'),
(128, '528', 1, 'in-use', '2025-04-27 09:03:00'),
(129, '528', 2, 'available', '2025-04-27 07:14:03'),
(131, '528', 4, 'available', '2025-04-27 07:14:03'),
(132, '528', 5, 'available', '2025-04-27 07:14:04'),
(133, '528', 15, 'available', '2025-04-27 07:14:04'),
(134, '528', 14, 'available', '2025-04-27 07:14:04'),
(135, '528', 13, 'available', '2025-04-27 07:14:05'),
(136, '528', 12, 'available', '2025-04-27 07:14:05'),
(138, '524', 25, 'available', '2025-04-27 07:13:48'),
(139, '528', 7, 'available', '2025-04-27 07:14:03'),
(140, '530', 12, 'available', '2025-04-27 07:14:11'),
(141, '524', 12, 'available', '2025-04-12 13:30:44'),
(149, '524', 38, 'available', '2025-04-27 07:13:52'),
(150, '524', 37, 'available', '2025-04-27 07:13:51'),
(151, '524', 36, 'available', '2025-04-27 07:13:51'),
(152, '524', 35, 'available', '2025-04-27 07:13:49'),
(153, '524', 34, 'available', '2025-04-27 07:13:50'),
(154, '524', 32, 'available', '2025-04-27 07:13:51'),
(155, '524', 33, 'available', '2025-04-27 07:13:50'),
(156, '524', 31, 'available', '2025-04-27 07:13:51'),
(157, '524', 23, 'available', '2025-04-27 07:13:47'),
(158, '524', 18, 'available', '2025-04-27 07:13:47'),
(159, '524', 21, 'available', '2025-04-27 07:13:47'),
(161, '517', 1, 'in-use', '2025-04-27 08:58:54'),
(169, '517', 2, 'available', '2025-04-27 08:48:15'),
(247, '517', 3, 'available', '2025-04-27 08:48:15'),
(249, '517', 4, 'available', '2025-04-27 08:48:14'),
(262, '542', 8, 'in-use', '2025-04-27 09:03:07');

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
('2025-03-13', 2),
('2025-03-23', 0),
('2025-04-12', 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_computers`
--

CREATE TABLE `lab_computers` (
  `id` int(11) NOT NULL,
  `laboratory` varchar(10) NOT NULL,
  `pc_number` varchar(10) NOT NULL,
  `status` enum('available','in-use') DEFAULT 'available',
  `student_idno` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_computers`
--

INSERT INTO `lab_computers` (`id`, `laboratory`, `pc_number`, `status`, `student_idno`, `created_at`, `updated_at`) VALUES
(1, '517', '4', 'available', NULL, '2025-04-27 08:46:32', '2025-04-27 08:46:32'),
(2, '517', '1', 'available', NULL, '2025-04-27 08:49:31', '2025-04-27 08:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `lab_schedules`
--

CREATE TABLE `lab_schedules` (
  `id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `laboratory` varchar(50) NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `subject` varchar(100) NOT NULL,
  `professor` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_schedules`
--

INSERT INTO `lab_schedules` (`id`, `day`, `laboratory`, `time_start`, `time_end`, `subject`, `professor`, `created_at`, `updated_at`) VALUES
(3, 'Tuesday', 'Laboratory 517', '07:30:00', '12:30:00', 'dwada', 'dawdawd', '2025-04-27 10:37:27', '2025-04-27 10:37:27'),
(4, 'Monday', 'Laboratory 524', '08:30:00', '12:30:00', 'dwadaw', 'dawdwda', '2025-04-27 10:37:51', '2025-04-27 10:37:51'),
(8, 'Saturday', 'Laboratory 517', '07:30:00', '12:30:00', 'dawd', 'wadadawdaw', '2025-04-27 13:38:50', '2025-04-27 13:38:50');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `announcement_id`, `is_read`, `created_at`) VALUES
(1, 13, 14, 0, '2025-04-02 16:51:26'),
(2, 14, 14, 0, '2025-04-02 16:51:26'),
(3, 1, 14, 0, '2025-04-02 16:51:26'),
(4, 3, 14, 0, '2025-04-02 16:51:26'),
(5, 16, 14, 0, '2025-04-02 16:51:26'),
(6, 15, 14, 0, '2025-04-02 16:51:26'),
(7, 12, 14, 0, '2025-04-02 16:51:26'),
(8, 7, 14, 0, '2025-04-02 16:51:26'),
(9, 6, 14, 0, '2025-04-02 16:51:26'),
(10, 4, 14, 0, '2025-04-02 16:51:26'),
(11, 5, 14, 0, '2025-04-02 16:51:26'),
(12, 10, 14, 0, '2025-04-02 16:51:26'),
(13, 11, 14, 0, '2025-04-02 16:51:26'),
(14, 2, 14, 1, '2025-04-02 16:51:26'),
(15, 8, 14, 0, '2025-04-02 16:51:26'),
(16, 17, 14, 0, '2025-04-02 16:51:26');

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
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `name`, `description`, `image`, `link`, `created_at`, `updated_at`) VALUES
(7, 'TEAB B', 'dwad', 'assets/images/resources/1746001755_800x600_Wallpaper_Blue_Sky.png', 'https://docs.google.com/forms/d/e/1FAIpQLSdrOO4QMDuyXZoFn0Ysh-r-9zxvZrB-HOO2LegMfNA-EtjAFA/formResponse', '2025-04-30 08:29:15', NULL);

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
  `points` int(11) NOT NULL,
  `role` varchar(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `lastname`, `firstname`, `middlename`, `course`, `year`, `username`, `password`, `email`, `address`, `remaining_sessions`, `points`, `role`, `profile_image`, `created_at`) VALUES
(1, '20183201', 'Monreal', 'Jeff', '', 'BS-Information Techn', 3, 'Jeffrey', '$2y$10$lBOdbK0MqdTvgzVKMUnSEOdzCo7Ai8fmIr8IyE76OJ8pe5coHy8J.', NULL, NULL, 30, 0, '', 'uploads/profile_67ad71220ee12.jpg', '2025-02-13 04:10:38'),
(2, '20952503', 'Cabunilas', 'Vince Bryant', 'N.', 'BS-Information Techn', 3, 'Vince', '$2y$10$twPu7cUcwWDXkPE3SlQR6.NLRybSZvvfEo2nBG6mGXx9NqxaXmmR6', 'vincebryant42@gmail.com', 'Cebu, City', 23, 0, '', '../uploads/profile_67c6ec0d2164c.png', '2025-02-13 04:14:38'),
(3, '20934721', 'Escoton', 'Julius', '', 'BS-Information Techn', 2, 'Joboy', '$2y$10$Ebzkn9jgH7A70vxKyJyyjuSkRgyHc49YsRl0AQaZinQCDmMnnWfLW', NULL, NULL, 30, 0, '', 'uploads/profile_67ad7301f18d0.jpg', '2025-02-13 04:20:03'),
(4, '20983134', 'Tormis', 'Francine', '', 'CHM', 3, 'pransin_noob', '$2y$10$hkZsRiyxNGlIGIbjTVNfVO6be.T1LStWe1qlOgvVWxasbTLHqvREm', 'pransin@gmail.com', 'Digos noob', 30, 0, '', '../uploads/profile_680269559cabe.png', '2025-02-13 14:08:20'),
(5, '20010012', 'Tudtud', 'Daphne', '', 'BS-Computer Science', 1, 'Sashimi', '$2y$10$Jad4spx3QyWBnw2WaeICReNb1ERgN9xC2qIDPV7ZtgVeQZkt7iDnW', 'sashimi@gmail.com', 'Tisa noob', 30, 0, '', '../uploads/profile_67c7261dbfbda.jpg', '2025-02-14 02:44:15'),
(6, '20914241', 'McArthur', 'Newbie', '', 'College of Education', 4, 'Newbie', '$2y$10$TZyAGn9J4LT1hQRFkhMaCueHcRkgqu2nD6K0y9pK5peHVqasjS1VG', '', '', 29, 0, '', 'uploads/profile_67b602449f3d5.jpg', '2025-02-19 16:09:03'),
(7, '20019922', 'Stalin', 'Joseph', 'R.', 'College of Criminal ', 4, 'MotherRussia', '$2y$10$2ilyiu9/P94FeowL12mia.jQAv/BGDaeSvGihD6XGMzOON6zYtWc2', NULL, NULL, 30, 0, '', NULL, '2025-03-03 10:01:52'),
(8, '11111111', 'Putin', 'Vladimir', '', 'BS-Computer Science', 4, 'Vodka', '$2y$10$unQMQb8AwVxwBnl92N1eU.WVpKCGimKEPothWhQWkP/BrFFshQqVy', '', '', 30, 0, '', 'uploads/profile_67c65840c9f6c.png', '2025-03-04 01:12:13'),
(10, '29892812', 'Musk', 'Elon', '', 'BS-Computer Science', 2, 'tesla', '$2y$10$mPVy4vWCndHRim5pLLgWz.qIi/DUfIrlaalhvSDKlR9gqQNUtZK7i', NULL, NULL, 29, 0, '', NULL, '2025-03-09 08:59:45'),
(11, '28617809', 'Graham', 'Micro', NULL, 'CCJ', 4, 'tidet', '$2y$10$KFBy5hvEaRYRrkfSux3vGeD3fGaA0yoZAwDzrL53keO.H2HdvUAHu', '', '', 30, 0, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 12:49:15'),
(12, '08726712', 'Turner', 'Jimmy', NULL, 'COE', 3, 'minecraft', '$2y$10$hPeFf6qgU0dImsMz5B856.l6yojAnltVWNCrscyW.Cc9j3XGN0KMS', NULL, NULL, 30, 4, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 13:28:16'),
(13, '92863763', 'Bean', 'Ms', NULL, 'CON', 1, 'beans', '$2y$10$6D/wMUExrTwLja/QHQr3UeaEZxXKSWt1rAetQWB.Em83RJX4zhBS.', NULL, NULL, 30, 0, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 14:00:22'),
(14, '21387321', 'Chill', 'Mc', NULL, 'BS-Computer Science', 2, 'epic', '$2y$10$GcSZYlVRYMy2DiYpfiUGA.KMzbhpcNv5n2Q.5v8ddKHXLQFWKYvS.', NULL, NULL, 30, 0, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 14:26:33'),
(15, '28763712', 'Cat', 'Meow', NULL, 'CAS', 2, 'memo', '$2y$10$nWTI3PEUv4lvVZhY/B0q/eTrqAhkZyRyx4JS55WViI5FWWXcKriry', NULL, NULL, 30, 0, 'student', '../assets/images/logo/AVATAR.png', '2025-03-20 14:36:31'),
(16, '09827897', 'McFlurry', 'Julio', NULL, 'COE', 2, 'Julio', '$2y$10$dSzolT5ls7M9ohCa/VLd.ez8CZEfTJvFyM4Fis1X3jazRWtOKzThq', NULL, NULL, 30, 0, 'student', '../assets/images/logo/AVATAR.png', '2025-03-23 03:34:06'),
(17, '98767229', 'WashingMachine', 'George', NULL, 'CCJ', 3, 'Washing', '$2y$10$ogV4YvE75ZlOm6OKyDmhcu9B4GbMtIdrxBoZatO8q4S0lZuTSeHWa', NULL, NULL, 30, 0, 'student', '../assets/images/logo/AVATAR.png', '2025-03-23 03:39:33'),
(18, '20867836', 'Soft', 'Micro', NULL, 'BS-Computer Science', 3, 'Micro', '$2y$10$UmMG0rYNww8XrS3SSYsJmezqpZdaeQko8Zga.d1SzU6QNRp.Ybefa', NULL, NULL, 30, 0, 'student', '../assets/images/logo/AVATAR.png', '2025-04-02 16:52:55');

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
-- Indexes for table `lab_computers`
--
ALTER TABLE `lab_computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_pc_idx` (`laboratory`,`pc_number`);

--
-- Indexes for table `lab_schedules`
--
ALTER TABLE `lab_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laboratory_day_idx` (`laboratory`,`day`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `computer_status`
--
ALTER TABLE `computer_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `lab_computers`
--
ALTER TABLE `lab_computers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lab_schedules`
--
ALTER TABLE `lab_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sit_ins`
--
ALTER TABLE `sit_ins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`sit_in_id`) REFERENCES `sit_ins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
