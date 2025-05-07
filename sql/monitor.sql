-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2025 at 05:13 PM
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
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `title`, `content`, `is_read`, `related_id`, `related_type`, `created_at`) VALUES
(1, 'New Reservation Request', 'Cabunilas, Vince Bryant N. requested to use PC #1 in Laboratory 524 on May 8, 2025 at 12:00 PM', 1, 24, 'reservation', '2025-05-07 11:36:02'),
(2, 'New Reservation Request', 'Escoton, Julius requested to use PC #1 in Laboratory 517 on May 9, 2025 at 1:00 PM', 1, 25, 'reservation', '2025-05-07 12:33:23'),
(3, 'New Reservation Request', 'Tormis, Francine requested to use PC #16 in Laboratory 517 on May 8, 2025 at 12:00 PM', 1, 26, 'reservation', '2025-05-07 12:47:07'),
(4, 'New Reservation Request', 'Cabunilas, Vince Bryant N. requested to use PC #1 in Laboratory 517 on May 8, 2025 at 3:00 PM', 0, 27, 'reservation', '2025-05-07 14:38:24'),
(5, 'New Reservation Request', 'Cabunilas, Vince Bryant N. requested to use PC #2 in Laboratory 524 on May 8, 2025 at 1:00 PM', 0, 28, 'reservation', '2025-05-07 14:40:31'),
(6, 'New Reservation Request', 'Monreal, Jeff requested to use PC #13 in Laboratory 524 on May 8, 2025 at 4:00 PM', 0, 29, 'reservation', '2025-05-07 14:46:02');

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
(18, 'CCS ADMIN', 'Free robux', '2025-05-07 14:16:40', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `computers`
--

CREATE TABLE `computers` (
  `id` int(11) NOT NULL,
  `laboratory` varchar(50) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'available',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, '524', 1, 'available', '2025-05-07 14:36:35'),
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
(30, '524', 2, 'available', '2025-05-07 14:37:38'),
(31, '526', 12, 'available', '2025-03-09 09:44:45'),
(32, '524', 3, 'available', '2025-04-27 07:13:44'),
(33, '524', 7, 'available', '2025-05-07 14:37:38'),
(34, '530', 2, 'available', '2025-04-27 07:14:12'),
(35, '526', 1, 'available', '2025-04-27 07:13:59'),
(36, '530', 10, 'available', '2025-04-27 07:14:11'),
(71, '524', 13, 'available', '2025-05-07 15:07:28'),
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
(106, '526', 8, 'available', '2025-05-04 13:44:34'),
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
(123, '542', 1, 'available', '2025-05-04 13:40:05'),
(124, '542', 6, 'available', '2025-04-27 07:14:22'),
(125, '542', 10, 'available', '2025-04-27 07:14:19'),
(126, '542', 9, 'available', '2025-04-27 07:14:19'),
(127, '542', 7, 'available', '2025-04-27 07:14:20'),
(128, '528', 1, 'available', '2025-05-04 13:50:46'),
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
(161, '517', 1, 'available', '2025-05-07 14:42:55'),
(169, '517', 2, 'available', '2025-05-04 13:26:26'),
(247, '517', 3, 'available', '2025-04-27 08:48:15'),
(249, '517', 4, 'available', '2025-04-27 08:48:14'),
(262, '542', 8, 'available', '2025-05-07 14:37:48'),
(269, '526', 13, 'available', '2025-05-04 13:27:38'),
(281, '517', 16, 'available', '2025-05-07 14:36:42');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Available','In-Class') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_schedules`
--

INSERT INTO `lab_schedules` (`id`, `day`, `laboratory`, `time_start`, `time_end`, `subject`, `professor`, `created_at`, `updated_at`) VALUES
(9, 'Monday', 'Laboratory 517', '07:30:00', '10:30:00', 'Systems-Architecture', 'Mr. Jeff Salimbangon', '2025-05-07 14:50:01', '2025-05-07 14:58:32'),
(10, 'Monday', 'Laboratory 517', '10:30:00', '12:30:00', 'Information-Security', 'Mr. Huebert Ferolino', '2025-05-07 14:52:04', '2025-05-07 14:54:59'),
(11, 'Monday', 'Laboratory 517', '12:30:00', '14:30:00', 'Python', 'Mr. Dennis Durano', '2025-05-07 14:54:04', '2025-05-07 14:55:32'),
(12, 'Monday', 'Laboratory 517', '14:30:00', '16:30:00', 'Integrated Programming', 'Mr. Willson Gayo', '2025-05-07 14:54:47', '2025-05-07 14:55:40'),
(13, 'Monday', 'Laboratory 517', '16:30:00', '18:30:00', '.Net', 'Mr. Leo Bermudez', '2025-05-07 14:57:09', '2025-05-07 14:57:09'),
(14, 'Monday', 'Laboratory 517', '18:30:00', '20:00:00', 'OOP', 'Mr. Carlsan Kim', '2025-05-07 14:58:02', '2025-05-07 14:58:10'),
(15, 'Monday', 'Laboratory 517', '20:00:00', '21:30:00', 'Quality Assurance Testing', 'Mr. Noel Anthony Mirambel', '2025-05-07 15:01:26', '2025-05-07 15:01:26'),
(16, 'Tuesday', 'Laboratory 517', '07:30:00', '10:30:00', 'Systems-Architecture', 'Mr. Jeff Salimbangon', '2025-05-07 06:50:01', '2025-05-07 06:58:32'),
(17, 'Tuesday', 'Laboratory 517', '10:30:00', '12:30:00', 'Information-Security', 'Mr. Huebert Ferolino', '2025-05-07 06:52:04', '2025-05-07 06:54:59'),
(18, 'Tuesday', 'Laboratory 517', '12:30:00', '14:30:00', 'Python', 'Mr. Dennis Durano', '2025-05-07 06:54:04', '2025-05-07 06:55:32'),
(19, 'Tuesday', 'Laboratory 517', '14:30:00', '16:30:00', 'Integrated Programming', 'Mr. Willson Gayo', '2025-05-07 06:54:47', '2025-05-07 06:55:40'),
(20, 'Tuesday', 'Laboratory 517', '16:30:00', '18:30:00', '.Net', 'Mr. Leo Bermudez', '2025-05-07 06:57:09', '2025-05-07 06:57:09'),
(21, 'Tuesday', 'Laboratory 517', '18:30:00', '20:00:00', 'OOP', 'Mr. Carlsan Kim', '2025-05-07 06:58:02', '2025-05-07 06:58:10'),
(22, 'Tuesday', 'Laboratory 517', '20:00:00', '21:30:00', 'Quality Assurance Testing', 'Mr. Noel Anthony Mirambel', '2025-05-07 07:01:26', '2025-05-07 07:01:26'),
(23, 'Monday', 'Laboratory 524', '07:30:00', '10:30:00', 'Systems-Architecture', 'Mr. Jeff Salimbangon', '2025-05-07 06:50:01', '2025-05-07 06:58:32'),
(24, 'Monday', 'Laboratory 524', '10:30:00', '12:30:00', 'Information-Security', 'Mr. Huebert Ferolino', '2025-05-07 06:52:04', '2025-05-07 06:54:59'),
(25, 'Monday', 'Laboratory 524', '12:30:00', '14:30:00', 'Python', 'Mr. Dennis Durano', '2025-05-07 06:54:04', '2025-05-07 06:55:32'),
(26, 'Monday', 'Laboratory 524', '14:30:00', '16:30:00', 'Integrated Programming', 'Mr. Willson Gayo', '2025-05-07 06:54:47', '2025-05-07 06:55:40'),
(27, 'Monday', 'Laboratory 524', '16:30:00', '18:30:00', '.Net', 'Mr. Leo Bermudez', '2025-05-07 06:57:09', '2025-05-07 06:57:09'),
(28, 'Monday', 'Laboratory 524', '18:30:00', '20:00:00', 'OOP', 'Mr. Carlsan Kim', '2025-05-07 06:58:02', '2025-05-07 06:58:10'),
(29, 'Monday', 'Laboratory 524', '20:00:00', '21:30:00', 'Quality Assurance Testing', 'Mr. Noel Anthony Mirambel', '2025-05-07 07:01:26', '2025-05-07 07:01:26'),
(30, 'Monday', 'Laboratory 526', '07:30:00', '10:30:00', 'Systems-Architecture', 'Mr. Jeff Salimbangon', '2025-05-07 06:50:01', '2025-05-07 06:58:32'),
(31, 'Monday', 'Laboratory 526', '10:30:00', '12:30:00', 'Information-Security', 'Mr. Huebert Ferolino', '2025-05-07 06:52:04', '2025-05-07 06:54:59'),
(32, 'Monday', 'Laboratory 526', '12:30:00', '14:30:00', 'Python', 'Mr. Dennis Durano', '2025-05-07 06:54:04', '2025-05-07 06:55:32'),
(33, 'Monday', 'Laboratory 526', '14:30:00', '16:30:00', 'Integrated Programming', 'Mr. Willson Gayo', '2025-05-07 06:54:47', '2025-05-07 06:55:40'),
(34, 'Monday', 'Laboratory 526', '16:30:00', '18:30:00', '.Net', 'Mr. Leo Bermudez', '2025-05-07 06:57:09', '2025-05-07 06:57:09'),
(35, 'Monday', 'Laboratory 526', '18:30:00', '20:00:00', 'OOP', 'Mr. Carlsan Kim', '2025-05-07 06:58:02', '2025-05-07 06:58:10'),
(36, 'Monday', 'Laboratory 526', '20:00:00', '21:30:00', 'Quality Assurance Testing', 'Mr. Noel Anthony Mirambel', '2025-05-07 07:01:26', '2025-05-07 07:01:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `username`, `title`, `content`, `is_read`, `created_at`) VALUES
(1, 'Jeffrey', 'Reservation Approved', 'Your reservation for Laboratory 524, PC 13 on May 8, 2025 at 4:00 PM has been approved.', 1, '2025-05-07 14:46:20');

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

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `idno`, `fullname`, `purpose`, `laboratory`, `pc_number`, `time_in`, `time_out`, `date`, `created_at`, `updated_at`, `status`) VALUES
(16, '20952503', 'Cabunilas, Vince Bryant N.', 'C Programming', '524', 2, '10:30:00', '21:11:49', '2025-05-05', '2025-05-04 13:11:22', '2025-05-04 13:11:40', 'completed'),
(17, '20952503', 'Cabunilas, Vince Bryant N.', 'Java Programming', '517', 2, '10:00:00', '21:26:25', '2025-05-05', '2025-05-04 13:26:09', '2025-05-04 13:26:18', 'completed'),
(18, '20934721', 'Escoton, Julius', 'PHP', '526', 13, '12:00:00', '21:27:38', '2025-05-05', '2025-05-04 13:27:22', '2025-05-04 13:27:28', 'completed'),
(19, '20934721', 'Escoton, Julius', 'Java Programming', '542', 1, '12:00:00', '21:40:05', '2025-05-05', '2025-05-04 13:29:05', '2025-05-04 13:38:56', 'completed'),
(20, '20934721', 'Escoton, Julius', 'C#', '517', 1, '12:00:00', NULL, '2025-05-05', '2025-05-04 13:41:35', '2025-05-04 13:41:45', 'rejected'),
(21, '20934721', 'Escoton, Julius', 'Java Programming', '524', 1, '12:00:00', '21:42:42', '2025-05-05', '2025-05-04 13:42:24', '2025-05-04 13:42:29', 'completed'),
(22, '20934721', 'Escoton, Julius', 'C#', '526', 8, '13:00:00', '21:44:34', '2025-05-05', '2025-05-04 13:44:18', '2025-05-04 13:44:28', 'completed'),
(23, '20934721', 'Escoton, Julius', 'C#', '524', 7, '12:30:00', NULL, '2025-05-05', '2025-05-04 13:55:06', '2025-05-04 14:04:27', 'approved'),
(24, '20952503', 'Cabunilas, Vince Bryant N.', 'C Programming', '524', 1, '12:00:00', '22:36:35', '2025-05-08', '2025-05-07 11:36:02', '2025-05-07 13:17:45', 'completed'),
(25, '20934721', 'Escoton, Julius', 'Java Programming', '517', 1, '13:00:00', '22:36:47', '2025-05-09', '2025-05-07 12:33:23', '2025-05-07 14:33:20', 'completed'),
(26, '20983134', 'Tormis, Francine', 'Java Programming', '517', 16, '12:00:00', '22:36:42', '2025-05-08', '2025-05-07 12:47:07', '2025-05-07 14:09:22', 'completed'),
(27, '20952503', 'Cabunilas, Vince Bryant N.', 'Java Programming', '517', 1, '15:00:00', '22:42:55', '2025-05-08', '2025-05-07 14:38:24', '2025-05-07 14:38:40', 'completed'),
(28, '20952503', 'Cabunilas, Vince Bryant N.', 'PHP', '524', 2, '13:00:00', NULL, '2025-05-08', '2025-05-07 14:40:31', '2025-05-07 14:41:44', 'rejected'),
(29, '20183201', 'Monreal, Jeff', 'Java Programming', '524', 13, '16:00:00', '23:07:28', '2025-05-08', '2025-05-07 14:46:02', '2025-05-07 14:46:20', 'completed');

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

--
-- Dumping data for table `sit_ins`
--

INSERT INTO `sit_ins` (`id`, `idno`, `fullname`, `purpose`, `laboratory`, `pc_number`, `time_in`, `time_out`, `date`, `created_at`, `status`) VALUES
(70, '20952503', '', 'Information Security', '524', 1, '21:39:24', '21:39:31', '2025-05-04', '2025-05-04 13:39:24', 'completed'),
(71, '20952503', '', 'System Analysis and Design', '542', 1, '21:43:30', '21:43:43', '2025-05-04', '2025-05-04 13:43:30', 'completed'),
(72, '20952503', '', 'System Analysis and Design', '528', 1, '21:50:41', '21:50:46', '2025-05-04', '2025-05-04 13:50:41', 'completed');

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
(1, '20183201', 'Monreal', 'Jeff', '', 'BS-Information Techn', 3, 'Jeffrey', '$2y$10$lBOdbK0MqdTvgzVKMUnSEOdzCo7Ai8fmIr8IyE76OJ8pe5coHy8J.', NULL, NULL, 29, 0, '', 'uploads/profile_67ad71220ee12.jpg', '2025-02-13 04:10:38'),
(2, '20952503', 'Cabunilas', 'Vince Bryant', 'N.', 'BS-Information Techn', 3, 'vince', '$2y$10$twPu7cUcwWDXkPE3SlQR6.NLRybSZvvfEo2nBG6mGXx9NqxaXmmR6', 'vincebryant42@gmail.com', 'Cebu, City', 15, 0, '', '../uploads/profile_67c6ec0d2164c.png', '2025-02-13 04:14:38'),
(3, '20934721', 'Escoton', 'Julius', '', 'BS-Information Techn', 2, 'Joboy', '$2y$10$Ebzkn9jgH7A70vxKyJyyjuSkRgyHc49YsRl0AQaZinQCDmMnnWfLW', NULL, NULL, 25, 0, '', 'uploads/profile_67ad7301f18d0.jpg', '2025-02-13 04:20:03'),
(4, '20983134', 'Tormis', 'Francine', '', 'CHM', 3, 'pransin_noob', '$2y$10$hkZsRiyxNGlIGIbjTVNfVO6be.T1LStWe1qlOgvVWxasbTLHqvREm', 'pransin@gmail.com', 'Digos noob', 29, 0, '', '../uploads/profile_680269559cabe.png', '2025-02-13 14:08:20'),
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
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `related_id_type_index` (`related_id`,`related_type`),
  ADD KEY `is_read_index` (`is_read`);

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
-- Indexes for table `computers`
--
ALTER TABLE `computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_pc` (`laboratory`,`pc_number`);

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
  ADD KEY `username` (`username`);

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
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `computers`
--
ALTER TABLE `computers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `computer_status`
--
ALTER TABLE `computer_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=292;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sit_ins`
--
ALTER TABLE `sit_ins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

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

-- Add status column to lab_schedules table if it doesn't exist
ALTER TABLE `lab_schedules` 
ADD COLUMN `status` enum('Available','In-Class') NOT NULL DEFAULT 'Available' 
AFTER `professor`;

-- Update existing records to have 'Available' status
UPDATE `lab_schedules` SET `status` = 'Available' WHERE `status` IS NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
