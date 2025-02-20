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
-- Table structure for table `rides`
--

DROP TABLE IF EXISTS `rides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rides` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passenger_id` int NOT NULL,
  `driver_id` int DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `pickup_location` text NOT NULL,
  `dropoff_location` text NOT NULL,
  `status` enum('pending','accepted','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `passenger_id` (`passenger_id`),
  KEY `driver_id` (`driver_id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `rides_ibfk_1` FOREIGN KEY (`passenger_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rides_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rides_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rides`
--

LOCK TABLES `rides` WRITE;
/*!40000 ALTER TABLE `rides` DISABLE KEYS */;
INSERT INTO `rides` VALUES (1,6,NULL,NULL,'123 Main St','456 Elm St','completed','2025-02-19 00:19:28'),(2,7,4,2,'789 Pine St','987 Oak St','accepted','2025-02-19 00:19:28'),(3,8,5,3,'654 Birch St','321 Cedar St','completed','2025-02-19 00:19:28'),(4,9,3,NULL,'222 Willow St','555 Maple Ave','completed','2025-02-19 01:12:22'),(5,10,3,1,'222 Willow St','555 Maple Ave','accepted','2025-02-19 01:14:36'),(6,11,NULL,NULL,'222 Willow St, PARIS','555 Maple Ave? lyon','cancelled','2025-02-19 01:16:55'),(7,11,NULL,NULL,'222 Willow St, PARIS','555 Maple Ave? lyon','cancelled','2025-02-19 01:17:39'),(8,11,NULL,NULL,'222 Willow St, PARIS','555 Maple Ave? lyon','pending','2025-02-19 02:44:57'),(9,11,NULL,NULL,'222 Willow St, PARIS','555 Maple Ave? lyon','pending','2025-02-19 15:21:35'),(10,11,3,NULL,'222 Willow St, PARIS','555 Maple Ave? lyon','accepted','2025-02-19 15:24:05'),(11,9,NULL,NULL,'222 Willow St','555 Maple Ave','pending','2025-02-19 15:25:50'),(12,9,NULL,NULL,'LOSA Willow St','5HAWTHORNE5 Maple Ave','pending','2025-02-19 15:54:27'),(13,7,NULL,NULL,'123 Main St','456 Elm St','cancelled','2025-02-20 01:14:57'),(14,7,NULL,NULL,'DORIME','PARIS','pending','2025-02-20 16:46:51'),(15,7,NULL,NULL,'jerusalem','PARIS','pending','2025-02-20 16:47:19'),(16,7,NULL,NULL,'jerusalem','PARIS','pending','2025-02-20 16:50:37'),(17,7,NULL,NULL,'jerusalem','PARIS','pending','2025-02-20 16:51:36'),(18,7,NULL,NULL,'jerusalem','PARIS','pending','2025-02-20 16:52:03'),(19,7,NULL,NULL,'jerusalem','PARIS','pending','2025-02-20 16:52:31'),(20,7,NULL,NULL,'jerusalem','PARIS','pending','2025-02-20 16:53:54'),(21,7,NULL,NULL,'EXAU','PARIS','pending','2025-02-20 16:56:45'),(22,7,NULL,NULL,'EXAU','PARIS','pending','2025-02-20 16:58:01'),(23,7,NULL,NULL,'EXAU','PARIS','pending','2025-02-20 16:58:06'),(24,7,NULL,NULL,'jerusalem','PARIS','pending','2025-02-20 16:58:18'),(25,7,NULL,NULL,'JUDAH','PARIS','pending','2025-02-20 17:01:03'),(26,7,NULL,NULL,'LA VILLETE ','JUMAJO','pending','2025-02-20 17:03:54'),(27,7,NULL,NULL,'guantanamo','BASTIA','pending','2025-02-20 17:06:51'),(28,7,NULL,NULL,'JUDAH','PARIS','pending','2025-02-20 17:11:16'),(29,7,NULL,NULL,'JUDAH','PARIS','pending','2025-02-20 17:11:47'),(30,7,NULL,NULL,'guantanamo','BASTIA','pending','2025-02-20 17:24:25'),(31,7,NULL,NULL,'guantanamo','BASTIA','pending','2025-02-20 17:31:29'),(32,7,NULL,NULL,'UWA','BASTIA','pending','2025-02-20 17:31:58'),(33,7,NULL,NULL,'784','855','pending','2025-02-20 18:01:23'),(34,7,NULL,NULL,'salomon','papa','pending','2025-02-20 18:02:01'),(35,7,NULL,NULL,'POMPEI','JUMANJI','pending','2025-02-20 18:04:48'),(36,7,NULL,NULL,'ROME ','POMPEI','pending','2025-02-20 18:06:25'),(37,7,NULL,NULL,'ROME ','POMPEI','pending','2025-02-20 18:06:50'),(38,7,NULL,NULL,'JUMANI','JUMAJO','pending','2025-02-20 18:07:15'),(39,7,NULL,NULL,'JUMANI','JUMAJO','pending','2025-02-20 18:10:12'),(40,7,NULL,NULL,'123 Main St','456 Elm St','pending','2025-02-20 18:15:21'),(41,7,NULL,NULL,'OUBACK','AUSTRALIE','pending','2025-02-20 18:15:57'),(42,7,NULL,NULL,'TAKE ME HOME','IMOA','pending','2025-02-20 20:04:24');
/*!40000 ALTER TABLE `rides` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-02-20 22:16:19
