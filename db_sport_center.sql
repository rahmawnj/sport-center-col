/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE IF NOT EXISTS `add_ons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `add_ons`;
INSERT INTO `add_ons` (`id`, `name`, `price`, `stock`, `deleted_at`, `created_at`, `updated_at`) VALUES
	(1, 'Raket Padel', 35000.00, 20, NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 'Handuk', 10000.00, 50, NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 'Bola Billiard Tambahan', 15000.00, 5, NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00');

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'NULL jika tamu/guest yang booking',
  `guest_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Wajib diisi jika user_id NULL',
  `guest_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Wajib diisi jika user_id NULL',
  `guest_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Wajib diisi jika user_id NULL',
  `court_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bookings_user_id_foreign` (`user_id`),
  KEY `bookings_court_id_foreign` (`court_id`),
  CONSTRAINT `bookings_court_id_foreign` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `bookings`;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `cache`;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `cache_locks`;

CREATE TABLE IF NOT EXISTS `cash_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('cash_in','cash_out','petty_cash_update','transfer_to_bank') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `handled_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_transactions_handled_by_foreign` (`handled_by`),
  CONSTRAINT `cash_transactions_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `cash_transactions`;

CREATE TABLE IF NOT EXISTS `courts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `facility_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('available','maintenance','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courts_facility_id_foreign` (`facility_id`),
  CONSTRAINT `courts_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `courts`;
INSERT INTO `courts` (`id`, `facility_id`, `name`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Padel Court A', 'available', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 1, 'Padel Court B', 'available', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 2, 'Billiard Table 1', 'available', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(4, 2, 'Billiard Table 2', 'available', '2026-10-10 20:56:00', '2026-10-10 20:56:00');

CREATE TABLE IF NOT EXISTS `daily_closings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `closing_date` date NOT NULL,
  `total_system_cash` decimal(15,2) NOT NULL,
  `total_actual_cash` decimal(15,2) NOT NULL,
  `total_bank_transfer` decimal(15,2) NOT NULL,
  `difference` decimal(15,2) NOT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_closings_closed_by_foreign` (`closed_by`),
  CONSTRAINT `daily_closings_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `daily_closings`;

CREATE TABLE IF NOT EXISTS `facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_bookable_online` tinyint(1) NOT NULL DEFAULT '1',
  `turnaround_minutes` int unsigned NOT NULL DEFAULT '5',
  `status` enum('active','maintenance','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facilities_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `facilities`;
INSERT INTO `facilities` (`id`, `name`, `icon`, `slug`, `is_bookable_online`, `turnaround_minutes`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Padel', NULL, 'padel', 1, 5, 'active', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 'Billiard', NULL, 'billiard', 1, 5, 'active', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 'Gym', NULL, 'gym', 1, 5, 'active', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(4, 'AC', NULL, 'ac', 0, 5, 'active', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(5, 'Free WiFi', NULL, 'free-wifi', 0, 5, 'active', '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(6, 'Loker', NULL, 'loker', 0, 5, 'active', '2026-10-10 20:56:00', '2026-10-10 20:56:00');

CREATE TABLE IF NOT EXISTS `facility_zone_space` (
  `zone_space_id` bigint unsigned NOT NULL,
  `facility_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`zone_space_id`,`facility_id`),
  KEY `facility_zone_space_facility_id_foreign` (`facility_id`),
  CONSTRAINT `facility_zone_space_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `facility_zone_space_zone_space_id_foreign` FOREIGN KEY (`zone_space_id`) REFERENCES `zone_spaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `facility_zone_space`;
INSERT INTO `facility_zone_space` (`zone_space_id`, `facility_id`, `created_at`, `updated_at`) VALUES
	(1, 4, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(1, 5, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(1, 6, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 4, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 5, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 6, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 4, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 5, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 6, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(4, 4, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(4, 5, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(4, 6, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(5, 4, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(5, 5, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(5, 6, '2026-10-10 20:56:00', '2026-10-10 20:56:00');

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `failed_jobs`;

CREATE TABLE IF NOT EXISTS `gate_access_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `zone_id` bigint unsigned NOT NULL,
  `action` enum('check_in','check_out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gate_access_logs_user_id_foreign` (`user_id`),
  KEY `gate_access_logs_zone_id_foreign` (`zone_id`),
  CONSTRAINT `gate_access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gate_access_logs_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `gate_access_logs`;

CREATE TABLE IF NOT EXISTS `iot_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_space_id` bigint unsigned NOT NULL,
  `device_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('online','offline') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offline',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `iot_devices_device_code_unique` (`device_code`),
  KEY `iot_devices_zone_space_id_foreign` (`zone_space_id`),
  CONSTRAINT `iot_devices_zone_space_id_foreign` FOREIGN KEY (`zone_space_id`) REFERENCES `zone_spaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `iot_devices`;

CREATE TABLE IF NOT EXISTS `iot_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `iot_device_id` bigint unsigned NOT NULL,
  `action` enum('turn_on','turn_off') COLLATE utf8mb4_unicode_ci NOT NULL,
  `triggered_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `iot_logs_iot_device_id_foreign` (`iot_device_id`),
  KEY `iot_logs_triggered_by_foreign` (`triggered_by`),
  CONSTRAINT `iot_logs_iot_device_id_foreign` FOREIGN KEY (`iot_device_id`) REFERENCES `iot_devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `iot_logs_triggered_by_foreign` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `iot_logs`;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `jobs`;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `job_batches`;

CREATE TABLE IF NOT EXISTS `membership_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `duration_days` int NOT NULL,
  `session_quota` int DEFAULT NULL COMMENT 'Batas kedatangan/sesi. Isi NULL jika Unlimited',
  `description` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `membership_packages`;

CREATE TABLE IF NOT EXISTS `member_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `member_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bisa untuk UID Kartu RFID/Barcode masuk Gym',
  `qr_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_profiles_user_id_unique` (`user_id`),
  UNIQUE KEY `member_profiles_member_code_unique` (`member_code`),
  CONSTRAINT `member_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `member_profiles`;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `migrations`;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2024_01_01_000000_create_passkeys_table', 1),
	(5, '2025_08_14_170933_add_two_factor_columns_to_users_table', 1),
	(6, '2025_08_15_000001_create_member_profiles_table', 1),
	(7, '2025_08_15_000002_create_facilities_table', 1),
	(8, '2025_08_15_000003_create_courts_table', 1),
	(9, '2025_08_15_000004_create_packages_table', 1),
	(10, '2025_08_15_000005_create_package_pricing_rules_table', 1),
	(11, '2025_08_15_000006_create_bookings_table', 1),
	(12, '2025_08_15_000007_create_user_memberships_table', 1),
	(13, '2025_08_15_000008_create_transactions_table', 1),
	(14, '2025_08_15_000009_add_duration_minutes_to_packages_table', 1),
	(15, '2025_08_15_000010_add_promo_fields_to_package_pricing_rules_table', 1),
	(16, '2025_08_15_000011_add_customer_name_to_transactions_table', 1),
	(17, '2025_08_15_000012_add_turnaround_minutes_to_facilities_table', 1),
	(18, '2025_08_15_000013_add_duration_days_to_packages_table', 1),
	(19, '2025_08_15_000014_add_invoice_and_qr_to_transactions_table', 1),
	(20, '2025_08_15_000015_regenerate_transaction_invoice_and_qr', 1),
	(21, '2025_08_15_000016_add_qr_code_to_member_profiles_table', 1),
	(22, '2025_08_15_000017_backfill_member_codes', 1),
	(23, '2026_08_19_053531_create_roles_table', 1),
	(24, '2026_08_19_053624_create_membership_system_tables', 1),
	(25, '2026_08_19_053709_create_zones_system_tables', 1),
	(26, '2026_08_19_053819_create_trainers_facilities_tables', 1),
	(27, '2026_08_19_053903_create_pricing_addons_tables', 1),
	(28, '2026_08_19_054015_create_transactions_tables', 1),
	(29, '2026_08_19_054118_create_finance_tables', 1),
	(30, '2026_08_19_054232_create_iot_tables', 1),
	(31, '2026_08_19_064814_add_role_relationship_to_users_table', 1),
	(32, '2026_08_19_230706_add_missing_membership_fields', 1),
	(33, '2026_08_19_230742_enhance_pricing_rates_and_create_gate_access_logs', 1),
	(34, '2026_09_07_000001_add_role_id_to_users_table', 1),
	(35, '2026_09_07_000002_add_used_sessions_to_user_subscriptions_table', 1),
	(36, '2026_09_07_000003_add_soft_deletes_to_users_table', 1),
	(37, '2026_09_07_000004_add_session_quota_to_membership_packages_table', 1),
	(38, '2026_09_08_000001_add_booking_status_to_transactions', 1),
	(39, '2026_09_08_000002_create_transaction_payment_proofs_table', 1),
	(40, '2026_10_08_072858_create_permission_tables', 1);

CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `model_has_permissions`;

CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `model_has_roles`;

CREATE TABLE IF NOT EXISTS `operational_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint NOT NULL COMMENT '1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat, 6=Sabtu, 7=Minggu',
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operational_hours_zone_id_foreign` (`zone_id`),
  CONSTRAINT `operational_hours_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `operational_hours`;

CREATE TABLE IF NOT EXISTS `packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `facility_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('session','membership','entry') COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_minutes` int unsigned NOT NULL DEFAULT '60',
  `duration_days` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `packages_facility_id_foreign` (`facility_id`),
  CONSTRAINT `packages_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `packages`;

CREATE TABLE IF NOT EXISTS `package_pricing_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint unsigned NOT NULL,
  `day_type` enum('weekday','weekend','all') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `start_time` time DEFAULT NULL COMMENT 'NULL jika berlaku seharian',
  `end_time` time DEFAULT NULL COMMENT 'NULL jika berlaku seharian',
  `price` decimal(15,2) NOT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_pricing_rules_package_id_foreign` (`package_id`),
  CONSTRAINT `package_pricing_rules_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `package_pricing_rules`;

CREATE TABLE IF NOT EXISTS `passkeys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential` json NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`),
  KEY `passkeys_user_id_index` (`user_id`),
  CONSTRAINT `passkeys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `passkeys`;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `password_reset_tokens`;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `permissions`;

CREATE TABLE IF NOT EXISTS `pricing_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_space_id` bigint unsigned NOT NULL,
  `rental_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `unit_type` enum('per_hour','per_visit','per_session') COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_booking_duration` int NOT NULL DEFAULT '1',
  `day_of_week` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Format CSV: 1,2,3,4,5 untuk Weekday. NULL = Tiap hari',
  `start_time` time DEFAULT NULL COMMENT 'Jam awal promo berlaku',
  `end_time` time DEFAULT NULL COMMENT 'Jam akhir promo berlaku',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `trainer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pricing_rates_zone_space_id_foreign` (`zone_space_id`),
  KEY `pricing_rates_trainer_id_foreign` (`trainer_id`),
  CONSTRAINT `pricing_rates_trainer_id_foreign` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pricing_rates_zone_space_id_foreign` FOREIGN KEY (`zone_space_id`) REFERENCES `zone_spaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `pricing_rates`;
INSERT INTO `pricing_rates` (`id`, `zone_space_id`, `rental_type`, `price`, `unit_type`, `min_booking_duration`, `day_of_week`, `start_time`, `end_time`, `is_active`, `created_at`, `updated_at`, `trainer_id`) VALUES
	(1, 1, 'Reguler', 150000.00, 'per_hour', 1, NULL, NULL, NULL, 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00', NULL),
	(2, 2, 'Reguler', 150000.00, 'per_hour', 1, NULL, NULL, NULL, 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00', NULL),
	(3, 3, 'Reguler', 50000.00, 'per_hour', 1, NULL, NULL, NULL, 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00', NULL),
	(4, 4, 'Reguler', 50000.00, 'per_hour', 1, NULL, NULL, NULL, 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00', NULL),
	(5, 5, 'Reguler', 35000.00, 'per_hour', 1, NULL, NULL, NULL, 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00', NULL);

CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `roles`;
INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
	(1, 'Superadmin', '2026-10-10 20:56:00', '2026-10-10 20:56:00');

CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `role_has_permissions`;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `sessions`;

CREATE TABLE IF NOT EXISTS `trainers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `trainers`;
INSERT INTO `trainers` (`id`, `name`, `specialty`, `deleted_at`, `created_at`, `updated_at`) VALUES
	(1, 'Coach Padel 1', 'Padel', NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 'Coach Gym 1', 'Personal Training', NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00');

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_type` enum('member','general') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `user_id` bigint unsigned DEFAULT NULL,
  `guest_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `payment_status` enum('unpaid','dp_paid','fully_paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `booking_status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `handled_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_booking_code_unique` (`booking_code`),
  KEY `transactions_user_id_foreign` (`user_id`),
  KEY `transactions_handled_by_foreign` (`handled_by`),
  CONSTRAINT `transactions_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `transactions`;

CREATE TABLE IF NOT EXISTS `transaction_add_ons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `add_on_id` bigint unsigned NOT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `price_rate` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_add_ons_transaction_id_foreign` (`transaction_id`),
  KEY `transaction_add_ons_add_on_id_foreign` (`add_on_id`),
  CONSTRAINT `transaction_add_ons_add_on_id_foreign` FOREIGN KEY (`add_on_id`) REFERENCES `add_ons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_add_ons_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `transaction_add_ons`;

CREATE TABLE IF NOT EXISTS `transaction_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `zone_space_id` bigint unsigned NOT NULL,
  `trainer_id` bigint unsigned DEFAULT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `price_rate` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_details_transaction_id_foreign` (`transaction_id`),
  KEY `transaction_details_zone_space_id_foreign` (`zone_space_id`),
  KEY `transaction_details_trainer_id_foreign` (`trainer_id`),
  CONSTRAINT `transaction_details_trainer_id_foreign` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaction_details_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_details_zone_space_id_foreign` FOREIGN KEY (`zone_space_id`) REFERENCES `zone_spaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `transaction_details`;

CREATE TABLE IF NOT EXISTS `transaction_payment_proofs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `payment_option` enum('full_payment','half_payment','pay_later') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_payment_proofs_transaction_id_foreign` (`transaction_id`),
  CONSTRAINT `transaction_payment_proofs_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `transaction_payment_proofs`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_profile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint unsigned NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `users`;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `image_profile`, `deleted_at`, `created_at`, `updated_at`, `role_id`, `phone`) VALUES
	(1, 'Superadmin', 'superadmin@example.com', '2026-10-10 20:56:00', '$2y$12$t4XGVyc60ylqL4eVVWTHbOMp/LA0jPswGxLDzpv31Yy/iAF.D8GkS', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00', 1, NULL);

CREATE TABLE IF NOT EXISTS `user_memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `package_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_memberships_user_id_foreign` (`user_id`),
  KEY `user_memberships_package_id_foreign` (`package_id`),
  CONSTRAINT `user_memberships_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_memberships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `user_memberships`;

CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `membership_package_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `used_sessions` int NOT NULL DEFAULT '0' COMMENT 'Jumlah sesi yang sudah dipakai',
  `status` enum('active','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_subscriptions_user_id_foreign` (`user_id`),
  KEY `user_subscriptions_membership_package_id_foreign` (`membership_package_id`),
  CONSTRAINT `user_subscriptions_membership_package_id_foreign` FOREIGN KEY (`membership_package_id`) REFERENCES `membership_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `user_subscriptions`;

CREATE TABLE IF NOT EXISTS `zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pricing_model` enum('per_person','per_space','per_trainer_session','per_table') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_online_bookable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `zones`;
INSERT INTO `zones` (`id`, `name`, `pricing_model`, `is_online_bookable`, `created_at`, `updated_at`) VALUES
	(1, 'Padel', 'per_space', 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 'Billiard', 'per_table', 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 'Gym', 'per_person', 1, '2026-10-10 20:56:00', '2026-10-10 20:56:00');

CREATE TABLE IF NOT EXISTS `zone_spaces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int NOT NULL DEFAULT '1',
  `status` enum('available','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `zone_spaces_zone_id_foreign` (`zone_id`),
  CONSTRAINT `zone_spaces_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `zone_spaces`;
INSERT INTO `zone_spaces` (`id`, `zone_id`, `name`, `capacity`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Padel Court A', 4, 'available', NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(2, 1, 'Padel Court B', 4, 'available', NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(3, 2, 'Billiard Table 1', 4, 'available', NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(4, 2, 'Billiard Table 2', 4, 'available', NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00'),
	(5, 3, 'Gym Floor', 20, 'available', NULL, '2026-10-10 20:56:00', '2026-10-10 20:56:00');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
