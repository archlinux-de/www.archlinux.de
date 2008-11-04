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

class PackageStatistics extends Page implements IDBCachable {


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
	$this->setValue('title', 'Paket-Statistiken');

	if (!($body = $this->PersistentCache->getObject('PackageStatistics:')))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure('Keine Daten vorhanden!');
		}

	$this->setValue('body', $body);
	}

private static function getPositoryStatistics($db, $packages, $size)
	{
	$repolist = '';
	try
		{
		$repos = $db->getRowSet('SELECT id, name FROM repositories')->toArray();
		}
	catch (DBNoDataException $e)
		{
		$repos = array();
		}

	try
		{
		$arches = $db->getRowSet('SELECT id, name FROM architectures')->toArray();
		}
	catch (DBNoDataException $e)
		{
		$arches = array();
		}

	$stm = $db->prepare
			('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				packages
			WHERE
				repository = ?
				AND arch = ?
			');

	foreach ($repos as $repo)
		{
		$repolist .= '<tr><th><a href="?page=Packages;repository='.$repo['id'].'">['.$repo['name'].']</a></th><td style="padding:0px;"><table style="padding:0px;">';

		foreach ($arches as $arch)
			{
			$repolist .= '<tr><th style="width:50px;padding:0px;"><a href="?page=Packages;repository='.$repo['id'].';architecture='.$arch['id'].'">'.$arch['name'].'</a></th>';

			$stm->bindInteger($repo['id']);
			$stm->bindInteger($arch['id']);
			$data = $stm->getRow();

			$pkgpercent = round(($data['packages'] / $packages) * 200);
			$sizepercent = round(($data['size'] / $size) * 200);

			$repolist .= '<td style="width:100px;text-align:right;padding:0px;">'.self::formatNumber($data['packages']).' Pakete</td>
			<td style="width:100px;padding:0px;"><div style="background-color:#1793d1;width:'.$pkgpercent.'px;">&nbsp;</div></td>
			<td style="width:100px;text-align:right;padding:0px;">'.self::formatBytes($data['size']).'Byte</td>
			<td style="width:100px;padding:0px;"><div style="background-color:#1793d1;width:'.$sizepercent.'px;">&nbsp;</div></td>';

			$repolist .= '</tr>';
			}

		$repolist .='</table></td></tr>';
		}

	$stm->close();

	return $repolist;
	}

private static function formatBytes($bytes)
	{
	$kb = 1024;
	$mb = $kb * 1024;
	$gb = $mb * 1024;

	if ($bytes >= $gb)	// GB
		{
		$result = round($bytes / $gb, 2);
		$postfix = '&nbsp;G';
		}
	elseif ($bytes >= $mb)	// MB
		{
		$result =  round($bytes / $mb, 2);
		$postfix = '&nbsp;M';
		}
	elseif ($bytes >= $kb)	// KB
		{
		$result =  round($bytes / $kb, 2);
		$postfix = '&nbsp;K';
		}
	else			//  B
		{
		$result =  $bytes;
		$postfix = '&nbsp;';
		}

	return self::formatNumber($result, 2).$postfix;
	}

private static function formatNumber($number, $decs=0)
	{
	return number_format($number, $decs, ',', '.');
	}

public static function updateDBCache(DB $db, PersistentCache $cache)
	{
	try
		{
		$data = $db->getRow
			('
			SELECT
				(SELECT COUNT(*) FROM architectures) AS architectures,
				(SELECT COUNT(*) FROM repositories) AS repositories,
				(SELECT COUNT(*) FROM packages) AS packages,
				(SELECT COUNT(*) FROM files) AS files,
				(SELECT SUM(csize) FROM packages) AS csize,
				(SELECT SUM(isize) FROM packages) AS isize,
				(SELECT COUNT(*) FROM packagers) AS packagers,
				(SELECT COUNT(*) FROM groups) AS groups,
				(SELECT COUNT(*) FROM licenses) AS licenses,
				(SELECT COUNT(*) FROM depends) AS depends,
				(SELECT COUNT(*) FROM optdepends) AS optdepends,
				(SELECT COUNT(*) FROM conflicts) AS conflicts,
				(SELECT COUNT(*) FROM replaces) AS replaces,
				(SELECT COUNT(*) FROM provides) AS provides,
				(SELECT COUNT(*) FROM file_index) AS file_index,
				(SELECT AVG(csize) FROM packages) AS avgcsize,
				(SELECT AVG(isize) FROM packages) AS avgisize,
				(SELECT
					AVG(pkgs)
				FROM
					(
					SELECT
						COUNT(packages.id) AS pkgs
					FROM
						packages
							JOIN
								packagers
							ON
								packages.packager = packagers.id
					GROUP BY packagers.id
					) AS temp
				) AS avgpkgperpackager,
				(SELECT
					AVG(pkgfiles)
				FROM
					(
					SELECT
						COUNT(id) AS pkgfiles
					FROM
						files
					GROUP BY package
					) AS temp2
				) AS avgfiles
			');

		$body = '<div id="box">
			<h1 id="packagename">Paket-Statistiken</h1>
			<table id="packagedetails">
				<tr>
					<th colspan="2" class="packagedetailshead">Umfang</th>
				</tr>
				<tr>
					<th>Architekturen</th>
					<td>'.$data['architectures'].'</td>
				</tr>
				<tr>
					<th>Repositorien</th>
					<td>'.$data['repositories'].'</td>
				</tr>
				<tr>
					<th>Gruppen</th>
					<td>'.self::formatNumber($data['groups']).'</td>
				</tr>
				<tr>
					<th>Pakete</th>
					<td>'.self::formatNumber($data['packages']).'</td>
				</tr>
				<tr>
					<th>Dateien</th>
					<td>'.self::formatNumber($data['files']).'</td>
				</tr>
				<tr>
					<th>Größe des Datei-Index</th>
					<td>'.self::formatNumber($data['file_index']).'</td>
				</tr>
				<tr>
					<th>Lizenzen</th>
					<td>'.self::formatNumber($data['licenses']).'</td>
				</tr>
				<tr>
					<th>Abhängigkeiten</th>
					<td>'.self::formatNumber($data['depends']).'</td>
				</tr>
				<tr>
					<th>Optionale Abhängigkeiten</th>
					<td>'.self::formatNumber($data['optdepends']).'</td>
				</tr>
				<tr>
					<th>Bereitstellungen</th>
					<td>'.self::formatNumber($data['provides']).'</td>
				</tr>
				<tr>
					<th>Konflikte</th>
					<td>'.self::formatNumber($data['conflicts']).'</td>
				</tr>
				<tr>
					<th>Ersetzungen</th>
					<td>'.self::formatNumber($data['replaces']).'</td>
				</tr>
				<tr>
					<th>Größe der Repositorien</th>
					<td>'.self::formatBytes($data['csize']).'Byte</td>
				</tr>
				<tr>
					<th>Größe der Dateien</th>
					<td>'.self::formatBytes($data['isize']).'Byte</td>
				</tr>
				<tr>
					<th>Packer</th>
					<td>'.$data['packagers'].'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Durchschnitte</th>
				</tr>
				<tr>
					<th>Größe der Pakete</th>
					<td>&empty; '.self::formatBytes($data['avgcsize']).'Byte</td>
				</tr>
				<tr>
					<th>Größe der Dateien</th>
					<td>&empty; '.self::formatBytes($data['avgisize']).'Byte</td>
				</tr>
				<tr>
					<th>Dateien pro Paket</th>
					<td>&empty; '.self::formatNumber($data['avgfiles'], 2).'</td>
				</tr>
				<tr>
					<th>Pakete pro Packer</th>
					<td>&empty; '.self::formatNumber($data['avgpkgperpackager'], 2).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Repositorien</th>
				</tr>
					'.self::getPositoryStatistics($db, $data['packages'], $data['csize']).'
			</table>
			</div>
			';

		$cache->addObject('PackageStatistics:', $body);
		}
	catch (DBNoDataException $e)
		{
		}
	}

}

?>
