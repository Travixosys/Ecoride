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
-- Table structure for table `ride_requests`
--

DROP TABLE IF EXISTS `ride_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ride_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passenger_id` int NOT NULL,
  `driver_id` int DEFAULT NULL,
  `carpool_id` int DEFAULT NULL,
  `pickup_location` text NOT NULL,
  `dropoff_location` text NOT NULL,
  `passenger_count` int NOT NULL DEFAULT '1',
  `status` enum('pending','accepted','cancelled','completed','disputed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `passenger_id` (`passenger_id`),
  KEY `driver_id` (`driver_id`),
  CONSTRAINT `ride_requests_ibfk_1` FOREIGN KEY (`passenger_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ride_requests_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ride_requests_chk_1` CHECK ((`passenger_count` between 1 and 8))
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ride_requests`
--

LOCK TABLES `ride_requests` WRITE;
/*!40000 ALTER TABLE `ride_requests` DISABLE KEYS */;
INSERT INTO `ride_requests` VALUES (1,7,3,1,'Paris','Lyon',1,'completed','2025-05-22 08:48:55'),(2,8,3,2,'Paris','Lille',1,'completed','2025-05-22 08:48:55'),(3,9,3,3,'Nice','Marseille',1,'completed','2025-05-22 08:48:55'),(4,10,3,4,'Nice','Geneva',1,'disputed','2025-05-22 08:48:55'),(5,11,4,5,'Lille','Brussels',1,'completed','2025-05-22 08:48:55'),(6,12,4,6,'Rouen','Caen',1,'accepted','2025-05-22 08:48:55'),(7,13,4,7,'Toulouse','Bordeaux',1,'completed','2025-05-22 08:48:55'),(8,14,5,8,'Dijon','Grenoble',1,'accepted','2025-05-22 08:48:55'),(9,8,5,9,'Nantes','Tours',1,'completed','2025-05-22 08:48:55'),(10,8,5,10,'Strasbourg','Nancy',1,'completed','2025-05-22 08:48:55'),(11,9,6,11,'Avignon','Montpellier',1,'pending','2025-05-22 08:48:55'),(12,10,6,12,'Clermont-Ferrand','Limoges',1,'accepted','2025-05-22 08:48:55'),(13,11,6,13,'Nice','Saint-Maur',1,'disputed','2025-05-22 08:48:55'),(14,12,6,14,'La Rochelle','Angers',1,'completed','2025-05-22 08:48:55'),(15,7,5,9,'Nantes','Tours',1,'completed','2025-05-22 08:48:55'),(16,8,6,11,'Avignon','Montpellier',1,'accepted','2025-05-23 12:21:01'),(17,7,3,15,'ourcq','jumia',2,'completed','2025-05-23 13:32:11');
/*!40000 ALTER TABLE `ride_requests` ENABLE KEYS */;
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
