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
  `role` enum('admin','driver','user','employee') DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `suspended` tinyint(1) NOT NULL DEFAULT '0',
  `driver_rating` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `credits` int DEFAULT '20',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin One','admin1@ecoride.com','$2y$10$XcAcyijKq.lO2lCMPdhtnuYIaitGZ1sfWWxOftkJN7wD2anbrF2M2','admin','1111111111',NULL,0,NULL,'2025-05-21 21:05:07',20),(2,'Admin Two','admin2@ecoride.com','$2y$10$8BIgwWY0DJcc7nKPOhRxiuPntyGg06I8LnD1nCL6VeIa60Htypxd2','admin','2222222222',NULL,0,NULL,'2025-05-21 21:05:07',20),(3,'Driver One','driver1@ecoride.com','$2y$10$1npVnIHW4IkmfEssiCSEvuvMJ61ZVW9NWyc/ndeKI2EZyACwoSFQS','driver','3333333333','DRV1001',0,4.80,'2025-05-21 21:05:07',20),(4,'Driver Two','driver2@ecoride.com','$2y$10$Ul40up/armGo7rRE.5qejey56HT3qYZfCjRvhpxPRY/9pt0iVJySS','driver','4444444444','DRV1002',0,4.50,'2025-05-21 21:05:07',20),(5,'Driver Three','driver3@ecoride.com','$2y$10$qJVTq/Hdp1jYxBnGLK8dOubtRvsE1dOcPBw0d9qNFeK4KS9dXOpWG','driver','5555555555','DRV1003',0,4.00,'2025-05-21 21:05:07',20),(6,'Driver Four','driver4@ecoride.com','$2y$10$GadQqjA1SN2KOt2rZpR/n.bBkZjGuTCcFHUboT7UvJknaJQPV.QZm','driver','6666666666','DRV1004',0,NULL,'2025-05-24 12:13:10',20),(7,'User One','user1@ecoride.com','$2y$10$QD3JMJoHNivmMiL63/5LfOawpoKw9tNLpcVaHRFzVqdz1Y7QTqdMi','user','7777777777',NULL,0,NULL,'2025-05-23 13:32:54',20),(8,'User Two','user2@ecoride.com','$2y$10$/XRAMVEIKvGOD8IJR77kDuZTzYfrG4lbUg6YEavPmts5jxuRaZMSu','user','8888888888',NULL,0,NULL,'2025-05-23 12:21:01',25),(9,'User Three','user3@ecoride.com','$2y$10$jiFT3owvzzCVMnLXvwze2OQ2CT5EoBP4MpsqWKgOiIy3UodVXQwbW','user','9999999999',NULL,0,NULL,'2025-05-23 13:29:15',30),(10,'User Four','user4@ecoride.com','$2y$10$tu7j8z.JHL0Zw/n/RxUNNeyIFdl9G/WTAsr7GtBWEK2nfJ7anciC2','user','1212121212',NULL,0,NULL,'2025-05-21 21:05:07',20),(11,'User Five','user5@ecoride.com','$2y$10$V5c/nHZoJ577SUZcvBRda.tlUJSz9OkV0YorWLQ99eteX/pRKEEZO','user','1313131313',NULL,0,NULL,'2025-05-24 16:11:33',30),(12,'User Six','user6@ecoride.com','$2y$10$2aTBz8EwIAj8zicUeqYGUuyD24mZ3zGahQlFGnjkDew/8YAl8Ol4y','user','1414141414',NULL,0,NULL,'2025-05-21 21:05:07',20),(13,'User Seven','user7@ecoride.com','$2y$10$VO2atFp41uuxf4W7AUxK/O50k2oNpnK0o17aRUI1CI1FRs2pB2eum','user','1515151515',NULL,0,NULL,'2025-05-21 21:05:07',20),(14,'User Eight','user8@ecoride.com','$2y$10$iMIPSKpohZnvqaxNQsiaEO9lSDb6mZYsxEjxCgPxcAohUswUWd4QG','user','1616161616',NULL,0,NULL,'2025-05-21 21:05:08',20),(15,'Employee One','employee1@ecoride.com','$2y$10$rssHR.EUT9y8bEUV9Xz5ROW1udDxOabd/kQ.IX/soEAF9n7XT6ZOu','employee','1717171717',NULL,0,NULL,'2025-05-21 21:05:08',20),(16,'Employee Two','employee2@ecoride.com','$2y$10$0NW9LXTo3tAOlCcpYuzUtesNWEe1kS3bXpiDKeUlhyOiYfOmdaPC.','employee','1818181818',NULL,0,NULL,'2025-05-21 21:05:08',20),(17,'EcoRide Support','contact@ecoride.com','$2y$10$U4FB0D6GPWd.ztuWagbyKeDQQYHZLcQtdUgnCPeTBpfCQ5fqV/p5G','admin',NULL,NULL,0,NULL,'2025-05-26 15:24:13',0),(18,'Albus Dumbledore','albusd@ecoride.com','$2y$10$JELh4FlTiTP1PGQyNyw42e29Q8SrIt1F9L1ivOpzc6y8fsa/aJvy.','driver','0658926428',NULL,0,NULL,'2025-05-28 15:48:29',20),(19,'John Wick','john.wick@ecoride.com','$2y$10$Y7ey/avjxkjZ7luxJtHlROYWgLbWAU2efjeP1nNZjfKTB2q2Eq9Ou','driver','+1 212 555 0194',NULL,0,NULL,'2025-05-28 17:04:58',20),(20,'Uu  udidiid','albuud@ecoride.com','$2y$10$bTEEsTb8r7zUq9h/uY2Ku.CtR/e7PLEFE6IX/GFoLRwG7vvvSLK8W','user','02789358',NULL,0,NULL,'2025-06-17 16:41:08',20);
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

-- Dump completed on 2025-06-25 21:43:18
