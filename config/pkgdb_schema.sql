-- MySQL dump 10.13  Distrib 5.5.8, for Linux (x86_64)
--
-- Host: localhost    Database: pkgdb
-- ------------------------------------------------------
-- Server version	5.5.8

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
-- Table structure for table `architectures`
--

DROP TABLE IF EXISTS `architectures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `architectures` (
  `id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumblob NOT NULL,
  `expires` int(11) DEFAULT NULL,
  PRIMARY KEY (`key`(100)),
  KEY `expires` (`expires`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conflicts`
--

DROP TABLE IF EXISTS `conflicts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conflicts` (
  `package` int(11) unsigned NOT NULL,
  `conflicts` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `conflicts` (`conflicts`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `depends`
--

DROP TABLE IF EXISTS `depends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `depends` (
  `package` int(11) unsigned NOT NULL,
  `depends` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `depends` (`depends`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file_index`
--

DROP TABLE IF EXISTS `file_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_index` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(20))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `package` int(11) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pacakge` (`package`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `licenses`
--

DROP TABLE IF EXISTS `licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licenses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `name` varchar(255) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mirrors`
--

DROP TABLE IF EXISTS `mirrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mirrors` (
  `host` varchar(255) NOT NULL,
  `protocol` enum('http','https','ftp') NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `lastsync` int(10) unsigned DEFAULT NULL,
  `delay` int(10) unsigned DEFAULT NULL,
  `time` double unsigned DEFAULT NULL,
  PRIMARY KEY (`host`),
  KEY `country` (`country`),
  KEY `lastsync` (`lastsync`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `optdepends`
--

DROP TABLE IF EXISTS `optdepends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `optdepends` (
  `package` int(11) unsigned NOT NULL,
  `optdepends` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `optdepends` (`optdepends`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `package_file_index`
--

DROP TABLE IF EXISTS `package_file_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_file_index` (
  `package` int(11) unsigned NOT NULL,
  `file_index` int(11) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `file_index` (`file_index`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `package_group`
--

DROP TABLE IF EXISTS `package_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_group` (
  `package` int(11) unsigned NOT NULL,
  `group` int(11) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `group` (`group`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `package_license`
--

DROP TABLE IF EXISTS `package_license`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_license` (
  `package` int(11) unsigned NOT NULL,
  `license` int(11) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `license` (`license`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `packagers`
--

DROP TABLE IF EXISTS `packagers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packagers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `base` varchar(255) NOT NULL,
  `version` varchar(100) NOT NULL,
  `desc` text NOT NULL,
  `csize` int(11) unsigned NOT NULL,
  `isize` int(11) unsigned NOT NULL,
  `md5sum` varchar(32) NOT NULL,
  `url` varchar(255) NOT NULL,
  `arch` tinyint(1) unsigned NOT NULL,
  `builddate` int(10) unsigned NOT NULL,
  `mtime` int(10) unsigned NOT NULL,
  `packager` int(11) unsigned NOT NULL,
  `force` tinyint(1) unsigned NOT NULL,
  `repository` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `repository` (`repository`),
  KEY `builddate` (`builddate`),
  KEY `arch` (`arch`),
  KEY `packager` (`packager`),
  KEY `mtime` (`mtime`),
  KEY `name` (`name`(20)),
  FULLTEXT KEY `desc` (`desc`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pkgstats_packages`
--

DROP TABLE IF EXISTS `pkgstats_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pkgstats_packages` (
  `pkgname` varchar(255) NOT NULL,
  `month` mediumint(6) unsigned NOT NULL,
  `count` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`pkgname`,`month`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pkgstats_users`
--

DROP TABLE IF EXISTS `pkgstats_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pkgstats_users` (
  `ip` char(40) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `arch` enum('i686','x86_64') NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `mirror` varchar(255) DEFAULT NULL,
  `packages` smallint(5) unsigned NOT NULL,
  KEY `mirror` (`mirror`(20)),
  KEY `country` (`country`(10)),
  KEY `ip` (`ip`(20),`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `provides`
--

DROP TABLE IF EXISTS `provides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provides` (
  `package` int(11) unsigned NOT NULL,
  `provides` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `provides` (`provides`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `replaces`
--

DROP TABLE IF EXISTS `replaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `replaces` (
  `package` int(11) unsigned NOT NULL,
  `replaces` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `replaces` (`replaces`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `repositories`
--

DROP TABLE IF EXISTS `repositories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repositories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
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

-- Dump completed on 2011-01-20  7:27:57
