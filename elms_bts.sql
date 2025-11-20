-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 19, 2025 at 10:57 PM
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
-- Database: `elms_bts`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `trainer_id` varchar(20) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_submissions`
--

CREATE TABLE `activity_submissions` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `guest_id` varchar(50) DEFAULT NULL,
  `submission_text` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `score` decimal(5,2) DEFAULT NULL,
  `graded_by` varchar(50) DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_by` varchar(20) DEFAULT NULL,
  `date_posted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_assignments`
--

CREATE TABLE `batch_assignments` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `trainer_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `batch_name` text NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competencies`
--

CREATE TABLE `competencies` (
  `id` int(11) NOT NULL,
  `competency_code` varchar(50) NOT NULL,
  `competency_name` varchar(255) NOT NULL,
  `competency_type` enum('basic','common','core') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `hours` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `competency_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`competency_types`)),
  `status` enum('active','archived') DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--

CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL,
  `trainer_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `date_assigned` datetime DEFAULT current_timestamp(),
  `assigned_by` varchar(20) NOT NULL,
  `batch` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_batches`
--

CREATE TABLE `course_batches` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `batch_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `trainer_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_type` enum('pdf','video','text','quiz','assignment','assessment') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `competency_type` enum('basic','common','core') NOT NULL,
  `activity_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_data`)),
  `is_locked` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_topics`
--

CREATE TABLE `course_topics` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `competency_id` varchar(255) NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `topic_description` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `added_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `batch_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `date_requested` datetime DEFAULT current_timestamp(),
  `processed_date` datetime DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `trainee_id` varchar(20) NOT NULL,
  `material_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `teacher_remarks` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL,
  `graded_by` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_activities`
--

CREATE TABLE `topic_activities` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `activity_title` varchar(255) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `activity_type` enum('assignment','quiz','project','discussion') DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_score` int(11) DEFAULT NULL,
  `max_attempts` int(11) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_materials`
--

CREATE TABLE `topic_materials` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `material_title` varchar(255) DEFAULT NULL,
  `material_description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `role` enum('admin','trainer','trainee','guest') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT 1,
  `status` enum('active','archived') DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`trainer_id`);

--
-- Indexes for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `trainee_id` (`guest_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `batch_assignments`
--
ALTER TABLE `batch_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `batch_id` (`batch_name`(768)),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `competencies`
--
ALTER TABLE `competencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `competency_code` (`competency_code`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`trainer_id`,`course_code`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `course_batches`
--
ALTER TABLE `course_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_batch` (`course_code`,`batch_name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`);

--
-- Indexes for table `course_topics`
--
ALTER TABLE `course_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `graded_by` (`graded_by`);

--
-- Indexes for table `topic_activities`
--
ALTER TABLE `topic_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_activities_ibfk_1` (`topic_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `topic_materials`
--
ALTER TABLE `topic_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_materials_ibfk_1` (`topic_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_assignments`
--
ALTER TABLE `batch_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competencies`
--
ALTER TABLE `competencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_batches`
--
ALTER TABLE `course_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_topics`
--
ALTER TABLE `course_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic_activities`
--
ALTER TABLE `topic_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic_materials`
--
ALTER TABLE `topic_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  ADD CONSTRAINT `activity_submissions_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `topic_activities` (`id`),
  ADD CONSTRAINT `activity_submissions_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `batch_assignments`
--
ALTER TABLE `batch_assignments`
  ADD CONSTRAINT `batch_assignments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `batch_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `batch_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `batch_assignments_ibfk_5` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD CONSTRAINT `course_assignments_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `course_assignments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `course_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `course_batches`
--
ALTER TABLE `course_batches`
  ADD CONSTRAINT `course_batches_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `course_batches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `course_batches_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`);

--
-- Constraints for table `course_topics`
--
ALTER TABLE `course_topics`
  ADD CONSTRAINT `course_topics_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `course_topics_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `course_materials` (`id`),
  ADD CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `topic_activities`
--
ALTER TABLE `topic_activities`
  ADD CONSTRAINT `topic_activities_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `topic_activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `topic_materials`
--
ALTER TABLE `topic_materials`
  ADD CONSTRAINT `topic_materials_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `course_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `topic_materials_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
