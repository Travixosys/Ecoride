-- TiDB-compatible schema for EcoRide
-- Run this entire script in TiDB SQL Editor

-- Disable foreign key checks during import
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- 1. USERS TABLE (base table, no dependencies)
-- =============================================
DROP TABLE IF EXISTS `ride_reviews`;
DROP TABLE IF EXISTS `ride_requests`;
DROP TABLE IF EXISTS `carpools`;
DROP TABLE IF EXISTS `rides`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','driver','admin','employee') NOT NULL DEFAULT 'user',
  `phone_number` varchar(20) DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `driver_rating` decimal(3,2) DEFAULT NULL,
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

INSERT INTO `users` VALUES
(1,'Admin One','admin1@ecoride.com','$2y$10$Ih/McIKtTQfYaj5e4Gzln.iNB63etNAEP78vPHDj9V5ycIsiUQjga','admin','1111111111',NULL,NULL,'active','2025-02-18 23:49:16'),
(2,'Admin Two','admin2@ecoride.com','$2y$10$5PC/orq77OsnBULDoY1MSOGf63d/K3LOfN883Ry54J5BbZqZGVR.m','admin','2222222222',NULL,NULL,'active','2025-02-18 23:49:16'),
(3,'Driver One','driver1@ecoride.com','$2y$10$p2t7XkRPFUsTfMd4PFGGQeNXDYpT9bvT407QeIamzlp.VdSETQMx2','driver','3333333333','DRV1001',4.80,'active','2025-02-18 23:49:16'),
(4,'Driver Two','driver2@ecoride.com','$2y$10$eUPPwSZHEPZLLR6tIOVC5OoE8PPrJdybAhvAQqs1Fc32bX4sZJF3O','driver','4444444444','DRV1002',4.50,'active','2025-02-18 23:49:16'),
(5,'Driver Three','driver3@ecoride.com','$2y$10$PLSi1tjb6MqcC/gkWvIMdOCoKtwmMKUcjN9N2WpTb55iL/Sk7L7wK','driver','5555555555','DRV1053',4.60,'active','2025-02-20 20:31:46'),
(6,'Driver Four','driver4@ecoride.com','$2y$10$8ISu7o0OyceQOyeh0x3wqu9ThKXnmsGbzFT.JEf80W6pDPRcYc25e','driver','6666666666',NULL,NULL,'active','2025-02-18 23:49:16'),
(7,'User One','user1@ecoride.com','$2y$10$Mag8eBGIQDroQH7rCJA4ROxnh8V1R9Z3qi0qo/lv6adDRGNWyY4Ry','user','7777777777',NULL,NULL,'active','2025-02-18 23:49:16'),
(8,'User Two','user2@ecoride.com','$2y$10$JppaeyM7IfORRhNzBHFs0u9.125LQPJBCtUOqgi4OAFBJfSOMp7KC','user','8888888888',NULL,NULL,'active','2025-02-18 23:49:16'),
(9,'User Three','user3@ecoride.com','$2y$10$gB1hztShz5I0t6aTrDDuHeXv7cC5EWBK/ZGqmyXHi/vR8tzE8jN5G','user','9999999999',NULL,NULL,'active','2025-02-18 23:49:16'),
(10,'User Four','user4@ecoride.com','$2y$10$HCz0guZO4vtQy0uQHIfvxufMff09rAmbMRyqyNjMzguL3uEBdJz4e','user','1212121212',NULL,NULL,'active','2025-02-18 23:49:16'),
(11,'User Five','user5@ecoride.com','$2y$10$feYccIVnRQhRIA8odg2t9eZQetBY7R8d36Pgq8KSatCmImx27mXYS','user','1313131313',NULL,NULL,'active','2025-02-18 23:49:16'),
(12,'User Six','user6@ecoride.com','$2y$10$xTqjk9GKRzAnXMui476J6u33Lq5pL/t5A191NMipbkCvvDYGEbSFS','user','1414141414',NULL,NULL,'active','2025-02-18 23:49:16'),
(13,'User Seven','user7@ecoride.com','$2y$10$Jqe2WVvTfvE2cAqGPXdaP.Ns5rvphktqk6iAFcSXM1iz8DH1JzbIu','user','1515151515',NULL,NULL,'active','2025-02-18 23:49:16'),
(14,'User Eight','user8@ecoride.com','$2y$10$KeHnlLciuw7RKmnkM5Nj6ugv1XvhlffMw7FqrPg7uK3ZU5e1nao72','user','1616161616',NULL,NULL,'active','2025-02-18 23:49:17'),
(15,'Alice','alice@ecoride.com','$2y$10$322nhNDADBsaIYGYZ.JLE.9q2.15p6ZbxCLfxnwD07/ariHlJyJ2q','user',NULL,NULL,NULL,'active','2025-02-19 17:30:57'),
(16,'John Wick','wick@ecoride.com','$2y$10$n2G5j2iNKdrve7GQic5CAu0XU5OAKeD4Q9ei3OOVCsgecX.XyO7Ay','driver','0789258695',NULL,NULL,'active','2025-02-19 18:09:47'),
(17,'Sheila Brown','sbrown@ecoride.com','$2y$10$sMgUAdO/pCAuYQnEtDK/UuBOZ4ap4wOT8RRYTw1OP6scTxg4zQ5qO','driver','0783357535','1578879',NULL,'active','2025-02-20 20:35:47'),
(20,'John Doe','johndoe@ecoride.com','$2y$10$/4c5uEMoKCCYy2isbO/Qw.Kw0v9RNCq9RD1tET8eMu2.cWTY8T7/K','user','123456789',NULL,NULL,'active','2025-02-19 20:52:39'),
(23,'John PET','johnpet@ecoride.com','$2y$10$xjEOnehTcPJ0TQcxqdz1EuNQwPT3X8wOKRRAN7T2ppXlyXGp5zQ/C','user','123456789',NULL,NULL,'active','2025-02-19 20:55:50'),
(24,'Bob Smith','bob@ecoride.com','$2y$10$Gbjosv5POPVWh4D6glmMc.c0rfgWOxQ/4qe2mpte8shmX.X.gdy96','driver','987654321',NULL,NULL,'active','2025-02-19 20:57:01'),
(25,'Employee One','employee1@ecoride.com','$2y$10$Ih/McIKtTQfYaj5e4Gzln.iNB63etNAEP78vPHDj9V5ycIsiUQjga','employee','5551234567',NULL,NULL,'active','2025-02-20 10:00:00');

