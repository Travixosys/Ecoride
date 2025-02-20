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

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','driver','admin') NOT NULL DEFAULT 'user',
  `phone_number` varchar(20) DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `driver_rating` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin One','admin1@ecoride.com','$2y$10$Ih/McIKtTQfYaj5e4Gzln.iNB63etNAEP78vPHDj9V5ycIsiUQjga','admin','1111111111',NULL,NULL,'2025-02-18 23:49:16'),(2,'Admin Two','admin2@ecoride.com','$2y$10$5PC/orq77OsnBULDoY1MSOGf63d/K3LOfN883Ry54J5BbZqZGVR.m','admin','2222222222',NULL,NULL,'2025-02-18 23:49:16'),(3,'Driver One','driver1@ecoride.com','$2y$10$p2t7XkRPFUsTfMd4PFGGQeNXDYpT9bvT407QeIamzlp.VdSETQMx2','driver','3333333333','DRV1001',4.80,'2025-02-18 23:49:16'),(4,'Driver Two','driver2@ecoride.com','$2y$10$eUPPwSZHEPZLLR6tIOVC5OoE8PPrJdybAhvAQqs1Fc32bX4sZJF3O','driver','4444444444','DRV1002',4.50,'2025-02-18 23:49:16'),(5,'Driver Three','driver3@ecoride.com','$2y$10$PLSi1tjb6MqcC/gkWvIMdOCoKtwmMKUcjN9N2WpTb55iL/Sk7L7wK','driver','5555555555','DRV1053',4.60,'2025-02-20 20:31:46'),(6,'Driver Four','driver4@ecoride.com','$2y$10$8ISu7o0OyceQOyeh0x3wqu9ThKXnmsGbzFT.JEf80W6pDPRcYc25e','driver','6666666666',NULL,NULL,'2025-02-18 23:49:16'),(7,'User One','user1@ecoride.com','$2y$10$Mag8eBGIQDroQH7rCJA4ROxnh8V1R9Z3qi0qo/lv6adDRGNWyY4Ry','user','7777777777',NULL,NULL,'2025-02-18 23:49:16'),(8,'User Two','user2@ecoride.com','$2y$10$JppaeyM7IfORRhNzBHFs0u9.125LQPJBCtUOqgi4OAFBJfSOMp7KC','user','8888888888',NULL,NULL,'2025-02-18 23:49:16'),(9,'User Three','user3@ecoride.com','$2y$10$gB1hztShz5I0t6aTrDDuHeXv7cC5EWBK/ZGqmyXHi/vR8tzE8jN5G','user','9999999999',NULL,NULL,'2025-02-18 23:49:16'),(10,'User Four','user4@ecoride.com','$2y$10$HCz0guZO4vtQy0uQHIfvxufMff09rAmbMRyqyNjMzguL3uEBdJz4e','user','1212121212',NULL,NULL,'2025-02-18 23:49:16'),(11,'User Five','user5@ecoride.com','$2y$10$feYccIVnRQhRIA8odg2t9eZQetBY7R8d36Pgq8KSatCmImx27mXYS','user','1313131313',NULL,NULL,'2025-02-18 23:49:16'),(12,'User Six','user6@ecoride.com','$2y$10$xTqjk9GKRzAnXMui476J6u33Lq5pL/t5A191NMipbkCvvDYGEbSFS','user','1414141414',NULL,NULL,'2025-02-18 23:49:16'),(13,'User Seven','user7@ecoride.com','$2y$10$Jqe2WVvTfvE2cAqGPXdaP.Ns5rvphktqk6iAFcSXM1iz8DH1JzbIu','user','1515151515',NULL,NULL,'2025-02-18 23:49:16'),(14,'User Eight','user8@ecoride.com','$2y$10$KeHnlLciuw7RKmnkM5Nj6ugv1XvhlffMw7FqrPg7uK3ZU5e1nao72','user','1616161616',NULL,NULL,'2025-02-18 23:49:17'),(15,'Alice','alice@ecoride.com','$2y$10$322nhNDADBsaIYGYZ.JLE.9q2.15p6ZbxCLfxnwD07/ariHlJyJ2q','user',NULL,NULL,NULL,'2025-02-19 17:30:57'),(16,'John Wick','wick@ecoride.com','$2y$10$n2G5j2iNKdrve7GQic5CAu0XU5OAKeD4Q9ei3OOVCsgecX.XyO7Ay','driver','0789258695',NULL,NULL,'2025-02-19 18:09:47'),(17,'Sheila Brown','sbrown@ecoride.com','$2y$10$sMgUAdO/pCAuYQnEtDK/UuBOZ4ap4wOT8RRYTw1OP6scTxg4zQ5qO','driver','0783357535','1578879',NULL,'2025-02-20 20:35:47'),(20,'John Doe','johndoe@ecoride.com','$2y$10$/4c5uEMoKCCYy2isbO/Qw.Kw0v9RNCq9RD1tET8eMu2.cWTY8T7/K','user','123456789',NULL,NULL,'2025-02-19 20:52:39'),(23,'John PET','johnpet@ecoride.com','$2y$10$xjEOnehTcPJ0TQcxqdz1EuNQwPT3X8wOKRRAN7T2ppXlyXGp5zQ/C','user','123456789',NULL,NULL,'2025-02-19 20:55:50'),(24,'Bob Smith','bob@ecoride.com','$2y$10$Gbjosv5POPVWh4D6glmMc.c0rfgWOxQ/4qe2mpte8shmX.X.gdy96','driver','987654321',NULL,NULL,'2025-02-19 20:57:01');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `driver_id` int NOT NULL,
  `make` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `year` int NOT NULL,
  `plate` varchar(255) NOT NULL,
  `seats` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plate` (`plate`),
  KEY `driver_id` (`driver_id`),
  CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicles_chk_1` CHECK ((`seats` >= 1))
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,3,'Toyota','Corolla',2020,'ABC123',4,'2025-02-19 00:17:26'),(2,4,'Honda','Civic',2019,'XYZ789',3,'2025-02-19 00:17:26'),(3,5,'Ford','Focus',2021,'LMN456',4,'2025-02-19 00:17:26'),(4,16,'Porsche','Cayenne',2010,'ABC-789-KING',2,'2025-02-19 18:09:47'),(5,17,'Porsche','Cayenne',2018,'king-85',4,'2025-02-19 19:08:39'),(6,24,'Toyota','Corolla',2022,'XYZ123',4,'2025-02-19 20:57:01');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-02-20 22:21:30
