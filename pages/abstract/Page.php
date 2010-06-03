<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

require ('modules/DB.php');
require ('modules/IOutput.php');
require ('pages/abstract/IDBCachable.php');
require ('modules/PersistentCache.php');
Modul::set('PersistentCache', new PersistentCache());

abstract class Page extends Modul implements IOutput{

protected $variables = array();

private static $availablePages = array
	(
	'ArchitectureDifferences' => 'pages/ArchitectureDifferences.php',
	'GetFileFromMirror' => 'pages/GetFileFromMirror.php',
	'GetOpenSearch' => 'pages/GetOpenSearch.php',
	'GetRecentNews' => 'pages/GetRecentNews.php',
	'GetRecentPackages' => 'pages/GetRecentPackages.php',
	'MirrorStatus' => 'pages/MirrorStatus.php',
	'MirrorStatusReflector' => 'pages/MirrorStatusReflector.php',
	'NotFound' => 'pages/NotFound.php',
	'PackageDetails' => 'pages/PackageDetails.php',
	'PackageStatistics' => 'pages/PackageStatistics.php',
	'PackageUsageStatistics' => 'pages/PackageUsageStatistics.php',
	'Packagers' => 'pages/Packagers.php',
	'Packages' => 'pages/Packages.php',
	'PostPackageList' => 'pages/PostPackageList.php',
	'Start' => 'pages/Start.php'
	);

public static function loadPage($name)
	{
	if (isset(self::$availablePages[$name]))
		{
		include_once(self::$availablePages[$name]);
		}
	else
		{
		throw new RuntimeException('Seite '.$name.' wurde nicht gefunden!', 0);
		}
	}

public function __construct()
	{
	$this->DB->connect(
		$this->Settings->getValue('sql_host'),
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database'));

	$this->variables['body']	 = '';
	$this->variables['title']	 = '';
	$this->variables['meta.robots']	 = 'index,follow';
	}

public function setValue($key, $value)
	{
	$this->variables[$key] = $value;
	}

public function getValue($key)
	{
	return $this->variables[$key];
	}

protected function showWarning($text)
	{
	$this->setValue('meta.robots', 'noindex,nofollow');
	$this->setValue('title', 'Warnung');
	$this->setValue('body', '<div id="warning">'.$text.'</div>');
	$this->sendOutput();
	}

protected function showFailure($text)
	{
	$this->setValue('meta.robots', 'noindex,nofollow');
	$this->setValue('title', 'Fehler');
	$this->setValue('body', '<div id="warning">'.$text.'</div>');
	$this->sendOutput();
	}

public function prepare()
	{
	$this->setValue('title', 'Warnung');
	$this->setValue('body', 'kein Text');
	}

private function sendOutput()
	{
	$file = '<!DOCTYPE HTML>
<html>
<head>
	<meta name="robots" content="'.$this->getValue('meta.robots').'" />
	<title>archlinux.de - '.$this->getValue('title').'</title>
	<link rel="stylesheet" media="screen" href="arch.css?v=3" />
	<link rel="stylesheet" media="screen" href="archnavbar.css?v=1" />
	<link rel="alternate" type="application/atom+xml" title="Aktuelle Ankündigungen" href="'.$this->Settings->getValue('news_feed').'" />
	<link rel="alternate" type="application/atom+xml" title="Aktualisierte Pakete" href="?page=GetRecentPackages" />
	<link rel="search" type="application/opensearchdescription+xml" href="?page=GetOpenSearch" title="www.archlinux.de" />
	<link rel="shortcut icon" href="favicon.ico" />
</head>
<body>
	<div id="archnavbar" class="anb-'.strtolower($this->getName()).'">
		<div id="archnavbarlogo"><h1><a href="?page=Start">Arch Linux</a></h1></div>
		<div id="archnavbarmenu">
		<ul id="archnavbarlist">
			<li id="anb-start"><a href="?page=Start">Start</a></li>
			<li id="anb-pakete"><a href="?page=Packages">Pakete</a></li>
			<li id="anb-forum"><a href="https://bbs.archlinux.de/">Forum</a></li>
			<li id="anb-wiki"><a href="https://wiki.archlinux.de/">Wiki</a></li>
			<li id="anb-download"><a href="https://wiki.archlinux.de/title/Download">Download</a></li>
			<li id="anb-spenden"><a href="https://wiki.archlinux.de/title/Spenden">Spenden</a></li>
		</ul>
		</div>
	</div>
	<div id="content">
		'.$this->getValue('body').'
		<div id="footer">
			<a href="https://wiki.archlinux.de/title/wiki.archlinux.de:Datenschutz">Datenschutz</a> &ndash;
			<a href="https://wiki.archlinux.de/title/wiki.archlinux.de:Impressum">Impressum</a>
		</div>
	</div>
</body>
</html>
';
	$this->Output->writeOutput($file);
	}

public function show()
	{
	$this->sendOutput();
	}
}

?>