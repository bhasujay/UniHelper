-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2026 at 09:15 AM
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
-- Table structure for table `applications_z_scores`
--

CREATE TABLE `applications_z_scores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `district` varchar(100) NOT NULL,
  `stream` varchar(100) NOT NULL,
  `subject1` varchar(100) NOT NULL,
  `subject2` varchar(100) NOT NULL,
  `subject3` varchar(100) NOT NULL,
  `z_score` decimal(5,4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `degree_program`
--

CREATE TABLE `degree_program` (
  `program_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `university_id` int(11) DEFAULT NULL,
  `stream` enum('physical-science','biological-science','technology','commerce','arts','other') DEFAULT NULL,
  `unicode` varchar(20) DEFAULT NULL,
  `major_id` int(11) DEFAULT NULL,
  `descriptions` varchar(255) DEFAULT NULL,
  `duration` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `degree_program`
--

INSERT INTO `degree_program` (`program_id`, `name`, `university_id`, `stream`, `unicode`, `major_id`, `descriptions`, `duration`) VALUES
(101, 'MEDICINE', 1, 'biological-science', '001A', 3, 'Comprehensive medical education covering human anatomy, physiology, pathology, and clinical practice. Students gain hands-on experience through clinical rotations and develop skills in diagnosis, treatment, and patient care.', '4'),
(102, 'ENGINEERING', 3, 'physical-science', '008G', 2, 'Rigorous engineering program focusing on mathematical foundations, physics principles, and practical applications. Students learn design thinking, problem-solving, and technical skills across various engineering disciplines.', '4'),
(103, 'COMPUTER SCIENCE', 16, 'physical-science', '012C', 1, 'Comprehensive study of computer systems, algorithms, programming languages, and software development. Students learn data structures, artificial intelligence, machine learning, and software engineering principles.', '4'),
(104, 'PHYSICAL SCIENCE', 1, 'physical-science', '013A', 10, 'In-depth exploration of physics, chemistry, and mathematics. Students develop analytical thinking and experimental skills while studying fundamental principles of matter, energy, and natural phenomena.', '4'),
(105, 'INFORMATION TECHNOLOGY (IT)', 3, 'physical-science', '026G', 1, 'Practical IT program covering network administration, database management, cybersecurity, and system analysis. Students gain hands-on experience with modern technologies and industry-standard tools.', '4'),
(106, 'MEDICINE', 2, 'biological-science', '001B', 3, 'Advanced medical curriculum emphasizing clinical skills, research methodology, and evidence-based practice. Students participate in clinical rotations and develop expertise in patient diagnosis and treatment.', '4'),
(107, 'MEDICINE', 16, 'biological-science', '001C', 3, 'Comprehensive medical training with focus on community health, preventive medicine, and clinical excellence. Students learn through case studies, clinical rotations, and research projects.', '4'),
(212, 'MEDICINE', 9, 'biological-science', '001D', 3, NULL, '4'),
(213, 'MEDICINE', 5, 'biological-science', '001E', 3, NULL, '4'),
(214, 'MEDICINE', 4, 'biological-science', '001F', 3, NULL, '4'),
(215, 'MEDICINE', 3, 'biological-science', '001G', 3, NULL, '4'),
(216, 'MEDICINE', 8, 'biological-science', '001H', 3, NULL, '4'),
(217, 'MEDICINE', 11, 'biological-science', '001K', 3, NULL, '4'),
(219, 'MEDICINE', 20, 'biological-science', '001M', 3, NULL, '4'),
(220, 'MEDICINE', 19, 'biological-science', '001U', 3, NULL, '4'),
(221, 'DENTAL SURGERY', 2, 'biological-science', '002B', 11, NULL, '4'),
(222, 'DENTAL SURGERY', 16, 'biological-science', '002C', 11, NULL, '4'),
(223, 'VETERINARY SCIENCE', 2, 'biological-science', '003B', 12, NULL, '4'),
(224, 'AGRICULTURE', 5, 'biological-science', '004E', 4, NULL, '4'),
(225, 'AGRICULTURE', 8, 'biological-science', '004H', 4, NULL, '4'),
(226, 'AGRICULTURE', 11, 'biological-science', '004K', 4, NULL, '4'),
(228, 'AGRICULTURE', 20, 'biological-science', '004M', 4, NULL, '4'),
(229, 'FOOD SCIENCE & NUTRITION', 20, 'biological-science', '005M', NULL, NULL, '4'),
(230, 'BIOLOGICAL SCIENCE', 1, 'biological-science', '006A', 9, NULL, '4'),
(231, 'BIOLOGICAL SCIENCE', 2, 'biological-science', '006B', 9, NULL, '4'),
(232, 'BIOLOGICAL SCIENCE', 16, 'biological-science', '006C', 9, NULL, '4'),
(233, 'BIOLOGICAL SCIENCE', 9, 'biological-science', '006D', 9, NULL, '4'),
(234, 'BIOLOGICAL SCIENCE', 5, 'biological-science', '006E', 9, NULL, '4'),
(235, 'BIOLOGICAL SCIENCE', 4, 'biological-science', '006F', 9, NULL, '4'),
(236, 'BIOLOGICAL SCIENCE', 8, 'biological-science', '006H', 9, NULL, '4'),
(237, 'BIOLOGICAL SCIENCE', 12, 'biological-science', '006J', 9, NULL, '4'),
(241, 'ENGINEERING', 2, 'physical-science', '008B', 2, NULL, '4'),
(242, 'ENGINEERING', 16, 'physical-science', '008C', 2, NULL, '4'),
(243, 'ENGINEERING', 5, 'physical-science', '008E', 2, NULL, '4'),
(244, 'ENGINEERING', 4, 'physical-science', '008F', 2, NULL, '4'),
(245, 'ENGINEERING', 3, 'physical-science', '008G', 2, NULL, '4'),
(246, 'ENGINEERING', 12, 'physical-science', '008J', 2, NULL, '4'),
(249, 'QUANTITY SURVEYING', 3, 'physical-science', '011G', 18, NULL, '4'),
(250, 'COMPUTER SCIENCE', 16, 'physical-science', '012C', 1, NULL, '4'),
(251, 'COMPUTER SCIENCE', 9, 'physical-science', '012D', 1, NULL, '4'),
(252, 'COMPUTER SCIENCE', 5, 'physical-science', '012E', 1, NULL, '4'),
(253, 'COMPUTER SCIENCE', 4, 'physical-science', '012F', 1, NULL, '4'),
(254, 'COMPUTER SCIENCE', 15, 'physical-science', '012T', 1, NULL, '4'),
(256, 'PHYSICAL SCIENCE', 1, 'physical-science', '013A', 10, NULL, '4'),
(257, 'PHYSICAL SCIENCE', 2, 'physical-science', '013B', 10, NULL, '4'),
(258, 'PHYSICAL SCIENCE', 16, 'physical-science', '013C', 10, NULL, '4'),
(259, 'PHYSICAL SCIENCE', 9, 'physical-science', '013D', 10, NULL, '4'),
(260, 'PHYSICAL SCIENCE', 5, 'physical-science', '013E', 10, NULL, '4'),
(261, 'PHYSICAL SCIENCE', 4, 'physical-science', '013F', 10, NULL, '4'),
(262, 'PHYSICAL SCIENCE', 8, 'physical-science', '013H', 10, NULL, '4'),
(263, 'PHYSICAL SCIENCE', 12, 'physical-science', '013J', 10, NULL, '4'),
(264, 'ARTIFICIAL INTELLIGENCE', 3, 'physical-science', '117G', 117, NULL, '4'),
(265, 'MANAGEMENT', 1, 'commerce', '016A', 22, NULL, '4'),
(266, 'MANAGEMENT', 2, 'commerce', '016B', 22, NULL, '4'),
(267, 'MANAGEMENT', 16, 'commerce', '016C', 22, NULL, '4'),
(268, 'MANAGEMENT', 9, 'commerce', '016D', 22, NULL, '4'),
(269, 'MANAGEMENT', 5, 'commerce', '016E', 22, NULL, '4'),
(270, 'MANAGEMENT', 4, 'commerce', '016F', 22, NULL, '4'),
(271, 'MANAGEMENT', 8, 'commerce', '016H', 22, NULL, '4'),
(272, 'MANAGEMENT', 12, 'commerce', '016J', 22, NULL, '4'),
(273, 'MANAGEMENT', 11, 'commerce', '016K', 22, NULL, '4'),
(274, 'COMMERCE', 16, 'commerce', '018C', 8, NULL, '4'),
(275, 'COMMERCE', 9, 'commerce', '018D', 8, NULL, '4'),
(276, 'ENGINEERING TECHNOLOGY (ET)', 1, 'technology', '102A', 93, NULL, '4'),
(277, 'ENGINEERING TECHNOLOGY (ET)', 16, 'technology', '102C', 93, NULL, '4'),
(278, 'ENGINEERING TECHNOLOGY (ET)', 9, 'technology', '102D', 93, NULL, '4'),
(279, 'ENGINEERING TECHNOLOGY (ET)', 5, 'technology', '102E', 93, NULL, '4'),
(280, 'ENGINEERING TECHNOLOGY (ET)', 4, 'technology', '102F', 93, NULL, '4'),
(281, 'ENGINEERING TECHNOLOGY (ET)', 11, 'technology', '102K', 93, NULL, '4'),
(283, 'LAW', 1, 'arts', '025A', 5, NULL, '4'),
(284, 'LAW', 2, 'arts', '025B', 5, NULL, '4'),
(285, 'LAW', 5, 'arts', '025E', 5, NULL, '4');

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
(3, 'Medicine & Health'),
(4, 'Agriculture'),
(5, 'Law'),
(6, 'Education'),
(7, 'Arts & Humanities'),
(8, 'Commerce & Business'),
(9, 'Biological Sciences'),
(10, 'Physical Sciences'),
(11, 'Dental Surgery'),
(12, 'Veterinary Science'),
(13, 'Food Science & Nutrition'),
(14, 'Biological Science'),
(15, 'Applied Sciences (Biological Science)'),
(16, 'Engineering (EM)'),
(17, 'Engineering (TM)'),
(18, 'Quantity Surveying'),
(19, 'Physical Science'),
(20, 'Surveying Science'),
(21, 'Applied Sciences (Physical Science)'),
(22, 'Management'),
(23, 'Real Estate Management and Valuation'),
(24, 'Commerce'),
(25, 'Arts (including Additional Intake)'),
(26, 'Arts (SP) - Mass Media'),
(27, 'Arts (SAB)'),
(28, 'Management Studies (TV)'),
(29, 'Architecture'),
(30, 'Design'),
(31, 'Information Technology (IT)'),
(32, 'Management and Information Technology (MIT)'),
(33, 'Management and Public Policy'),
(34, 'Communication Studies'),
(35, 'Urban Informatics and Planning'),
(36, 'Peace and Conflict Resolution'),
(37, 'Ayurveda Medicine and Surgery'),
(38, 'Unani Medicine and Surgery'),
(39, 'Fashion Design & Product Development'),
(40, 'Food Science & Technology'),
(41, 'Siddha Medicine and Surgery'),
(42, 'Nursing'),
(43, 'Information and Communication Technology (ICT)'),
(44, 'Agricultural Technology & Management'),
(45, 'Arts (SP) - Performing Arts'),
(46, 'Health Promotion'),
(47, 'Pharmacy'),
(48, 'Medical Laboratory Sciences'),
(49, 'Radiography'),
(50, 'Physiotherapy'),
(51, 'Environmental Conservation & Management'),
(52, 'Facilities Management'),
(53, 'Transport Management & Logistics Engineering (TMLE)'),
(54, 'Biochemistry & Molecular Biology'),
(55, 'Industrial Statistics & Mathematical Finance'),
(56, 'Statistics & Operations Research'),
(57, 'Fisheries & Marine Sciences'),
(58, 'Islamic Studies'),
(59, 'Science and Technology'),
(60, 'Computer Science & Technology'),
(61, 'Entrepreneurship and Management'),
(62, 'Animal Production and Food Technology'),
(63, 'Music'),
(64, 'Dance'),
(65, 'Art & Design'),
(66, 'Drama & Theatre'),
(67, 'Visual & Technological Arts'),
(68, 'Export Agriculture'),
(69, 'Industrial Information Technology'),
(70, 'Mineral Resources and Technology'),
(71, 'Business Information Systems (Honours) (BIS)'),
(72, 'Management and Information Technology (SEUSL)'),
(73, 'Physical Education'),
(74, 'Sports Science & Management'),
(75, 'Speech and Hearing Sciences'),
(76, 'Arabic Language'),
(77, 'Visual Arts'),
(78, 'Animal Science & Fisheries'),
(79, 'Food Production & Technology Management'),
(80, 'Aquatic Resources Technology'),
(81, 'Hospitality, Tourism and Events Management'),
(82, 'Information Technology & Management'),
(83, 'Tourism & Hospitality Management'),
(84, 'Agricultural Resource Management and Technology'),
(85, 'Agribusiness Management'),
(86, 'Green Technology'),
(87, 'Information Systems'),
(88, 'Landscape Architecture'),
(89, 'Translation Studies'),
(90, 'Software Engineering'),
(91, 'Film & Television Studies'),
(92, 'Project Management'),
(93, 'Engineering Technology (ET)'),
(94, 'Biosystems Technology (BST)'),
(95, 'Information Communication Technology'),
(96, 'Teaching English as a Second Language (TESL)'),
(97, 'Marine and Fresh Water Sciences'),
(98, 'Food Business Management'),
(99, 'Physical Science - ICT'),
(100, 'Business Science'),
(101, 'Financial Engineering'),
(102, 'Geographical Information Science'),
(103, 'Social Work'),
(104, 'Financial Mathematics and Industrial Statistics'),
(105, 'Human Resource Development'),
(106, 'Occupational Therapy'),
(107, 'Optometry'),
(108, 'Artificial Intelligence'),
(109, 'Applied Chemistry'),
(110, 'Electronics and Computer Science'),
(111, 'Indigenous Medicinal Resources'),
(112, 'Health Information and Communication Technology'),
(113, 'Health Tourism and Hospitality Management'),
(114, 'Biomedical Technology'),
(115, 'Indigenous Pharmaceutical Technology'),
(116, 'Yoga and Parapsychology'),
(117, 'Social Studies in Indigenous Knowledge'),
(118, 'Accounting Information Systems'),
(119, 'Arts-Information Technology'),
(120, 'Aquatic Bioresources'),
(121, 'Urban Bioresources'),
(122, 'Financial Economics'),
(123, 'English Language & Applied Linguistics'),
(124, 'Banking and Insurance'),
(125, 'Creative Music Technology and Production'),
(126, 'Plantation Management and Technology'),
(127, 'Data Science'),
(128, 'Primary Education'),
(129, 'Medical Imaging Technology'),
(130, 'Polymer Science and Industrial Management'),
(131, 'Service Management');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` int(11) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `question` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qna_posts`
--

