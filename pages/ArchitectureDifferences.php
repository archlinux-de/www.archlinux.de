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

class ArchitectureDifferences extends Page{

private $showminor = false;


protected function makeMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=Spenden">Spenden</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Download">ISOs</a></li>
			<li class="selected">Pakete</li>
			<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Bugs">Bugs</a></li>
			<li><a href="http://wiki.archlinux.de">Wiki</a></li>
			<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li><a href="?page=Start">Start</a></li>
		</ul>';
	}

protected function makeSubMenu()
	{
	return '
		<ul id="nav">
			<li><a href="?page=PackageStatistics">Statistiken</a></li>
			<li><a href="?page=MirrorCheck">Server</a></li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li class="selected">Architekturen</li>
			<li><a href="?page=Packages">Suche</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', 'Architektur-Unterschiede');
	$this->showminor = $this->Io->isRequest('showminor');

	try
		{
		$packages = $this->DB->getRowSet
			('
			(
			SELECT
				i.id AS iid,
				i.name,
				i.version AS iversion,
				x.id AS xid,
				x.version AS xversion,
				repositories.id AS repoid,
				repositories.name AS reponame,
				GREATEST(i.builddate, x.builddate) AS builddate
			FROM
				pkgdb.packages i
					JOIN
						pkgdb.packages x
					ON
						i.id <> x.id
						AND i.name = x.name
						AND i.version <> x.version
						AND i.repository = x.repository
						AND i.arch = 1
						AND x.arch = 2
					JOIN
						pkgdb.repositories
					ON
						i.repository = repositories.id
			)
			UNION
			(
			SELECT
				i.id AS iid,
				i.name,
				i.version AS iversion,
				0 AS xid,
				0 AS xversion,
				repositories.id AS repoid,
				repositories.name AS reponame,
				i.builddate AS builddate
			FROM
				pkgdb.packages i
					JOIN
						pkgdb.repositories
					ON
						i.repository = repositories.id
			WHERE
				i.arch = 1
				AND NOT EXISTS
					(
					SELECT
						id
					FROM
						pkgdb.packages x
					WHERE
						i.name = x.name
						AND i.repository = x.repository
						AND x.arch = 2
					)
			)
			UNION
			(
			SELECT
				0 AS iid,
				x.name,
				0 AS iversion,
				x.id AS xid,
				x.version AS xversion,
				repositories.id AS repoid,
				repositories.name AS reponame,
				x.builddate AS builddate
			FROM
				pkgdb.packages x
					JOIN
						pkgdb.repositories
					ON
						x.repository = repositories.id
			WHERE
				x.arch = 2
				AND NOT EXISTS
					(
					SELECT
						id
					FROM
						pkgdb.packages i
					WHERE
						i.name = x.name
						AND i.repository = x.repository
						AND i.arch = 1
					)
			)
			ORDER BY
				repoid ASC,
				builddate DESC
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$body = '
		<div class="greybox" id="searchbox">
			<h4 style="text-align: right">Architektur-Unterschiede</h4>
			<p style="font-size:12px;">Diese Tabelle zeigt Unterschiede in den Paket-Versionen zu den beiden Architekturen <em>i686</em> und <em>x86_64</em>.</p>
			<p style="font-size:12px;">Versionsunterschiede im Nachkommabereich deuten an, daß die entsprechende Aktualisierung nur eine Architektur betraf. Diese Unterschiede werden daher standardmäßig ausgeblendet.</p>
			<div style="font-size:10px; text-align:right;padding-bottom:10px;">
			'.($this->Io->isRequest('showminor') ? '<a href="?page=ArchitectureDifferences">Architekturspezifische Änderungen ausblenden</a>' : '<a href="?page=ArchitectureDifferences;showminor">Architekturspezifische Änderungen anzeigen</a>').'
			</div>
		</div>
		<table id="packages">
			<tr>
				<th>Name</th>
				<th>i686</th>
				<th>x86_64</th>
				<th>Aktualisierung</th>
			</tr>';

	$line = 0;
	$repo = 0;

	foreach ($packages as $package)
		{
		if ($this->isMinorPackageRelease($package['iversion'], $package['xversion']) && !$this->showminor)
			{
			continue;
			}

		$style = $package['reponame'] == 'testing' ? ' testingpackage' : '';
		if ($repo != $package['repoid'])
			{
			$body .= '<tr>
					<th colspan="4" class="pages" style="background-color:#1793d1;text-align:center;">['.$package['reponame'].']</th>
				</tr>';
			}
		$minor = $this->showminor && $this->isMinorPackageRelease($package['iversion'], $package['xversion']) ? ' style="color:green;"' : '';

		if ($this->compareVersions($package['iversion'], $package['xversion']) < 0)
			{
			$iold = ' style="color:red;"';
			$xold = '';
			}
		else
			{
			$iold = '';
			$xold = ' style="color:red;"';
			}

		$body .= '<tr class="packageline'.$line.$style.'"'.$minor.'>
				<td>'.$package['name'].'</td>
				<td>'.(empty($package['iid']) ? '' : '<a href="?page=PackageDetails;package='.$package['iid'].'"'.$iold.'>'.$package['iversion'].'</a>').'</td>
				<td>'.(empty($package['xid']) ? '' : '<a href="?page=PackageDetails;package='.$package['xid'].'"'.$xold.'>'.$package['xversion'].'</a>').'</td>
				<td>'.formatDate($package['builddate']).'</td>
			</tr>';

		$line = abs($line-1);
		$repo = $package['repoid'];
		}

	$body .= '</table>';

	$this->setValue('body', $body);
	}

private function isMinorPackageRelease($ver1, $ver2)
	{
	return $this->getPackageVersion($ver1) == $this->getPackageVersion($ver2) && floor($this->getPackageRelease($ver1)) == floor($this->getPackageRelease($ver2));
	}

private function getPackageVersion($version)
	{
	$temp = explode('-', $version);
	array_pop($temp);
	return implode('-', $temp);
	}

private function getPackageRelease($version)
	{
	$temp = explode('-', $version);
	return array_pop($temp);
	}

private function compareVersions($ver1, $ver2)
	{
	return version_compare($ver1, $ver2);
	}

}

?>