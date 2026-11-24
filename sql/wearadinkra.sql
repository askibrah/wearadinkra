-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: wearadinkra
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `wearadinkra`
--

/*!40000 DROP DATABASE IF EXISTS `wearadinkra`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `wearadinkra` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `wearadinkra`;

--
-- Table structure for table `activation_tokens`
--

DROP TABLE IF EXISTS `activation_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activation_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` int NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activation_tokens`
--

LOCK TABLES `activation_tokens` WRITE;
/*!40000 ALTER TABLE `activation_tokens` DISABLE KEYS */;
INSERT INTO `activation_tokens` VALUES (1,1,'2dc41c471ad6123db68879543f7f24e9fa393a7203fa168d91161b03020b3c5c',1795325074,'2026-11-21 05:24:35','2026-11-21 05:24:34'),(2,2,'90321666fb8c813e5a8ca497c1b665dbd4c59a7d03761472658d883c36f902e5',1795325244,'2026-11-21 05:31:23','2026-11-21 05:27:24'),(3,2,'032c416d6f73c9a1206c61048c20fb8e05b73e7bdc01180a181083b5a993be5e',1795325483,NULL,'2026-11-21 05:31:23'),(4,3,'ac3f2e2fd0e97fc211ceb86e3cdec9f7120685f15ee074aad8df76b4f3e690af',1795326213,NULL,'2026-11-21 05:43:33'),(5,5,'5eb65e3273d31084b55f27f511f288546fe5095322c04a9c938f7f82f9cebb05',1795564008,NULL,'2026-11-23 23:46:48'),(6,6,'dcae9655c5580d8dc0369f19500602ca79e988526ac4f66310d603f27f10b935',1795564231,NULL,'2026-11-23 23:50:31');
/*!40000 ALTER TABLE `activation_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(40) NOT NULL DEFAULT 'admin',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Super Admin','admin@wearadinkra.com','$2y$10$aUS501dw9dPsQS0/9NztW.1VVFT7tXSl9TtJP4wxxjKG.kFfm76sW','super_admin','active','2026-11-21 08:19:13','+233277962888'),(2,'Manager','manager@wearadinkra.com','$2y$10$zrUN6IR/3BwZrCfOqj9iP.yV8WaG8R8NjwlwSCPbQ4lGNXi8MiS.u','manager','active','2026-11-21 08:19:13',NULL);
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempts` (`identifier`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (24,'admin:askibrah@gmail.com|::1','::1',0,1795424195),(25,'admin:askibrah@gmail.com|::1','::1',0,1795440896),(26,'admin:askibrah@gmail.com|::1','::1',0,1795440899);
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `channel` varchar(20) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` int NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_otp_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_codes`
--

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
INSERT INTO `otp_codes` VALUES (1,1,'account','$2y$10$udKEmbcyucVYszZfTjjYReMT634hCPdEHuVjaUgMziOQ64yUr6mw.',1795239274,'2026-11-21 05:24:35','2026-11-21 05:24:34'),(2,1,'reset','$2y$10$RH.V4GvGqIzY/ex/N0pxeO4W/DTmXNCe4XUifJsjUsQ.PP.D6pnW6',1795239576,'2026-11-21 05:24:36','2026-11-21 05:24:36'),(3,2,'account','$2y$10$ypNcWVvI9ulPEvdaJvGHmezRszoywWNoyOSVB7HJls06si8FocMpO',1795239444,'2026-11-21 05:31:23','2026-11-21 05:27:24'),(4,2,'account','$2y$10$7lM7melmwXXCwpgSDXNv5eXoTw.fangP3EZMdaH8CoX42sOZ16dBm',1795239683,NULL,'2026-11-21 05:31:23'),(5,3,'account','$2y$10$O5VMYt6zTmoh199WweUeC.PQziF4Mgm7E.OuRobc0O.7QOen2W8qC',1795240413,NULL,'2026-11-21 05:43:33'),(6,5,'account','$2y$10$1LRagHqH8cMdBKYqEbU9Me0yS1TqvbmtFfY21Sty0M0DheaqeejIq',1795478208,NULL,'2026-11-23 23:46:48'),(7,6,'account','$2y$10$qCGKlD1O646gYf2Eu72sYuzpSJxeju9ZLzgFklKcFP4X45bszsdTu',1795478431,NULL,'2026-11-23 23:50:31');
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` int NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,1,'8bbf75129569e740ba5797864bd50bab38a10c5eba37d3865dd4598fe85764d4',1795239576,'2026-11-21 05:24:36','2026-11-21 05:24:36');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(60) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `image` text NOT NULL,
  `images` text,
  `video` text,
  `created_at` datetime NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (34,'MAN',200.00,'African Shirt',9999,'assets/product-images/p_20261122_235827_2540731a7a88.jpg','[\"assets\\/product-images\\/p_20261122_235827_2540731a7a88.jpg\"]','','2026-11-22 23:16:39','GYE NYAME \n\nA symbol expressing the omnipotence of God.\n\nCelebrate Ghanaian heritage with this beautiful Gye Nyame shirt from wearadinkra. Featuring vibrant Kente-inspired patterns, a bold black Gye Nyame symbol, a contrasting black pocket and a comfortable straight-cut hem, this outfit brings together faith, culture and contemporary African fashion.\n\nPerfect for cultural occasions, church gatherings, casual outings and everyday elegance.'),(35,'KENTE',66.00,'African Shirt',7,'assets/product-images/p_20261122_235738_594b7e8fcc8e.jpg','[\"assets\\/product-images\\/p_20261122_235738_594b7e8fcc8e.jpg\"]','','2026-11-22 23:46:41','GYE NYAME \n\nA symbol expressing the omnipotence of God.\n\nCelebrate Ghanaian heritage with this beautiful Gye Nyame shirt from wearadinkra. Featuring vibrant Kente-inspired patterns, a bold black Gye Nyame symbol, a contrasting black pocket and a comfortable straight-cut hem, this outfit brings together faith, culture and contemporary African fashion.\n\nPerfect for cultural occasions, church gatherings, casual outings and everyday elegance.'),(36,'T-SHIRT',77.00,'African Shirt',9999,'assets/product-images/p_20261122_235704_0c91fdb96739.jpg','[\"assets\\/product-images\\/p_20261122_235704_0c91fdb96739.jpg\"]','','2026-11-22 23:49:19','GYE NYAME \n\nA symbol expressing the omnipotence of God.\n\nCelebrate Ghanaian heritage with this beautiful Gye Nyame shirt from wearadinkra. Featuring vibrant Kente-inspired patterns, a bold black Gye Nyame symbol, a contrasting black pocket and a comfortable straight-cut hem, this outfit brings together faith, culture and contemporary African fashion.\n\nPerfect for cultural occasions, church gatherings, casual outings and everyday elegance.'),(37,'hy',66.00,'African Shirt',9999,'assets/product-images/p_20261123_001257_79052e5c4a56.jpg','[\"assets\\/product-images\\/p_20261123_001257_79052e5c4a56.jpg\",\"assets\\/product-images\\/p_20261123_001257_b72f10cae280.jpg\"]','https://youtu.be/wDchsz8nmbo','2026-11-23 00:13:01','https://youtu.be/wDchsz8nmbo'),(38,'GIGI Kids’ Regular Dress',150.00,'Children',9999,'assets/product-images/p_20261123_142015_3a80a16b40cb.jpg','[\"assets\\/product-images\\/p_20261123_142015_3a80a16b40cb.jpg\"]','','2026-11-23 14:20:17','Up your little angel’s style game this season with our Kids’ Regular Dress.\nWhether you’re taking her to the fun park, vacation school, or visiting grandma, this one is comfy, cool, and seriously easy to style for them.');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `order_id` varchar(40) DEFAULT NULL,
  `author` varchar(150) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text NOT NULL,
  `images` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,'WA-99999','IBRAHIM HAMID',4,'Lovely quality and authentic symbols.','[]','2026-11-21 07:14:18'),(2,1,'WA-58656','IBRAHIM HAMID',4,'Nice dress','[]','2026-11-21 07:21:57');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seo_settings`
--

DROP TABLE IF EXISTS `seo_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `settings` text NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_settings`
--

LOCK TABLES `seo_settings` WRITE;
/*!40000 ALTER TABLE `seo_settings` DISABLE KEYS */;
INSERT INTO `seo_settings` VALUES (2,'{\"writeTags\":true,\"allowIndex\":true,\"allowFollow\":true,\"siteTitle\":\"Wear Adinkra | Preserving Ghanaian Heritage\",\"titleTemplate\":\"%s | Wear Adinkra\",\"metaDescription\":\"Authentic Ghanaian fashion inspired by Adinkra symbols. Wear your heritage withwearadinkra.\",\"keywords\":\"adinkra, ghanaian fashion, african fashion, wear adinkra, kente, heritage clothing\",\"baseUrl\":\"http:\\/\\/localhost\\/wearadinkra\\/\",\"ogTitle\":\"\",\"ogDescription\":\"\",\"ogImage\":\"\",\"ogType\":\"website\",\"twitterHandle\":\"@wearadinkra\",\"twitterTitle\":\"\",\"twitterDescription\":\"\",\"twitterImage\":\"\",\"themeColor\":\"#1c5d99\",\"favicon\":\"askibrah@gmail.com\",\"listListings\":true,\"gscToken\":\"askibrah@gmail.com\",\"bingToken\":\"\",\"gaId\":\"\",\"gtmId\":\"\",\"adsenseId\":\"\",\"orgName\":\"Wear Adinkra\",\"orgDescription\":\"\",\"orgLogo\":\"\",\"facebookPage\":\"\",\"twitterProfile\":\"\",\"instagramUrl\":\"\",\"linkedinUrl\":\"\",\"youtubeUrl\":\"\"}','2026-11-21 22:07:27');
/*!40000 ALTER TABLE `seo_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_visits`
--

DROP TABLE IF EXISTS `site_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_visits` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `device` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visits_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=464 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_visits`
--

LOCK TABLES `site_visits` WRITE;
/*!40000 ALTER TABLE `site_visits` DISABLE KEYS */;
INSERT INTO `site_visits` VALUES (1,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 21:44:02'),(2,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 21:44:55'),(4,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 21:47:18'),(5,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 21:48:28'),(6,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:04:33'),(7,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:04:52'),(8,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:08:27'),(9,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:08:30'),(10,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:08:33'),(11,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:08:38'),(12,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:10:45'),(13,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:10:50'),(14,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:12:27'),(15,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:13:48'),(16,'::1','','','Direct','/wearadinkra/','Mobile','2026-11-21 22:14:36'),(17,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:15:51'),(18,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 22:16:44'),(19,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-21 23:58:11'),(20,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:03:03'),(21,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:03:45'),(22,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:06:16'),(23,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:12:46'),(24,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:12:53'),(25,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:13:01'),(26,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:22:02'),(27,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:32:12'),(28,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:33:55'),(29,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:34:36'),(30,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:38:09'),(31,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:45:47'),(32,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:50:27'),(33,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:53:03'),(34,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:53:59'),(35,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:54:29'),(36,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 10:57:50'),(37,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 11:02:00'),(38,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 11:02:06'),(39,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 11:05:51'),(40,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 11:13:04'),(41,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 11:14:47'),(42,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 11:21:32'),(43,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 11:22:29'),(44,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 11:23:21'),(45,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 11:24:41'),(46,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 11:24:58'),(47,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 11:37:28'),(48,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 11:38:23'),(49,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 12:23:39'),(50,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 12:45:46'),(51,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 12:46:19'),(52,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 12:51:47'),(53,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 12:55:39'),(54,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 12:57:41'),(55,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:03:10'),(56,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:03:20'),(57,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:04:54'),(58,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:05:48'),(59,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:05:53'),(60,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:06:52'),(61,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:10:31'),(62,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:11:15'),(63,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 13:11:18'),(64,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 13:11:56'),(65,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 13:43:24'),(66,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 18:57:13'),(67,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 18:58:11'),(68,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 18:59:46'),(69,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 18:59:48'),(70,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:20:05'),(71,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:20:08'),(72,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:27:31'),(73,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:28:22'),(74,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:29:08'),(75,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:30:29'),(76,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:33:39'),(77,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:40:48'),(78,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:42:24'),(79,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:52:13'),(80,'::1','','','Direct','/wearadinkra/','Mobile','2026-11-22 19:54:37'),(81,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:56:10'),(82,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:56:24'),(83,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 19:57:01'),(84,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:04:56'),(85,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:04'),(86,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:06'),(87,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:08'),(88,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:10'),(89,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:15'),(90,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:16'),(91,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:20'),(92,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:22'),(93,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:24'),(94,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:25'),(95,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:27'),(96,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:30'),(97,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:31'),(98,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:33'),(99,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:35'),(100,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:37'),(101,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:39'),(102,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:40'),(103,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:43'),(104,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:45'),(105,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:47'),(106,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:49'),(107,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:05:55'),(108,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:07:55'),(109,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:08:00'),(110,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:09:05'),(111,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:15:54'),(112,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:20:06'),(113,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:33:09'),(114,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:36:52'),(115,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 20:59:59'),(116,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 21:21:58'),(117,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 21:24:09'),(118,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 21:29:18'),(119,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:24:02'),(120,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:27:33'),(121,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:30:40'),(122,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:32:47'),(123,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:33:28'),(124,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:34:16'),(125,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:38:52'),(126,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-22 22:39:01'),(127,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:39:37'),(128,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:41:36'),(129,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:42:01'),(130,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:42:40'),(131,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:43:27'),(132,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:44:00'),(133,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:54:57'),(134,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:58:22'),(135,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 22:59:53'),(136,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:01:21'),(137,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:02:01'),(138,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:07:56'),(139,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:08:16'),(140,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:08:46'),(141,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:09:36'),(142,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:09:42'),(143,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:09:57'),(144,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:11:08'),(145,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:13:08'),(146,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:14:54'),(147,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:16:16'),(148,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:19:29'),(149,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:23:37'),(150,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:24:05'),(151,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:27:12'),(152,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:30:01'),(153,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:33:32'),(154,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:34:35'),(155,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:38:37'),(156,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:40:25'),(157,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:42:01'),(158,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:42:28'),(159,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:44:38'),(160,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:44:48'),(161,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:45:09'),(162,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:47:25'),(163,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:57:14'),(164,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:58:02'),(165,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:58:33'),(166,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-22 23:59:06'),(167,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:03:32'),(168,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:05:39'),(169,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:06:04'),(170,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:06:40'),(171,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:07:05'),(172,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:08:39'),(173,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:10:14'),(174,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:13:16'),(175,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:14:16'),(176,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:14:35'),(177,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:17:08'),(178,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:17:32'),(179,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:17:45'),(180,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:18:41'),(181,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:20:56'),(182,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:23:52'),(183,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:24:08'),(184,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:24:31'),(185,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:29:03'),(186,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:43:41'),(187,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:47:48'),(188,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:48:22'),(189,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:48:40'),(190,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:49:31'),(191,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:49:46'),(192,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:49:59'),(193,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:50:18'),(194,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:50:27'),(195,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:50:31'),(196,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:50:35'),(197,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:52:35'),(198,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:53:35'),(199,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:53:50'),(200,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:54:00'),(201,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:54:11'),(202,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:54:17'),(203,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:54:20'),(204,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:54:22'),(205,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 00:58:23'),(206,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:00:20'),(207,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:01:03'),(208,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:02:17'),(209,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:03:38'),(210,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:04:04'),(211,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:04:18'),(212,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:05:20'),(213,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:05:37'),(214,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:07:10'),(215,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:08:11'),(216,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:12:36'),(217,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:14:20'),(218,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:16:30'),(219,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:17:54'),(220,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:18:20'),(221,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:19:21'),(222,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:21:06'),(223,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:21:59'),(224,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:25:09'),(225,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:26:15'),(226,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:26:27'),(227,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:27:57'),(228,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:30:11'),(229,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:30:25'),(230,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:33:50'),(231,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:34:05'),(232,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:39:14'),(233,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 01:40:01'),(234,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:40:40'),(235,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:41:18'),(236,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:50:03'),(237,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:50:17'),(238,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:51:32'),(239,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 01:52:33'),(240,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 01:52:48'),(241,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:52:56'),(242,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:54:27'),(243,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:55:18'),(244,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:56:34'),(245,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 01:56:57'),(246,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:57:24'),(247,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:58:38'),(248,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 01:59:01'),(249,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 02:00:41'),(250,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 02:00:43'),(251,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 02:02:41'),(252,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 02:06:23'),(253,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 02:07:40'),(254,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 02:09:38'),(255,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 02:18:31'),(256,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 02:33:55'),(257,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:04:54'),(258,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:06:29'),(259,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:08:26'),(260,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:10:44'),(261,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:15:20'),(262,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:23:19'),(263,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:26:43'),(264,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:29:50'),(265,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:36:47'),(266,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 03:37:10'),(267,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 08:20:26'),(268,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 08:39:41'),(269,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 12:53:16'),(270,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 12:54:48'),(271,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 12:55:53'),(272,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 12:56:48'),(273,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:02:01'),(274,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:02:04'),(275,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:03:12'),(276,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:10:42'),(277,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:15:36'),(278,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:16:55'),(279,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:18:43'),(280,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:19:05'),(281,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:19:43'),(282,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:21:04'),(283,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:22:51'),(284,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:25:20'),(285,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:25:24'),(286,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:29:39'),(287,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:29:42'),(288,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:31:03'),(289,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:31:25'),(290,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:32:08'),(291,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:32:12'),(292,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:33:12'),(293,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:34:00'),(294,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:36:44'),(295,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:36:51'),(296,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:42:04'),(297,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 13:45:14'),(298,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:45:33'),(299,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:46:37'),(300,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:47:35'),(301,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:49:16'),(302,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 13:49:31'),(303,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:50:06'),(304,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:50:17'),(305,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:50:23'),(306,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:50:29'),(307,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:52:10'),(308,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:52:20'),(309,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:52:23'),(310,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:52:29'),(311,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:52:47'),(312,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:54:49'),(313,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:56:48'),(314,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 13:59:36'),(315,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:01:54'),(316,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:01:58'),(317,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:04:30'),(318,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:07:09'),(319,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:08:07'),(320,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:08:20'),(321,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:09:16'),(322,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:09:24'),(323,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:12:19'),(324,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:13:21'),(325,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:13:28'),(326,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:14:45'),(327,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:15:00'),(328,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:17:10'),(329,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:20:24'),(330,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:22:54'),(331,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:26:12'),(332,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:27:56'),(333,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:31:28'),(334,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:34:29'),(335,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:46:06'),(336,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:46:43'),(337,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:49:18'),(338,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:50:08'),(339,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:50:18'),(340,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:51:22'),(341,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:52:24'),(342,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:56:04'),(343,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:56:56'),(344,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 14:57:09'),(345,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:00:38'),(346,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:00:40'),(347,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:01:40'),(348,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:02:06'),(349,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:02:52'),(350,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:03:20'),(351,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:04:13'),(352,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:09:09'),(353,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:09:21'),(354,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:11:29'),(355,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:11:54'),(356,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:11:58'),(357,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 15:13:37'),(358,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:14:20'),(359,'::1','','','Direct','/wearadinkra/index.html','Mobile','2026-11-23 15:19:19'),(360,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:19:27'),(361,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:20:31'),(362,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:20:37'),(363,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:20:39'),(364,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:21:44'),(365,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:22:12'),(366,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:22:13'),(367,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:22:20'),(368,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:22:41'),(369,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:23:59'),(370,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:24:10'),(371,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:24:21'),(372,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:24:25'),(373,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:25:52'),(374,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:26:02'),(375,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:26:14'),(376,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:26:18'),(377,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:26:38'),(378,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:08'),(379,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:14'),(380,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:19'),(381,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:22'),(382,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:24'),(383,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:26'),(384,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:31'),(385,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:34'),(386,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:37'),(387,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:39'),(388,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:41'),(389,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 15:42:44'),(390,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:11:27'),(391,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:12:14'),(392,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:12:15'),(393,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:12:22'),(394,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:21:49'),(395,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:33:08'),(396,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:34:37'),(397,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:35:05'),(398,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:37:00'),(399,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:37:05'),(400,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:40:05'),(401,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:40:11'),(402,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:40:27'),(403,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:40:33'),(404,'::1','','','Direct','/wearadinkra/','Desktop','2026-11-23 22:43:43'),(405,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:44:26'),(406,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:45:57'),(407,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:47:37'),(408,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:47:41'),(409,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:47:44'),(410,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:47:48'),(411,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:47:50'),(412,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:47:53'),(413,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:48:01'),(414,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:53:47'),(415,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:55:16'),(416,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 22:56:36'),(417,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:00:13'),(418,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:00:35'),(419,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:01:24'),(420,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:02:05'),(421,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:05:31'),(422,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:05:40'),(423,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:06:37'),(424,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:06:58'),(425,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:07:23'),(426,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:10:28'),(427,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:11:26'),(428,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:12:35'),(429,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:14:33'),(430,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:15:04'),(431,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:18:34'),(432,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:21:41'),(433,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:23:26'),(434,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:25:43'),(435,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:30:20'),(436,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:31:43'),(437,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:34:19'),(438,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:37:28'),(439,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:49:28'),(440,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-23 23:50:02'),(441,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:05:35'),(442,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:09:32'),(443,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:17:53'),(444,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:26:09'),(445,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:31:28'),(446,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:33:10'),(447,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:36:31'),(448,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:41:27'),(449,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:42:21'),(450,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:42:27'),(451,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:43:11'),(452,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:54:15'),(453,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 00:56:32'),(454,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:01:05'),(455,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:08:58'),(456,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:09:39'),(457,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:12:41'),(458,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:13:38'),(459,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:13:57'),(460,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:23:36'),(461,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:25:43'),(462,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:31:10'),(463,'::1','','','Direct','/wearadinkra/index.html','Desktop','2026-11-24 01:32:09');
/*!40000 ALTER TABLE `site_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_orders`
--

DROP TABLE IF EXISTS `store_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(40) NOT NULL,
  `customer` varchar(190) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `method` varchar(40) DEFAULT NULL,
  `items` text,
  `status` varchar(30) NOT NULL DEFAULT 'processing',
  `is_new` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `guest` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_orders`
--

LOCK TABLES `store_orders` WRITE;
/*!40000 ALTER TABLE `store_orders` DISABLE KEYS */;
INSERT INTO `store_orders` VALUES (3,'WA-67926','IBRAHIM HAMID','askibrah@gmail.com','0553796160',210.00,'paystack','[{\"name\":\"Adinkra Messenger Bag\",\"quantity\":1,\"price\":210}]','delivered',0,'2026-11-22 10:45:33',1),(4,'WA-53239','ewr er','saas@f,com','9809000809',85.00,'paystack','[{\"name\":\"Adinkra Baby Onesie\",\"quantity\":1,\"price\":85}]','shipped',0,'2026-11-22 11:00:38',1),(6,'WA-37936','Valued Customer','','',209.70,'paystack','[{\"name\":\"T-SHIRT\",\"quantity\":1,\"price\":209.7}]','processing',0,'2026-11-22 13:11:34',1),(7,'WA-25041','IBRAHIM HAMID','askibrah@gmail.com','0553796160',707.00,'paypal','[{\"name\":\"KENTE\",\"quantity\":1,\"price\":700}]','processing',0,'2026-11-22 13:14:27',1),(8,'WA-35951','IBRAHIM HAMID','askibrah@gmail.com','0553796160',123.22,'paypal','[{\"name\":\"HAND BAG\",\"quantity\":1,\"price\":122}]','shipped',0,'2026-11-22 13:18:38',1),(9,'WA-59533','fdad ff','Add@gmail.com','4636664364',90.00,'paystack','[{\"name\":\"Adinkra Heritage Belt\",\"quantity\":1,\"price\":90}]','processing',0,'2026-11-22 13:28:41',1),(10,'WA-65872','IBRAHIM HAMID','askibrah@gmail.com','0553796160',123.22,'paystack','[{\"name\":\"HAND BAG\",\"quantity\":1,\"price\":122}]','delivered',0,'2026-11-22 19:34:06',1),(11,'WA-89367','IBRAHIM HAMID','askibrah@gmail.com','0553796160',623.68,'paystack','[{\"name\":\"Adinkra Classic Dress\",\"quantity\":1,\"price\":617.5}]','processing',0,'2026-11-22 19:41:35',0),(12,'WA-28121','IBRAHIM HAMID','askibrah@gmail.com','0553796160',191.90,'paystack','[{\"name\":\"MAN\",\"quantity\":1,\"price\":190}]','processing',0,'2026-11-22 20:13:05',0),(13,'WA-19390','Customer','','',0.00,'','[]','delivered',0,'2026-11-23 03:27:31',1),(14,'WA-39830','Customer','','',0.00,'','[]','shipped',0,'2026-11-23 03:28:02',1),(15,'WA-88415','Customer','','',0.00,'','[]','in_transit',0,'2026-11-23 15:02:26',1),(17,'WA-85249','Customer','','',0.00,'','[]','shipped',0,'2026-11-24 01:13:30',1);
/*!40000 ALTER TABLE `store_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `email_verified_at` datetime DEFAULT NULL,
  `phone_verified_at` datetime DEFAULT NULL,
  `google_id` varchar(190) DEFAULT NULL,
  `facebook_id` varchar(190) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `activated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'MySQL User','my1795238674563@example.com','+233238674563','$2y$10$pVftT41Z3VNQ/FaR5hCFM.dwXGoCV6hI2ILUaWMNVtgYeG8t7BjCy','active','2026-11-21 05:24:35','2026-11-21 05:24:35',NULL,NULL,'2026-11-21 05:24:34','2026-11-21 05:24:35'),(2,'IBRAHIM HAMID','askibrah@gmail.com','+2330553796160','$2y$10$7LjZjYNI8eSxuXSKAS6Sruwk.7OHHJb3PjHqyP5RLxDNYP1pulE9W','active',NULL,NULL,NULL,NULL,'2026-11-21 05:27:24',NULL),(3,'Phone User','ph1795239813146@example.com','+233209813146','$2y$10$kqqxig5gNpS1UzdgPPpbneiJkeePFM1oZJEINVglBLwcFPzF3xn/m','pending',NULL,NULL,NULL,NULL,'2026-11-21 05:43:33',NULL),(4,'Phone User','phone1795239864@example.com','+233205239864','$2y$10$xYGDIksinvOEbYKdperOiOf8HyoWXOde4ENpIwcBhP5MrUqZDIewK','active',NULL,NULL,NULL,NULL,'2026-11-21 05:44:24','2026-11-21 05:44:24'),(5,'mala','askibra@gmail.com','+000662667581','$2y$10$V9hyUKd8CLX.EMhpeoV0CuXp4JQXNdJaKmzqDlV.sUVGEwG6BbPCe','pending',NULL,NULL,NULL,NULL,'2026-11-23 23:46:48',NULL),(6,'lcr6','askibrahi@gmail.com','+000218702382','$2y$10$7A3JBGLto8fSVt.2YHmK2ux0zItM7c0DMGxKwSOPPEmLSa9m27.wG','pending',NULL,NULL,NULL,NULL,'2026-11-23 23:50:31',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-24  1:35:38
