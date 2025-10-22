-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 07:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `degree_program`
--

CREATE TABLE `degree_program` (
  `program_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `university_id` int(11) DEFAULT NULL,
  `stream` enum('physical-science','biological-science','technology','commerce','arts','other') DEFAULT NULL,
  `unicode_code` varchar(20) DEFAULT NULL,
  `major_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `majors`
--

CREATE TABLE `majors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `majors`
--

INSERT INTO `majors` (`id`, `name`) VALUES
(1, 'Computer Science'),
(2, 'Engineering'),
(3, 'Medicine'),
(4, 'Law'),
(5, 'Business Administration'),
(6, 'Biotechnology'),
(7, 'Psychology'),
(8, 'Mathematics'),
(9, 'Physics'),
(10, 'Economics');

-- --------------------------------------------------------

--
-- Table structure for table `qna_hierarchy`
--

CREATE TABLE `qna_hierarchy` (
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qna_posts`
--

CREATE TABLE `qna_posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qna_posts`
--

INSERT INTO `qna_posts` (`post_id`, `user_id`, `title`, `body`, `score`, `views`, `created_at`, `updated_at`) VALUES
(1, 18, 'test', 'test content', 0, 0, '2025-10-21 22:25:01', '2025-10-21 22:25:01'),
(2, 18, 'wow', 'ay yo', 0, 0, '2025-10-21 22:25:26', '2025-10-21 22:25:26'),
(3, 18, 'I have a  question', 'sike I don&#39;t have any question bro', 0, 0, '2025-10-21 22:29:10', '2025-10-21 22:29:10'),
(5, 18, 'wlkkefewfkq3', 'qkmefm34kotmnq45gq4gme4k55kkkkkkkkkkkkkkkkkkkkkkkkkkkkkk', 0, 0, '2025-10-22 04:54:46', '2025-10-22 04:54:46'),
(6, 18, 'test 2', 'test text', 0, 0, '2025-10-22 05:38:19', '2025-10-22 05:38:19');

-- --------------------------------------------------------

--
-- Table structure for table `qna_post_tags`
--

CREATE TABLE `qna_post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `universities`
--

CREATE TABLE `universities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `universities`
--

INSERT INTO `universities` (`id`, `name`) VALUES
(1, 'University of Colombo'),
(2, 'University of Peradeniya'),
(3, 'University of Moratuwa'),
(4, 'University of Kelaniya'),
(5, 'University of Sri Jayewardenepura'),
(6, 'University of Ruhuna'),
(7, 'Eastern University, Sri Lanka'),
(8, 'South Eastern University of Sri Lanka'),
(9, 'Wayamba University of Sri Lanka'),
(10, 'Sabaragamuwa University of Sri Lanka');

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `al_year`, `university`, `major`, `profile_role`, `profile_picture`, `created_at`) VALUES
(18, 'Ayanna', 'J', 'a@a.a', '0761234567', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-applicant', '2023', NULL, NULL, NULL, '', '2025-10-19 04:03:44'),
(26, 'Bhasu', 'Jayaweera', 'b@a.b', '0761234567', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-undergrad', NULL, 1, 1, NULL, NULL, '2025-10-19 06:39:52'),
(27, 'c', 'c', 'c@a.c', '1212121212', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-profile', NULL, 2, NULL, 'lecturer', NULL, '2025-10-21 21:28:49'),
(28, 'Admin', 'User', 'admin@uh.test', '0000000000', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-admin', NULL, NULL, NULL, NULL, NULL, '2025-10-22 02:32:59'),
(29, 'good', 'bruh', 'bruh@bruh.com', '1231231231', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-undergrad', NULL, 1, 1, NULL, NULL, '2025-10-22 09:53:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `degree_program`
--
ALTER TABLE `degree_program`
  ADD PRIMARY KEY (`program_id`),
  ADD KEY `university_id` (`university_id`),
  ADD KEY `major_id` (`major_id`);

--
-- Indexes for table `majors`
--
ALTER TABLE `majors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qna_hierarchy`
--
ALTER TABLE `qna_hierarchy`
  ADD PRIMARY KEY (`parent_id`,`child_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `qna_posts`
--
ALTER TABLE `qna_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `fk_userid` (`user_id`);

--
-- Indexes for table `qna_post_tags`
--
ALTER TABLE `qna_post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `unique_tag_name` (`tag_name`);

--
-- Indexes for table `universities`
--
ALTER TABLE `universities`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `degree_program`
--
ALTER TABLE `degree_program`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `majors`
--
ALTER TABLE `majors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `qna_posts`
--
ALTER TABLE `qna_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `universities`
--
ALTER TABLE `universities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `degree_program`
--
ALTER TABLE `degree_program`
  ADD CONSTRAINT `degree_program_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`),
  ADD CONSTRAINT `degree_program_ibfk_2` FOREIGN KEY (`major_id`) REFERENCES `majors` (`id`);

--
-- Constraints for table `qna_hierarchy`
--
ALTER TABLE `qna_hierarchy`
  ADD CONSTRAINT `qna_hierarchy_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `qna_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qna_hierarchy_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `qna_posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `qna_posts`
--
ALTER TABLE `qna_posts`
  ADD CONSTRAINT `fk_userid` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `qna_post_tags`
--
ALTER TABLE `qna_post_tags`
  ADD CONSTRAINT `qna_post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `qna_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qna_post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE;

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