INSERT INTO `qna_posts` (`post_id`, `user_id`, `title`, `body`, `score`, `views`, `created_at`, `updated_at`, `question`) VALUES
(46, 18, 'a', 'a', 0, 0, '2025-10-22 13:37:39', '2025-10-22 13:37:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `qna_post_tags`
--

CREATE TABLE `qna_post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qna_post_tags`
--

INSERT INTO `qna_post_tags` (`post_id`, `tag_id`) VALUES
(46, 22);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`) VALUES
(1, 'Physics'),
(2, 'Chemistry'),
(3, 'Mathematics'),
(4, 'Agricultural Science'),
(5, 'Biology'),
(6, 'Combined Mathematics'),
(7, 'Higher Mathematics'),
(8, 'Common General Test'),
(9, 'General English'),
(10, 'Civil Technology'),
(11, 'Mechanical Technology'),
(12, 'Electrical, Electronic and Information Technology'),
(13, 'Food Technology'),
(14, 'Agriculture Technology'),
(15, 'Bio Resource Technology'),
(16, 'Information & Communication Technology'),
(17, 'Economics'),
(18, 'Geography'),
(19, 'Political Science'),
(20, 'Logic and Scientific Method'),
(21, 'History of Sri Lanka'),
(22, 'History of India'),
(23, 'History of Europe'),
(24, 'Modern World History'),
(25, 'Home Economics'),
(26, 'Communication & Media Studies'),
(27, 'Business Statistics'),
(28, 'Business Studies'),
(29, 'Accountancy'),
(30, 'Buddhism'),
(31, 'Hinduism'),
(32, 'Christianity'),
(33, 'Islam'),
(34, 'Buddhist Civilization'),
(35, 'Hindu Civilization'),
(36, 'Islam Civilization'),
(37, 'Greek and Roman Civilization'),
(38, 'Christian Civilization'),
(39, 'Art'),
(40, 'Dancing (Indigenous)'),
(41, 'Dancing (Bharatha)'),
(42, 'Music (Oriental)'),
(43, 'Music (Carnatic)'),
(44, 'Music (Western)'),
(45, 'Drama and Theatre (Sinhala)'),
(46, 'Drama and Theatre (Tamil)'),
(47, 'Drama and Theatre (English)'),
(48, 'Engineering Technology'),
(49, 'Biosystems Technology'),
(50, 'Science for Technology'),
(51, 'Sinhala'),
(52, 'Tamil'),
(53, 'English'),
(54, 'Pali'),
(55, 'Sanskrit'),
(56, 'Arabic'),
(57, 'Malay'),
(58, 'French'),
(59, 'German'),
(60, 'Russian'),
(61, 'Hindi'),
(62, 'Chinese'),
(63, 'Japanese');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`tag_id`, `tag_name`) VALUES
(22, 'a'),
(17, 'aa'),
(18, 'as'),
(20, 'huththo'),
(21, 'nigga'),
(19, 'snjjd'),
(23, 'test'),
(16, 'www');

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
(1, 'University Of Colombo'),
(2, 'University Of Peradeniya'),
(3, 'University Of Moratuwa'),
(4, 'University Of Ruhuna'),
(5, 'University Of Jaffana '),
(6, 'University Of Sabaragamuwa'),
(7, 'University of Eastern (Trincomalee Campus)'),
(8, 'University of Eastern'),
(9, 'University of Kelaniya'),
(10, 'University of Gampaha Wickramarachchi Indigenous Medicine'),
(11, 'University of Rajarata'),
(12, 'University of South Eastern'),
(13, 'University of Swami Vipulananda Aesthetic Studies'),
(14, 'University of Colombo (Sripalee Campus)'),
(15, 'University of Colombo School of Computing'),
(16, 'University of Sri Jayewardenepura'),
(17, 'University of Visual & Performing Arts'),
(18, 'University of Vavuniya'),
(19, 'University of Uva Wellassa'),
(20, 'University of Wayamba');

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
  `public` tinyint(1) DEFAULT 1,
  `moderator` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `al_year`, `university`, `major`, `profile_role`, `profile_picture`, `created_at`, `public`, `moderator`) VALUES
(18, 'Ayanna', 'J', 'a@a.a', '0761234567', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-applicant', '2023', NULL, NULL, NULL, '', '2025-10-19 04:03:44', 1, 0),
(26, 'Bhasu', 'Jayaweera', 'b@a.b', '0761234567', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-undergrad', NULL, NULL, NULL, NULL, NULL, '2025-10-19 06:39:52', 1, 0),
(27, 'c', 'c', 'c@a.c', '1212121212', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-profile', NULL, NULL, NULL, 'lecturer', NULL, '2025-10-21 21:28:49', 1, 0),
(28, 'Admin', 'User', 'admin@uh.test', '0000000000', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-admin', NULL, NULL, NULL, NULL, NULL, '2025-10-22 02:32:59', 1, 0),
(29, 'good', 'bruh', 'bruh@bruh.com', '1231231231', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-undergrad', NULL, NULL, NULL, NULL, NULL, '2025-10-22 09:53:59', 1, 0),
(30, 'test', 'pro', 'tp@p.p', '1212121212', 'd116661feec7656cf66d30da99e4402d0187c7f292102075ed504c9812088897', 'role-profile', NULL, 3, NULL, 'IEEE Chairperson', NULL, '2025-10-22 19:31:32', 1, 0),
(31, 'chamath', 'madusanka', 'chamathperera200369@gmail.com', '0765256718', '0df8e77f7e4c44f305512c68f11d4741449d13f7cf8095cf97dcb963af36045f', 'role-applicant', '2024', NULL, NULL, NULL, NULL, '2025-10-23 00:42:15', 1, 0),
(32, 'chamath', 'madusanka', 'test@example.com', '0777123456', '0df8e77f7e4c44f305512c68f11d4741449d13f7cf8095cf97dcb963af36045f', 'role-undergrad', NULL, 1, 2, NULL, NULL, '2025-10-23 08:19:39', 1, 0),
(33, 'chamath', 'madusanka', '2023cs139@stu.ucsc.cmb.ac.lk', '0765256718', '0df8e77f7e4c44f305512c68f11d4741449d13f7cf8095cf97dcb963af36045f', 'role-applicant', '2024', NULL, NULL, NULL, NULL, '2025-10-23 13:21:42', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `program_id`, `created_at`) VALUES
(8, 31, 253, '2026-01-29 06:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `z_score_data`
--

CREATE TABLE `z_score_data` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `district` varchar(50) NOT NULL,
  `actual_z_score` decimal(6,4) DEFAULT NULL,
  `predicted_z_score` decimal(6,4) DEFAULT NULL,
  `status` enum('predicted','actual','both') DEFAULT 'predicted',
  `prediction_error` decimal(6,4) DEFAULT NULL,
  `prediction_date` date DEFAULT NULL,
  `actual_data_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications_z_scores`
--
ALTER TABLE `applications_z_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `otps`
--
ALTER TABLE `otps`
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
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

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
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_user_program` (`user_id`,`program_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `z_score_data`
--
ALTER TABLE `z_score_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_program_year_district` (`program_id`,`year`,`district`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications_z_scores`
--
ALTER TABLE `applications_z_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `degree_program`
--
ALTER TABLE `degree_program`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT for table `majors`
--
ALTER TABLE `majors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `qna_posts`
--
ALTER TABLE `qna_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `universities`
--
ALTER TABLE `universities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `z_score_data`
--
ALTER TABLE `z_score_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications_z_scores`
--
ALTER TABLE `applications_z_scores`
  ADD CONSTRAINT `app-z` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `degree_program` (`program_id`);

--
-- Constraints for table `z_score_data`
--
ALTER TABLE `z_score_data`
  ADD CONSTRAINT `z_score_data_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `degree_program` (`program_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Feedback Table Creation Script
-- Run this SQL script in your MySQL/MariaDB to create the feedback table
-- This table stores user feedback submissions from the feedback forum

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
