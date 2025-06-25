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
-- Table structure for table `carpools`
--

DROP TABLE IF EXISTS `carpools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carpools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` int NOT NULL,
  `vehicle_id` int NOT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `dropoff_location` varchar(255) DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `total_seats` int NOT NULL DEFAULT '4',
  `occupied_seats` int NOT NULL DEFAULT '0',
  `status` enum('upcoming','in progress','completed','disputed','resolved','canceled') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_carpools_driver` (`driver_id`),
  KEY `fk_carpools_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_carpools_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_carpools_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carpools`
--

LOCK TABLES `carpools` WRITE;
/*!40000 ALTER TABLE `carpools` DISABLE KEYS */;
INSERT INTO `carpools` VALUES (1,3,1,'Paris','Lyon','2025-05-23 10:48:55',4,2,'completed','2025-05-22 08:48:55','2025-05-23 13:03:36'),(2,3,1,'Paris','Lille','2025-05-22 09:48:55',4,3,'disputed','2025-05-22 08:48:55','2025-05-24 16:52:06'),(3,3,1,'Nice','Marseille','2025-05-20 10:48:55',4,4,'completed','2025-05-22 08:48:55','2025-05-23 13:29:15'),(4,3,1,'Nice','Geneva','2025-05-21 10:48:55',3,3,'resolved','2025-05-22 08:48:55','2025-05-22 12:36:18'),(5,4,2,'Lille','Brussels','2025-05-23 10:48:55',4,1,'completed','2025-05-22 08:48:55','2025-05-24 16:11:33'),(6,4,2,'Rouen','Caen','2025-05-22 07:48:55',4,2,'in progress','2025-05-22 08:48:55','2025-05-22 08:48:55'),(7,4,2,'Toulouse','Bordeaux','2025-05-20 10:48:55',4,2,'completed','2025-05-22 08:48:55','2025-05-22 08:48:55'),(8,5,3,'Dijon','Grenoble','2025-05-24 10:48:55',2,1,'upcoming','2025-05-22 08:48:55','2025-05-22 08:48:55'),(9,5,3,'Nantes','Tours','2025-05-22 09:48:55',2,2,'disputed','2025-05-22 08:48:55','2025-05-24 16:52:08'),(10,5,3,'Strasbourg','Nancy','2025-05-19 10:48:55',2,2,'disputed','2025-05-22 08:48:55','2025-05-24 17:00:48'),(11,6,4,'Avignon','Montpellier','2025-05-22 22:48:55',3,1,'upcoming','2025-05-22 08:48:55','2025-05-23 12:21:01'),(12,6,4,'Clermont-Ferrand','Limoges','2025-05-22 08:48:55',3,1,'in progress','2025-05-22 08:48:55','2025-05-22 08:48:55'),(13,6,4,'Nice','Saint-Maur','2025-05-21 10:48:55',3,3,'disputed','2025-05-22 08:48:55','2025-05-22 08:48:55'),(14,6,4,'La Rochelle','Angers','2025-05-20 10:48:55',3,3,'completed','2025-05-22 08:48:55','2025-05-22 08:48:55'),(15,3,1,'ourcq','jumia','2026-02-25 12:58:00',2,2,'completed','2025-05-23 13:31:32','2025-05-23 13:32:54'),(16,3,1,'studi-lyon','studi-paris','2025-09-07 14:30:00',2,0,'upcoming','2025-05-27 13:57:40','2025-05-27 13:57:40'),(17,3,1,'studi-lyon','studi-paris-brest','2025-06-27 14:52:00',2,0,'upcoming','2025-06-17 19:20:10','2025-06-17 19:20:10');
/*!40000 ALTER TABLE `carpools` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-25 21:43:17
