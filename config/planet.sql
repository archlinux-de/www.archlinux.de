CREATE TABLE `planet_entries` (
  `id` int(11) unsigned NOT NULL,
  `feedId` int(11) unsigned NOT NULL,
  `entryId` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `updateTime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feedId` (`feedId`,`entryId`),
  KEY `updateTime` (`updateTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planet_feeds` (
  `id` int(11) unsigned NOT NULL,
  `feedURL` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `websiteURL` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feed` (`feedURL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;