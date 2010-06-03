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

class Start extends Page {

private $arch 			= 1;

protected function makeSubMenu()
	{
	return '<ul>
			<li class="selected">Aktuelles</li>
			<li><a href="https://wiki.archlinux.de/title/Download">Herunterladen</a></li>
			<li><a href="https://wiki.archlinux.de/title/Bugs">Bugs</a></li>
			<li><a href="https://planet.archlinux.de">Planet</a></li>
		</ul>';
	}

public function prepare()
	{
	if ($this->Input->Cookie->isInt('architecture') && $this->Input->Cookie->getInt('architecture') > 0)
		{
		$this->arch = $this->Input->Cookie->getInt('architecture');
		}

	$this->setValue('title', 'Start');

	$body =
	'
		<div id="right">
			<div class="greybox">
				<form method="post" action="?page=Packages">
					<label for="searchfield">Paket-Suche:</label>
					<input type="text" name="search" size="20" maxlength="200" id="searchfield" style="width:230px" />
				</form>
				<script type="text/javascript">
					/* <![CDATA[ */
					document.getElementById("searchfield").focus();
					/* ]]> */
				</script>
			</div>
			<div class="greybox">
				<h3>Aktualisierte Pakete</h3>
				'.$this->getRecentPackages().'
				<div style="text-align:right;font-size:x-small"><a href="?page=Packages">&#187; Paket-Suche</a></div>
			</div>
			<div class="greybox">
				<h3>Aktuelle Themen im Forum</h3>
				'.$this->getRecentThreads().'
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
				<div style="font-size:x-small;text-align:right;"><a href="https://wiki.archlinux.de/title/%C3%9Cber_Arch_Linux" class="link">mehr über Arch Linux</a></div>
			</div>
			<h2>Aktuelle Ankündigungen</h2>
			'.$this->getNews().'
		</div>
	';

	$this->setValue('body', $body);
	}

private function getRecentThreads()
	{
	$result = '';

	if (! ($result = $this->PersistentCache->getObject('bbs_feed')))
		{
		try
			{
			$file = new RemoteFile($this->Settings->getValue('bbs_feed'));
			$feed = new SimpleXMLElement($file->getFileContent());

			foreach ($feed->entry as $entry)
				{
				$result .=
					'
					<h4><a href="'.$entry->link->attributes()->href.'">'.cutString($entry->title, 54).'</a></h4>
					<p>'.cutString(strip_tags($entry->summary), 400).'</p>
					';
				}
			$this->PersistentCache->addObject('bbs_feed', $result, 600);
			}
		catch(FileException $e)
			{
			}
		}

	return $result;
	}

private function getNews()
	{
	$result = '';

	if (! ($result = $this->PersistentCache->getObject('news_feed')))
		{
		try
			{
			$file = new RemoteFile($this->Settings->getValue('news_feed'));
			$feed = new SimpleXMLElement($file->getFileContent());

			foreach ($feed->entry as $entry)
				{
				$result .=
					'
					<span style="float:right; font-size:x-small;padding-top:14px">'.$this->L10n->getDateTime(strtotime($entry->updated)).'</span>
					<h3><a href="'.$entry->link->attributes()->href.'">'.$entry->title.'</a></h3>
					'.$entry->summary.'
					';
				}
			$this->PersistentCache->addObject('news_feed', $result, 1800);
			}
		catch(FileException $e)
			{
			}
		}

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
				repositories.name AS repository,
				architectures.name AS architecture
			FROM
				packages,
				repositories,
				architectures
			WHERE
				packages.repository = repositories.id
				AND repositories.name IN (\'core\', \'extra\', \'testing\')
				AND packages.arch = architectures.id
				AND packages.arch = ?
			ORDER BY
				packages.builddate DESC
			LIMIT
				20
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
				<td><a href="?page=PackageDetails;repo='.$package['repository'].';arch='.$package['architecture'].';pkgname='.$package['name'].'">'.$package['name'].'</a></td>
				<th>'.$package['version'].'</th>
			</tr>
			';
		}

	isset($stm) && $stm->close();

	return $result.'</table>';
	}

}

?>
