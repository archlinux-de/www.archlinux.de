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

class Start extends Page{

private $board 			= 20;
private $archNewsForum 		= 257;
private $importantTag		= 3;
private $solvedTag		= 1;
private $arch 			= 1;

protected function makeSubMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://planet.archlinux.de">Planet</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Bugs">Bugs</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Download">Herunterladen</a></li>
			<li class="selected">Aktuelles</li>
		</ul>';
	}

public function prepare()
	{
	if ($this->Input->Cookie->isInt('architecture') && $this->Input->Cookie->getInt('architecture') > 0)
		{
		$this->arch = $this->Input->Cookie->getInt('architecture');
		}

	$this->setValue('title', 'Start');

	if (!($body = $this->ObjectCache->getObject('AL:Start:'.$this->arch.':')))
		{
		$body =
		'
			<div id="search">
			<form method="post" action="?page=Packages">
				<div>Paket-Suche:&nbsp;&nbsp;<input type="text" name="search" size="20" maxlength="200" id="searchfield" /></div>
			</form>
			<script type="text/javascript">
				/* <![CDATA[ */
				document.getElementById("searchfield").focus();
				/* ]]> */
			</script>
			</div>
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

		$this->ObjectCache->addObject('AL:Start:'.$this->arch.':', $body, 60*60);
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
				ll.threads t,
				ll.forums f
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
				t.id,
				t.name,
				p.dat,
				p.text
			FROM
				ll.threads t,
				ll.posts p
			WHERE
				t.forumid = ?
				AND t.deleted = 0
				AND t.tag = ?
				AND p.threadid = t.id
				AND p.counter = 0
			ORDER BY
				t.id DESC
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
			<span style="float:right; font-size:x-small;padding-top:14px">'.$this->L10n->getDateTime($thread['dat']).'</span>
			<h3><a href="http://forum.archlinux.de/?page=Postings;id='.$this->board.';thread='.$thread['id'].'">'.$thread['name'].'</a></h3>
			<p>'.$thread['text'].'</p>
			';
		}

	$stm->close();

	return $result;
	}

private function getRecentPackages()
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				packages.id,
				packages.name,
				packages.version,
				repositories.name AS repository
			FROM
				packages,
				repositories
			WHERE
				packages.repository = repositories.id
				AND repositories.name IN (\'core\', \'extra\', \'testing\')
				AND packages.arch = ?
			ORDER BY
				packages.builddate DESC
			LIMIT
				15
			');
		$stm->bindInteger($this->arch);
		$packages = $stm->getRowSet();
		}
	catch(DBNoDataException $e)
		{
		$packages = array();
		}

	$result = '<table id="recentupdates">';

	foreach ($packages as $package)
		{
		$style = $package['repository'] == 'testing' ? ' class="testingpackage"' : '';
		$result .= '
			<tr'.$style.'>
				<td><a href="?page=PackageDetails;package='.$package['id'].'">'.$package['name'].'</a></td>
				<th>'.$package['version'].'</th>
			</tr>
			';
		}

	isset($stm) && $stm->close();

	return $result.'</table>';
	}

}

?>
