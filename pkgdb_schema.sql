-- phpMyAdmin SQL Dump
-- version 2.10.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Erstellungszeit: 01. Februar 2008 um 14:54
-- Server Version: 5.0.51
-- PHP-Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Datenbank: `pkgdb`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `dependencies`
-- 

CREATE TABLE `dependencies` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `package` int(11) unsigned NOT NULL,
  `dependency` int(11) unsigned NOT NULL,
  `comment` varchar(255) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `package` (`package`),
  KEY `dependency` (`dependency`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `files`
-- 

CREATE TABLE `files` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `package` int(11) unsigned NOT NULL,
  `path` varchar(255) collate latin1_general_ci NOT NULL,
  `file` varchar(100) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `pacakge` (`package`),
  KEY `file` (`file`(20))
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `maintainers`
-- 

CREATE TABLE `maintainers` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `username` varchar(255) collate latin1_general_ci NOT NULL,
  `realname` varchar(255) collate latin1_general_ci NOT NULL,
  `email` varchar(255) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `packages`
-- 

CREATE TABLE `packages` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `origid` int(11) NOT NULL,
  `repository` int(11) unsigned NOT NULL,
  `maintainer` int(11) unsigned default NULL,
  `needupdate` tinytext collate latin1_general_ci NOT NULL,
  `pkgname` varchar(255) collate latin1_general_ci NOT NULL,
  `pkgver` varchar(255) collate latin1_general_ci NOT NULL,
  `pkgrel` varchar(255) collate latin1_general_ci NOT NULL,
  `pkgdesc` varchar(255) collate latin1_general_ci NOT NULL,
  `url` varchar(200) collate latin1_general_ci NOT NULL,
  `lastupdate` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `origid` (`origid`),
  UNIQUE KEY `repository` (`repository`,`pkgname`(20)),
  KEY `maintainer` (`maintainer`),
  KEY `lastupdate` (`lastupdate`),
  KEY `pkgname` (`pkgname`(20)),
  FULLTEXT KEY `pkgdesc` (`pkgdesc`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `repositories`
-- 

CREATE TABLE `repositories` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sources`
-- 

CREATE TABLE `sources` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `package` int(11) unsigned NOT NULL,
  `url` varchar(255) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `package` (`package`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
