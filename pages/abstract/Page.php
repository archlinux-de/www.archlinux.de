<?php
/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
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
	$this->setValue('body', '<div class="warning">'.$text.'</div>');
	$this->sendOutput();
	}

protected function showFailure($text)
	{
	$this->setValue('meta.robots', 'noindex,nofollow');
	$this->setValue('title', 'Fehler');
	$this->setValue('body', '<div class="warning">'.$text.'</div>');
	$this->sendOutput();
	}

public function prepare()
	{
	$this->setValue('title', 'Warnung');
	$this->setValue('body', 'kein Text');
	}

protected function makeMenu()
	{
	return '<ul>
			<li><a href="https://wiki.archlinux.de/title/Spenden">Spenden</a></li>
			<li><a href="?page=Packages">Pakete</a></li>
			<li><a href="https://wiki.archlinux.de">Wiki</a></li>
			<li><a href="https://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li class="selected">Start</li>
		</ul>';
	}

protected function makeSubMenu()
	{
	return '';
	}

private function sendOutput()
	{
	$file = '<!DOCTYPE HTML>
<html>
<head>
	<meta name="robots" content="'.$this->getValue('meta.robots').'" />
	<title>archlinux.de - '.$this->getValue('title').'</title>
	<link rel="stylesheet" media="screen" href="arch.css" />
	<link rel="alternate" type="application/atom+xml" title="Aktuelle Ankündigungen" href="?page=GetRecentNews" />
	<link rel="alternate" type="application/atom+xml" title="Aktualisierte Pakete" href="?page=GetRecentPackages" />
	<link rel="alternate" type="application/atom+xml" title="Aktuelle Themen im Forum" href="https://forum.archlinux.de/?page=GetRecent;id=20" />
	<link rel="search" type="application/opensearchdescription+xml" href="?page=GetOpenSearch" title="www.archlinux.de" />
	<link rel="shortcut icon" href="favicon.ico" />
</head>
<body>
	<img id="logo" src="images/logo.png" alt ="Arch Linux" />
	<div id="site-menu">
		'.$this->makeMenu().'
	</div>
	<div id="main-menu">
		'.$this->makeSubMenu().'
	</div>
	<div id="content">
		'.$this->getValue('body').'
		<div id="foot">
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