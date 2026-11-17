/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: IncomeRecords
-- ------------------------------------------------------
-- Server version	11.8.8-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `HITS`
--

DROP TABLE IF EXISTS `HITS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `HITS` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Reward` decimal(10,2) unsigned DEFAULT NULL,
  `RequesterName` varchar(45) DEFAULT NULL,
  `JobName` varchar(400) DEFAULT NULL,
  `Returned` bit(1) DEFAULT NULL,
  `TimeAccepted` bigint(20) unsigned DEFAULT NULL,
  `TimeFinished` bigint(20) DEFAULT NULL,
  `ExpirationTime` bigint(20) DEFAULT NULL,
  `UserName` varchar(45) DEFAULT NULL,
  `AccountName` varchar(45) DEFAULT NULL,
  `ComputerId` tinyint(4) DEFAULT NULL,
  `GroupId` varchar(45) DEFAULT NULL,
  `RequesterId` varchar(45) DEFAULT NULL,
  `HitId` varchar(45) DEFAULT NULL,
  `IsFailed` bit(1) DEFAULT NULL,
  `TypeOfFail` varchar(45) DEFAULT NULL,
  `ExpirationDate` date DEFAULT NULL,
  `AcceptedDate` date DEFAULT NULL,
  `UniqueJobHash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `testIndex` (`AccountName`,`HitId`),
  KEY `timeAcceptedIndex` (`TimeAccepted`),
  KEY `requesterNameIdx` (`RequesterName`),
  KEY `rewardIdx` (`Reward`),
  KEY `JobNameIdx` (`JobName`(383)),
  KEY `ComputerIdIdx` (`ComputerId`),
  KEY `UniqueJobHash` (`UniqueJobHash`),
  KEY `AcceptedDate` (`AcceptedDate`)
) ENGINE=InnoDB AUTO_INCREMENT=2352564 DEFAULT CHARSET=big5 COLLATE=big5_chinese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HITS_DAILY`
--

DROP TABLE IF EXISTS `HITS_DAILY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `HITS_DAILY` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Reward` decimal(10,2) unsigned DEFAULT NULL,
  `RequesterName` varchar(45) DEFAULT NULL,
  `JobName` varchar(400) DEFAULT NULL,
  `Returned` bit(1) DEFAULT NULL,
  `TimeAccepted` bigint(20) unsigned DEFAULT NULL,
  `TimeFinished` bigint(20) DEFAULT NULL,
  `ExpirationTime` bigint(20) DEFAULT NULL,
  `UserName` varchar(45) DEFAULT NULL,
  `AccountName` varchar(45) DEFAULT NULL,
  `ComputerId` tinyint(4) DEFAULT NULL,
  `GroupId` varchar(45) DEFAULT NULL,
  `RequesterId` varchar(45) DEFAULT NULL,
  `HitId` varchar(45) DEFAULT NULL,
  `IsFailed` bit(1) DEFAULT NULL,
  `TypeOfFail` varchar(45) DEFAULT NULL,
  `ExpirationDate` date DEFAULT NULL,
  `AcceptedDate` date DEFAULT NULL,
  `UniqueJobHash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `RequesterNameIdx` (`RequesterName`),
  KEY `JobNameIdx` (`JobName`(383)),
  KEY `UniqueJobHashIdx` (`UniqueJobHash`),
  KEY `AcceptedDateIdx` (`AcceptedDate`),
  KEY `ComputerIdIdx` (`ComputerId`),
  KEY `UserNameIdx` (`UserName`),
  KEY `TimeAcceptedIdx` (`TimeAccepted`)
) ENGINE=InnoDB AUTO_INCREMENT=2016 DEFAULT CHARSET=big5 COLLATE=big5_chinese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Unique_Jobs`
--

DROP TABLE IF EXISTS `Unique_Jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Unique_Jobs` (
  `JobName` varchar(400) DEFAULT NULL,
  `RequesterName` varchar(45) DEFAULT NULL,
  `Reward` decimal(10,2) DEFAULT NULL,
  `TimeFirstAccepted` bigint(20) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UniqueJobHash` varchar(255) DEFAULT NULL,
  `AllTotalIncome` int(11) DEFAULT NULL,
  `AllNumReturns` int(11) DEFAULT NULL,
  `AllNumSubmitted` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UniqueJobHash_UNIQUE` (`UniqueJobHash`)
) ENGINE=InnoDB AUTO_INCREMENT=1908 DEFAULT CHARSET=big5 COLLATE=big5_chinese_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-15  9:57:00
