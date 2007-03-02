<?php

$board = 20;
$forum = 257;

define('PATH', './');

require (PATH.'modules/Modul.php');
require (PATH.'modules/Settings.php');
require (PATH.'modules/Exceptions.php');
require (PATH.'modules/Functions.php');
require (PATH.'modules/Io.php');
require (PATH.'modules/DB.php');

$Io = new Io();
$Settings = new Settings();

$DB = new DB($Settings->getValue('ll_user'),
		$Settings->getValue('ll_password'),
		$Settings->getValue('ll_database')
		);

function getRecent()
	{
	global $DB, $board;

	try
		{
		$stm = $DB->prepare
			('
			SELECT
				t.id,
				t.name,
				t.lastdate,
				t.forumid,
				f.name AS forum
			FROM
				threads t,
				forums f
			WHERE
				t.deleted = 0
				AND t.forumid = f.id
				AND f.boardid = ?
			ORDER BY
				t.lastdate DESC
			LIMIT
				30
			');

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

		$result .= '<tr><td><a href="http://www.laber-land.de/?page=Postings;thread='.$thread['id'].';post=-1;id='.$board.'">'.$thread['name'].'</a></td></tr>';
		}

	return $result;
	}

function getNews()
	{
	global $DB, $forum, $board;

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
			ORDER BY
				id DESC
			LIMIT
				10
			');
		$stm->bindInteger($forum);
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
			'<table style="width:100%;">
				<tr>
				<td style="font-weight:bold;font-size:18px;vertical-align:bottom;"><a href="http://www.laber-land.de/?page=Postings;id='.$board.';thread='.$thread['id'].'">'.$thread['name'].'</a></td>
				<td style="text-align:right;font-size:8px;vertical-align:middle;">'.formatDate($thread['firstdate']).'</td>
				</tr>
				<tr>
				<td style="font-size:12px;border-top: 1px dotted #8faecd;padding-bottom:20px;" colspan="2">'.$thread['summary'].'</td>
				</tr>
			</table>';
		}

	return $result;
	}

function getDescription()
	{
	global $DB, $board;

	try
		{
		$stm = $DB->prepare
			('
			SELECT
				description
			FROM
				boards
			WHERE
				id = ?'
			);
		$stm->bindInteger($board);
		return $stm->getColumn();
		}
	catch(DBNoDataException $e)
		{
		return '';
		}
	}

$body =
'<?xml version="1.0" encoding="UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1-strict.dtd ">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>archlinux.de</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
		<meta http-equiv="content-language" content="de" />
		<meta http-equiv="expires" content="120" />
		<meta name="robots" content="index,follow" />
		<meta name="revisit-after" content="3 days" />
		<link rel="shortcut icon" href="http://www.archlinux.de/favicon.ico" />
		<link rel="stylesheet" href="arch.css" />
	</head>
	<body>
		<div id="head_container">
			<div id="title">
				<div id="logo"><img src="images/logo.png" alt="Arch logo" /></div>
				<img src="images/title.png" alt="Arch linux" />
			</div>
			<div id="main_nav">
				<ul>
				<li><a href="http://wiki.archlinux.de/?title=Download">Dateien</a></li>
				<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
				<li><a href="http://wiki.archlinux.de/?title=Bugs">Bugs</a></li>
				<li><a href="http://wiki.archlinux.de">Wiki</a></li>
				<li><a href="http://www.laber-land.de/?page=Forums;id=20">Forum</a></li>
				<li class="selected"><a href="#">Start</a></li>
				</ul>
			</div>
		</div>
		<div id="content">
			<div class="right">
				<div class="updates">
					<h3>Aktuelle Themen</h3>
					<table>
						'.getRecent().'
					</table>
				</div>
				<h3>Community Links:</h3>
				<a href="http://blog.archlinux.org/">Blog</a><br />
				<a href="http://www.archlinux.org/mailman/listinfo/arch/">Arch Mailing List</a><br />
				<a href="http://www.archlinux.org/mailman/listinfo/tur-users/">AUR Mailing List</a><br />
			</div>
			<div class="left">
				<div class="box">
					<h2>Willkommen bei Arch Linux</h2>
					<p>'.getDescription().'</p>
					<div style="font-size:10px;text-align:right;"><a href="http://wiki.archlinux.de/?title=%C3%9Cber_ArchLinux" class="link">mehr über Arch Linux</a></div>
				</div>
				'.getNews().'
			</div>
			<div class="foot">
				<a href="http://wiki.archlinux.de/?title=Wiki:Datenschutz">Datenschutz</a> ::
				<a href="http://wiki.archlinux.de/?title=Wiki:Impressum">Impressum</a>
			</div>
		</div>
	</body>
</html>';

$Io->out($body);

?>