-- Users Table Backup
-- Generated: 2025-11-20 16:59:18

-- Table structure for users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `storage_quota` bigint(20) DEFAULT 5368709120,
  `storage_used` bigint(20) DEFAULT 0,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `users`

INSERT INTO `users` VALUES ('1', 'admin', 'admin@clariocloud.local', '$2y$10$WvczDfoFTRxlvkqcOo.L2.cLRpg9CGWZ4GGE5qOK7QTBVqSGsFDR2', 'Administrator', '107374182400', '0', NULL, '1', '1', '2025-11-20 22:39:19', '2025-11-19 18:41:41', '2025-11-20 22:39:19');
INSERT INTO `users` VALUES ('3', 'user', 'user@clairocloud.local', '$2y$10$CBA0ZeR9xJVsS8EQ.8blKuLia/90ISYGua8SvMLaTJ44OVu1WKZUW', 'dzikri', '12884901888', '2096348', NULL, '1', '0', '2025-11-20 20:30:04', '2025-11-19 18:43:04', '2025-11-20 22:35:48');
INSERT INTO `users` VALUES ('4', 'user12', 'dzikri.muhammad36@gmail.com', '$2y$10$tcGMfMighF0kkGdLSOheO.r7bIVL301YwjN0gjZmP4BqPdfHY6ho6', 'dzikri', '5368709120', '0', NULL, '1', '0', NULL, '2025-11-19 13:38:39', '2025-11-19 19:38:39');
INSERT INTO `users` VALUES ('5', 'user3', 'user3@gmail.com', '$2y$10$chkYfodCHIM7wek/dMh4y..ZXZy1rBYJJob8P/rDlwAnbWqiCW7iK', 'usup', '42949672960', '357522', NULL, '1', '0', '2025-11-20 22:36:49', '2025-11-19 13:41:59', '2025-11-20 22:36:49');
