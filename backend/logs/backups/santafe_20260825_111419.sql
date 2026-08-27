-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: santafe_beach_club
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,'admin@beachclub.com','Logout','Logged out','::1','2026-08-24 23:07:31'),(2,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 23:07:40'),(3,'admin@beachclub.com','Logout','Logged out','::1','2026-08-24 23:09:13'),(4,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 23:09:24'),(5,'admin@beachclub.com','Logout','Logged out','::1','2026-08-24 23:10:35'),(6,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 23:10:52'),(7,'admin@beachclub.com','Logout','Logged out','::1','2026-08-24 23:16:09'),(8,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 23:16:17'),(9,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-25 10:20:13'),(10,'admin@beachclub.com','Login MFA','Completed 2FA verification successfully','::1','2026-08-25 18:13:47');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_otps`
--

DROP TABLE IF EXISTS `admin_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `otp_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_otp_lookup` (`admin_id`,`used`,`expires_at`),
  CONSTRAINT `admin_otps_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_otps`
--

LOCK TABLES `admin_otps` WRITE;
/*!40000 ALTER TABLE `admin_otps` DISABLE KEYS */;
INSERT INTO `admin_otps` VALUES (1,1,'5bcb1282f5b531e076f230da6295b98aaf1721b3f4468d8739f8aa60299f2117','2026-08-25 18:06:54',0,1,'2026-08-25 09:56:54'),(2,2,'85e32f2820d9bf99f8786b696ac9cece97eff19b128f9433e135f6fcbe63a526','2026-08-25 18:08:17',0,0,'2026-08-25 09:58:17'),(3,1,'1c398fd5e69ce767deda8ed2a6ea78cb0ab486bd033e0d9e030dc8884592d53c','2026-08-25 18:23:21',0,1,'2026-08-25 10:13:21');
/*!40000 ALTER TABLE `admin_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT 'receptionist',
  `email` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin@beachclub.com','$2y$12$C5Xp9v0jc8rBCkGcuDbKzO2vYufih6m9Trf8.ynnLvH0dyHNInN.6','2026-07-26 21:23:54','admin','Justinebatuhan017@gmail.com'),(2,'Justine@beachclub.com','$2y$12$pdUjUdN/BOwx2Fxi.twcYuGySJv89tjcojyd/kvwe62TSRuz0HaGm','2026-07-26 22:08:39','receptionist','Justinebatuhan017@gmail.com'),(3,'Isandro@beachclub.com','$2y$10$WC8von704uvQExpsHklrrOTNA4bqs233uMy0crr7Ij89HXSxQuwy6','2026-07-28 09:44:18','receptionist',NULL),(4,'John@beachclub.com','$2y$10$ufiI6yMLZ5rwGsYs1nyf0.Iqtz1zyvNR0TA70sujLnRTfIBuAOs/K','2026-07-29 09:02:36','receptionist',NULL),(6,'Jub@beachclub.com','$2y$10$pU/qMI6AwUIh8CO4xIsF8uuXLeMx8X5/bc8uf3wVgZjWF87BQ2mgO','2026-08-22 22:26:24','receptionist',NULL),(7,'Hermae@beachclub.com','$2y$10$oXw1rNUhJKmnsbBU3H6P2u8MW9XX8wYDDDXhFHJgsVn5768su7HUi','2026-08-23 15:13:36','receptionist',NULL);
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_name` varchar(100) NOT NULL,
  `guest_type` varchar(50) DEFAULT 'First Visit',
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests_count` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `accommodation_name` varchar(100) NOT NULL,
  `eta` varchar(10) DEFAULT '14:00',
  `status` varchar(20) DEFAULT 'Pending',
  `checked_in_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `checkin_token` varchar(64) DEFAULT NULL,
  `guest_email` varchar(150) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'Pay at Check-in',
  `cancellation_token` varchar(64) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `guest_country` varchar(50) DEFAULT NULL,
  `guest_special_requests` text DEFAULT NULL,
  `guest_notes` text DEFAULT NULL,
  `room_type_id` int(11) DEFAULT NULL,
  `checkout_notified_at` datetime DEFAULT NULL,
  `payment_deadline` datetime DEFAULT NULL,
  `payment_receipt_url` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'Pending',
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,'Justine Batuhan','First Visit','2026-08-24','2026-08-25',2,7,'Standard Room 107','14:00','Confirmed',NULL,NULL,'2026-08-24 15:09:04','2305088a744b8fa5c242f20180e91397','justinebatuhan017@gmail.com','GCash QR','941d686523acb048cc7cccd62d40999a',NULL,'9505223146','Philippines','',NULL,7,NULL,NULL,NULL,NULL,'Pending',NULL,NULL,0.00),(2,'Hermae Batuhan','Returning Guest','2026-08-24','2026-08-25',2,8,'Standard Room 108','14:00','Confirmed',NULL,NULL,'2026-08-24 15:25:22','29b070a825c08bde2a76393f0ab2bc00','justinebatuhan017@gmail.com','GCash QR','e21f0c21e24d9acd2b7550a854854281',NULL,'9505223146','Philippines','',NULL,7,NULL,NULL,NULL,NULL,'Pending',NULL,NULL,0.00);
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
INSERT INTO `gallery` VALUES (2,'6a6f063ca3cc9.jpg','2026-08-02 16:56:28'),(3,'6a6f38265ebe0.jpg','2026-08-02 20:29:26');
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guest_otps`
--

DROP TABLE IF EXISTS `guest_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guest_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `otp_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_guest_otp_lookup` (`booking_id`,`used`,`expires_at`),
  CONSTRAINT `guest_otps_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guest_otps`
--

LOCK TABLES `guest_otps` WRITE;
/*!40000 ALTER TABLE `guest_otps` DISABLE KEYS */;
INSERT INTO `guest_otps` VALUES (1,2,'081d5b1702b85bf81b523bc8257f3d5edad02cbba5cfa17173797a5413124523','2026-08-25 18:05:16',0,1,'2026-08-25 09:55:16'),(2,2,'763aa5039a1e590b3c1c0b116d7d54c5887e53fc753117c194ded2e09e81ca7a','2026-08-25 18:06:06',0,0,'2026-08-25 09:56:06');
/*!40000 ALTER TABLE `guest_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_name` varchar(150) NOT NULL,
  `guest_email` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'Unread',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(30) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'GCash Payment Pending Verification','Justine Batuhan paid via GCash for Standard Room 107 (REF-001). Please verify the receipt.','warning',1,'2026-08-24 23:09:07'),(2,2,'GCash Payment Pending Verification','Hermae Batuhan paid via GCash for Standard Room 108 (REF-002). Please verify the receipt.','warning',1,'2026-08-24 23:25:25');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_action_history`
--

DROP TABLE IF EXISTS `payment_action_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_action_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `performed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_history_payment` (`payment_id`),
  KEY `idx_payment_history_time` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_action_history`
--

LOCK TABLES `payment_action_history` WRITE;
/*!40000 ALTER TABLE `payment_action_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_action_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `guest_name` varchar(100) NOT NULL,
  `guest_email` varchar(150) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','verified','rejected','refunded') DEFAULT 'pending',
  `paid_at` datetime DEFAULT current_timestamp(),
  `accounting_status` varchar(20) DEFAULT 'deferred',
  `receipt_url` varchar(255) DEFAULT NULL,
  `amount_tendered` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'Justine Batuhan','justinebatuhan017@gmail.com',5.00,'GCash QR','TXN-59C376F7','verified','2026-08-24 23:09:04','deferred','uploads/receipts/gcash_rcpt_1669607f222fa8a7be0eb1f29667de66.png',NULL,NULL),(2,2,'Hermae Batuhan','justinebatuhan017@gmail.com',5.00,'GCash QR','TXN-E33C47DC','verified','2026-08-24 23:25:22','deferred','uploads/receipts/gcash_rcpt_dd955b5fad003eba002af0c2ec68a42b.jpg',NULL,NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pricing_rules`
--

DROP TABLE IF EXISTS `pricing_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pricing_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `room_type` varchar(50) DEFAULT 'all',
  `rule_type` varchar(20) NOT NULL DEFAULT 'weekend',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `days_of_week` varchar(30) DEFAULT '5,6,0',
  `adjustment_type` varchar(20) DEFAULT 'percent',
  `adjustment_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pricing_rules`
--

LOCK TABLES `pricing_rules` WRITE;
/*!40000 ALTER TABLE `pricing_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `pricing_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` varchar(20) DEFAULT 'percent',
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_action_time` (`ip_address`,`action`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
INSERT INTO `rate_limits` VALUES (1,'::1','calendar_api','2026-08-24 23:01:48'),(2,'::1','calendar_api','2026-08-24 23:31:36'),(3,'::1','calendar_api','2026-08-24 23:33:54'),(4,'::1','calendar_api','2026-08-24 23:33:55'),(5,'::1','calendar_api','2026-08-24 23:33:55'),(6,'::1','calendar_api','2026-08-24 23:33:56'),(7,'::1','calendar_api','2026-08-24 23:33:56'),(8,'::1','calendar_api','2026-08-24 23:33:57'),(9,'::1','calendar_api','2026-08-24 23:33:57'),(10,'::1','calendar_api','2026-08-24 23:33:57'),(11,'::1','calendar_api','2026-08-24 23:33:57'),(12,'::1','calendar_api','2026-08-24 23:35:16'),(13,'::1','calendar_api','2026-08-24 23:35:17'),(14,'::1','calendar_api','2026-08-24 23:36:03'),(15,'::1','calendar_api','2026-08-24 23:36:04'),(16,'::1','calendar_api','2026-08-24 23:36:04'),(17,'::1','calendar_api','2026-08-24 23:41:53'),(18,'::1','calendar_api','2026-08-24 23:41:56'),(19,'::1','calendar_api','2026-08-25 00:09:33'),(20,'::1','calendar_api','2026-08-25 00:12:25'),(21,'::1','calendar_api','2026-08-25 00:12:25'),(22,'::1','calendar_api','2026-08-25 00:23:31'),(23,'::1','calendar_api','2026-08-25 00:23:31'),(24,'::1','calendar_api','2026-08-25 00:23:32'),(25,'::1','calendar_api','2026-08-25 00:23:32'),(27,'::1','calendar_api','2026-08-25 18:21:35'),(28,'::1','calendar_api','2026-08-25 18:21:59'),(29,'::1','calendar_api','2026-08-25 18:22:03'),(30,'::1','calendar_api','2026-08-25 18:27:03'),(31,'::1','calendar_api','2026-08-25 18:30:02'),(32,'::1','calendar_api','2026-08-25 18:30:02'),(33,'::1','calendar_api','2026-08-25 18:30:03'),(34,'::1','calendar_api','2026-08-25 18:30:03'),(35,'::1','calendar_api','2026-08-25 18:30:32'),(26,'::1','login_attempt','2026-08-25 10:18:57');
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `total_rooms` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image_url` text DEFAULT NULL,
  `gallery_images` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=30461 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_types`
--

LOCK TABLES `room_types` WRITE;
/*!40000 ALTER TABLE `room_types` DISABLE KEYS */;
INSERT INTO `room_types` VALUES (1,'beachview_duplex',1,6900.00,'assets/rooms/beachview_duplex/beachview_duplex_6a6ff822ca283.png',NULL),(2,'beach_villa',3,7900.00,NULL,NULL),(5,'seaview_duplex',1,7900.00,NULL,NULL),(6,'standard_king',2,4300.00,NULL,NULL),(7,'standard_room',4,10.00,'assets/rooms/standard_room/standard_room_6a700236de3ae.png',NULL);
/*!40000 ALTER TABLE `room_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'ready',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=InnoDB AUTO_INCREMENT=66050 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'101','Beachview Duplex 101','beachview_duplex',6900.00,2,'ready'),(2,'102','Seaview Duplex 102','seaview_duplex',7900.00,2,'ready'),(3,'103','Beach Villa 103','beach_villa',7900.00,4,'ready'),(4,'104','Beach Villa 104','beach_villa',7900.00,4,'ready'),(5,'105','Beach Villa 105','beach_villa',7900.00,4,'ready'),(6,'106','Standard Family Room 106','standard_king',4300.00,4,'ready'),(7,'107','Standard Room 107','standard_room',10.00,2,'ready'),(8,'108','Standard Room 108','standard_room',10.00,2,'ready'),(11,'203','Standard Family Room 203','standard_king',4300.00,4,'ready'),(13,'109','Standard Room 109','standard_room',10.00,2,'ready'),(14,'110','Standard Room 110','standard_room',10.00,2,'ready');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `event_level` varchar(20) NOT NULL DEFAULT 'INFO',
  `username` varchar(100) DEFAULT 'anonymous',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `request_uri` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
INSERT INTO `security_logs` VALUES (1,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 23:07:40'),(2,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 23:09:24'),(3,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 23:10:52'),(4,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 23:16:17'),(5,'FAILED_LOGIN','WARNING','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Failed login attempt (bad password) for user: admin@beachclub.com','2026-08-25 10:18:57'),(6,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-25 10:20:13'),(7,'MFA_OTP_SENT','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','OTP dispatched for admin MFA (step 2)','2026-08-25 17:57:00'),(8,'MFA_OTP_SENT','INFO','justine@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','OTP dispatched for admin MFA (step 2)','2026-08-25 17:58:23'),(9,'MFA_OTP_SENT','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','OTP dispatched for admin MFA (step 2)','2026-08-25 18:13:28'),(10,'LOGIN_SUCCESS_MFA','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/verify_otp','Admin completed MFA and logged in (admin)','2026-08-25 18:13:47');
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('checkin_time','14:00','2026-07-26 21:41:16'),('checkout_time','12:00','2026-07-26 21:41:16'),('currency','PHP','2026-07-26 21:41:16'),('gcash_name','Ju****e B.','2026-08-22 16:48:07'),('gcash_number','0950 522 3146','2026-08-22 16:44:05'),('property_address','Barangay Poblacion, Santa Fe, Cebu','2026-07-26 21:41:16'),('property_email','info@santafebeachclub.com','2026-07-26 21:41:16'),('property_name','Santa Fe Beach Club','2026-07-26 21:41:16'),('property_phone','+63 32 123 4567','2026-07-26 21:41:16'),('property_timezone','Asia/Manila','2026-08-01 18:02:11');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'santafe_beach_club'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-25 19:14:20
