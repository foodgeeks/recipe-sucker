-- MySQL dump 10.13  Distrib 5.5.29, for osx10.10 (x86_64)
--
-- Host: localhost    Database: publisher_recipes
-- ------------------------------------------------------
-- Server version	5.5.29-log

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
-- Table structure for table `publisher_pages`
--

DROP TABLE IF EXISTS `publisher_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publisher_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `publisher_id` int(10) unsigned NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `invalid_recipe` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created` int(10) unsigned DEFAULT NULL,
  `modified` int(10) unsigned DEFAULT NULL,
  `last_crawled` int(10) unsigned NOT NULL DEFAULT '0',
  `hrecipe` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `hrecipe_last_scraped` int(10) unsigned NOT NULL DEFAULT '0',
  `schemaorg` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `schemaorg_last_scraped` int(10) unsigned NOT NULL DEFAULT '0',
  `datavocabularyorg` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `datavocabularyorg_last_scraped` int(10) unsigned NOT NULL DEFAULT '0',
  `scraper_version` float(5,2) DEFAULT '1.00',
  PRIMARY KEY (`id`),
  KEY `pub_uri` (`publisher_id`,`url`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `publisher_recipes`
--

DROP TABLE IF EXISTS `publisher_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publisher_recipes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `publisher_id` int(11) unsigned NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `teaser` varchar(255) DEFAULT NULL,
  `yield` varchar(64) DEFAULT NULL,
  `prep_time` char(24) DEFAULT NULL,
  `cook_time` char(24) DEFAULT NULL,
  `total_time` char(24) DEFAULT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `cooking_method` varchar(255) DEFAULT NULL,
  `recipe_category` varchar(255) DEFAULT NULL,
  `recipe_cuisine` varchar(255) DEFAULT NULL,
  `comments` text NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` int(10) unsigned NOT NULL DEFAULT '0',
  `prepped_for_import` int(10) unsigned NOT NULL DEFAULT '0',
  `unsupported_language` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `scraper_version` float(5,2) DEFAULT '1.00',
  `hrecipe` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `schemaorg` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `datavocabularyorg` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `title_publisher` (`title`,`publisher_id`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `publishers`
--

DROP TABLE IF EXISTS `publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publishers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `uri` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `last_crawled` int(10) unsigned NOT NULL DEFAULT '0',
  `do_not_crawl_in_future` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `num_recipes` int(10) unsigned NOT NULL DEFAULT '0',
  `notes` text,
  `created` int(10) unsigned NOT NULL,
  `modified` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `website` (`website`),
  KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recipes_prepped_for_import`
--

DROP TABLE IF EXISTS `recipes_prepped_for_import`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipes_prepped_for_import` (
  `publisher_recipe_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `publisher_id` int(10) unsigned DEFAULT NULL,
  `teaser` varchar(255) DEFAULT NULL,
  `ingredients` text NOT NULL,
  `directions` text NOT NULL,
  `serving_qty` smallint(5) unsigned DEFAULT NULL,
  `serving_to_qty` smallint(5) unsigned DEFAULT NULL,
  `serving_type` smallint(5) unsigned NOT NULL DEFAULT '0',
  `prep_time` int(10) unsigned NOT NULL DEFAULT '0',
  `overall_time` int(10) unsigned NOT NULL DEFAULT '0',
  `external_url` varchar(255) DEFAULT NULL,
  `external_photo_url` varchar(255) DEFAULT NULL,
  `last_imported` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`publisher_recipe_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-10-23 10:48:48
