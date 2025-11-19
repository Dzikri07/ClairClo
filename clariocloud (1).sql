-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 02:25 PM
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
-- Database: `clariocloud`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `description`, `created_at`, `ip_address`) VALUES
(1, NULL, 'EXPORT_USERS', 'Exported 2 user records to CSV', '2025-11-19 11:56:26', '::1'),
(2, NULL, 'EXPORT_LOGS', 'Exported activity logs to CSV', '2025-11-19 11:56:26', '::1'),
(3, NULL, 'BACKUP_SERVER', 'Created server backup: server_backup_2025-11-19_13-26-09.zip ( bytes, 0 files included)', '2025-11-19 12:26:09', '::1'),
(4, NULL, 'BACKUP_SERVER', 'Created server backup: server_backup_2025-11-19_13-28-35.zip (0 bytes, 0 files included)', '2025-11-19 12:28:35', '::1'),
(5, NULL, 'BACKUP_SERVER', 'Created server backup: server_backup_2025-11-19_13-28-39.zip (0 bytes, 0 files included)', '2025-11-19 12:28:39', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `size` bigint(20) UNSIGNED DEFAULT 0,
  `download_count` int(10) UNSIGNED DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_accessed` timestamp NULL DEFAULT NULL,
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
  `is_trashed` tinyint(1) NOT NULL DEFAULT 0,
  `trashed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `user_id`, `category_id`, `filename`, `file_path`, `thumbnail_path`, `original_name`, `description`, `mime`, `extension`, `size`, `download_count`, `uploaded_at`, `last_accessed`, `is_favorite`, `is_trashed`, `trashed_at`) VALUES