-- =============================================
-- 2. VEHICLES TABLE (depends on users)
-- =============================================
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
  CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

INSERT INTO `vehicles` VALUES
(1,3,'Toyota','Corolla',2020,'ABC123',4,'2025-02-19 00:17:26'),
(2,4,'Honda','Civic',2019,'XYZ789',3,'2025-02-19 00:17:26'),
(3,5,'Ford','Focus',2021,'LMN456',4,'2025-02-19 00:17:26'),
(4,16,'Porsche','Cayenne',2010,'ABC-789-KING',2,'2025-02-19 18:09:47'),
(5,17,'Porsche','Cayenne',2018,'king-85',4,'2025-02-19 19:08:39'),
(6,24,'Toyota','Corolla',2022,'XYZ123',4,'2025-02-19 20:57:01');

-- =============================================
-- 3. RIDES TABLE (depends on users, vehicles)
-- =============================================
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
);

INSERT INTO `rides` VALUES
(1,6,NULL,NULL,'123 Main St','456 Elm St','completed','2025-02-19 00:19:28'),
(2,7,4,2,'789 Pine St','987 Oak St','accepted','2025-02-19 00:19:28'),
(3,8,5,3,'654 Birch St','321 Cedar St','completed','2025-02-19 00:19:28'),
(4,9,3,NULL,'222 Willow St','555 Maple Ave','completed','2025-02-19 01:12:22'),
(5,10,3,1,'222 Willow St','555 Maple Ave','accepted','2025-02-19 01:14:36');

-- =============================================
-- 4. CARPOOLS TABLE (depends on users, vehicles)
-- =============================================
CREATE TABLE `carpools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` int NOT NULL,
  `vehicle_id` int NOT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `dropoff_location` varchar(255) DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `total_seats` int NOT NULL DEFAULT 4,
  `occupied_seats` int NOT NULL DEFAULT 0,
  `status` enum('upcoming','in progress','completed','disputed','resolved','canceled') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_carpools_driver` (`driver_id`),
  KEY `fk_carpools_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_carpools_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_carpools_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
);

