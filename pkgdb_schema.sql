-- phpMyAdmin SQL Dump
-- version 3.3.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 19. September 2010 um 17:10
-- Server Version: 5.1.50
-- PHP-Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `pkgdb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `architectures`
--

CREATE TABLE `architectures` (
  `id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumblob NOT NULL,
  `expires` int(11) DEFAULT NULL,
  PRIMARY KEY (`key`(100)),
  KEY `expires` (`expires`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `conflicts`
--

CREATE TABLE `conflicts` (
  `package` int(11) unsigned NOT NULL,
  `conflicts` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `conflicts` (`conflicts`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `depends`
--

CREATE TABLE `depends` (
  `package` int(11) unsigned NOT NULL,
  `depends` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `depends` (`depends`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `files`
--

CREATE TABLE `files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `package` int(11) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pacakge` (`package`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `file_index`
--

CREATE TABLE `file_index` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(15))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `groups`
--

CREATE TABLE `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `licenses`
--

CREATE TABLE `licenses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `name` varchar(255) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mirrors`
--

CREATE TABLE `mirrors` (
  `host` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `lastsync` int(10) unsigned DEFAULT NULL,
  `delay` int(10) unsigned DEFAULT NULL,
  `time` double unsigned DEFAULT NULL,
  PRIMARY KEY (`host`),
  KEY `country` (`country`),
  KEY `lastsync` (`lastsync`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mirror_log`
--

CREATE TABLE `mirror_log` (
  `host` varchar(255) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `lastsync` int(10) unsigned DEFAULT NULL,
  `error` varchar(255) DEFAULT NULL,
  `totaltime` double unsigned DEFAULT NULL,
  PRIMARY KEY (`host`,`time`),
  KEY `lastsync` (`lastsync`),
  KEY `host` (`host`),
  KEY `time` (`time`),
  KEY `totaltime` (`totaltime`),
  KEY `error` (`error`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `optdepends`
--

CREATE TABLE `optdepends` (
  `package` int(11) unsigned NOT NULL,
  `optdepends` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `optdepends` (`optdepends`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `packagers`
--

CREATE TABLE `packagers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `packages`
--

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
  KEY `name` (`name`(10)),
  KEY `mtime` (`mtime`),
  FULLTEXT KEY `desc` (`desc`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_file_index`
--

CREATE TABLE `package_file_index` (
  `package` int(11) unsigned NOT NULL,
  `file_index` int(11) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `file_index` (`file_index`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_group`
--

CREATE TABLE `package_group` (
  `package` int(11) unsigned NOT NULL,
  `group` int(11) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `group` (`group`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_license`
--

CREATE TABLE `package_license` (
  `package` int(11) unsigned NOT NULL,
  `license` int(11) unsigned NOT NULL,
  KEY `package` (`package`),
  KEY `license` (`license`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `pkgstats_packages`
--

CREATE TABLE `pkgstats_packages` (
  `pkgname` varchar(255) NOT NULL,
  `month` mediumint(6) unsigned NOT NULL,
  `count` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`pkgname`,`month`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `pkgstats_users`
--

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

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `provides`
--

CREATE TABLE `provides` (
  `package` int(11) unsigned NOT NULL,
  `provides` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `provides` (`provides`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `replaces`
--

CREATE TABLE `replaces` (
  `package` int(11) unsigned NOT NULL,
  `replaces` int(11) unsigned NOT NULL,
  `comment` varchar(255) NOT NULL,
  KEY `package` (`package`),
  KEY `replaces` (`replaces`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `repositories`
--

CREATE TABLE `repositories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
