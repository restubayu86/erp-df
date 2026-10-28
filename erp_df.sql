/*
 Navicat Premium Data Transfer

 Source Server         : Local
 Source Server Type    : MySQL
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : erp_df

 Target Server Type    : MySQL
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 22/10/2026 17:13:25
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for app_settings
-- ----------------------------
DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE `app_settings`  (
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`name`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of app_settings
-- ----------------------------
INSERT INTO `app_settings` VALUES ('schedule.auto_approve', '0');

-- ----------------------------
-- Table structure for auth_groups_users
-- ----------------------------
DROP TABLE IF EXISTS `auth_groups_users`;
CREATE TABLE `auth_groups_users`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `auth_groups_users_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `auth_groups_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_groups_users
-- ----------------------------
INSERT INTO `auth_groups_users` VALUES (1, 1, 'viewer', '2026-10-21 02:40:51');
INSERT INTO `auth_groups_users` VALUES (2, 1, 'admin', '2026-10-21 02:41:23');
INSERT INTO `auth_groups_users` VALUES (3, 2, 'viewer', '2026-10-21 02:42:37');
INSERT INTO `auth_groups_users` VALUES (4, 2, 'scheduler', '2026-10-21 02:42:58');
INSERT INTO `auth_groups_users` VALUES (5, 3, 'viewer', '2026-10-21 02:43:36');
INSERT INTO `auth_groups_users` VALUES (6, 3, 'kabag', '2026-10-21 02:44:11');
INSERT INTO `auth_groups_users` VALUES (7, 4, 'viewer', '2026-10-21 02:45:35');
INSERT INTO `auth_groups_users` VALUES (8, 4, 'operator', '2026-10-21 02:46:06');
INSERT INTO `auth_groups_users` VALUES (9, 5, 'viewer', '2026-10-21 02:47:00');

-- ----------------------------
-- Table structure for auth_identities
-- ----------------------------
DROP TABLE IF EXISTS `auth_identities`;
CREATE TABLE `auth_identities`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `secret2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `expires` datetime NULL DEFAULT NULL,
  `extra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `force_reset` tinyint(1) NOT NULL DEFAULT 0,
  `last_used_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `type_secret`(`type` ASC, `secret` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  CONSTRAINT `auth_identities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_identities
-- ----------------------------
INSERT INTO `auth_identities` VALUES (1, 1, 'email_password', NULL, 'mbcregency.3a@gmail.com', '$2y$12$IJWObPjzpqGZVBlEXewUP.NRCDztwXQNdalMiRvgaSgOjV4gaGYRi', NULL, NULL, 0, '2026-10-22 10:11:13', '2026-10-21 02:40:51', '2026-10-22 10:11:13');
INSERT INTO `auth_identities` VALUES (2, 2, 'email_password', NULL, 'jana.dyeing@sc.id', '$2y$12$T8zyLI08UEKr.3N5h8uFNeMXv3fkfSN3Xh9YJE76kBhwE38tXotxO', NULL, NULL, 0, '2026-10-22 05:00:26', '2026-10-21 02:42:36', '2026-10-22 05:00:26');
INSERT INTO `auth_identities` VALUES (3, 3, 'email_password', NULL, 'bagus.dyeing@sc.id', '$2y$12$5X68ojlGvvlglfsTpUiSg.dWBt8znXsjO0oYqIidhidglxexcms7K', NULL, NULL, 0, NULL, '2026-10-21 02:43:35', '2026-10-21 02:43:36');
INSERT INTO `auth_identities` VALUES (4, 4, 'email_password', NULL, 'eko.dyeing@sc.id', '$2y$12$Rzi6BI8sMg3dCNOuF73acOUfgHA5Lv2Q44qYBfwhRzZ58eqYYJQFq', NULL, NULL, 0, NULL, '2026-10-21 02:45:35', '2026-10-21 02:45:35');
INSERT INTO `auth_identities` VALUES (5, 5, 'email_password', NULL, 'arief.manager@sc.id', '$2y$12$mf4IeGeHP4nGkx4w/wJ.w.xpHxa5ENCwiMyJbvp2wZ1fhxNzZBgYW', NULL, NULL, 0, NULL, '2026-10-21 02:47:00', '2026-10-21 02:47:00');

-- ----------------------------
-- Table structure for auth_logins
-- ----------------------------
DROP TABLE IF EXISTS `auth_logins`;
CREATE TABLE `auth_logins`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int UNSIGNED NULL DEFAULT NULL,
  `date` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id_type_identifier`(`id_type` ASC, `identifier` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 23 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_logins
-- ----------------------------
INSERT INTO `auth_logins` VALUES (1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-21 03:09:22', 1);
INSERT INTO `auth_logins` VALUES (2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-21 03:54:09', 0);
INSERT INTO `auth_logins` VALUES (3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-21 03:54:37', 0);
INSERT INTO `auth_logins` VALUES (4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-21 03:54:56', 0);
INSERT INTO `auth_logins` VALUES (5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', 2, '2026-10-21 03:55:17', 1);
INSERT INTO `auth_logins` VALUES (6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.142.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-21 07:01:28', 1);
INSERT INTO `auth_logins` VALUES (7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-21 08:22:06', 1);
INSERT INTO `auth_logins` VALUES (8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.142.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-22 01:43:53', 1);
INSERT INTO `auth_logins` VALUES (9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.142.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36', 'email_password', 'admin@example.com.com', NULL, '2026-10-22 01:56:19', 0);
INSERT INTO `auth_logins` VALUES (10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.142.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-22 01:56:47', 1);
INSERT INTO `auth_logins` VALUES (11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-22 02:10:58', 1);
INSERT INTO `auth_logins` VALUES (12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-22 04:51:49', 0);
INSERT INTO `auth_logins` VALUES (13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-22 04:52:05', 0);
INSERT INTO `auth_logins` VALUES (14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-22 04:54:21', 0);
INSERT INTO `auth_logins` VALUES (15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-22 04:54:28', 0);
INSERT INTO `auth_logins` VALUES (16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-22 04:59:26', 0);
INSERT INTO `auth_logins` VALUES (17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', NULL, '2026-10-22 04:59:32', 0);
INSERT INTO `auth_logins` VALUES (18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'eko.dyeing@sc.id', NULL, '2026-10-22 04:59:51', 0);
INSERT INTO `auth_logins` VALUES (19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-22 05:00:01', 1);
INSERT INTO `auth_logins` VALUES (20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'jana.dyeing@sc.id', 2, '2026-10-22 05:00:26', 1);
INSERT INTO `auth_logins` VALUES (21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-22 05:00:57', 1);
INSERT INTO `auth_logins` VALUES (22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'email_password', 'mbcregency.3a@gmail.com', 1, '2026-10-22 10:11:13', 1);

-- ----------------------------
-- Table structure for auth_permissions_users
-- ----------------------------
DROP TABLE IF EXISTS `auth_permissions_users`;
CREATE TABLE `auth_permissions_users`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `permission` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `auth_permissions_users_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `auth_permissions_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_permissions_users
-- ----------------------------

-- ----------------------------
-- Table structure for auth_remember_tokens
-- ----------------------------
DROP TABLE IF EXISTS `auth_remember_tokens`;
CREATE TABLE `auth_remember_tokens`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `selector` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hashedValidator` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `selector`(`selector` ASC) USING BTREE,
  INDEX `auth_remember_tokens_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `auth_remember_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_remember_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for auth_token_logins
-- ----------------------------
DROP TABLE IF EXISTS `auth_token_logins`;
CREATE TABLE `auth_token_logins`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int UNSIGNED NULL DEFAULT NULL,
  `date` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id_type_identifier`(`id_type` ASC, `identifier` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_token_logins
-- ----------------------------

-- ----------------------------
-- Table structure for machines
-- ----------------------------
DROP TABLE IF EXISTS `machines`;
CREATE TABLE `machines`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('dyeing','finishing') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dyeing',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of machines
-- ----------------------------
INSERT INTO `machines` VALUES (1, 'JD-01', 'Jet Dyeing 1', 'dyeing', 1, '2026-10-21 03:06:10', NULL);
INSERT INTO `machines` VALUES (2, 'JD-02', 'Jet Dyeing 2', 'dyeing', 1, '2026-10-21 03:06:10', NULL);
INSERT INTO `machines` VALUES (3, 'ST-01', 'Stenter 1', 'finishing', 1, '2026-10-21 03:06:10', NULL);

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (1, '2020-12-28-223112', 'CodeIgniter\\Shield\\Database\\Migrations\\CreateAuthTables', 'default', 'CodeIgniter\\Shield', 1792550059, 1);
INSERT INTO `migrations` VALUES (2, '2021-07-04-041948', 'CodeIgniter\\Settings\\Database\\Migrations\\CreateSettingsTable', 'default', 'CodeIgniter\\Settings', 1792550059, 1);
INSERT INTO `migrations` VALUES (3, '2021-11-14-143905', 'CodeIgniter\\Settings\\Database\\Migrations\\AddContextColumn', 'default', 'CodeIgniter\\Settings', 1792550059, 1);
INSERT INTO `migrations` VALUES (4, '2026-07-20-181246', 'CodeIgniter\\Settings\\Database\\Migrations\\ConvertSqlsrvValueColumn', 'default', 'CodeIgniter\\Settings', 1792550059, 1);
INSERT INTO `migrations` VALUES (5, '2026-10-21-000001', 'App\\Database\\Migrations\\CreateScheduleTables', 'default', 'App', 1792551771, 2);
INSERT INTO `migrations` VALUES (6, '2026-10-21-000002', 'App\\Database\\Migrations\\ScheduleWorkflow', 'default', 'App', 1792551952, 3);
INSERT INTO `migrations` VALUES (7, '2026-10-22-000001', 'App\\Database\\Migrations\\AddNewFieldsToSchedules', 'default', 'App', 1792643373, 4);

-- ----------------------------
-- Table structure for schedule_logs
-- ----------------------------
DROP TABLE IF EXISTS `schedule_logs`;
CREATE TABLE `schedule_logs`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` int UNSIGNED NOT NULL,
  `from_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `to_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int UNSIGNED NULL DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `schedule_id`(`schedule_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of schedule_logs
-- ----------------------------
INSERT INTO `schedule_logs` VALUES (1, 1, 'approved', 'running', 1, NULL, '2026-10-21 03:11:44');
INSERT INTO `schedule_logs` VALUES (2, 1, 'running', 'paused', 1, NULL, '2026-10-21 03:11:53');
INSERT INTO `schedule_logs` VALUES (3, 1, 'paused', 'running', 1, NULL, '2026-10-21 03:11:55');
INSERT INTO `schedule_logs` VALUES (4, 1, 'running', 'done', 1, NULL, '2026-10-21 03:12:00');
INSERT INTO `schedule_logs` VALUES (5, 7, 'draft', 'cancelled', 1, NULL, '2026-10-22 10:11:32');

-- ----------------------------
-- Table structure for schedules
-- ----------------------------
DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `machine_id` int UNSIGNED NOT NULL,
  `order_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `customer` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `work_date` date NULL DEFAULT NULL,
  `shift` enum('pagi','siang','malam') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pagi',
  `priority` int UNSIGNED NULL DEFAULT 0,
  `process` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `status` enum('draft','submitted','approved','rejected','running','paused','done','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'draft',
  `created_by` int UNSIGNED NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `reviewed_by` int UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` datetime NULL DEFAULT NULL,
  `review_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `actual_start` datetime NULL DEFAULT NULL,
  `actual_end` datetime NULL DEFAULT NULL,
  `category` enum('Produksi','Perbaikan','Percobaan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Produksi',
  `design` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `mp_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `wash_machine` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `progress` int NULL DEFAULT 0,
  `dye_lot_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `dop_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `machine_id_start_at`(`machine_id` ASC, `start_at` ASC) USING BTREE,
  INDEX `idx_sched_scope`(`work_date` ASC, `shift` ASC, `machine_id` ASC, `priority` ASC) USING BTREE,
  CONSTRAINT `schedules_machine_id_foreign` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of schedules
-- ----------------------------
INSERT INTO `schedules` VALUES (1, 1, 'SO-0001', 'Customer B', '2026-10-20', 'pagi', 1, 'Dyeing', '2026-10-20 07:00:00', '2026-10-20 09:00:00', 'done', NULL, '2026-10-21 03:06:10', '2026-10-21 03:12:00', NULL, NULL, NULL, '2026-10-21 03:11:44', '2026-10-21 03:12:00', 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (2, 2, 'SO-0002', 'Customer C', '2026-10-20', 'pagi', 3, 'Dyeing', '2026-10-20 07:00:00', '2026-10-20 09:00:00', 'submitted', NULL, '2026-10-21 03:06:10', '2026-10-22 03:34:17', NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (3, 3, 'SO-0003', 'Customer D', '2026-10-20', 'pagi', 1, 'Finishing', '2026-10-20 07:00:00', '2026-10-20 09:00:00', 'draft', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (4, 1, 'SO-0004', 'Customer E', '2026-10-20', 'pagi', 2, 'Dyeing', '2026-10-20 09:00:00', '2026-10-20 11:00:00', 'approved', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (5, 2, 'SO-0005', 'Customer A', '2026-10-20', 'pagi', 1, 'Dyeing', '2026-10-20 09:00:00', '2026-10-20 11:00:00', 'approved', NULL, '2026-10-21 03:06:10', '2026-10-22 03:34:17', NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (6, 3, 'SO-0006', 'Customer B', '2026-10-20', 'pagi', 2, 'Finishing', '2026-10-20 09:00:00', '2026-10-20 11:00:00', 'submitted', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (7, 1, 'SO-0007', 'Customer C', '2026-10-20', 'pagi', 3, 'Dyeing', '2026-10-20 11:00:00', '2026-10-20 13:00:00', 'cancelled', NULL, '2026-10-21 03:06:10', '2026-10-22 10:11:32', NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (8, 2, 'SO-0008', 'Customer D', '2026-10-20', 'pagi', 2, 'Dyeing', '2026-10-20 11:00:00', '2026-10-20 13:00:00', 'approved', NULL, '2026-10-21 03:06:10', '2026-10-22 03:34:17', NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (9, 1, 'SO-0009', 'Customer E', '2026-10-20', 'siang', 5, 'Dyeing', '2026-10-20 15:00:00', '2026-10-20 17:00:00', 'approved', NULL, '2026-10-21 03:06:10', '2026-10-22 03:37:00', NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (10, 2, 'SO-0010', 'Customer A', '2026-10-20', 'siang', 4, 'Dyeing', '2026-10-20 15:00:00', '2026-10-20 17:00:00', 'submitted', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (11, 3, 'SO-0011', 'Customer B', '2026-10-20', 'siang', 3, 'Finishing', '2026-10-20 15:00:00', '2026-10-20 17:00:00', 'draft', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (12, 1, 'SO-0012', 'Customer C', '2026-10-20', 'siang', 6, 'Dyeing', '2026-10-20 17:00:00', '2026-10-20 19:00:00', 'approved', NULL, '2026-10-21 03:06:10', '2026-10-22 03:37:00', NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (13, 2, 'SO-0013', 'Customer D', '2026-10-20', 'siang', 5, 'Dyeing', '2026-10-20 17:00:00', '2026-10-20 19:00:00', 'approved', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (14, 3, 'SO-0014', 'Customer E', '2026-10-20', 'siang', 4, 'Finishing', '2026-10-20 17:00:00', '2026-10-20 19:00:00', 'submitted', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (16, 2, 'SO-0016', 'Customer B', '2026-10-20', 'siang', 6, 'Dyeing', '2026-10-20 19:00:00', '2026-10-20 21:00:00', 'approved', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (17, 1, 'SO-0017', 'Customer C', '2026-10-20', 'malam', 7, 'Dyeing', '2026-10-20 23:00:00', '2026-10-21 01:00:00', 'approved', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (18, 2, 'SO-0018', 'Customer D', '2026-10-20', 'malam', 7, 'Dyeing', '2026-10-20 23:00:00', '2026-10-21 01:00:00', 'submitted', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (19, 3, 'SO-0019', 'Customer E', '2026-10-20', 'malam', 5, 'Finishing', '2026-10-20 23:00:00', '2026-10-21 01:00:00', 'draft', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (20, 1, 'SO-0020', 'Customer A', '2026-10-20', 'malam', 8, 'Dyeing', '2026-10-21 01:00:00', '2026-10-21 03:00:00', 'approved', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (21, 2, 'SO-0021', 'Customer B', '2026-10-20', 'malam', 8, 'Dyeing', '2026-10-21 01:00:00', '2026-10-21 03:00:00', 'approved', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (22, 3, 'SO-0022', 'Customer C', '2026-10-20', 'malam', 6, 'Finishing', '2026-10-21 01:00:00', '2026-10-21 03:00:00', 'submitted', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (23, 1, 'SO-0023', 'Customer D', '2026-10-20', 'malam', 9, 'Dyeing', '2026-10-21 03:00:00', '2026-10-21 05:00:00', 'draft', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `schedules` VALUES (24, 2, 'SO-0024', 'Customer E', '2026-10-20', 'malam', 9, 'Dyeing', '2026-10-21 03:00:00', '2026-10-21 05:00:00', 'approved', NULL, '2026-10-21 03:06:10', NULL, NULL, NULL, NULL, NULL, NULL, 'Produksi', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `type` varchar(31) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'string',
  `context` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of settings
-- ----------------------------

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `status_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `last_active` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `deleted_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'Admin', NULL, NULL, 0, '2026-10-22 10:11:58', '2026-10-21 02:40:51', '2026-10-21 02:40:51', NULL);
INSERT INTO `users` VALUES (2, 'Jana', NULL, NULL, 0, '2026-10-22 05:00:33', '2026-10-21 02:42:36', '2026-10-21 02:42:36', NULL);
INSERT INTO `users` VALUES (3, 'Bagus', NULL, NULL, 0, NULL, '2026-10-21 02:43:35', '2026-10-21 02:43:35', NULL);
INSERT INTO `users` VALUES (4, 'Eko', NULL, NULL, 0, NULL, '2026-10-21 02:45:34', '2026-10-21 02:45:34', NULL);
INSERT INTO `users` VALUES (5, 'Arief', NULL, NULL, 0, NULL, '2026-10-21 02:46:59', '2026-10-21 02:46:59', NULL);

SET FOREIGN_KEY_CHECKS = 1;
