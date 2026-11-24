-- ============================================================
-- Wear Adinkra - CLEAN PRODUCTION INSTALL SQL
-- Import this file into MySQL (phpMyAdmin: Import tab).
-- It creates the database, all tables, and loads:
--   * admin accounts (admin@wearadinkra.com / manager@wearadinkra.com, password Admin123!)
--   * the current product catalogue
--   * customer reviews and SEO settings
-- Test users, test orders and local visit logs are intentionally excluded.
-- After import, update api/config.php (APP_URL, DB credentials, SMTP) and
-- change the demo admin passwords immediately.
-- ============================================================

--
-- Host: 127.0.0.1    Database: wearadinkra
-- ------------------------------------------------------

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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


--
-- Host: 127.0.0.1    Database: wearadinkra
-- ------------------------------------------------------

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
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Super Admin','admin@wearadinkra.com','$2y$10$aUS501dw9dPsQS0/9NztW.1VVFT7tXSl9TtJP4wxxjKG.kFfm76sW','super_admin','active','2026-11-21 08:19:13','+233277962888'),(2,'Manager','manager@wearadinkra.com','$2y$10$zrUN6IR/3BwZrCfOqj9iP.yV8WaG8R8NjwlwSCPbQ4lGNXi8MiS.u','manager','active','2026-11-21 08:19:13',NULL);
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (34,'MAN',200.00,'African Shirt',9999,'assets/product-images/p_20261122_235827_2540731a7a88.jpg','[\"assets\\/product-images\\/p_20261122_235827_2540731a7a88.jpg\"]','','2026-11-22 23:16:39','GYE NYAME \n\nA symbol expressing the omnipotence of God.\n\nCelebrate Ghanaian heritage with this beautiful Gye Nyame shirt from wearadinkra. Featuring vibrant Kente-inspired patterns, a bold black Gye Nyame symbol, a contrasting black pocket and a comfortable straight-cut hem, this outfit brings together faith, culture and contemporary African fashion.\n\nPerfect for cultural occasions, church gatherings, casual outings and everyday elegance.'),(35,'KENTE',66.00,'African Shirt',7,'assets/product-images/p_20261122_235738_594b7e8fcc8e.jpg','[\"assets\\/product-images\\/p_20261122_235738_594b7e8fcc8e.jpg\"]','','2026-11-22 23:46:41','GYE NYAME \n\nA symbol expressing the omnipotence of God.\n\nCelebrate Ghanaian heritage with this beautiful Gye Nyame shirt from wearadinkra. Featuring vibrant Kente-inspired patterns, a bold black Gye Nyame symbol, a contrasting black pocket and a comfortable straight-cut hem, this outfit brings together faith, culture and contemporary African fashion.\n\nPerfect for cultural occasions, church gatherings, casual outings and everyday elegance.'),(36,'T-SHIRT',77.00,'African Shirt',9999,'assets/product-images/p_20261122_235704_0c91fdb96739.jpg','[\"assets\\/product-images\\/p_20261122_235704_0c91fdb96739.jpg\"]','','2026-11-22 23:49:19','GYE NYAME \n\nA symbol expressing the omnipotence of God.\n\nCelebrate Ghanaian heritage with this beautiful Gye Nyame shirt from wearadinkra. Featuring vibrant Kente-inspired patterns, a bold black Gye Nyame symbol, a contrasting black pocket and a comfortable straight-cut hem, this outfit brings together faith, culture and contemporary African fashion.\n\nPerfect for cultural occasions, church gatherings, casual outings and everyday elegance.'),(37,'hy',66.00,'African Shirt',9999,'assets/product-images/p_20261123_001257_79052e5c4a56.jpg','[\"assets\\/product-images\\/p_20261123_001257_79052e5c4a56.jpg\",\"assets\\/product-images\\/p_20261123_001257_b72f10cae280.jpg\"]','https://youtu.be/wDchsz8nmbo','2026-11-23 00:13:01','https://youtu.be/wDchsz8nmbo'),(38,'GIGI Kids’ Regular Dress',150.00,'Children',9999,'assets/product-images/p_20261123_142015_3a80a16b40cb.jpg','[\"assets\\/product-images\\/p_20261123_142015_3a80a16b40cb.jpg\"]','','2026-11-23 14:20:17','Up your little angel’s style game this season with our Kids’ Regular Dress.\nWhether you’re taking her to the fun park, vacation school, or visiting grandma, this one is comfy, cool, and seriously easy to style for them.');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,'WA-99999','IBRAHIM HAMID',4,'Lovely quality and authentic symbols.','[]','2026-11-21 07:14:18'),(2,1,'WA-58656','IBRAHIM HAMID',4,'Nice dress','[]','2026-11-21 07:21:57');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `seo_settings`
--

LOCK TABLES `seo_settings` WRITE;
/*!40000 ALTER TABLE `seo_settings` DISABLE KEYS */;
INSERT INTO `seo_settings` VALUES (2,'{\"writeTags\":true,\"allowIndex\":true,\"allowFollow\":true,\"siteTitle\":\"Wear Adinkra | Preserving Ghanaian Heritage\",\"titleTemplate\":\"%s | Wear Adinkra\",\"metaDescription\":\"Authentic Ghanaian fashion inspired by Adinkra symbols. Wear your heritage withwearadinkra.\",\"keywords\":\"adinkra, ghanaian fashion, african fashion, wear adinkra, kente, heritage clothing\",\"baseUrl\":\"http:\\/\\/localhost\\/wearadinkra\\/\",\"ogTitle\":\"\",\"ogDescription\":\"\",\"ogImage\":\"\",\"ogType\":\"website\",\"twitterHandle\":\"@wearadinkra\",\"twitterTitle\":\"\",\"twitterDescription\":\"\",\"twitterImage\":\"\",\"themeColor\":\"#1c5d99\",\"favicon\":\"askibrah@gmail.com\",\"listListings\":true,\"gscToken\":\"askibrah@gmail.com\",\"bingToken\":\"\",\"gaId\":\"\",\"gtmId\":\"\",\"adsenseId\":\"\",\"orgName\":\"Wear Adinkra\",\"orgDescription\":\"\",\"orgLogo\":\"\",\"facebookPage\":\"\",\"twitterProfile\":\"\",\"instagramUrl\":\"\",\"linkedinUrl\":\"\",\"youtubeUrl\":\"\"}','2026-11-21 22:07:27');
/*!40000 ALTER TABLE `seo_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


