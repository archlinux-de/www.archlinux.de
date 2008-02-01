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

require (LL_PATH.'modules/ObjectCache.php');
Modul::__set('ObjectCache', new ObjectCache());

class Start extends Page{

private $board 			= 20;
private $archNewsForum 		= 257;
private $importantTag		= 3;
private $solvedTag		= 1;

public function prepare()
	{
	$this->setValue('title', 'Start');

	if (!($body = $this->ObjectCache->getObject('AL:Start::')))
		{
		$body =
		'
			<div id="right">
				<div class="greybox">
					<h3>Aktualisierte Pakete</h3>
					'.$this->getRecentPackages().'
					<div style="text-align:right;font-size:x-small"><a href="?page=Packages">&#187; Paket-Suche</a></div>
				</div>
				<div class="greybox">
					<h3>Aktuelle Themen im Forum</h3>
					'.$this->getRecentThreads().'
					<div style="text-align:right;font-size:x-small"><a href="http://forum.archlinux.de/?page=Recent;id=20;">&#187; alle aktuellen Themen</a></div>
				</div>
			</div>
			<div id="left">
				<div id="box">
					<h2>Willkommen bei Arch Linux</h2>
					<p>
					<strong>Arch Linux</strong> ist eine <em>flexible</em> und <em>leichtgewichtige</em> Distribution für jeden erdenklichen Einsatz-Zweck. Ein einfaches Grundsystem kann nach den Bedürfnissen des jeweiligen Nutzers nahezu beliebig erweitert werden.
					</p>
					<p>
					Nach einem gleitenden Release-System bieten wir zur Zeit vorkompilierte Pakete für die <code>i686</code>- und <code>x86_64</code>-Architekturen an. Zusätzliche Werkzeuge ermöglichen zudem den schnellen Eigenbau von Paketen.
					</p>
					<p>
					Arch Linux ist daher eine perfekte Distribution für erfahrene Anwender &mdash; und solche, die es werden wollen...
					</p>
					<div style="font-size:x-small;text-align:right;"><a href="http://wiki.archlinux.de/?title=%C3%9Cber_ArchLinux" class="link">mehr über Arch Linux</a></div>
				</div>
				<h2>Aktuelle Ankündigungen</h2>
				'.$this->getImportantNews().'
				<div style="text-align:right;font-size:x-small">
					<a href="http://forum.archlinux.de/?page=Threads;id=20;forum='.$this->archNewsForum.'">&#187; Archiv</a>
				</div>
			</div>
		';

		$this->ObjectCache->addObject('AL:Start::', $body, 60*60);
		}

	$this->setValue('body', $body);
	}

private function getRecentThreads()
	{
	try
		{
		$stm = $this->DB->prepare
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

		$stm->bindInteger($this->archNewsForum);
		$stm->bindInteger($this->board);
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
			<h4><a href="http://forum.archlinux.de/?page=Postings;thread='.$thread['id'].';post=-1;id='.$this->board.'">'.$thread['name'].'</a></h4>
			<p>'.$thread['summary'].'</p>
			';
		}

	$stm->close();

	return $result;
	}

private function getImportantNews()
	{
	try
		{
		$stm = $this->DB->prepare
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
		$stm->bindInteger($this->archNewsForum);
		$stm->bindInteger($this->importantTag);
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
			<span style="float:right; font-size:x-small;padding-top:14px">'.formatDate($thread['firstdate']).'</span>
			<h3><a href="http://forum.archlinux.de/?page=Postings;id='.$this->board.';thread='.$thread['id'].'">'.$thread['name'].'</a></h3>
			<p>'.$thread['summary'].'</p>
			';
		}

	$stm->close();

	return $result;
	}

private function getRecentPackages()
	{
	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				packages.id,
				packages.pkgname,
				packages.pkgver,
				packages.pkgrel,
				categories.name AS category
			FROM
				pkgdb.packages,
				pkgdb.categories
			WHERE
				packages.category = categories.id
			ORDER BY
				packages.lastupdate DESC
			LIMIT
				15
			');
		}
	catch(DBNoDataException $e)
		{
		$packages = array();
		}

	$result = '<table id="recentupdates">';

	foreach ($packages as $package)
		{
		$result .= '
			<tr>
				<td><a href="?page=PackageDetails;package='.$package['id'].'">'.$package['pkgname'].'</a></td>
				<td>'.$package['pkgver'].'-'.$package['pkgrel'].'</td>
				<th>'.$package['category'].'</th>
			</tr>
			';
		}

	return $result.'</table>';
	}

}

?>