(1, 5, 5, 'activity_logs_2025-11-19_12-56-26_1763557042_691dbeb2d8a01.csv', 'user_5\\activity_logs_2025-11-19_12-56-26_1763557042_691dbeb2d8a01.csv', NULL, 'activity_logs_2025-11-19_12-56-26.csv', NULL, 'text/plain', 'csv', 127, 0, '2025-11-19 12:57:22', NULL, 0, 0, NULL),
(2, 5, 1, '2025-2026_ganjil_tugas_activity_diagram_1763557053_691dbebd6a842.docx', 'user_5\\2025-2026_ganjil_tugas_activity_diagram_1763557053_691dbebd6a842.docx', NULL, '2025-2026 ganjil tugas activity diagram.docx', NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 274238, 1, '2025-11-19 12:57:33', '2025-11-19 13:18:01', 0, 0, NULL),
(3, 5, 2, 'Bored_Valkyrie_1763557061_691dbec539338.png', 'user_5\\Bored_Valkyrie_1763557061_691dbec539338.png', NULL, 'Bored_Valkyrie.png', NULL, 'image/png', 'png', 25354, 1, '2025-11-19 12:57:41', '2025-11-19 13:05:13', 1, 0, NULL),
(4, 5, 5, 'activity_logs_2025-11-19_12-56-26_1763557083_691dbedba9a68.csv', 'user_5\\activity_logs_2025-11-19_12-56-26_1763557083_691dbedba9a68.csv', NULL, 'activity_logs_2025-11-19_12-56-26.csv', NULL, 'text/plain', 'csv', 127, 0, '2025-11-19 12:58:03', NULL, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `file_categories`
--

CREATE TABLE `file_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `allowed_extensions` text NOT NULL COMMENT 'Comma-separated list of allowed extensions',
  `max_file_size` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Max file size in bytes (NULL = no limit)',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Icon class or name',
  `color` varchar(20) DEFAULT NULL COMMENT 'Category color code',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='File categories for organizing uploads';

--
-- Dumping data for table `file_categories`
--

INSERT INTO `file_categories` (`id`, `name`, `slug`, `description`, `allowed_extensions`, `max_file_size`, `icon`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Documents', 'documents', 'Document files including PDF, Word, Text files', 'pdf,doc,docx,txt,rtf,odt', 52428800, 'fa-file-text', '#3498db', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(2, 'Images', 'images', 'Image files including PNG, JPG, GIF, SVG', 'jpg,jpeg,png,gif,bmp,svg,webp,ico', 10485760, 'fa-image', '#e74c3c', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(3, 'Videos', 'videos', 'Video files including MP4, AVI, MOV, MKV', 'mp4,avi,mov,mkv,wmv,flv,webm,m4v', 524288000, 'fa-video', '#9b59b6', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(4, 'Audio', 'audio', 'Audio files including MP3, WAV, OGG', 'mp3,wav,ogg,m4a,flac,aac,wma', 52428800, 'fa-music', '#1abc9c', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(5, 'Spreadsheets', 'spreadsheets', 'Spreadsheet files including Excel, CSV', 'xlsx,xls,csv,ods', 20971520, 'fa-table', '#27ae60', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(6, 'Presentations', 'presentations', 'Presentation files including PowerPoint', 'ppt,pptx,odp,key', 52428800, 'fa-presentation', '#f39c12', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(7, 'Archives', 'archives', 'Compressed archive files', 'zip,rar,7z,tar,gz,bz2', 104857600, 'fa-archive', '#95a5a6', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(8, 'Code', 'code', 'Source code and programming files', 'php,js,html,css,json,xml,sql,py,java,cpp,c,h,sh', 5242880, 'fa-code', '#34495e', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01'),
(9, 'Others', 'others', 'Other file types not categorized', '*', 104857600, 'fa-file', '#7f8c8d', 1, '2025-11-19 11:42:01', '2025-11-19 11:42:01');

-- --------------------------------------------------------

--
-- Table structure for table `file_storage_paths`
--

CREATE TABLE `file_storage_paths` (
  `id` int(10) UNSIGNED NOT NULL,
  `file_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `storage_path` varchar(500) NOT NULL COMMENT 'Path: /uploads/user_<user_id>/',
  `file_path` varchar(500) NOT NULL COMMENT 'Full relative path from uploads directory',
  `thumbnail_path` varchar(500) DEFAULT NULL COMMENT 'Thumbnail path if applicable',
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT 0 COMMENT 'File size in bytes',
  `mime_type` varchar(100) DEFAULT NULL,
  `file_extension` varchar(20) DEFAULT NULL,
  `download_count` int(10) UNSIGNED DEFAULT 0 COMMENT 'Number of times downloaded',
  `last_downloaded` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Soft delete flag',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='File storage paths and metadata tracking';

--
-- Dumping data for table `file_storage_paths`
--

INSERT INTO `file_storage_paths` (`id`, `file_id`, `user_id`, `storage_path`, `file_path`, `thumbnail_path`, `original_filename`, `stored_filename`, `file_size`, `mime_type`, `file_extension`, `download_count`, `last_downloaded`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'uploads\\user_5', 'user_5\\activity_logs_2025-11-19_12-56-26_1763557042_691dbeb2d8a01.csv', NULL, 'activity_logs_2025-11-19_12-56-26.csv', 'activity_logs_2025-11-19_12-56-26_1763557042_691dbeb2d8a01.csv', 127, 'text/plain', 'csv', 0, NULL, 0, NULL, '2025-11-19 12:57:22', '2025-11-19 12:57:22'),
(2, 2, 5, 'uploads\\user_5', 'user_5\\2025-2026_ganjil_tugas_activity_diagram_1763557053_691dbebd6a842.docx', NULL, '2025-2026 ganjil tugas activity diagram.docx', '2025-2026_ganjil_tugas_activity_diagram_1763557053_691dbebd6a842.docx', 274238, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 1, '2025-11-19 13:18:01', 0, NULL, '2025-11-19 12:57:33', '2025-11-19 13:18:27'),
(3, 3, 5, 'uploads\\user_5', 'user_5\\Bored_Valkyrie_1763557061_691dbec539338.png', NULL, 'Bored_Valkyrie.png', 'Bored_Valkyrie_1763557061_691dbec539338.png', 25354, 'image/png', 'png', 1, '2025-11-19 13:05:13', 0, NULL, '2025-11-19 12:57:41', '2025-11-19 13:06:37'),
(4, 4, 5, 'uploads\\user_5', 'user_5\\activity_logs_2025-11-19_12-56-26_1763557083_691dbedba9a68.csv', NULL, 'activity_logs_2025-11-19_12-56-26.csv', 'activity_logs_2025-11-19_12-56-26_1763557083_691dbedba9a68.csv', 127, 'text/plain', 'csv', 0, NULL, 0, NULL, '2025-11-19 12:58:03', '2025-11-19 12:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `storage_requests`
--

CREATE TABLE `storage_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `requested_quota` bigint(20) UNSIGNED NOT NULL COMMENT 'Requested storage quota in bytes',
  `current_quota` bigint(20) UNSIGNED NOT NULL COMMENT 'Current storage quota in bytes',
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Admin who processed the request',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Storage increase requests table';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `storage_quota` bigint(20) UNSIGNED DEFAULT 5368709120 COMMENT 'Storage quota in bytes (default 5GB)',
  `storage_used` bigint(20) UNSIGNED DEFAULT 0 COMMENT 'Storage used in bytes',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User accounts table';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `storage_quota`, `storage_used`, `avatar`, `is_active`, `is_admin`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@clariocloud.local', '$2y$10$WvczDfoFTRxlvkqcOo.L2.cLRpg9CGWZ4GGE5qOK7QTBVqSGsFDR2', 'Administrator', 107374182400, 0, NULL, 1, 1, '2025-11-19 13:18:41', '2025-11-19 11:41:41', '2025-11-19 13:18:41'),
(3, 'user', 'user@clairocloud.local', '$2y$10$CBA0ZeR9xJVsS8EQ.8blKuLia/90ISYGua8SvMLaTJ44OVu1WKZUW', 'dzikri', 1073741824, 0, NULL, 1, 0, NULL, '2025-11-19 11:43:04', '2025-11-19 12:36:06'),
(4, 'user12', 'dzikri.muhammad36@gmail.com', '$2y$10$tcGMfMighF0kkGdLSOheO.r7bIVL301YwjN0gjZmP4BqPdfHY6ho6', 'dzikri', 5368709120, 0, NULL, 1, 0, NULL, '2025-11-19 06:38:39', '2025-11-19 12:38:39'),
(5, 'user3', 'user3@gmail.com', '$2y$10$chkYfodCHIM7wek/dMh4y..ZXZy1rBYJJob8P/rDlwAnbWqiCW7iK', 'usup', 10737418240, 299846, NULL, 1, 0, '2025-11-19 12:57:15', '2025-11-19 06:41:59', '2025-11-19 12:58:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `filename` (`filename`),
  ADD KEY `is_favorite` (`is_favorite`),
  ADD KEY `is_trashed` (`is_trashed`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_extension` (`extension`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `file_categories`
--
ALTER TABLE `file_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `file_storage_paths`
--
ALTER TABLE `file_storage_paths`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_file_id` (`file_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_file_extension` (`file_extension`),
  ADD KEY `idx_download_count` (`download_count`),
  ADD KEY `idx_is_deleted` (`is_deleted`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_storage_path` (`storage_path`),
  ADD KEY `idx_deleted_timestamp` (`is_deleted`,`deleted_at`);

--
-- Indexes for table `storage_requests`
--
ALTER TABLE `storage_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_storage_requests_admin` (`admin_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `file_categories`
--
ALTER TABLE `file_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `file_storage_paths`
--
ALTER TABLE `file_storage_paths`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `storage_requests`
--
ALTER TABLE `storage_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `fk_files_category` FOREIGN KEY (`category_id`) REFERENCES `file_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_files_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `file_storage_paths`
--
ALTER TABLE `file_storage_paths`
  ADD CONSTRAINT `fk_file_storage_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_file_storage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `storage_requests`
--
ALTER TABLE `storage_requests`
  ADD CONSTRAINT `fk_storage_requests_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_storage_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
