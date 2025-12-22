-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 10:02 PM
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
-- Database: `unihelper`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('role-applicant','role-undergrad','role-profile','role-admin') DEFAULT 'role-applicant',
  `al_year` year(4) DEFAULT NULL,
  `university` int(11) DEFAULT NULL,
  `major` int(11) DEFAULT NULL,
  `profile_role` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `public` tinyint(1) NOT NULL DEFAULT 1,
  `moderator` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `al_year`, `university`, `major`, `profile_role`, `profile_picture`, `created_at`, `public`, `moderator`) VALUES
(18, 'Ayanna', 'J', 'a@a.a', '0761234567', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-applicant', '2023', NULL, NULL, NULL, '', '2025-10-19 04:03:44', 1, 0),
(26, 'Bhasu', 'Jayaweera', 'b@a.b', '0761234567', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-undergrad', NULL, 1, 1, NULL, NULL, '2025-10-19 06:39:52', 1, 0),
(27, 'c', 'c', 'c@a.c', '1212121212', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-profile', NULL, 2, NULL, 'lecturer', NULL, '2025-10-21 21:28:49', 1, 0),
(28, 'Admin', 'User', 'admin@uh.test', '0000000000', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-admin', NULL, NULL, NULL, NULL, NULL, '2025-10-22 02:32:59', 1, 0),
(29, 'good', 'bruh', 'bruh@bruh.com', '1231231231', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-undergrad', NULL, 1, 1, NULL, NULL, '2025-10-22 09:53:59', 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `major` (`major`),
  ADD KEY `university` (`university`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_major` FOREIGN KEY (`major`) REFERENCES `majors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_university` FOREIGN KEY (`university`) REFERENCES `universities` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
