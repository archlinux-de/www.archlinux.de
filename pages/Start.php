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

private $arch = 1;


public function prepare()
	{
	if ($this->Input->Cookie->isInt('architecture') && $this->Input->Cookie->getInt('architecture') > 0)
		{
		$this->arch = $this->Input->Cookie->getInt('architecture');
		}

	$this->setValue('title', 'Start');

	$body =
	'<div id="left-wrapper">
		<div id="left">
			<div id="intro" class="box">
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
				<p class="readmore"><a href="https://wiki.archlinux.de/title/%C3%9Cber_Arch_Linux">mehr über Arch Linux</a></p>
			</div>
			<div id="news">
			<h3>Aktuelle Ankündigungen</h3>
			'.$this->getNews().'
			</div>
		</div>
	</div>
		<div id="right">
			<div id="pkgsearch">
				<form method="post" action="?page=Packages">
					<label for="searchfield">Paket-Suche:</label>
					<input type="text" name="search" size="20" maxlength="200" id="searchfield" />
				</form>
				<script type="text/javascript">
					/* <![CDATA[ */
					document.getElementById("searchfield").focus();
					/* ]]> */
				</script>
			</div>
			<div id="pkgrecent" class="box">
				<h3>Aktualisierte Pakete</h3>
				'.$this->getRecentPackages().'
			</div>
			<div id="sidebar">
				<h4>Dokumentation</h4>
				<ul>
					<li><a href="https://wiki.archlinux.de/">Wiki</a></li>
					<li><a href="https://wiki.archlinux.de/title/Offizielle_Arch_Linux_Installations-Anleitung">Offizielle Arch Linux Installations-Anleitung</a></li>
				</ul>
				<h4>Gemeinschaft</h4>
				<ul>
					<li><a href="https://planet.archlinux.de/">Planet archlinux.de</a></li>
					<li><a href="https://www.archlinux.org/">Archlinux.org</a></li>
					<li><a href="https://wiki.archlinux.org/index.php/International_Communities">Internationale Gemeinschaft</a></li>
				</ul>
				<h4>Unterstützung</h4>
				<ul>
					<li><a href="https://wiki.archlinux.de/title/Spenden">Spenden (archlinux.de)</a></li>
					<li><a href="https://www.archlinux.org/donate/">Spenden (international)</a></li>
				</ul>
				<h4>Entwicklung</h4>
				<ul>
					<li><a href="?page=Packages">Pakete</a></li>
					<li><a href="?page=ArchitectureDifferences">Architektur-Unterschiede</a></li>
					<li><a href="http://aur.archlinux.org/index.php?setlang=de">AUR</a></li>
					<li><a href="https://bugs.archlinux.org/">Bug Tracker</a></li>
					<li><a href="https://www.archlinux.org/svn/">SVN Repositories</a></li>
					<li><a href="https://projects.archlinux.org/">Projekte in Git</a></li>
					<li><a href="https://git.archlinux.de/">archlinux.de in Git</a></li>
					<li><a href="https://wiki.archlinux.org/index.php/DeveloperWiki">Entwickler-Wiki</a></li>
				</ul>
				<h4>Informationen</h4>
				<ul>
					<li><a href="https://wiki.archlinux.de/title/%C3%9Cber_Arch_Linux">über Arch Linux</a></li>
					<li><a href="https://wiki.archlinux.de/title/Download">Arch herunterladen</a></li>
					<li><a href="https://wiki.archlinux.de/title/Arch_in_den_Medien">Arch in den Medien</a></li>
					<li><a href="https://www.archlinux.org/art/">Logos</a></li>
					<li><a href="https://www.archlinux.org/developers/">Entwickler</a></li>
					<li><a href="https://www.archlinux.org/trustedusers/">Trusted Users</a></li>
					<li><a href="https://www.archlinux.org/fellows/">Ehemalige</a></li>
					<li><a href="?page=MirrorStatus">Mirror-Status</a></li>
					<li><a href="?page=PackageStatistics">Statistiken</a></li>
				</ul>
			</div>
		</div>
	';

	$this->setValue('body', $body);
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
					<h4><a href="'.$entry->link->attributes()->href.'">'.$entry->title.'</a></h4>
					<p class="date">'.$this->L10n->getDate(strtotime($entry->updated)).'</p>
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

	$result = '<table>';

	foreach ($packages as $package)
		{
		$result .= '
			<tr class="'.$package['repository'].'">
				<td class="pkgname"><a href="?page=PackageDetails;repo='.$package['repository'].';arch='.$package['architecture'].';pkgname='.$package['name'].'">'.$package['name'].'</a></td>
				<td class="pkgver">'.$package['version'].'</td>
			</tr>
			';
		}

	isset($stm) && $stm->close();

	return $result.'</table>';
	}

}

?>
