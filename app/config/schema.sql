/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `architectures` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licenses` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_group` (
  `package` mediumint(8) unsigned NOT NULL,
  `group` smallint(5) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_license` (
  `package` mediumint(8) unsigned NOT NULL,
  `license` smallint(5) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `license` (`license`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_relation` (
  `packageId` mediumint(8) unsigned NOT NULL,
  `dependsId` mediumint(8) unsigned DEFAULT NULL,
  `dependsName` varchar(255) NOT NULL,
  `dependsVersion` varchar(100) DEFAULT NULL,
  `type` enum('replaces','depends','optdepends','conflicts','provides','makedepends','checkdepends') NOT NULL,
  KEY `packageId` (`packageId`),
  KEY `dependsId` (`dependsId`),
  KEY `dependsName` (`dependsName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packagers` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `base` varchar(100) NOT NULL,
  `version` varchar(100) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `csize` bigint(12) unsigned NOT NULL,
  `isize` bigint(12) unsigned NOT NULL,
  `md5sum` char(32) NOT NULL,
  `sha256sum` char(64) NOT NULL,
  `pgpsig` blob NOT NULL,
  `url` varchar(255) NOT NULL,
  `arch` tinyint(3) unsigned NOT NULL,
  `builddate` int(10) unsigned NOT NULL,
  `mtime` int(10) unsigned NOT NULL,
  `packager` mediumint(8) unsigned NOT NULL,
  `repository` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `repository` (`repository`),
  KEY `builddate` (`builddate`),
  KEY `arch` (`arch`),
  KEY `packager` (`packager`),
  KEY `mtime` (`mtime`),
  KEY `name` (`name`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pkgstats_modules` (
  `name` varchar(50) NOT NULL,
  `month` mediumint(6) unsigned NOT NULL,
  `count` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`name`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pkgstats_packages` (
  `pkgname` varchar(255) NOT NULL,
  `month` mediumint(6) unsigned NOT NULL,
  `count` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`pkgname`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pkgstats_users` (
  `ip` char(40) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `arch` enum('i686','x86_64') NOT NULL,
  `cpuarch` enum('i686','x86_64') DEFAULT NULL,
  `countryCode` char(2) DEFAULT NULL,
  `mirror` varchar(255) DEFAULT NULL,
  `packages` smallint(5) unsigned NOT NULL,
  `modules` smallint(5) unsigned DEFAULT NULL,
  KEY `mirror` (`mirror`(20)),
  KEY `ip` (`ip`(20),`time`),
  KEY `countryCode` (`countryCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repositories` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `arch` tinyint(3) unsigned NOT NULL,
  `testing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `mtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`arch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
