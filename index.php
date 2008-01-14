<?php

define('IN_LL', null);

$board 			= 20;
$archNewsForum 		= 257;
$importantTag		= 3;
$solvedTag		= 1;

require('LLPath.php');

require (LL_PATH.'modules/Settings.php');
require (LL_PATH.'modules/Modul.php');
require (LL_PATH.'modules/Exceptions.php');
require (LL_PATH.'modules/Functions.php');
require (LL_PATH.'modules/Io.php');
require (LL_PATH.'modules/ObjectCache.php');

$Cache = new ObjectCache();
$Io = new Io();
$Settings = new Settings();

if (!($body = $Cache->getObject('www.archlinux.de/index.php')))
	{
	require (LL_PATH.'modules/DB.php');
	$DB = new DB($Settings->getValue('sql_user'),
			$Settings->getValue('sql_password'),
			$Settings->getValue('sql_database')
			);
	$body =
'<?xml version="1.0" encoding="UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
	<head>
		<title>archlinux.de</title>
		<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
		<meta http-equiv="content-language" content="de" />
		<meta http-equiv="expires" content="120" />
		<meta name="robots" content="index,follow" />
		<meta name="revisit-after" content="3 days" />
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel="stylesheet" href="arch.css" />
	</head>
	<body>
		<h1 id="logo">Arch Linux</h1>
		<div id="nav_bar">
			<ul id="nav">
				<li><a href="http://wiki.archlinux.de/?title=Download">ISOs</a></li>
				<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
				<li><a href="http://wiki.archlinux.de/?title=Bugs">Bugs</a></li>
				<li><a href="http://wiki.archlinux.de">Wiki</a></li>
				<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
				<li class="selected">Start</li>
			</ul>
		</div>
		<div id="subnav_bar"></div>
		<div id="content">
			<div id="right">
				<div class="greybox">
					<h3>Neues über Arch Linux</h3>
					'.getNews().'
					<a style="float:right;font-size:x-small" href="http://forum.archlinux.de/?page=Threads;id=20;forum='.$archNewsForum.'">&#187; Archiv</a>
				</div>
				<br />
				<div class="greybox">
					<h3>Aktuelle Themen im Forum</h3>
					'.getRecent().'
					<a style="float:right;font-size:x-small" href="http://forum.archlinux.de/?page=Recent;id=20;">&#187; alle aktuellen Themen</a>
				</div>
			</div>
			<div id="left">
				<div id="box">
					<h2>Willkommen bei Arch Linux</h2>
					<p><strong>Arch Linux</strong> ist eine <em>kleine und flexible</em> Linux-Distribution, mit dem Ziel alles so einfach wie möglich zu halten.<br /><br />Zur Zeit bieten wir optimierte Pakete für <code>i686</code> und <code>x86_64</code> Architekturen. Diese Auswahl wird von einem <a href="http://wiki.archlinux.de/?title=AUR" class="link">Community-Repository</a> vervollständigt, welches täglich wächst und an Qualität zunimmt. <br /><br />Unsere starke Gemeinschaft ist vielfältig und hilfsbereit. Besuche unsere <a href="http://forum.archlinux.de/?page=Forums;id=20" class="link">Foren</a> und unser <a href="http://wiki.archlinux.de" class="link">Wiki</a>, wenn Du mehr erfahren möchtest.</p>
					<div style="font-size:x-small;text-align:right;"><a href="http://wiki.archlinux.de/?title=%C3%9Cber_ArchLinux" class="link">mehr über Arch Linux</a></div>
				</div>
				<h2 class="title">Aktuelle Ankündigungen</h2>
				'.getImportantNews().'
				<span style="float:right;font-size:x-small">
					<a href="http://forum.archlinux.de/?page=Threads;id=20;forum='.$archNewsForum.'">&#187; Archiv</a>
				</span>
				<br />
			</div>
			<div id="foot">
				<a href="http://wiki.archlinux.de/?title=Wiki:Datenschutz">Datenschutz</a> ::
				<a href="http://wiki.archlinux.de/?title=Wiki:Impressum">Impressum</a>
			</div>
		</div>
	</body>
</html>';
	$Cache->addObject('www.archlinux.de/index.php', $body, 30*60);
	}

$Io->out($body);


function getRecent()
	{
	global $DB, $board, $archNewsForum;

	try
		{
		$stm = $DB->prepare
			('
			SELECT
				t.id,
				t.name,
				t.lastdate,
				t.forumid,
				t.summary,
				f.name AS forum
			FROM
				threads t,
				forums f
			WHERE
				t.deleted = 0
				AND t.forumid = f.id
				AND t.forumid <> ?
				AND f.boardid = ?
			ORDER BY
				t.lastdate DESC
			LIMIT
				4
			');

		$stm->bindInteger($archNewsForum);
		$stm->bindInteger($board);
		$threads = $stm->getRowSet();
		}
	catch(DBNoDataException $e)
		{
		$threads = array();
		}

	$result = '';

	foreach ($threads as $thread)
		{
		$thread['name'] = cutString($thread['name'], 54);

		$result .=
			'
			<h4 class="recent"><a href="http://forum.archlinux.de/?page=Postings;thread='.$thread['id'].';post=-1;id='.$board.'">'.$thread['name'].'</a></h4>
			<p class="recent">'.$thread['summary'].'</p>
			';
		}

	$stm->close();

	return $result;
	}

function getImportantNews()
	{
	global $DB, $archNewsForum, $board, $importantTag;

	try
		{
		$stm = $DB->prepare
			('
			SELECT
				id,
				name,
				firstdate,
				summary
			FROM
				threads
			WHERE
				forumid = ?
				AND deleted = 0
				AND tag = ?
			ORDER BY
				id DESC
			LIMIT
				6
			');
		$stm->bindInteger($archNewsForum);
		$stm->bindInteger($importantTag);
		$threads = $stm->getRowSet();
		}
	catch(DBNoDataException $e)
		{
		$threads = array();
		}

	$result = '';

	foreach ($threads as $thread)
		{
		$result .=
			'
			<span style="float:right; font-size:x-small">'.formatDate($thread['firstdate']).'</span>
			<h4 class="news"><a href="http://forum.archlinux.de/?page=Postings;id='.$board.';thread='.$thread['id'].'">'.$thread['name'].'</a></h4>
			<p class="news">'.$thread['summary'].'</p>
			';
		}

	$stm->close();

	return $result;
	}

function getNews()
	{
	global $DB, $archNewsForum, $board, $importantTag, $solvedTag;

	try
		{
		$stm = $DB->prepare
			('
			SELECT
				id,
				name,
				summary
			FROM
				threads
			WHERE
				forumid = ?
				AND deleted = 0
				AND tag NOT IN (?,?)
			ORDER BY
				id DESC
			LIMIT
				3
			');
		$stm->bindInteger($archNewsForum);
		$stm->bindInteger($importantTag);
		$stm->bindInteger($solvedTag);
		$threads = $stm->getRowSet();
		}
	catch(DBNoDataException $e)
		{
		$threads = array();
		}

	$result = '';

	foreach ($threads as $thread)
		{
		$thread['name'] = cutString($thread['name'], 54);

		$result .=
			'
			<h4 class="recent"><a href="http://forum.archlinux.de/?page=Postings;thread='.$thread['id'].';post=-1;id='.$board.'">'.$thread['name'].'</a></h4>
			<p class="recent">'.$thread['summary'].'</p>
			';
		}

	$stm->close();

	return $result;
	}

?>