-- MySQL dump 10.13  Distrib 5.5.62, for Win64 (AMD64)
--
-- Host: localhost    Database: tournament
-- ------------------------------------------------------
-- Server version	5.7.25

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team` varchar(50) DEFAULT NULL,
  `nickname` varchar(50) NOT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `lifes` int(1) DEFAULT '2',
  `is_suspended` tinyint(1) DEFAULT '0',
  `prize` bigint(20) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `wins` int(11) DEFAULT NULL,
  `games_played` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `players_tournament_id_fk` (`tournament_id`),
  CONSTRAINT `players_tournament_id_fk` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 AVG_ROW_LENGTH=682;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
INSERT INTO `players` VALUES (187,NULL,'Player1',22,2,0,NULL,'Брянск',0,0),(188,NULL,'Player2',22,2,0,NULL,'Брянск',0,0),(189,NULL,'Player3',22,2,0,NULL,'Брянск',0,0),(190,NULL,'Player4',22,2,0,NULL,'Брянск',0,0),(191,NULL,'Player5',22,2,0,NULL,'Брянск',0,0),(192,NULL,'Player6',22,2,0,NULL,'Брянск',0,0),(193,NULL,'Player7',22,2,0,NULL,'Брянск',0,0),(194,NULL,'Player8',22,2,0,NULL,'Брянск',0,0),(195,NULL,'Player9',22,2,0,NULL,'Брянск',0,0),(196,NULL,'Player10',22,2,0,NULL,'Брянск',0,0),(197,NULL,'Player11',22,2,0,NULL,'Брянск',0,0),(198,NULL,'Player12',22,2,0,NULL,'Брянск',0,0),(199,NULL,'Player13',22,2,0,NULL,'Брянск',0,0),(200,NULL,'Player14',22,2,0,NULL,'Брянск',0,0),(201,NULL,'Player15',22,2,0,NULL,'Брянск',0,0),(202,NULL,'Player16',22,2,0,NULL,'Калуга',0,0),(203,NULL,'Player17',22,2,0,NULL,'Калуга',0,0),(204,NULL,'Player18',22,2,0,NULL,'Калуга',0,0),(205,NULL,'Player19',22,2,0,NULL,'Калуга',0,0),(206,NULL,'Player20',22,2,0,NULL,'Калуга',0,0),(207,NULL,'Player21',22,2,0,NULL,'Орел',0,0),(208,NULL,'Player22',22,2,0,NULL,'Орел',0,0),(209,NULL,'Player23',22,2,0,NULL,'Калуга',0,0),(210,NULL,'Player24',22,2,0,NULL,'Калуга',0,0),(211,NULL,'Player25',22,2,0,NULL,'Калуга',0,0),(212,NULL,'Player26',22,2,0,NULL,'Орел',0,0),(213,NULL,'Player27',22,2,0,NULL,'Орел',0,0),(214,NULL,'Player28',22,2,0,NULL,'Орел',0,0),(215,NULL,'Player29',22,2,0,NULL,'Орел',0,0),(216,NULL,'Player30',22,2,0,NULL,'Орел',0,0),(217,NULL,'Player31',22,2,0,NULL,'Орел',0,0),(218,NULL,'Player32',22,2,0,NULL,'Брянск',0,0),(219,NULL,'Player33',22,2,0,NULL,'Брянск',0,0),(220,NULL,'Player',22,2,0,NULL,'Брянск',0,0),(221,NULL,'Player',22,2,0,NULL,'Брянск',0,0),(222,NULL,'Player',22,2,0,NULL,'Брянск',0,0),(223,NULL,'Player',22,2,0,NULL,'Брянск',0,0),(224,NULL,'Player',22,2,0,NULL,'Брянск',0,0),(225,NULL,'Player',22,2,0,NULL,'Брянск',0,0),(247,NULL,'Player0',23,2,0,NULL,NULL,0,0),(248,NULL,'Player1',23,2,0,NULL,NULL,0,0),(249,NULL,'Player2',23,2,0,NULL,NULL,0,0),(250,NULL,'Player3',23,2,0,NULL,NULL,0,0),(251,NULL,'Player4',23,2,0,NULL,NULL,0,0),(252,NULL,'Player5',23,2,0,NULL,NULL,0,0),(253,NULL,'Player6',23,2,0,NULL,NULL,0,0),(254,NULL,'Player7',23,2,0,NULL,NULL,0,0),(255,NULL,'Player8',23,2,0,NULL,NULL,0,0),(256,NULL,'Player9',23,2,0,NULL,NULL,0,0),(257,NULL,'Player10',23,2,0,NULL,NULL,0,0),(258,NULL,'Player11',23,2,0,NULL,NULL,0,0),(259,NULL,'Player13',23,2,0,NULL,NULL,0,0),(260,NULL,'Player14',23,2,0,NULL,NULL,0,0),(261,NULL,'Player15',23,2,0,NULL,NULL,0,0),(262,NULL,'Player16',23,2,0,NULL,NULL,0,0),(263,NULL,'Player17',23,2,0,NULL,NULL,0,0),(264,NULL,'Player18',23,2,0,NULL,NULL,0,0);
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tournament`
--

DROP TABLE IF EXISTS `tournament`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tournament` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `status` enum('awaiting','in progress','ended') NOT NULL DEFAULT 'awaiting',
  `current_round` int(11) DEFAULT NULL,
  `round_count` int(11) DEFAULT NULL,
  `toss` blob COMMENT 'serilization of TOSS method result',
  `prize_pool` bigint(20) DEFAULT NULL,
  `type` enum('1','2') DEFAULT NULL,
  `regions` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tournament_user_id_fk` (`owner_id`),
  CONSTRAINT `tournament_user_id_fk` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 AVG_ROW_LENGTH=8192;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tournament`
--

LOCK TABLES `tournament` WRITE;
/*!40000 ALTER TABLE `tournament` DISABLE KEYS */;
INSERT INTO `tournament` VALUES (22,'Tournament_Regional','2020-02-20',14,'awaiting',NULL,NULL,'null',120000,'2','[\"Брянск\", \"Орел\", \"Калуга\"]'),(23,'Tournament_Normal','2020-02-20',14,'awaiting',NULL,NULL,'null',100000,'1',NULL);
/*!40000 ALTER TABLE `tournament` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(320) NOT NULL,
  `hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_login_uindex` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 AVG_ROW_LENGTH=16384;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (14,'test','test','test@test.ru','$2y$10$QUxaymdEkPcBDa.G3wpW9.lTwo7oruS19lFaXN9xYy1IVWz2zWzl2'),(15,'pass1234QQ','pass1234QQ','p@p.ru','$2y$10$QUxaymdEkPcBDa.G3wpW9.lTwo7oruS19lFaXN9xYy1IVWz2zWzl2');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'tournament'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-11-18 12:21:31
