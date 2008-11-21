-- phpMyAdmin SQL Dump
-- version 2.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 07. November 2008 um 02:34
-- Server Version: 5.0.68
-- PHP-Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `pkgdb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `architectures`
--

CREATE TABLE `architectures` (
  `id` tinyint(1) unsigned NOT NULL auto_increment,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumblob NOT NULL,
  `expires` int(11) default NULL,
  PRIMARY KEY  (`key`(100)),
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
  `comment` varchar(50) NOT NULL,
  KEY `package` (`package`),
  KEY `depends` (`depends`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `files`
--

CREATE TABLE `files` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `package` int(11) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `pacakge` (`package`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `file_index`
--

CREATE TABLE `file_index` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`(15))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `groups`
--

CREATE TABLE `groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `licenses`
--

CREATE TABLE `licenses` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `name` varchar(255) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mirrors`
--

CREATE TABLE `mirrors` (
  `host` varchar(255) NOT NULL,
  `country` varchar(255) default NULL,
  `ftp` varchar(255) NOT NULL,
  `http` varchar(255) NOT NULL,
  `rsync` varchar(255) NOT NULL,
  `i686` tinyint(4) NOT NULL default '1',
  `x86_64` tinyint(4) NOT NULL default '1',
  `contact` varchar(255) default NULL,
  `added` date default NULL,
  `removed` date default NULL,
  `note` text,
  `ticketnr` varchar(255) default NULL,
  `oldtickets` varchar(255) default NULL,
  `official` tinyint(1) NOT NULL default '1',
  `deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`host`),
  KEY `country` (`country`),
  KEY `official` (`official`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mirror_log`
--

CREATE TABLE `mirror_log` (
  `host` varchar(255) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `lastsync` int(10) unsigned default NULL,
  `error` varchar(255) default NULL,
  `totaltime` double unsigned default NULL,
  PRIMARY KEY  (`host`,`time`),
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
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `email` (`email`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `packages`
--

CREATE TABLE `packages` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filename` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
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
  PRIMARY KEY  (`id`),
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
-- Tabellenstruktur für Tabelle `package_statistics`
--

CREATE TABLE `package_statistics` (
  `name` varchar(255) NOT NULL,
  `arch` varchar(10) NOT NULL,
  `count` int(11) unsigned NOT NULL default '0',
  `lastupdate` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`name`,`arch`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_statistics_log`
--

CREATE TABLE `package_statistics_log` (
  `ip` char(40) NOT NULL,
  `visited` int(10) unsigned NOT NULL,
  `arch` varchar(10) NOT NULL,
  `count` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`ip`,`visited`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `provides`
--

CREATE TABLE `provides` (
  `package` int(11) unsigned NOT NULL,
  `provides` int(11) unsigned NOT NULL,
  `comment` varchar(50) NOT NULL,
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
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
