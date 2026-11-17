/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: AMT_Alerts
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
-- Table structure for table `HIT_ids_to_correct`
--

DROP TABLE IF EXISTS `HIT_ids_to_correct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `HIT_ids_to_correct` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hit_id` varchar(60) NOT NULL,
  `locked_timestamp` int(10) unsigned DEFAULT NULL,
  `correct_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hit_id_UNIQUE` (`hit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2961 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `features_and_bugs`
--

DROP TABLE IF EXISTS `features_and_bugs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `features_and_bugs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `description` text DEFAULT NULL,
  `fk_created_by_user` varchar(45) DEFAULT NULL,
  `date_added` int(11) NOT NULL,
  `date_last_modified` int(11) DEFAULT NULL,
  `fk_requesterUserId` varchar(45) DEFAULT NULL,
  `fk_titleRequesterHash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `title` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `priority` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_features_and_bugs_1_idx` (`fk_requesterUserId`),
  KEY `fk_features_and_bugs_3_idx` (`fk_created_by_user`),
  KEY `fk_features_and_bugs_2_idx` (`fk_titleRequesterHash`),
  CONSTRAINT `fk_features_and_bugs_1` FOREIGN KEY (`fk_requesterUserId`) REFERENCES `requesters` (`requesterUserId`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_features_and_bugs_2` FOREIGN KEY (`fk_titleRequesterHash`) REFERENCES `tasks` (`titleRequesterHash`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_features_and_bugs_3` FOREIGN KEY (`fk_created_by_user`) REFERENCES `users` (`user_name`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hit_ids`
--

DROP TABLE IF EXISTS `hit_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hit_ids` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hit_id` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hit_id_UNIQUE` (`hit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2628309 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `note` text DEFAULT NULL,
  `fk_user` varchar(45) DEFAULT NULL,
  `date_added` int(11) NOT NULL,
  `date_last_modified` int(11) DEFAULT NULL,
  `fk_requesterUserId` varchar(45) DEFAULT NULL,
  `fk_titleRequesterHash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requester_names`
--

DROP TABLE IF EXISTS `requester_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requester_names` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `requesterUserId` varchar(45) NOT NULL,
  `requesterName` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_requester_names_1_idx` (`requesterUserId`),
  CONSTRAINT `fk_requester_names_1` FOREIGN KEY (`requesterUserId`) REFERENCES `requesters` (`requesterUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=800 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requesters`
--

DROP TABLE IF EXISTS `requesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requesters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `requesterUserId` varchar(45) NOT NULL,
  `wakeupAlertDefault` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `emailAlertDefault` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `moneyAmountThreshold` int(11) unsigned NOT NULL DEFAULT 0,
  `minTasksThreshold` int(11) unsigned NOT NULL DEFAULT 0,
  `dateAdded` int(11) unsigned NOT NULL DEFAULT 0,
  `alarm_active` tinyint(4) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `requesterUserId_UNIQUE` (`requesterUserId`)
) ENGINE=InnoDB AUTO_INCREMENT=824 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ta_accounts`
--

DROP TABLE IF EXISTS `ta_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ta_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_name` varchar(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_name_UNIQUE` (`account_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ta_dates`
--

DROP TABLE IF EXISTS `ta_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ta_dates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `day` tinyint(4) unsigned NOT NULL,
  `month` tinyint(4) unsigned NOT NULL,
  `year` smallint(6) unsigned NOT NULL,
  `date_string` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_string_UNIQUE` (`date_string`)
) ENGINE=InnoDB AUTO_INCREMENT=1261 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ta_rewards`
--

DROP TABLE IF EXISTS `ta_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ta_rewards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reward` decimal(4,2) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reward_UNIQUE` (`reward`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ta_status`
--

DROP TABLE IF EXISTS `ta_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ta_status` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_UNIQUE` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task_user_tags`
--

DROP TABLE IF EXISTS `task_user_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_user_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_UNIQUE` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task_user_tags_idx`
--

DROP TABLE IF EXISTS `task_user_tags_idx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_user_tags_idx` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_tasks_accomplished_id` int(11) NOT NULL,
  `fk_tag` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wakeupAlert` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `emailAlert` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `taskTitle` varchar(255) NOT NULL,
  `minTasksThreshold` int(11) unsigned NOT NULL DEFAULT 0,
  `moneyAmountThreshold` int(11) unsigned NOT NULL DEFAULT 0,
  `requesterUserId` varchar(45) NOT NULL,
  `dateAdded` int(11) unsigned NOT NULL DEFAULT 0,
  `titleRequesterHash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL COMMENT 'UTF8_BIN BECAUSE OF CASE SENSITIVE!',
  `alarm_active` tinyint(4) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `titleRequesterHash_UNIQUE` (`titleRequesterHash`),
  KEY `fk_tasks_1_idx` (`requesterUserId`),
  CONSTRAINT `fk_tasks_1` FOREIGN KEY (`requesterUserId`) REFERENCES `requesters` (`requesterUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2462 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tasks_accomplished`
--

DROP TABLE IF EXISTS `tasks_accomplished`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks_accomplished` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fk_requesterUserId` varchar(45) NOT NULL,
  `fk_titleRequesterHash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `amt_hit_id` varchar(30) NOT NULL,
  `amt_feedback` text DEFAULT NULL,
  `date_inserted` int(11) NOT NULL,
  `fk_amt_status` varchar(40) NOT NULL,
  `fk_amt_date` varchar(10) NOT NULL,
  `fk_amt_reward` decimal(4,2) unsigned NOT NULL,
  `fk_amt_account` varchar(60) NOT NULL,
  `FLBS_EDIT` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_hits_accomplished_1_idx` (`fk_requesterUserId`),
  KEY `fk_hits_accomplished_2_idx` (`fk_titleRequesterHash`),
  KEY `fk_tasks_accomplished_4_idx` (`fk_amt_date`),
  KEY `fk_tasks_accomplished_5_idx` (`fk_amt_account`),
  KEY `fk_tasks_accomplished_6_idx` (`fk_amt_status`),
  KEY `fk_tasks_accomplished_3_idx` (`fk_amt_reward`),
  KEY `fk_tasks_accomplished_7_idx` (`amt_hit_id`),
  KEY `index11` (`fk_titleRequesterHash`,`fk_amt_reward`,`fk_amt_account`,`fk_amt_date`),
  CONSTRAINT `fk_tasks_accomplished_1` FOREIGN KEY (`fk_requesterUserId`) REFERENCES `requesters` (`requesterUserId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_accomplished_2` FOREIGN KEY (`fk_titleRequesterHash`) REFERENCES `tasks` (`titleRequesterHash`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_accomplished_3` FOREIGN KEY (`fk_amt_reward`) REFERENCES `ta_rewards` (`reward`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_accomplished_4` FOREIGN KEY (`fk_amt_date`) REFERENCES `ta_dates` (`date_string`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_accomplished_5` FOREIGN KEY (`fk_amt_account`) REFERENCES `ta_accounts` (`account_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_accomplished_6` FOREIGN KEY (`fk_amt_status`) REFERENCES `ta_status` (`status`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tasks_accomplished_7` FOREIGN KEY (`amt_hit_id`) REFERENCES `hit_ids` (`hit_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3375044 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name_UNIQUE` (`user_name`)
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

-- Dump completed on 2026-11-15  9:56:10