INSERT INTO `carpools` VALUES
(1,3,1,'Paris','Lyon','2025-05-23 10:48:55',4,2,'completed','2025-05-22 08:48:55','2025-05-23 13:03:36'),
(2,3,1,'Paris','Lille','2025-05-22 09:48:55',4,3,'disputed','2025-05-22 08:48:55','2025-05-24 16:52:06'),
(3,3,1,'Nice','Marseille','2025-05-20 10:48:55',4,4,'completed','2025-05-22 08:48:55','2025-05-23 13:29:15'),
(4,3,1,'Nice','Geneva','2025-05-21 10:48:55',3,3,'resolved','2025-05-22 08:48:55','2025-05-22 12:36:18'),
(5,4,2,'Lille','Brussels','2025-05-23 10:48:55',4,1,'completed','2025-05-22 08:48:55','2025-05-24 16:11:33'),
(6,4,2,'Rouen','Caen','2025-05-22 07:48:55',4,2,'in progress','2025-05-22 08:48:55','2025-05-22 08:48:55'),
(7,4,2,'Toulouse','Bordeaux','2025-05-20 10:48:55',4,2,'completed','2025-05-22 08:48:55','2025-05-22 08:48:55'),
(8,5,3,'Dijon','Grenoble','2025-05-24 10:48:55',2,1,'upcoming','2025-05-22 08:48:55','2025-05-22 08:48:55'),
(9,5,3,'Nantes','Tours','2025-05-22 09:48:55',2,2,'disputed','2025-05-22 08:48:55','2025-05-24 16:52:08'),
(10,5,3,'Strasbourg','Nancy','2025-05-19 10:48:55',2,2,'disputed','2025-05-22 08:48:55','2025-05-24 17:00:48'),
(11,6,4,'Avignon','Montpellier','2025-05-22 22:48:55',3,1,'upcoming','2025-05-22 08:48:55','2025-05-23 12:21:01'),
(12,6,4,'Clermont-Ferrand','Limoges','2025-05-22 08:48:55',3,1,'in progress','2025-05-22 08:48:55','2025-05-22 08:48:55'),
(13,6,4,'Nice','Saint-Maur','2025-05-21 10:48:55',3,3,'disputed','2025-05-22 08:48:55','2025-05-22 08:48:55'),
(14,6,4,'La Rochelle','Angers','2025-05-20 10:48:55',3,3,'completed','2025-05-22 08:48:55','2025-05-22 08:48:55'),
(15,3,1,'ourcq','jumia','2026-02-25 12:58:00',2,2,'completed','2025-05-23 13:31:32','2025-05-23 13:32:54'),
(16,3,1,'studi-lyon','studi-paris','2025-09-07 14:30:00',2,0,'upcoming','2025-05-27 13:57:40','2025-05-27 13:57:40'),
(17,3,1,'studi-lyon','studi-paris-brest','2025-06-27 14:52:00',2,0,'upcoming','2025-06-17 19:20:10','2025-06-17 19:20:10');

-- =============================================
-- 5. RIDE_REQUESTS TABLE (depends on users)
-- =============================================
CREATE TABLE `ride_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passenger_id` int NOT NULL,
  `driver_id` int DEFAULT NULL,
  `carpool_id` int DEFAULT NULL,
  `pickup_location` text NOT NULL,
  `dropoff_location` text NOT NULL,
  `passenger_count` int NOT NULL DEFAULT 1,
  `status` enum('pending','accepted','cancelled','completed','disputed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `passenger_id` (`passenger_id`),
  KEY `driver_id` (`driver_id`),
  CONSTRAINT `ride_requests_ibfk_1` FOREIGN KEY (`passenger_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ride_requests_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

INSERT INTO `ride_requests` VALUES
(1,7,3,1,'Paris','Lyon',1,'completed','2025-05-22 08:48:55'),
(2,8,3,2,'Paris','Lille',1,'completed','2025-05-22 08:48:55'),
(3,9,3,3,'Nice','Marseille',1,'completed','2025-05-22 08:48:55'),
(4,10,3,4,'Nice','Geneva',1,'disputed','2025-05-22 08:48:55'),
(5,11,4,5,'Lille','Brussels',1,'completed','2025-05-22 08:48:55'),
(6,12,4,6,'Rouen','Caen',1,'accepted','2025-05-22 08:48:55'),
(7,13,4,7,'Toulouse','Bordeaux',1,'completed','2025-05-22 08:48:55'),
(8,14,5,8,'Dijon','Grenoble',1,'accepted','2025-05-22 08:48:55'),
(9,8,5,9,'Nantes','Tours',1,'completed','2025-05-22 08:48:55'),
(10,8,5,10,'Strasbourg','Nancy',1,'completed','2025-05-22 08:48:55'),
(11,9,6,11,'Avignon','Montpellier',1,'pending','2025-05-22 08:48:55'),
(12,10,6,12,'Clermont-Ferrand','Limoges',1,'accepted','2025-05-22 08:48:55'),
(13,11,6,13,'Nice','Saint-Maur',1,'disputed','2025-05-22 08:48:55'),
(14,12,6,14,'La Rochelle','Angers',1,'completed','2025-05-22 08:48:55'),
(15,7,5,9,'Nantes','Tours',1,'completed','2025-05-22 08:48:55'),
(16,8,6,11,'Avignon','Montpellier',1,'accepted','2025-05-23 12:21:01'),
(17,7,3,15,'ourcq','jumia',2,'completed','2025-05-23 13:32:11');

-- =============================================
-- 6. RIDE_REVIEWS TABLE (depends on ride_requests, users)
-- =============================================
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
  CONSTRAINT `ride_reviews_ibfk_3` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`)
);

INSERT INTO `ride_reviews` VALUES
(1,2,8,3,5,'ui test ','approved','2025-05-23 11:58:37'),
(2,9,8,5,2,'teting','approved','2025-05-23 11:59:19'),
(3,10,8,5,4,'22223','rejected','2025-05-23 12:06:01'),
(4,12,7,3,4,'Good driver, punctual','approved','2025-05-23 14:01:23'),
(5,12,7,3,4,'Good driver, punctual','pending','2025-05-23 14:02:01'),
(6,17,7,3,2,'thank you','pending','2025-05-23 22:38:36');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify tables were created
SELECT 'Schema import completed successfully!' AS status;
SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE();
