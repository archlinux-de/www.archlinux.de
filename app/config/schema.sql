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
