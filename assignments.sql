-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 11, 2026 at 08:08 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_assignments_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE IF NOT EXISTS `assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_name` varchar(100) NOT NULL,
  `student_id` varchar(8) NOT NULL,
  `subject` varchar(30) NOT NULL,
  `assignment_title` varchar(200) NOT NULL,
  `due_date` date NOT NULL,
  `marks` int NOT NULL,
  `remarks` text,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `student_name`, `student_id`, `subject`, `assignment_title`, `due_date`, `marks`, `remarks`, `submitted_at`) VALUES
(28, 'Nabuuma Immaculate', '34567891', 'Computer Science', 'Database Design', '2026-04-18', 92, 'Excellent Understanding', '2026-04-11 07:56:40'),
(29, 'James Stephen', '45678912', 'Biology', 'Human Anatomy', '2026-04-20', 88, '', '2026-04-11 08:01:32'),
(30, 'Lubale Johnson', '56789123', 'Physics', 'Newton Laws', '2026-04-22', 81, 'Well Explained', '2026-04-11 08:05:15'),
(26, 'Mukisa Simon', '12345678', 'Mathematics', 'Algebra', '2026-04-15', 85, 'Good Work', '2026-04-11 07:50:20'),
(27, 'Natukunda Ritah', '23456789', 'English', 'Essay Writing', '2026-04-16', 78, 'Needs improvement in grammar ', '2026-04-11 07:54:48');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
