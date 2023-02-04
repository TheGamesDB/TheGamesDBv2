-- MySQL dump 10.19  Distrib 10.3.34-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: TGDB_Sanitise
-- ------------------------------------------------------
-- Server version	10.3.34-MariaDB-0ubuntu0.20.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ESRB_rating`
--

DROP TABLE IF EXISTS `ESRB_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ESRB_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ESRB_rating`
--

LOCK TABLES `ESRB_rating` WRITE;
/*!40000 ALTER TABLE `ESRB_rating` DISABLE KEYS */;
INSERT INTO `ESRB_rating` VALUES (7,'AO - Adult Only 18+'),(3,'E - Everyone'),(4,'E10+ - Everyone 10+'),(6,'EC - Early Childhood'),(1,'M - Mature 17+'),(5,'Not Rated'),(8,'RP - Rating Pending'),(2,'T - Teen');
/*!40000 ALTER TABLE `ESRB_rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_allowance_level`
--

DROP TABLE IF EXISTS `api_allowance_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_allowance_level` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `monthly_allowance` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_allowance_level`
--

LOCK TABLES `api_allowance_level` WRITE;
/*!40000 ALTER TABLE `api_allowance_level` DISABLE KEYS */;
INSERT INTO `api_allowance_level` VALUES (0,0),(1,3000),(2,3000),(3,6000),(4,12000);
/*!40000 ALTER TABLE `api_allowance_level` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_month_counter`
--

DROP TABLE IF EXISTS `api_month_counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_month_counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiusers_id` int(10) unsigned NOT NULL,
  `IP` varbinary(16) NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `is_extra` int(1) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `apiusers_id_2` (`apiusers_id`,`is_extra`,`date`,`IP`) USING BTREE,
  KEY `apiusers_id` (`apiusers_id`),
  KEY `is_extra` (`is_extra`),
  KEY `date` (`date`),
  KEY `IP` (`IP`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_month_counter`
--

LOCK TABLES `api_month_counter` WRITE;
/*!40000 ALTER TABLE `api_month_counter` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_month_counter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `apiusers`
--

DROP TABLE IF EXISTS `apiusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apiusers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `apikey` varchar(64) NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  `api_allowance_level_id` int(10) unsigned NOT NULL,
  `lastupdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_refresh_date` date NOT NULL DEFAULT '1970-01-01',
  `extra_allowance` int(10) unsigned NOT NULL,
  `is_private_key` tinyint(1) NOT NULL,
  `is_banned` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `apikey` (`apikey`),
  UNIQUE KEY `userid` (`userid`,`is_private_key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apiusers`
--

LOCK TABLES `apiusers` WRITE;
/*!40000 ALTER TABLE `apiusers` DISABLE KEYS */;
/*!40000 ALTER TABLE `apiusers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banners` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(16) NOT NULL DEFAULT '',
  `side` varchar(16) DEFAULT NULL,
  `games_id` int(10) unsigned NOT NULL DEFAULT 0,
  `userid` int(10) unsigned NOT NULL DEFAULT 1,
  `subkey` varchar(16) DEFAULT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(45) DEFAULT NULL,
  `dateadded` int(10) unsigned DEFAULT NULL,
  `languageid` int(10) NOT NULL DEFAULT 7,
  `resolution` varchar(9) DEFAULT NULL,
  `colors` varchar(255) DEFAULT NULL,
  `artistcolors` varchar(255) DEFAULT NULL,
  `mirrorupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `FK_banners_1` (`userid`),
  KEY `Index_3` (`games_id`),
  KEY `mirrorupdate` (`mirrorupdate`),
  KEY `type` (`type`,`games_id`),
  KEY `type_2` (`type`,`side`,`games_id`),
  KEY `type_3` (`type`,`side`) USING BTREE,
  FULLTEXT KEY `side` (`side`),
  FULLTEXT KEY `type_4` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=354425 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO TGDB.banners VALUES (15, 'fanart', null, 2, 1, null, 'fanart/2-1.jpg', null, 1273693143, 1, '1920x1080', '|125,143,152|129,127,132|149,157,162|150,143,147|157,164,171|214,219,222|', null, '2022-08-15 18:23:21');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `code` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'United Arab Emirates','AE'),(2,'Argentina','AR'),(3,'Austria','AT'),(4,'Australia','AU'),(5,'Belgium','BE'),(6,'Bulgaria','BG'),(7,'Brazil','BR'),(8,'Canada','CA'),(9,'Switzerland','CH'),(10,'Chile','CL'),(11,'China','CN'),(12,'Czech Republic','CZ'),(13,'Germany','DE'),(14,'Denmark','DK'),(15,'Spain','ES'),(16,'Finland','FI'),(17,'France','FR'),(18,'United Kingdom of Great Britain and Northern Ireland','GB'),(19,'Greece','GR'),(20,'Hong Kong','HK'),(21,'Croatia','HR'),(22,'Hungary','HU'),(23,'Indonesia','ID'),(24,'Ireland','IE'),(25,'Israel','IL'),(26,'India','IN'),(27,'Italy','IT'),(28,'Japan','JP'),(29,'Korea (Republic of)','KR'),(30,'Malta','MT'),(31,'Mexico','MX'),(32,'Malaysia','MY'),(33,'Netherlands','NL'),(34,'Norway','NO'),(35,'New Zealand','NZ'),(36,'Peru','PE'),(37,'Poland','PL'),(38,'Portugal','PT'),(39,'Romania','RO'),(40,'Russian Federation','RU'),(41,'Saudi Arabia','SA'),(42,'Sweden','SE'),(43,'Singapore','SG'),(44,'Slovenia','SI'),(45,'Slovakia','SK'),(46,'Thailand','TH'),(47,'Turkey','TR'),(48,'Taiwan','TW'),(49,'Ukraine','UA'),(50,'United States of America','US'),(51,'South Africa','ZA');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devs_list`
--

DROP TABLE IF EXISTS `devs_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devs_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10780 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devs_list`
--

LOCK TABLES `devs_list` WRITE;
/*!40000 ALTER TABLE `devs_list` DISABLE KEYS */;
INSERT INTO `devs_list` VALUES (1,'Example Publisher');
/*!40000 ALTER TABLE `devs_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `SOUNDEX` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `players` tinyint(4) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `publisher_to_be_removed` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overview` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `rating` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hits` int(10) unsigned DEFAULT 0,
  `mirrorupdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `disabled` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `platform` int(10) NOT NULL,
  `coop` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ram` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hdd` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sound` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alternates_to_be_removed` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Platform` (`platform`),
  KEY `ReleaseDateRevised` (`release_date`),
  KEY `lastupdatedRevised` (`last_updated`),
  KEY `id` (`id`),
  KEY `id_2` (`id`,`game_title`,`SOUNDEX`),
  KEY `game_title` (`game_title`),
  KEY `SOUNDEX_2` (`SOUNDEX`),
  FULLTEXT KEY `GameTitle` (`game_title`),
  FULLTEXT KEY `SOUNDEX` (`SOUNDEX`)
) ENGINE=InnoDB AUTO_INCREMENT=103657 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games`
--

LOCK TABLES `games` WRITE;
/*!40000 ALTER TABLE `games` DISABLE KEYS */;
/*!40000 ALTER TABLE `games` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_alts`
--

DROP TABLE IF EXISTS `games_alts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_alts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `games_id` int(10) unsigned NOT NULL,
  `name` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `SOUNDEX` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `games_id` (`games_id`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `SOUNDEX` (`SOUNDEX`)
) ENGINE=InnoDB AUTO_INCREMENT=23793 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_alts`
--

LOCK TABLES `games_alts` WRITE;
/*!40000 ALTER TABLE `games_alts` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_alts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_devs`
--

DROP TABLE IF EXISTS `games_devs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_devs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `games_id` int(11) NOT NULL,
  `dev_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `games_id` (`games_id`,`dev_id`),
  KEY `dev_id` (`dev_id`),
  KEY `games_id_2` (`games_id`)
) ENGINE=InnoDB AUTO_INCREMENT=145102 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_devs`
--

LOCK TABLES `games_devs` WRITE;
/*!40000 ALTER TABLE `games_devs` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_devs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_genre`
--

DROP TABLE IF EXISTS `games_genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_genre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `games_id` int(11) NOT NULL,
  `genres_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `games_id_2` (`games_id`,`genres_id`),
  KEY `games_id` (`games_id`)
) ENGINE=InnoDB AUTO_INCREMENT=155861 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_genre`
--

LOCK TABLES `games_genre` WRITE;
/*!40000 ALTER TABLE `games_genre` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_hashes`
--

DROP TABLE IF EXISTS `games_hashes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_hashes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `games_id` int(10) NOT NULL,
  `hash` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `games_id` (`games_id`,`hash`,`type`),
  FULLTEXT KEY `hash` (`hash`),
  FULLTEXT KEY `type` (`type`),
  FULLTEXT KEY `hash_2` (`hash`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_hashes`
--

LOCK TABLES `games_hashes` WRITE;
/*!40000 ALTER TABLE `games_hashes` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_hashes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_legacy`
--

DROP TABLE IF EXISTS `games_legacy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_legacy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `GameTitle` varchar(255) NOT NULL DEFAULT '',
  `GameID` varchar(45) DEFAULT NULL,
  `Players` tinyint(4) DEFAULT NULL,
  `ReleaseDate` varchar(100) DEFAULT NULL,
  `Developer` varchar(100) DEFAULT NULL,
  `Publisher` varchar(100) DEFAULT NULL,
  `Runtime` varchar(100) DEFAULT NULL,
  `Genre` varchar(100) DEFAULT NULL,
  `Actors` text DEFAULT NULL,
  `Overview` text DEFAULT NULL,
  `bannerrequest` int(10) unsigned DEFAULT 0,
  `created` int(10) DEFAULT NULL,
  `lastupdated` int(10) unsigned DEFAULT NULL,
  `Airs_DayOfWeek` varchar(45) DEFAULT NULL,
  `Airs_Time` varchar(45) DEFAULT NULL,
  `Rating` varchar(45) DEFAULT NULL,
  `flagged` int(10) unsigned DEFAULT 0,
  `forceupdate` int(10) unsigned DEFAULT 0,
  `hits` int(10) unsigned DEFAULT 0,
  `updateID` int(10) NOT NULL DEFAULT 0,
  `requestcomment` varchar(255) NOT NULL DEFAULT '',
  `locked` varchar(3) NOT NULL DEFAULT 'no',
  `mirrorupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lockedby` int(11) NOT NULL DEFAULT 0,
  `autoimport` varchar(16) DEFAULT NULL,
  `disabled` varchar(3) NOT NULL DEFAULT 'No',
  `IMDB_ID` varchar(25) DEFAULT NULL,
  `zap2it_id` varchar(12) DEFAULT NULL,
  `Platform` varchar(100) DEFAULT NULL,
  `coop` varchar(10) DEFAULT NULL,
  `Youtube` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `hdd` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `sound` varchar(255) DEFAULT NULL,
  `Alternates` text NOT NULL,
  `author` int(10) unsigned DEFAULT NULL,
  `updatedby` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Genre` (`Genre`),
  KEY `Platform` (`Platform`),
  KEY `updatedby` (`updatedby`),
  FULLTEXT KEY `GameTitle` (`GameTitle`),
  FULLTEXT KEY `Alternates` (`Alternates`)
) ENGINE=MyISAM AUTO_INCREMENT=56176 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_legacy`
--

LOCK TABLES `games_legacy` WRITE;
/*!40000 ALTER TABLE `games_legacy` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_legacy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_lock`
--

DROP TABLE IF EXISTS `games_lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_lock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `games_id` int(10) unsigned NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_locked` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `games_id_2` (`games_id`,`type`),
  KEY `games_id` (`games_id`),
  CONSTRAINT `games_id_fk` FOREIGN KEY (`games_id`) REFERENCES `games` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=405543 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_lock`
--

LOCK TABLES `games_lock` WRITE;
/*!40000 ALTER TABLE `games_lock` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_lock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_pubs`
--

DROP TABLE IF EXISTS `games_pubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_pubs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `games_id` int(10) unsigned NOT NULL,
  `pub_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `games_id` (`games_id`,`pub_id`),
  KEY `games_id_2` (`games_id`),
  KEY `pub_id` (`pub_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100122 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_pubs`
--

LOCK TABLES `games_pubs` WRITE;
/*!40000 ALTER TABLE `games_pubs` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_pubs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_reports`
--

DROP TABLE IF EXISTS `games_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolver_user_id` int(11) DEFAULT NULL,
  `resolver_username` text DEFAULT NULL,
  `games_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `metadata_0` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `games_id` (`games_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_reports`
--

LOCK TABLES `games_reports` WRITE;
/*!40000 ALTER TABLE `games_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_uids`
--

DROP TABLE IF EXISTS `games_uids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_uids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `games_id` int(11) NOT NULL,
  `games_uids_patterns_id` int(11) DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial` (`uid`),
  KEY `games_id` (`games_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3768 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_uids`
--

LOCK TABLES `games_uids` WRITE;
/*!40000 ALTER TABLE `games_uids` DISABLE KEYS */;
/*!40000 ALTER TABLE `games_uids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games_uids_patterns`
--

DROP TABLE IF EXISTS `games_uids_patterns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games_uids_patterns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform_id` int(11) NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `regex_pattern` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games_uids_patterns`
--

LOCK TABLES `games_uids_patterns` WRITE;
/*!40000 ALTER TABLE `games_uids_patterns` DISABLE KEYS */;
INSERT INTO `games_uids_patterns` VALUES (1,4971,'title_id','[A-Z0-9]{16}'),(2,11,'serial_id','[A-Z]{4}-[0-9]{5}'),(3,10,'serial_id','[A-Z]{4}-[0-9]{5}'),(4,4919,'entitlement_id','UP[0-9]{4}-[A-Z]{4}[0-9]{5}_00-[A-Z0-9]{16}'),(5,12,'entitlement_id','UP[0-9]{4}-[A-Z]{4}[0-9]{5}_00-[A-Z0-9]{16}'),(6,4912,'title_id','[A-Z0-9]{16}'),(7,12,'serial_id','[A-Z]{4}-[0-9]{5}'),(8,4919,'serial_id','[A-Z]{4}-[0-9]{5}'),(9,16,'serial_id','T(-| |)(\\d{4,5})(D|M|ND|N)(-| |)(\\d{2}([A-Z]|)|)'),(10,16,'serial_id','MK(-| |)\\d{5}((-| |)\\d{2}([A-Z]|)|)'),(11,16,'serial_id','HDR-(\\d{4})'),(12,16,'serial_id','\\d{3}-\\d{4}(-\\d{2}|)');
/*!40000 ALTER TABLE `games_uids_patterns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genres`
--

DROP TABLE IF EXISTS `genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `genres` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `genre` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genres`
--

LOCK TABLES `genres` WRITE;
/*!40000 ALTER TABLE `genres` DISABLE KEYS */;
INSERT INTO `genres` VALUES (1,'Action'),(2,'Adventure'),(3,'Construction and Management Simulation'),(4,'Role-Playing'),(5,'Puzzle'),(6,'Strategy'),(7,'Racing'),(8,'Shooter'),(9,'Life Simulation'),(10,'Fighting'),(11,'Sports'),(12,'Sandbox'),(13,'Flight Simulator'),(14,'MMO'),(15,'Platform'),(16,'Stealth'),(17,'Music'),(18,'Horror'),(19,'Vehicle Simulation'),(20,'Board'),(21,'Education'),(22,'Family'),(23,'Party'),(24,'Productivity'),(25,'Quiz'),(26,'Utility'),(27,'Virtual Console'),(28,'Unofficial'),(29,'GBA Video / PSP Video');
/*!40000 ALTER TABLE `genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platforms`
--

DROP TABLE IF EXISTS `platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platforms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `alias` varchar(100) DEFAULT NULL,
  `icon` varchar(100) NOT NULL,
  `console` varchar(100) DEFAULT NULL,
  `controller` varchar(100) DEFAULT NULL,
  `developer` text DEFAULT NULL,
  `manufacturer` text DEFAULT NULL,
  `media` text DEFAULT NULL,
  `cpu` text DEFAULT NULL,
  `memory` text DEFAULT NULL,
  `graphics` text DEFAULT NULL,
  `sound` text DEFAULT NULL,
  `maxcontrollers` text DEFAULT NULL,
  `display` text DEFAULT NULL,
  `overview` text DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_2` (`name`),
  UNIQUE KEY `alias_2` (`alias`),
  KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=5018 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platforms`
--

LOCK TABLES `platforms` WRITE;
/*!40000 ALTER TABLE `platforms` DISABLE KEYS */;
INSERT INTO `platforms` VALUES (1,'PC','pc','pc-1336524756.png','1.png',NULL,'IBM',NULL,NULL,'x86 Based',NULL,NULL,NULL,NULL,NULL,'PC stands for Personal Computer. Mass-market consumer computers use highly standardized components and so are simple for an end user to assemble into a working system. A typical desktop computer consists of a computer case which holds the power supply, motherboard, hard disk and often an optical disc drive. External devices such as a computer monitor or visual display unit, keyboard, and a pointing device are usually found in a personal computer.',NULL);
/*!40000 ALTER TABLE `platforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platforms_images`
--

DROP TABLE IF EXISTS `platforms_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platforms_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(16) NOT NULL DEFAULT '',
  `platforms_id` int(10) unsigned NOT NULL DEFAULT 0,
  `userid` int(10) unsigned NOT NULL DEFAULT 1,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(45) DEFAULT NULL,
  `date_added` int(10) unsigned DEFAULT NULL,
  `resolution` varchar(9) DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `FK_banners_1` (`userid`),
  KEY `Index_3` (`platforms_id`),
  KEY `mirrorupdate` (`updated`)
) ENGINE=InnoDB AUTO_INCREMENT=473 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platforms_images`
--

LOCK TABLES `platforms_images` WRITE;
/*!40000 ALTER TABLE `platforms_images` DISABLE KEYS */;
INSERT INTO `platforms_images` VALUES (1,'banner',2,1,'platform/banners/2-1.png',NULL,1322554351,NULL,'2022-08-15 16:24:10');
/*!40000 ALTER TABLE `platforms_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pubdev`
--

DROP TABLE IF EXISTS `pubdev`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pubdev` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keywords` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pubdev`
--

LOCK TABLES `pubdev` WRITE;
/*!40000 ALTER TABLE `pubdev` DISABLE KEYS */;
INSERT INTO `pubdev` VALUES (1,'2k','0-folder.png');
/*!40000 ALTER TABLE `pubdev` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publishers`
--

DROP TABLE IF EXISTS `publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publishers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `logo` varchar(512) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publishers`
--

LOCK TABLES `publishers` WRITE;
/*!40000 ALTER TABLE `publishers` DISABLE KEYS */;
INSERT INTO `publishers` VALUES (1,'2k, 2K','2k.png');
/*!40000 ALTER TABLE `publishers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pubs_list`
--

DROP TABLE IF EXISTS `pubs_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pubs_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8621 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pubs_list`
--

LOCK TABLES `pubs_list` WRITE;
/*!40000 ALTER TABLE `pubs_list` DISABLE KEYS */;
INSERT INTO `pubs_list` VALUES (2374,'.GEARS Studios');
/*!40000 ALTER TABLE `pubs_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ratings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(16) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `itemid` bigint(20) unsigned NOT NULL DEFAULT 0,
  `userid` bigint(20) unsigned NOT NULL DEFAULT 0,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `itemid` (`itemid`,`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regions`
--

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` VALUES (1,'NTSC'),(2,'NTSC-U'),(3,'NTSC-C'),(4,'NTSC-J'),(5,'NTSC-K'),(6,'PAL'),(7,'PAL-A'),(8,'PAL-B'),(9,'Other');
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statistics`
--

DROP TABLE IF EXISTS `statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` text NOT NULL,
  `count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statistics`
--

LOCK TABLES `statistics` WRITE;
/*!40000 ALTER TABLE `statistics` DISABLE KEYS */;
INSERT INTO `statistics` VALUES (1,'fanart',9422),(2,'banner',4604),(3,'boxart',45379),(4,'screenshot',23009),(8,'clearlogo',14021),(9,'boxart-front',89884),(10,'boxart-back',20548),(11,'total',91216),(12,'overview',61158);
/*!40000 ALTER TABLE `statistics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_edits`
--

DROP TABLE IF EXISTS `user_edits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_edits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(10) unsigned NOT NULL,
  `games_id` int(10) unsigned NOT NULL,
  `type` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `games_id` (`games_id`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB AUTO_INCREMENT=859120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_edits`
--

LOCK TABLES `user_edits` WRITE;
/*!40000 ALTER TABLE `user_edits` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_edits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_games`
--

DROP TABLE IF EXISTS `user_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `users_id` int(10) unsigned NOT NULL,
  `games_id` int(10) unsigned NOT NULL,
  `platforms_id` int(11) NOT NULL,
  `is_booked` int(1) unsigned NOT NULL DEFAULT 1,
  `added` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_id` (`users_id`,`games_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_games`
--

LOCK TABLES `user_games` WRITE;
/*!40000 ALTER TABLE `user_games` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_games` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL DEFAULT '',
  `userpass` varchar(255) NOT NULL DEFAULT '',
  `emailaddress` varchar(45) DEFAULT NULL,
  `ipaddress` varchar(45) DEFAULT NULL,
  `userlevel` varchar(45) DEFAULT 'USER',
  `languageid` int(10) unsigned NOT NULL DEFAULT 7,
  `favorites` text DEFAULT NULL,
  `favorites_displaymode` varchar(8) NOT NULL DEFAULT 'banners',
  `bannerlimit` int(11) DEFAULT 3,
  `banneragreement` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `uniqueid` varchar(16) DEFAULT NULL,
  `lastupdatedby_admin` int(10) unsigned DEFAULT NULL,
  `mirrorupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueid` (`uniqueid`),
  KEY `mirrorupdate` (`mirrorupdate`)
) ENGINE=InnoDB AUTO_INCREMENT=21099 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Database Dump','','database@example.com','','SUPERADMIN',1,'','',3,1,1,'',NULL,'1999-12-31 23:00:00');
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

-- Dump completed on 2022-08-15 17:26:13
