CREATE DATABASE  IF NOT EXISTS `ecoridepool` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `ecoridepool`;
-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: ecoridepool
-- ------------------------------------------------------
-- Server version	8.0.36

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ride_reviews`
--

DROP TABLE IF EXISTS `ride_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ride_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ride_request_id` int NOT NULL,
  `reviewer_id` int NOT NULL,
  `target_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `comment` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ride_request_id` (`ride_request_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `target_id` (`target_id`),
  CONSTRAINT `ride_reviews_ibfk_1` FOREIGN KEY (`ride_request_id`) REFERENCES `ride_requests` (`id`),
  CONSTRAINT `ride_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ride_reviews_ibfk_3` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ride_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ride_reviews`
--

LOCK TABLES `ride_reviews` WRITE;
/*!40000 ALTER TABLE `ride_reviews` DISABLE KEYS */;
INSERT INTO `ride_reviews` VALUES (1,2,8,3,5,'ui test ','approved','2025-05-23 11:58:37'),(2,9,8,5,2,'teting','approved','2025-05-23 11:59:19'),(3,10,8,5,4,'22223','rejected','2025-05-23 12:06:01'),(4,12,7,3,4,'Good driver, punctual','approved','2025-05-23 14:01:23'),(5,12,7,3,4,'Good driver, punctual','pending','2025-05-23 14:02:01'),(6,17,7,3,2,'thank you','pending','2025-05-23 22:38:36');
/*!40000 ALTER TABLE `ride_reviews` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-25 21:43:18
