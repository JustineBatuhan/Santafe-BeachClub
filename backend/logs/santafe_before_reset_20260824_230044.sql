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
) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (117,'admin','Logout','Logged out','::1','2026-08-01 12:12:08'),(118,'Justine','Login','Logged in successfully','::1','2026-08-01 12:12:14'),(119,'Justine','Logout','Logged out','::1','2026-08-01 12:12:53'),(120,'admin','Login','Logged in successfully','::1','2026-08-01 12:13:05'),(121,'admin','Login','Logged in successfully','::1','2026-08-01 17:44:49'),(122,'admin','Gallery Photo Added','Added: 279681050_479617070625211_7375756857409074021_n.jpg','::1','2026-08-02 16:38:54'),(123,'admin','Gallery Photo Added','Added: 279681050_479617070625211_7375756857409074021_n.jpg','::1','2026-08-02 16:56:28'),(124,'admin','Gallery Photo Deleted','Removed: 6a6f021e452e3.jpg','::1','2026-08-02 16:56:37'),(125,'admin','Logout','Logged out','::1','2026-08-02 18:13:33'),(126,'Justine','Login','Logged in successfully','::1','2026-08-02 18:13:38'),(127,'Justine','Logout','Logged out','::1','2026-08-02 18:19:03'),(128,'admin','Login','Logged in successfully','::1','2026-08-02 18:19:07'),(129,'admin','Logout','Logged out','::1','2026-08-02 18:24:36'),(130,'Justine','Login','Logged in successfully','::1','2026-08-02 18:24:42'),(131,'Justine','Logout','Logged out','::1','2026-08-02 18:29:02'),(132,'admin','Login','Logged in successfully','::1','2026-08-02 18:29:13'),(133,'admin','Gallery Photo Added','Added: hero-slide-3.jpg','::1','2026-08-02 20:29:26'),(134,'Justine','Login','Logged in successfully','::1','2026-08-02 20:30:49'),(135,'Justine','Logout','Logged out','::1','2026-08-02 20:31:54'),(136,'admin','Login','Logged in successfully','::1','2026-08-02 20:32:00'),(137,'admin','Logout','Logged out','::1','2026-08-02 20:41:16'),(138,'Justine','Login','Logged in successfully','::1','2026-08-02 20:41:22'),(139,'Justine','Logout','Logged out','::1','2026-08-02 20:41:31'),(140,'admin','Login','Logged in successfully','::1','2026-08-02 20:41:39'),(141,'Justine','Login','Logged in successfully','192.168.1.7','2026-08-03 09:35:44'),(142,'admin','Room Photo Updated','Primary photo for beachview_duplex set to assets/rooms/beachview_duplex/beachview_duplex_6a6ff822ca283.png','::1','2026-08-03 10:08:34'),(143,'admin','Room Photo Updated','Primary photo for standard_room set to assets/rooms/standard_room/standard_room_6a6ffef28bdfb.png','::1','2026-08-03 10:37:38'),(144,'admin','Room Photo Updated','Primary photo for standard_room set to assets/rooms/standard_room/standard_room_6a70022b6a0f4.png','::1','2026-08-03 10:51:23'),(145,'admin','Room Photo Updated','Primary photo for standard_room set to assets/rooms/standard_room/standard_room_6a70022d45a85.png','::1','2026-08-03 10:51:25'),(146,'admin','Room Photo Updated','Primary photo for standard_room set to assets/rooms/standard_room/standard_room_6a70022f5e234.png','::1','2026-08-03 10:51:27'),(147,'admin','Room Photo Updated','Primary photo for standard_room set to assets/rooms/standard_room/standard_room_6a7002347ab91.png','::1','2026-08-03 10:51:32'),(148,'admin','Room Photo Updated','Primary photo for standard_room set to assets/rooms/standard_room/standard_room_6a700236de3ae.png','::1','2026-08-03 10:51:34'),(149,'admin','Check-in Guest','Checked in booking ID #45','::1','2026-08-03 11:16:02'),(150,'admin','Logout','Logged out','::1','2026-08-03 18:17:52'),(151,'Justine','Login','Logged in successfully','::1','2026-08-03 18:18:08'),(152,'Justine','Logout','Logged out','::1','2026-08-03 18:19:28'),(153,'admin','Login','Logged in successfully','::1','2026-08-03 18:19:39'),(154,'admin','Login','Logged in successfully','10.79.147.182','2026-08-04 13:05:10'),(155,'admin','Login','Logged in successfully','10.0.25.14','2026-08-04 14:59:46'),(156,'admin','Check-in Guest','Checked in booking ID #46','10.0.25.14','2026-08-04 15:06:40'),(157,'admin','Login','Logged in successfully','::1','2026-08-04 17:42:39'),(158,'admin','Check-in Guest','Checked in booking ID #47','::1','2026-08-04 17:45:19'),(159,'admin','Login','Logged in successfully','192.168.1.12','2026-08-09 17:01:45'),(160,'admin','Check-in Guest','Checked in booking ID #50','192.168.1.12','2026-08-09 17:07:54'),(161,'admin','Login','Logged in successfully','::1','2026-08-18 18:54:23'),(162,'admin','Logout','Logged out','::1','2026-08-18 19:10:20'),(163,'Justine','Login','Logged in successfully','::1','2026-08-18 19:10:33'),(164,'Justine','Logout','Logged out','::1','2026-08-18 19:13:47'),(165,'admin','Login','Logged in successfully','::1','2026-08-18 19:13:52'),(166,'admin','Check-in Guest','Checked in booking ID #53','::1','2026-08-18 20:03:46'),(167,'admin','Logout','Logged out','::1','2026-08-18 21:11:57'),(168,'Justine','Login','Logged in successfully','::1','2026-08-18 21:12:09'),(169,'Justine','Logout','Logged out','::1','2026-08-18 21:23:07'),(170,'admin','Login','Logged in successfully','::1','2026-08-18 21:23:12'),(171,'admin','Walk-in Check-in','Checked in walk-in guest Isandro Batiancila into Standard Room 107 (Booking #3)','::1','2026-08-18 21:27:06'),(172,'admin','Check-in Guest','Checked in booking ID #2','::1','2026-08-18 22:06:06'),(173,'admin','Login','Logged in successfully','::1','2026-08-21 09:04:39'),(174,'admin','Logout','Logged out','::1','2026-08-21 09:39:44'),(175,'admin','Login','Logged in successfully','::1','2026-08-21 09:39:51'),(176,'admin','Logout','Logged out','::1','2026-08-21 11:50:43'),(177,'admin','Login','Logged in successfully','::1','2026-08-21 12:49:52'),(178,'admin','Logout','Logged out','::1','2026-08-21 13:12:56'),(179,'Justine','Login','Logged in successfully','::1','2026-08-21 13:13:25'),(180,'Justine','Logout','Logged out','::1','2026-08-21 13:14:03'),(181,'admin','Login','Logged in successfully','::1','2026-08-21 13:14:10'),(182,'admin','Check-in Guest','Checked in booking ID #4','::1','2026-08-21 13:32:12'),(183,'admin','Check-in Guest','Checked in booking ID #5','::1','2026-08-21 14:24:02'),(184,'admin','Check-in Guest','Checked in booking ID #6','::1','2026-08-21 14:33:17'),(185,'admin','Logout','Logged out','::1','2026-08-22 08:37:47'),(186,'admin','Login','Logged in successfully','::1','2026-08-22 08:38:02'),(187,'justinebatuhan@beachclub.com','Login','Logged in successfully','::1','2026-08-22 08:58:04'),(188,'justinebatuhan@beachclub.com','Staff Created','Added receptionist account: Isandro','::1','2026-08-22 08:58:30'),(189,'justinebatuhan@beachclub.com','Staff Deleted','Removed account: Isandro','::1','2026-08-22 08:59:14'),(190,'justinebatuhan@beachclub.com','Password Reset','Reset password for: Justinebatuhan@beachclub.com','::1','2026-08-22 08:59:32'),(191,'admin@beachclub.com','Payment Refunded','Refunded PHP 3,450.00 for payment ID 10 (Guest: hehehe Batuhan). Reason: No show — policy refund','::1','2026-08-22 10:11:49'),(192,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 11:51:57'),(193,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 11:52:34'),(194,'admin@beachclub.com','Room Price Updated','Price for standard_room set to 1','::1','2026-08-22 14:12:27'),(195,'admin@beachclub.com','Room Price Updated','Price for standard_room set to 10','::1','2026-08-22 14:12:39'),(196,'admin@beachclub.com','Room Price Updated','Price for standard_room set to 10','::1','2026-08-22 15:36:03'),(197,'admin@beachclub.com','Login','Logged in successfully','192.168.1.8','2026-08-22 17:48:15'),(198,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 21:51:55'),(199,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 21:52:09'),(200,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 21:54:49'),(201,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 21:55:56'),(202,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:09:13'),(203,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:09:36'),(204,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:09:50'),(205,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:10:02'),(206,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:11:41'),(207,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:12:42'),(208,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:12:47'),(209,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:13:52'),(210,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:14:00'),(211,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:14:18'),(212,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:16:41'),(213,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:16:52'),(214,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:17:24'),(215,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:20:18'),(216,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:21:30'),(217,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:22:51'),(218,'admin@beachclub.com','Staff Created','Added receptionist account: Jub@beachclub.com','::1','2026-08-22 22:26:24'),(219,'admin@beachclub.com','Logout','Logged out','::1','2026-08-22 22:26:30'),(220,'Jub@beachclub.com','Login','Logged in successfully','::1','2026-08-22 22:26:40'),(221,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-23 13:06:16'),(222,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-23 13:11:38'),(223,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-23 14:48:11'),(224,'admin@beachclub.com','Staff Created','Added receptionist account: Hermae@beachclub.com','::1','2026-08-23 15:13:36'),(225,'admin@beachclub.com','Logout','Logged out','::1','2026-08-23 15:13:43'),(226,'Hermae@beachclub.com','Login','Logged in successfully','::1','2026-08-23 15:13:55'),(227,'Hermae@beachclub.com','Logout','Logged out','::1','2026-08-23 15:14:09'),(228,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-23 15:14:24'),(229,'admin@beachclub.com','Logout','Logged out','::1','2026-08-23 15:21:59'),(230,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-23 15:22:16'),(231,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 09:28:04'),(232,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 20:25:25'),(233,'admin@beachclub.com','Logout','Logged out','::1','2026-08-24 20:28:32'),(234,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 20:31:16'),(235,'admin@beachclub.com','Logout','Logged out','::1','2026-08-24 20:41:31'),(236,'justine@beachclub.com','Login','Logged in successfully','::1','2026-08-24 20:41:51'),(237,'justine@beachclub.com','Logout','Logged out','::1','2026-08-24 20:42:07'),(238,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 20:42:16'),(239,'admin@beachclub.com','Login','Logged in successfully','::1','2026-08-24 22:42:51');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin@beachclub.com','$2y$10$Z4wwyU57PNFtaliPW680COkSTmGXYPqQIM5cmfQRMc8GVGCjc/H8K','2026-07-26 21:23:54','admin'),(2,'Justine@beachclub.com','$2y$10$aorJYl6/7G/3nyaEkDCXMuP3WGjvtyIwxWNjXXvOjXJbOmFXS91FO','2026-07-26 22:08:39','receptionist'),(3,'Isandro@beachclub.com','$2y$10$WC8von704uvQExpsHklrrOTNA4bqs233uMy0crr7Ij89HXSxQuwy6','2026-07-28 09:44:18','receptionist'),(4,'John@beachclub.com','$2y$10$ufiI6yMLZ5rwGsYs1nyf0.Iqtz1zyvNR0TA70sujLnRTfIBuAOs/K','2026-07-29 09:02:36','receptionist'),(6,'Jub@beachclub.com','$2y$10$pU/qMI6AwUIh8CO4xIsF8uuXLeMx8X5/bc8uf3wVgZjWF87BQ2mgO','2026-08-22 22:26:24','receptionist'),(7,'Hermae@beachclub.com','$2y$10$oXw1rNUhJKmnsbBU3H6P2u8MW9XX8wYDDDXhFHJgsVn5768su7HUi','2026-08-23 15:13:36','receptionist');
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
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,'Hermae Batuhan','First Visit','2026-08-18','2026-08-19',2,7,'Standard Room 107','14:00','Cancelled','2026-08-18 12:33:38','fb0906bf38d6afdf04ad122e21b23d30','justinebatuhan017@gmail.com','GCash','a33e1aa1ba235c62afeb08cfe00580b0',NULL,'092161721231','Philippines','dasd',NULL,7,NULL,NULL,NULL,NULL,'Pending'),(2,'Justine Batuhan','First Visit','2026-08-18','2026-08-19',2,8,'Standard Room 108','14:00','Checked Out','2026-08-18 12:34:31','9896d5f23fa78b03439cf56ef5aa9638','justinebatuhan017@gmail.com','GCash','df2dfc3b2f01e9fadefe56e257dc6165',NULL,'092161721231','Philippines','dasd',NULL,7,'2026-08-21 09:06:09',NULL,NULL,NULL,'Pending'),(3,'Isandro Batiancila','Walk-in','2026-08-18','2026-08-22',1,7,'Standard Room 107','21:27','Checked Out','2026-08-18 13:27:06','a539a42e95fc4c195b1e923c5964072c','justinebatuhan017@gmail.com','Front Desk Cash','edb025139f60714aeeb98fc9f24c4149',NULL,'09505223146',NULL,NULL,NULL,7,NULL,NULL,NULL,NULL,'Pending'),(4,'Justine Batuhan','VIP Member','2026-08-21','2026-08-22',2,2,'Seaview Duplex 102','14:00','Checked Out','2026-08-21 05:29:42','91a51d25a8add3b47a13c9020d467bcd','justinebatuhan017@gmail.com','GCash','4c435e5d1ef5d9913f5a601fdd84ac3a',NULL,'09505223146','Philippines','sdasdas',NULL,5,NULL,NULL,NULL,NULL,'Pending'),(5,'jub Batuhan','VIP Member','2026-08-22','2026-08-23',2,1,'Beachview Duplex 101','14:00','Checked Out','2026-08-21 06:22:56','91ac68b92818f6c6eca80fa3e94e9612','justinebatuhan017@gmail.com','GCash','909daf03c314c8b8342037ce7fd5ba48',NULL,'09505223146','Philippines','sdasdas',NULL,1,NULL,NULL,NULL,NULL,'Pending'),(6,'hehehe Batuhan','VIP Member','2026-09-02','2026-09-03',2,1,'Beachview Duplex 101','14:00','Cancelled','2026-08-21 06:26:14','3bfaafc8fa604886e7976285d7199ccc','justinebatuhan017@gmail.com','GCash','a51f9cff582e8eb4556bc61b0ca33bd3',NULL,'09505223146','Philippines','sdasdas',NULL,1,NULL,NULL,NULL,NULL,'Pending'),(7,'John Batuhan','VIP Member','2026-08-22','2026-08-23',2,2,'Seaview Duplex 102','14:00','Cancelled','2026-08-22 05:54:28','d522144f72da6f211eedee6068dce95e','justinebatuhan017@gmail.com','Online Payment','e21b0cd96d71a281fb10879eeab539bb',NULL,'092161721231','Philippines','dasdasdas',NULL,5,NULL,NULL,NULL,NULL,'Pending'),(8,'John Batuhan','VIP Member','2026-08-22','2026-08-23',2,7,'Standard Room 107','14:00','Cancelled','2026-08-22 09:00:35','2d8112348aab4cca1d0a929fb98870e6','justinebatuhan017@gmail.com','GCash QR','0f9747b011f985ebd55cbfa19fa75bbd',NULL,'092161721231','Philippines','dasdasdas',NULL,7,NULL,NULL,NULL,NULL,'Pending'),(9,'Jones Batuhan','VIP Member','2026-08-22','2026-08-23',2,8,'Standard Room 108','14:00','Confirmed','2026-08-22 09:44:59','8bfabd2f4435bc479f51b517e7635247','justinebatuhan017@gmail.com','GCash QR','85f17d82839f15b4595973b9507c9531',NULL,'092161721231','Philippines','dasdasdas',NULL,7,NULL,NULL,NULL,NULL,'Pending'),(10,'Luis Batuhan','VIP Member','2026-08-22','2026-08-23',2,7,'Standard Room 107','14:00','Confirmed','2026-08-22 11:10:43','0cd56f8ddbc91434cbaf5e46bb382339','justinebatuhan017@gmail.com','GCash QR','287f712da70ab86f30b41e9a9dcbefe4',NULL,'092161721231','Philippines','dasdasdas',NULL,7,NULL,NULL,NULL,NULL,'Pending'),(11,'Justine Batuhan','First Visit','2026-08-23','2026-08-24',2,7,'Standard Room 107','14:00','Confirmed','2026-08-23 13:58:49','9992719751e09a92c76f83a314dea3fa','justinebatuhan5@gmail.com','GCash QR','84477c0af6c09221f4326a57363b1970',NULL,'09505223146','Philippines','sadsadas',NULL,7,NULL,NULL,NULL,NULL,'Pending');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
INSERT INTO `inquiries` VALUES (1,'Justine Batuhan','justinebatuhan017@gmail.com','I can i ask if where is the church','Hi how why my booking is not proceed','Resolved','2026-08-02 16:57:11'),(2,'Justine Batuhan','justinebatuhan017@gmail.com','dasd','sadasdasdasd','Unread','2026-08-23 15:34:59'),(3,'neonel bayot','justinebatuhan017@gmail.com','Mag pap pyo ko','HAHAHAHAH','Unread','2026-08-24 09:39:01');
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
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (21,6,'Payment Refunded','Payment INV-10010 for guest hehehe Batuhan has been marked as refunded. Reason: No show — policy refund','warning',1,'2026-08-22 10:11:49'),(22,7,'Online Payment Pending','John Batuhan initiated an online payment for Seaview Duplex 102 (REF-007). Awaiting webhook confirmation.','info',1,'2026-08-22 13:54:31'),(23,8,'GCash Payment Pending Verification','John Batuhan paid via GCash for Standard Room 107 (REF-008). Please verify the receipt.','warning',1,'2026-08-22 17:00:37'),(24,9,'GCash Payment Pending Verification','Jones Batuhan paid via GCash for Standard Room 108 (REF-009). Please verify the receipt.','warning',1,'2026-08-22 17:45:03'),(25,10,'GCash Payment Pending Verification','Luis Batuhan paid via GCash for Standard Room 107 (REF-010). Please verify the receipt.','warning',1,'2026-08-22 19:10:51'),(26,NULL,'Room maintenance cleared','Room 101 (Beachview Duplex 101) was cleared from maintenance by admin@beachclub.com. Reception: this room is now available for assignment.','success',1,'2026-08-23 15:29:29'),(27,11,'GCash Payment Pending Verification','Justine Batuhan paid via GCash for Standard Room 107 (REF-011). Please verify the receipt.','warning',1,'2026-08-23 21:58:51');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'Hermae Batuhan','justinebatuhan017@gmail.com',1450.00,'GCash','989723128763231','rejected','2026-08-18 20:33:38','deferred','uploads/receipts/receipt_1787056418_9183.jpg',NULL,NULL),(2,2,'Justine Batuhan','justinebatuhan017@gmail.com',1450.00,'GCash QR','2312313213213213','verified','2026-08-18 20:34:31','deferred','uploads/receipts/receipt_1787056471_3148.png',NULL,NULL),(3,3,'Isandro Batiancila','justinebatuhan017@gmail.com',11600.00,'Front Desk Cash','TXN-02C9EAA5','verified','2026-08-18 21:27:06','deferred',NULL,NULL,NULL),(4,2,'Justine Batuhan','justinebatuhan017@gmail.com',1450.00,'Front Desk Cash','TXN-A43AA80F','verified','2026-08-18 22:06:06','deferred',NULL,NULL,NULL),(5,4,'Justine Batuhan','justinebatuhan017@gmail.com',3950.00,'GCash QR','989723128763231','verified','2026-08-21 13:29:42','deferred','uploads/receipts/rcpt_2e3dcb6e30e6c30f0df22f60109ec193.png',NULL,NULL),(6,4,'Justine Batuhan','justinebatuhan017@gmail.com',3950.00,'Front Desk Cash','TXN-71910007','verified','2026-08-21 13:32:12','deferred',NULL,NULL,NULL),(7,5,'jub Batuhan','justinebatuhan017@gmail.com',3450.00,'GCash QR','989723128763231','verified','2026-08-21 14:22:56','deferred','uploads/receipts/rcpt_5ce4e042a185029f7ae92a549a4d4e2b.png',NULL,NULL),(8,5,'jub Batuhan','justinebatuhan017@gmail.com',3450.00,'Front Desk Cash','TXN-022B0D38','verified','2026-08-21 14:24:02','deferred',NULL,NULL,NULL),(9,6,'hehehe Batuhan','justinebatuhan017@gmail.com',3450.00,'GCash QR','2312313213213213','verified','2026-08-21 14:26:14','deferred','uploads/receipts/rcpt_5256d632dd13a9ca695f97d098e7e8d6.png',NULL,NULL),(10,6,'hehehe Batuhan','justinebatuhan017@gmail.com',3450.00,'Front Desk Cash','TXN-3B32B7AF','refunded','2026-08-21 14:33:17','deferred',NULL,NULL,NULL),(11,7,'John Batuhan','justinebatuhan017@gmail.com',3950.00,'Online Payment','link_d3a69560b96d4743676e4e1c','rejected','2026-08-22 13:54:29','deferred','https://pm.link/org-iLPyopc2xVdMNXKWjXqqLNBi/test/rIwcLSz',NULL,NULL),(12,8,'John Batuhan','justinebatuhan017@gmail.com',5.00,'GCash QR','TXN-A44AC36D','rejected','2026-08-22 17:00:35','deferred','uploads/receipts/gcash_rcpt_d5e4fbeeb2a3e55eb069f4512701ba67.png',NULL,NULL),(13,9,'Jones Batuhan','justinebatuhan017@gmail.com',5.00,'GCash QR','TXN-3E08193A','verified','2026-08-22 17:44:59','deferred','uploads/receipts/gcash_rcpt_a33bcbb105d62608c29724e8f3bcc0cc.png',NULL,NULL),(14,10,'Luis Batuhan','justinebatuhan017@gmail.com',5.00,'GCash QR','TXN-6EBF9131','verified','2026-08-22 19:10:43','deferred','uploads/receipts/gcash_rcpt_4885ad6284c91a5a8f83594e3aacfb7f.png',NULL,NULL),(15,11,'Justine Batuhan','justinebatuhan5@gmail.com',5.00,'GCash QR','TXN-127ACAA8','verified','2026-08-23 21:58:49','deferred','uploads/receipts/gcash_rcpt_de7fbdef9c4b540bddae878448d4810b.png',NULL,NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
INSERT INTO `rate_limits` VALUES (182,'::1','calendar_api','2026-08-22 22:28:08'),(183,'::1','calendar_api','2026-08-23 15:12:55'),(184,'::1','calendar_api','2026-08-23 15:12:57'),(185,'::1','calendar_api','2026-08-23 15:14:04'),(186,'::1','calendar_api','2026-08-23 15:17:28'),(187,'::1','calendar_api','2026-08-23 15:29:26'),(188,'::1','calendar_api','2026-08-24 09:31:59'),(189,'::1','calendar_api','2026-08-24 09:32:08'),(190,'::1','calendar_api','2026-08-24 09:32:09'),(126,'::1','calendar_api','2026-08-24 15:01:50'),(127,'::1','calendar_api','2026-08-24 15:01:51'),(128,'::1','calendar_api','2026-08-24 15:01:51'),(129,'::1','calendar_api','2026-08-24 15:01:51'),(130,'::1','calendar_api','2026-08-24 15:01:51'),(131,'::1','calendar_api','2026-08-24 15:01:51'),(132,'::1','calendar_api','2026-08-24 15:01:52'),(133,'::1','calendar_api','2026-08-24 15:01:52'),(134,'::1','calendar_api','2026-08-24 15:01:52'),(135,'::1','calendar_api','2026-08-24 15:01:52'),(136,'::1','calendar_api','2026-08-24 15:01:52'),(137,'::1','calendar_api','2026-08-24 15:01:52'),(138,'::1','calendar_api','2026-08-24 15:01:53'),(139,'::1','calendar_api','2026-08-24 15:01:53'),(140,'::1','calendar_api','2026-08-24 15:01:53'),(141,'::1','calendar_api','2026-08-24 15:01:53'),(142,'::1','calendar_api','2026-08-24 15:01:57'),(143,'::1','calendar_api','2026-08-24 15:01:58'),(144,'::1','calendar_api','2026-08-24 15:01:58'),(145,'::1','calendar_api','2026-08-24 15:02:14'),(146,'::1','calendar_api','2026-08-24 15:02:17'),(193,'::1','calendar_api','2026-08-24 20:39:06'),(194,'::1','calendar_api','2026-08-24 20:41:57'),(195,'::1','calendar_api','2026-08-24 21:18:07'),(196,'::1','calendar_api','2026-08-24 21:18:13'),(197,'::1','calendar_api','2026-08-24 21:20:15'),(88,'::1','calendar_api','2026-08-25 14:59:43'),(89,'::1','calendar_api','2026-08-25 14:59:46'),(90,'::1','calendar_api','2026-08-25 14:59:48'),(91,'::1','calendar_api','2026-08-25 14:59:50'),(92,'::1','calendar_api','2026-08-25 14:59:50'),(93,'::1','calendar_api','2026-08-25 14:59:50'),(94,'::1','calendar_api','2026-08-25 14:59:51'),(95,'::1','calendar_api','2026-08-25 14:59:51'),(96,'::1','calendar_api','2026-08-25 14:59:51'),(97,'::1','calendar_api','2026-08-25 14:59:51'),(98,'::1','calendar_api','2026-08-25 14:59:51'),(99,'::1','calendar_api','2026-08-25 14:59:51'),(100,'::1','calendar_api','2026-08-25 14:59:52'),(101,'::1','calendar_api','2026-08-25 14:59:56'),(102,'::1','calendar_api','2026-08-25 14:59:57'),(103,'::1','calendar_api','2026-08-25 14:59:58'),(104,'::1','calendar_api','2026-08-25 14:59:59'),(105,'::1','calendar_api','2026-08-25 14:59:59'),(106,'::1','calendar_api','2026-08-25 14:59:59'),(107,'::1','calendar_api','2026-08-25 14:59:59'),(108,'::1','calendar_api','2026-08-25 15:00:00'),(109,'::1','calendar_api','2026-08-25 15:00:00'),(110,'::1','calendar_api','2026-08-25 15:00:00'),(111,'::1','calendar_api','2026-08-25 15:00:02'),(112,'::1','calendar_api','2026-08-25 15:00:02'),(113,'::1','calendar_api','2026-08-25 15:00:03'),(114,'::1','calendar_api','2026-08-25 15:00:03'),(115,'::1','calendar_api','2026-08-25 15:00:04'),(116,'::1','calendar_api','2026-08-25 15:00:04'),(117,'::1','calendar_api','2026-08-25 15:00:04'),(118,'::1','calendar_api','2026-08-25 15:00:05'),(119,'::1','calendar_api','2026-08-25 15:00:05'),(120,'::1','calendar_api','2026-08-25 15:00:05'),(121,'::1','calendar_api','2026-08-25 15:00:05'),(122,'::1','calendar_api','2026-08-25 15:01:37'),(123,'::1','calendar_api','2026-08-25 15:01:37'),(124,'::1','calendar_api','2026-08-25 15:01:40'),(125,'::1','calendar_api','2026-08-25 15:01:41'),(191,'::1','guest_lookup','2026-08-24 09:38:26'),(181,'::1','login_attempt','2026-08-22 22:21:59'),(192,'::1','login_attempt','2026-08-24 20:31:04');
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
) ENGINE=InnoDB AUTO_INCREMENT=24016 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=51871 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
INSERT INTO `security_logs` VALUES (1,'FAILED_LOGIN','WARNING','admin\" or \"1\"=\"1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Failed login attempt (unknown username): admin\" or \"1\"=\"1','2026-08-21 11:51:56'),(2,'FAILED_LOGIN','WARNING','admin\" or \"1\"=\"1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Failed login attempt (unknown username): admin\" or \"1\"=\"1','2026-08-21 11:52:02'),(3,'FAILED_LOGIN','WARNING','or 1=1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Failed login attempt (unknown username): or 1=1','2026-08-21 11:52:32'),(4,'FAILED_LOGIN','WARNING','or 1=1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Failed login attempt (unknown username): or 1=1','2026-08-21 11:52:39'),(5,'LOGIN_SUCCESS','INFO','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Admin logged in successfully (admin)','2026-08-21 12:49:52'),(6,'FAILED_LOGIN','WARNING','Justine','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Failed login attempt (bad password) for user: Justine','2026-08-21 13:13:06'),(7,'FAILED_LOGIN','WARNING','Justine','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Failed login attempt (bad password) for user: Justine','2026-08-21 13:13:18'),(8,'LOGIN_SUCCESS','INFO','Justine','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Admin logged in successfully (receptionist)','2026-08-21 13:13:25'),(9,'LOGIN_SUCCESS','INFO','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login.php','Admin logged in successfully (admin)','2026-08-21 13:14:10'),(10,'LOGIN_SUCCESS','INFO','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 08:38:02'),(11,'PASSWORD_CHANGE_FAILED','WARNING','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/settings','Incorrect current password attempt','2026-08-22 08:49:27'),(12,'PASSWORD_CHANGE_FAILED','WARNING','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/settings','Incorrect current password attempt','2026-08-22 08:55:17'),(13,'LOGIN_SUCCESS','INFO','justinebatuhan@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 08:58:04'),(14,'STAFF_CREATED','INFO','justinebatuhan@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/admin_staff','Added receptionist account: Isandro','2026-08-22 08:58:30'),(15,'STAFF_DELETED','WARNING','justinebatuhan@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/admin_staff','Removed staff account: Isandro','2026-08-22 08:59:14'),(16,'PASSWORD_RESET','INFO','justinebatuhan@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/admin_staff','Reset password for staff: Justinebatuhan@beachclub.com','2026-08-22 08:59:32'),(17,'USERNAME_CHANGED','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/settings','Username changed from justinebatuhan@beachclub.com to admin@beachclub.com','2026-08-22 10:11:18'),(18,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 11:52:34'),(19,'LOGIN_SUCCESS','INFO','admin@beachclub.com','192.168.1.8','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 17:48:15'),(20,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 21:52:09'),(21,'FAILED_LOGIN','WARNING','justine@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Failed login attempt (bad password) for user: justine@beachclub.com','2026-08-22 21:55:23'),(22,'FAILED_LOGIN','WARNING','justine@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Failed login attempt (bad password) for user: justine@beachclub.com','2026-08-22 21:55:37'),(23,'FAILED_LOGIN','WARNING','justine@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Failed login attempt (bad password) for user: justine@beachclub.com','2026-08-22 21:55:41'),(24,'FAILED_LOGIN','WARNING','justine@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Failed login attempt (bad password) for user: justine@beachclub.com','2026-08-22 21:55:45'),(25,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 21:55:56'),(26,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:09:36'),(27,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:10:02'),(28,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:12:42'),(29,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:13:52'),(30,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:14:18'),(31,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:16:52'),(32,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:20:18'),(33,'FAILED_LOGIN','WARNING','justine@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Failed login attempt (bad password) for user: justine@beachclub.com','2026-08-22 22:21:59'),(34,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-22 22:22:51'),(35,'STAFF_CREATED','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/admin_staff','Added receptionist account: Jub@beachclub.com','2026-08-22 22:26:24'),(36,'LOGIN_SUCCESS','INFO','Jub@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (receptionist)','2026-08-22 22:26:40'),(37,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-23 13:06:16'),(38,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-23 13:11:38'),(39,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-23 14:48:11'),(40,'STAFF_CREATED','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/admin_staff','Added receptionist account: Hermae@beachclub.com','2026-08-23 15:13:36'),(41,'LOGIN_SUCCESS','INFO','Hermae@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (receptionist)','2026-08-23 15:13:55'),(42,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-23 15:14:24'),(43,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-23 15:22:16'),(44,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 09:28:04'),(45,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 20:25:25'),(46,'FAILED_LOGIN','WARNING','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Failed login attempt (bad password) for user: admin@beachclub.com','2026-08-24 20:31:04'),(47,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 20:31:16'),(48,'LOGIN_SUCCESS','INFO','justine@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (receptionist)','2026-08-24 20:41:51'),(49,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 20:42:16'),(50,'LOGIN_SUCCESS','INFO','admin@beachclub.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','/SantaBeachClub-BookingSystem/frontend/login','Admin logged in successfully (admin)','2026-08-24 22:42:51');
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
-- Dumping events for database 'santafe_beach_club'
--

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

-- Dump completed on 2026-08-24 23:00:44
