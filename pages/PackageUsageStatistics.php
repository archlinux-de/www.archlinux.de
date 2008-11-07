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

class PackageUsageStatistics extends Page implements IDBCachable {


protected function makeMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=Spenden">Spenden</a></li>
			<li class="selected">Pakete</li>
			<li><a href="http://wiki.archlinux.de">Wiki</a></li>
			<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li><a href="?page=Start">Start</a></li>
		</ul>';
	}

protected function makeSubMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
			<li class="selected">Statistiken</li>
			<li><a href="?page=MirrorCheck">Server</a></li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li><a href="?page=Packages">Suche</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', 'Paket-Nutzung');

	if (!($body = $this->PersistentCache->getObject('PackageUsageStatistics:')))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure('Keine Daten gefunden!');
		}

	$this->setValue('body', $body);
	}

private static function formatNumber($number, $decs=0)
	{
	return number_format($number, $decs, ',', '.');
	}

public static function updateDBCache(DB $db, PersistentCache $cache)
	{
	try
		{
		$log = $db->getRow
			('
			SELECT
				(SELECT COUNT(*) FROM package_statistics_log) AS submissions,
				(SELECT COUNT(*) FROM (SELECT * FROM package_statistics_log GROUP BY ip) AS temp) AS differentips,
				(SELECT MIN(visited) FROM package_statistics_log) AS minvisited,
				(SELECT MAX(visited) FROM package_statistics_log) AS maxvisited,
				(SELECT SUM(count) FROM package_statistics_log) AS sumcount,
				(SELECT MIN(count) FROM package_statistics_log) AS mincount,
				(SELECT MAX(count) FROM package_statistics_log) AS maxcount,
				(SELECT AVG(count) FROM package_statistics_log) AS avgcount
			');

		$body = '<div id="box">
			<h1 id="packagename">Paket-Nutzung</h1>
			<table id="packagedetails">
				<tr>
					<th colspan="2" class="packagedetailshead">Allgemein</th>
				</tr>
				<tr>
					<th>Einsendungen</th>
					<td>'.self::formatNumber($log['submissions']).'</td>
				</tr>
				<tr>
					<th>unterschiedliche IPs</th>
					<td>'.self::formatNumber($log['differentips']).'</td>
				</tr>
				<tr>
					<th>Erster Eintrag</th>
					<td>'.formatDate($log['minvisited']).'</td>
				</tr>
				<tr>
					<th>Letzter Eintrag</th>
					<td>'.formatDate($log['maxvisited']).'</td>
				</tr>
				<tr>
					<th>Gesamtzahl Pakete</th>
					<td>'.self::formatNumber($log['sumcount']).'</td>
				</tr>
				<tr>
					<th>Minimal installierte Pakete</th>
					<td>'.self::formatNumber($log['mincount']).'</td>
				</tr>
				<tr>
					<th>Maximal installierte Pakete</th>
					<td>'.self::formatNumber($log['maxcount']).'</td>
				</tr>
				<tr>
					<th>&empty; installierte Pakete</th>
					<td>'.self::formatNumber($log['avgcount']).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Architekturen</th>
				</tr>
				'.self::getPackagesPerArchitecture($db).'
				<tr>
					<th colspan="2" class="packagedetailshead">Installierte Pakete</th>
				</tr>
				'.self::getPackagesPerRepository($db).'
				<tr>
					<th colspan="2" class="packagedetailshead">Ungenutzte Pakete</th>
				</tr>
				'.self::getUnusedPackagesPerRepository($db).'
			</table>
			</div>
			';

		$cache->addObject('PackageUsageStatistics:', $body);
		}
	catch (DBNoDataException $e)
		{
		}
	}

private static function getPackagesPerArchitecture(DB $db)
	{
	$arches = $db->getRowSet
		('
		SELECT
			SUM(count) AS count,
			arch AS name
		FROM
			package_statistics_log
		GROUP BY
			arch
		');

	$list = '';

	foreach ($arches as $arch)
		{
		$list .= '<tr><th>'.$arch['name'].'</th><td>'.self::formatNumber($arch['count']).'</td></tr>';
		}

	return $list;
	}

private static function getPackagesPerRepository(DB $db)
	{
	$repos = $db->getRowSet
		('(
		SELECT
			SUM(package_statistics.count) AS count,
			repositories.name
		FROM
			package_statistics,
			packages,
			repositories,
			architectures
		WHERE
			package_statistics.name = packages.name
			AND package_statistics.arch = architectures.name
			AND packages.repository = repositories.id
			AND repositories.name <> "testing"
			AND packages.arch = architectures.id
		GROUP BY
			repositories.id
		) UNION (
		SELECT
			SUM(package_statistics.count) AS count,
			"unknown" AS name
		FROM
			package_statistics
		WHERE
			package_statistics.name NOT IN (SELECT name FROM packages)
		)
		');

	$list = '';

	foreach ($repos as $repo)
		{
		$list .= '<tr><th>'.$repo['name'].'</th><td>'.self::formatNumber($repo['count']).'</td></tr>';
		}

	return $list;
	}

private static function getUnusedPackagesPerRepository(DB $db)
	{
	$repos = $db->getRowSet
		('
		SELECT
			COUNT(packages.id) AS count,
			repositories.name
		FROM
			packages,
			repositories
		WHERE
			packages.repository = repositories.id
			AND repositories.name <> "testing"
			AND packages.name NOT IN (SELECT name FROM package_statistics)
		GROUP BY
			repositories.id
		');

	$list = '';

	foreach ($repos as $repo)
		{
		$list .= '<tr><th>'.$repo['name'].'</th><td>'.self::formatNumber($repo['count']).'</td></tr>';
		}

	return $list;
	}

}

?>