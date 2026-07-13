/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `bank_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `device_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `device_tokens` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_tokens_device_token_unique` (`device_token`),
  KEY `device_tokens_user_id_index` (`user_id`),
  CONSTRAINT `device_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `escrow_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `escrow_transactions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `held_amount` decimal(15,2) NOT NULL,
  `fee_amount` decimal(15,2) NOT NULL,
  `net_amount` decimal(15,2) NOT NULL,
  `status` enum('held','released','refunded','disputed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'held',
  `held_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `release_notes` text COLLATE utf8mb4_unicode_ci,
  `dispute_reason` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escrow_transactions_transaction_id_foreign` (`transaction_id`),
  KEY `escrow_transactions_payment_id_foreign` (`payment_id`),
  KEY `escrow_transactions_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `escrow_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `escrow_transactions_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `escrow_transactions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `images` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `imageable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `imageable_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `images_imageable_type_imageable_id_index` (`imageable_type`,`imageable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indonesia_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indonesia_cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indonesia_cities_code_unique` (`code`),
  KEY `indonesia_cities_province_code_foreign` (`province_code`),
  CONSTRAINT `indonesia_cities_province_code_foreign` FOREIGN KEY (`province_code`) REFERENCES `indonesia_provinces` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indonesia_districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indonesia_districts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city_code` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indonesia_districts_code_unique` (`code`),
  KEY `indonesia_districts_city_code_foreign` (`city_code`),
  CONSTRAINT `indonesia_districts_city_code_foreign` FOREIGN KEY (`city_code`) REFERENCES `indonesia_cities` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indonesia_provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indonesia_provinces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indonesia_provinces_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indonesia_villages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indonesia_villages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `district_code` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indonesia_villages_code_unique` (`code`),
  KEY `indonesia_villages_district_code_foreign` (`district_code`),
  CONSTRAINT `indonesia_villages_district_code_foreign` FOREIGN KEY (`district_code`) REFERENCES `indonesia_districts` (`code`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `offer_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_offer_id_foreign` (`offer_id`),
  KEY `messages_sender_id_foreign` (`sender_id`),
  KEY `messages_receiver_id_foreign` (`receiver_id`),
  CONSTRAINT `messages_offer_id_foreign` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `notifications_is_read_index` (`is_read`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `helper_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requester_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `initiated_by` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `offered_price` decimal(15,2) NOT NULL,
  `status` enum('pending','accepted','rejected','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offers_post_id_foreign` (`post_id`),
  KEY `offers_helper_id_foreign` (`helper_id`),
  KEY `offers_requester_id_foreign` (`requester_id`),
  KEY `offers_initiated_by_foreign` (`initiated_by`),
  CONSTRAINT `offers_helper_id_foreign` FOREIGN KEY (`helper_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offers_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offers_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offers_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `snap_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `va_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_midtrans_order_id_unique` (`midtrans_order_id`),
  KEY `payments_transaction_id_foreign` (`transaction_id`),
  CONSTRAINT `payments_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('request','offer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_multiple` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_user_id_foreign` (`user_id`),
  KEY `posts_category_id_foreign` (`category_id`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refunds` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `gateway_refund_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refunds_gateway_refund_id_unique` (`gateway_refund_id`),
  KEY `refunds_transaction_id_foreign` (`transaction_id`),
  KEY `refunds_payment_id_foreign` (`payment_id`),
  KEY `refunds_user_id_foreign` (`user_id`),
  CONSTRAINT `refunds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_posts` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reporter_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','investigating','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_posts_post_id_foreign` (`post_id`),
  KEY `report_posts_reporter_id_foreign` (`reporter_id`),
  CONSTRAINT `report_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_posts_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_transactions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reporter_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reported_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','investigating','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_transactions_transaction_id_foreign` (`transaction_id`),
  KEY `report_transactions_reporter_id_foreign` (`reporter_id`),
  KEY `report_transactions_reported_id_foreign` (`reported_id`),
  CONSTRAINT `report_transactions_reported_id_foreign` FOREIGN KEY (`reported_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_transactions_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_transactions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_users` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reporter_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reported_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','investigating','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_users_reporter_id_foreign` (`reporter_id`),
  KEY `report_users_reported_id_foreign` (`reported_id`),
  CONSTRAINT `report_users_reported_id_foreign` FOREIGN KEY (`reported_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_users_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `request_posts` (
  `post_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_price` decimal(15,2) NOT NULL,
  `max_price` decimal(15,2) NOT NULL,
  `deadline` datetime NOT NULL,
  `method_service` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_details` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` point NOT NULL /*!80003 SRID 4326 */,
  `published_at` timestamp NULL DEFAULT NULL,
  `published_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `province_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `district_id` bigint unsigned DEFAULT NULL,
  `village_id` bigint unsigned DEFAULT NULL,
  KEY `request_posts_post_id_foreign` (`post_id`),
  KEY `request_posts_province_id_foreign` (`province_id`),
  KEY `request_posts_city_id_foreign` (`city_id`),
  KEY `request_posts_district_id_foreign` (`district_id`),
  KEY `request_posts_village_id_foreign` (`village_id`),
  CONSTRAINT `request_posts_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `indonesia_cities` (`id`),
  CONSTRAINT `request_posts_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `indonesia_districts` (`id`),
  CONSTRAINT `request_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_posts_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `indonesia_provinces` (`id`),
  CONSTRAINT `request_posts_village_id_foreign` FOREIGN KEY (`village_id`) REFERENCES `indonesia_villages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewer_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewed_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_transaction_id_foreign` (`transaction_id`),
  KEY `reviews_reviewer_id_foreign` (`reviewer_id`),
  KEY `reviews_reviewed_id_foreign` (`reviewed_id`),
  CONSTRAINT `reviews_reviewed_id_foreign` FOREIGN KEY (`reviewed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_posts` (
  `post_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_price` decimal(15,2) NOT NULL,
  `working_hours` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `portfolio_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experience_years` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `address_details` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` point NOT NULL /*!80003 SRID 4326 */,
  `status` enum('active','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `province_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `district_id` bigint unsigned DEFAULT NULL,
  `village_id` bigint unsigned DEFAULT NULL,
  KEY `service_posts_post_id_foreign` (`post_id`),
  KEY `service_posts_province_id_foreign` (`province_id`),
  KEY `service_posts_city_id_foreign` (`city_id`),
  KEY `service_posts_district_id_foreign` (`district_id`),
  KEY `service_posts_village_id_foreign` (`village_id`),
  CONSTRAINT `service_posts_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `indonesia_cities` (`id`),
  CONSTRAINT `service_posts_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `indonesia_districts` (`id`),
  CONSTRAINT `service_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_posts_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `indonesia_provinces` (`id`),
  CONSTRAINT `service_posts_village_id_foreign` FOREIGN KEY (`village_id`) REFERENCES `indonesia_villages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `skill_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skill_users` (
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `skill_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`skill_id`),
  KEY `skill_users_skill_id_foreign` (`skill_id`),
  CONSTRAINT `skill_users_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `skill_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skills_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaction_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_revisions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revision_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','fixed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `revision_deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_revisions_transaction_id_foreign` (`transaction_id`),
  CONSTRAINT `transaction_revisions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `offer_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requester_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `helper_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `final_price` decimal(15,2) NOT NULL,
  `admin_fee` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `deadline` datetime NOT NULL,
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `max_revision` int NOT NULL DEFAULT '2',
  `work_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','on_progress','pending_approval','pending_revision','revision','pending_refund','completed','disputed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_offer_id_foreign` (`offer_id`),
  KEY `transactions_requester_id_foreign` (`requester_id`),
  KEY `transactions_helper_id_foreign` (`helper_id`),
  CONSTRAINT `transactions_helper_id_foreign` FOREIGN KEY (`helper_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_offer_id_foreign` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `accepted_term_condition_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `neighborhood_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_profile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ktp_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wallet_balance` decimal(15,2) DEFAULT '0.00',
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','active','inactive','banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `province_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `district_id` bigint unsigned DEFAULT NULL,
  `village_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_province_id_foreign` (`province_id`),
  KEY `users_city_id_foreign` (`city_id`),
  KEY `users_district_id_foreign` (`district_id`),
  KEY `users_village_id_foreign` (`village_id`),
  CONSTRAINT `users_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `indonesia_cities` (`id`),
  CONSTRAINT `users_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `indonesia_districts` (`id`),
  CONSTRAINT `users_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `indonesia_provinces` (`id`),
  CONSTRAINT `users_village_id_foreign` FOREIGN KEY (`village_id`) REFERENCES `indonesia_villages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawals` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_account_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `admin_fee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','processing','completed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `processed_by` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `withdrawals_user_id_foreign` (`user_id`),
  KEY `withdrawals_bank_account_id_foreign` (`bank_account_id`),
  KEY `withdrawals_processed_by_foreign` (`processed_by`),
  CONSTRAINT `withdrawals_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `withdrawals_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `withdrawals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2016_08_03_072729_create_provinces_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2016_08_03_072750_create_cities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2016_08_03_072804_create_districts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2016_08_03_072819_create_villages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_04_24_143224_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_04_24_145938_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_04_24_151851_create_skills_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_04_24_153848_create_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_04_25_020906_create_request_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_04_25_025500_create_service_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_04_25_025939_create_offers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_04_25_030938_create_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_04_25_034353_create_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_04_25_035059_create_reviews_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_04_25_035319_create_report_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_04_25_040741_create_skill_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_05_09_163435_create_report_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_05_09_164100_create_images_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_05_10_151011_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_05_10_155550_create_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_05_10_160018_create_transaction_revisions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_05_10_161106_create_banners_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_05_15_111337_add_location_service_post_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_05_15_112154_add_date_term_condition_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_05_15_120929_change_type_data_image_morph_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_05_15_152325_delete_status_post_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_05_15_152432_add_status_service_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_05_15_152544_add_status_request_post_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_05_24_041913_create_expired_post_table_request_posts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_06_09_074023_create_device_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_06_09_074024_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_06_17_141131_add_location_ids_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_06_17_141248_remove_old_location_columns_from_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_06_20_133235_change_type_enum_posts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_06_24_121949_add_location_ids_to_request_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_06_24_122159_remove_old_location_columns_from_request_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_06_24_122629_add_location_ids_to_service_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_06_24_123423_remove_old_location_columns_from_service_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_06_27_164227_add_type_field_to_table_image',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_06_28_234411_create_bank_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_06_29_000001_add_midtrans_fields_to_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_06_29_000002_create_escrow_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_07_05_064818_create_report_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_07_07_081111_add_pending_approval_to_transaction_status_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_07_08_175304_add_pending_revision_to_transaction_status_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_07_08_175321_add_pending_revision_to_transaction_status_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_07_08_214751_add_pending_refund_to_transaction_status_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_07_09_154800_add_completed_to_offer_status_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_07_10_041100_create_withdrawals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_07_11_063936_add_revision_deadline_to_transaction_revisions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_07_11_064040_add_revision_status_to_transactions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_07_11_095559_add_completion_notes_to_transaction_revisions_table',3);
