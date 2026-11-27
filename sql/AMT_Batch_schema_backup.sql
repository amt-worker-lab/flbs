/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: AMT_Batch
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
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment` text NOT NULL,
  `fk_user_name` varchar(10) NOT NULL,
  `fk_id_jobs_history_index` int(11) unsigned DEFAULT NULL,
  `fk_requester_id` varchar(45) DEFAULT NULL,
  `last_modified` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_comments_1_idx` (`fk_user_name`),
  KEY `fk_comments_2_idx` (`fk_id_jobs_history_index`),
  KEY `fk_comments_3_idx` (`fk_requester_id`),
  CONSTRAINT `fk_comments_1` FOREIGN KEY (`fk_user_name`) REFERENCES `users` (`user_name`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_comments_2` FOREIGN KEY (`fk_id_jobs_history_index`) REFERENCES `jobs_history_index` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_comments_3` FOREIGN KEY (`fk_requester_id`) REFERENCES `requester_ids` (`requester_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_jobs_history_index` int(11) unsigned NOT NULL,
  `date_run_time` int(11) unsigned NOT NULL,
  `HITs_available` int(11) unsigned NOT NULL,
  `date_dmy` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_history_1_idx` (`id_jobs_history_index`),
  CONSTRAINT `fk_history_1` FOREIGN KEY (`id_jobs_history_index`) REFERENCES `jobs_history_index` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=184839251 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `history_new`
--

DROP TABLE IF EXISTS `history_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `history_new` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_jobs_history_index` int(11) unsigned NOT NULL,
  `date_run_time` int(11) unsigned NOT NULL,
  `HITs_available` int(11) unsigned NOT NULL,
  `date_dmy` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_history_1_idx` (`id_jobs_history_index`),
  KEY `index3` (`date_run_time`),
  KEY `index4` (`HITs_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `history_new2`
--

DROP TABLE IF EXISTS `history_new2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `history_new2` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_jobs_history_index` int(11) unsigned NOT NULL,
  `avg_HITs_available` int(11) unsigned NOT NULL,
  `date_dmy` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_history_1_idx` (`id_jobs_history_index`),
  KEY `index3` (`id_jobs_history_index`,`avg_HITs_available`,`date_dmy`)
) ENGINE=InnoDB AUTO_INCREMENT=157532221 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `history_weekly`
--

DROP TABLE IF EXISTS `history_weekly`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `history_weekly` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_jobs_history_index` int(11) unsigned NOT NULL,
  `date_run_time` int(11) unsigned NOT NULL,
  `HITs_available` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_history_weekly_1_idx` (`id_jobs_history_index`),
  CONSTRAINT `fk_history_weekly_1` FOREIGN KEY (`id_jobs_history_index`) REFERENCES `jobs_history_index` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=169235924 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `HITs_available` int(11) DEFAULT NULL,
  `time_posted` int(11) DEFAULT NULL,
  `time_last_update` int(11) DEFAULT NULL,
  `job_exists` int(11) DEFAULT NULL,
  `id_duplicate_jobs` int(11) NOT NULL DEFAULT 0,
  `on_page_number` int(11) DEFAULT NULL,
  `id_jobs_history_index` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_jobs_1_idx` (`id_jobs_history_index`),
  CONSTRAINT `fk_jobs_1` FOREIGN KEY (`id_jobs_history_index`) REFERENCES `jobs_history_index` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2378964 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs_history_index`
--

DROP TABLE IF EXISTS `jobs_history_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs_history_index` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` text DEFAULT NULL,
  `requester_id` varchar(45) DEFAULT NULL,
  `requester_name` varchar(255) DEFAULT NULL,
  `id_duplicate_jobs` int(11) unsigned NOT NULL DEFAULT 0,
  `qualifications` text DEFAULT NULL,
  `groupId` varchar(45) DEFAULT NULL,
  `reward` varchar(45) DEFAULT NULL,
  `time_allotted` varchar(45) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `hash_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash_id_UNIQUE` (`hash_id`),
  KEY `fk_jobs_history_index_1_idx` (`requester_id`),
  CONSTRAINT `fk_jobs_history_index_1` FOREIGN KEY (`requester_id`) REFERENCES `requester_ids` (`requester_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=324557 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `last_update`
--

DROP TABLE IF EXISTS `last_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `last_update` (
  `id` int(11) NOT NULL,
  `HITs_available` int(11) DEFAULT NULL,
  `jobs` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requester_ids`
--

DROP TABLE IF EXISTS `requester_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requester_ids` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `requester_id_UNIQUE` (`requester_id`)
) ENGINE=InnoDB AUTO_INCREMENT=298235 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name_UNIQUE` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vote` smallint(6) NOT NULL,
  `fk_user_name` varchar(10) NOT NULL,
  `fk_id_jobs_history_index` int(11) unsigned DEFAULT NULL,
  `fk_requester_id` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_votes_1_idx` (`fk_user_name`),
  KEY `fk_votes_2_idx` (`fk_id_jobs_history_index`),
  KEY `fk_votes_3_idx` (`fk_requester_id`),
  CONSTRAINT `fk_votes_1` FOREIGN KEY (`fk_user_name`) REFERENCES `users` (`user_name`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_votes_2` FOREIGN KEY (`fk_id_jobs_history_index`) REFERENCES `jobs_history_index` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_votes_3` FOREIGN KEY (`fk_requester_id`) REFERENCES `requester_ids` (`requester_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-26 23:01:48
