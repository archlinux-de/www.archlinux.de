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

class PackageStatistics extends Page{


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
			<li class="selected">Statistiken</li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li><a href="?page=Packages">Suche</a></li>
		</ul>';
	}


public function prepare()
	{
	$this->setValue('title', 'Paket-Statistiken');

	if (!($body = $this->ObjectCache->getObject('AL:PackageStatistics::')))
		{
		try
			{
			$data = $this->DB->getRow
				('
				SELECT
					(SELECT COUNT(*) FROM pkgdb.architectures) AS architectures,
					(SELECT COUNT(*) FROM pkgdb.repositories) AS repositories,
					(SELECT COUNT(*) FROM pkgdb.packages) AS packages,
					(SELECT COUNT(*) FROM pkgdb.files) AS files,
					(SELECT SUM(csize) FROM pkgdb.packages) AS csize,
					(SELECT SUM(isize) FROM pkgdb.packages) AS isize,
					(SELECT COUNT(*) FROM pkgdb.packagers) AS packagers,
					(SELECT COUNT(*) FROM pkgdb.groups) AS groups,
					(SELECT COUNT(*) FROM pkgdb.licenses) AS licenses,
					(SELECT COUNT(*) FROM pkgdb.depends) AS depends,
					(SELECT COUNT(*) FROM pkgdb.conflicts) AS conflicts,
					(SELECT COUNT(*) FROM pkgdb.replaces) AS replaces,
					(SELECT COUNT(*) FROM pkgdb.provides) AS provides,
					(SELECT COUNT(*) FROM pkgdb.file_index) AS file_index,
					(SELECT AVG(csize) FROM pkgdb.packages) AS avgcsize,
					(SELECT AVG(isize) FROM pkgdb.packages) AS avgisize,
					(SELECT
						AVG(pkgs)
					FROM
						(
						SELECT
							COUNT(packages.id) AS pkgs
						FROM
							pkgdb.packages
								JOIN
									pkgdb.packagers
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
							pkgdb.files
						GROUP BY package
						) AS temp2
					) AS avgfiles
				');
			}
		catch (DBNoDataException $e)
			{
			$this->Io->setStatus(Io::NOT_FOUND);
			$this->showFailure('Paket-Statistiken nicht gefunden!');
			}

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
					<td>'.$this->formatNumber($data['groups']).'</td>
				</tr>
				<tr>
					<th>Pakete</th>
					<td>'.$this->formatNumber($data['packages']).'</td>
				</tr>
				<tr>
					<th>Dateien</th>
					<td>'.$this->formatNumber($data['files']).'</td>
				</tr>
				<tr>
					<th>Größe des Datei-Index</th>
					<td>'.$this->formatNumber($data['file_index']).'</td>
				</tr>
				<tr>
					<th>Lizenzen</th>
					<td>'.$this->formatNumber($data['licenses']).'</td>
				</tr>
				<tr>
					<th>Abhängigkeiten</th>
					<td>'.$this->formatNumber($data['depends']).'</td>
				</tr>
				<tr>
					<th>Bereitstellungen</th>
					<td>'.$this->formatNumber($data['provides']).'</td>
				</tr>
				<tr>
					<th>Konflikte</th>
					<td>'.$this->formatNumber($data['conflicts']).'</td>
				</tr>
				<tr>
					<th>Ersetzungen</th>
					<td>'.$this->formatNumber($data['replaces']).'</td>
				</tr>
				<tr>
					<th>Größe der Repositorien</th>
					<td>'.$this->formatBytes($data['csize']).'Byte</td>
				</tr>
				<tr>
					<th>Größe der Dateien</th>
					<td>'.$this->formatBytes($data['isize']).'Byte</td>
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
					<td>&empty; '.$this->formatBytes($data['avgcsize']).'Byte</td>
				</tr>
				<tr>
					<th>Größe der Dateien</th>
					<td>&empty; '.$this->formatBytes($data['avgisize']).'Byte</td>
				</tr>
				<tr>
					<th>Dateien pro Paket</th>
					<td>&empty; '.$this->formatNumber($data['avgfiles'], 2).'</td>
				</tr>
				<tr>
					<th>Pakete pro Packer</th>
					<td>&empty; '.$this->formatNumber($data['avgpkgperpackager'], 2).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Repositorien</th>
				</tr>
					'.$this->getPositoryStatistics().'
			</table>
			<table id="packagedependencies">
				<tr>
					<th colspan="4" class="packagedependencieshead">Extrema</th>
				</tr>
				<tr>
					<th>Große Pakete</th>
					<th>Kleine Pakete</th>
					<th>Viele Dateien</th>
					<th>Wenige Dateien</th>
				</tr>
				<tr>
					<td>'.$this->getLargestPackages().'</td>
					<td>'.$this->getSmallestPackages().'</td>
					<td>'.$this->getMostFiles().'</td>
					<td>'.$this->getLeastFiles().'</td>
				</tr>
			</table>
			</div>
			';

		$this->ObjectCache->addObject('AL:PackageStatistics::', $body, 60*60*24*7);
		}

	$this->setValue('body', $body);
	}

private function getPositoryStatistics()
	{
	$repolist = '';
	$repos = $this->DB->getRowSet('SELECT id, name FROM pkgdb.repositories')->toArray();
	$arches = $this->DB->getRowSet('SELECT id, name FROM pkgdb.architectures')->toArray();

	$stm = $this->DB->prepare
			('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				pkgdb.packages
			WHERE
				repository = ?
				AND arch = ?
			');

	foreach ($repos as $repo)
		{
		$repolist .= '<tr><th><a href="?page=Packages;repository='.$repo['id'].'">['.$repo['name'].']</a></th><td style="padding:0px;"><table style="width:320px;padding:0px;">';

		foreach ($arches as $arch)
			{
			$repolist .= '<tr><th style="width:50px;padding:0px;"><a href="?page=Packages;repository='.$repo['id'].';architecture='.$arch['id'].'">'.$arch['name'].'</a></th>';

			$stm->bindInteger($repo['id']);
			$stm->bindInteger($arch['id']);
			$data = $stm->getRow();

			$repolist .= '<td style="width:100px;text-align:right;padding:0px;">'.$this->formatNumber($data['packages']).' Pakete</td>
			<td style="text-align:right;padding:0px;">'.$this->formatBytes($data['size']).'Byte</td>';

			$repolist .= '</tr>';
			}

		$repolist .='</table></td></tr>';
		}

	$stm->close();

	return $repolist;
	}

private function getLargestPackages()
	{
	$packages = $this->DB->getRowSet
		('
		SELECT
			id,
			name,
			csize
		FROM
			pkgdb.packages
		ORDER BY
			csize DESC
		LIMIT
			50
		');

	$list = '<table>';

	foreach ($packages as $package)
		{
		$list .= '<tr><td><a href="?page=PackageDetails;package='.$package['id'].'">'.cutString($package['name'], 20).'</a></td><td style="text-align:right;">'.$this->formatBytes($package['csize']).'Byte</td></tr>';
		}

	return $list.'</table>';
	}

private function getSmallestPackages()
	{
	$packages = $this->DB->getRowSet
		('
		SELECT
			id,
			name,
			csize
		FROM
			pkgdb.packages
		ORDER BY
			csize ASC
		LIMIT
			50
		');

	$list = '<table>';

	foreach ($packages as $package)
		{
		$list .= '<tr><td><a href="?page=PackageDetails;package='.$package['id'].'">'.cutString($package['name'], 20).'</a></td><td style="text-align:right;">'.$this->formatBytes($package['csize']).'Byte</td></tr>';
		}

	return $list.'</table>';
	}

private function getMostFiles()
	{
	$packages = $this->DB->getRowSet
		('
		SELECT
			packages.id,
			packages.name,
			(SELECT COUNT(*) FROM pkgdb.files WHERE files.package = packages.id) AS files
		FROM
			pkgdb.packages
		ORDER BY
			files DESC
		LIMIT
			50
		');

	$list = '<table>';

	foreach ($packages as $package)
		{
		$list .= '<tr><td><a href="?page=PackageDetails;package='.$package['id'].'">'.cutString($package['name'], 20).'</a></td><td style="text-align:right;">'.$this->formatNumber($package['files']).'</td></tr>';
		}

	return $list.'</table>';
	}

private function getLeastFiles()
	{
	$packages = $this->DB->getRowSet
		('
		SELECT
			packages.id,
			packages.name,
 			(SELECT COUNT(*) FROM pkgdb.files WHERE files.package = packages.id) AS files
		FROM
			pkgdb.packages
		ORDER BY
			files ASC
		LIMIT
			50
		');

	$list = '<table>';

	foreach ($packages as $package)
		{
		$list .= '<tr><td><a href="?page=PackageDetails;package='.$package['id'].'">'.cutString($package['name'], 20).'</a></td><td style="text-align:right;">'.$this->formatNumber($package['files']).'</td></tr>';
		}

	return $list.'</table>';
	}

private function formatBytes($bytes)
	{
	$kb = 1024;
	$mb = $kb * 1024;
	$gb = $mb * 1024;

	if ($bytes >= $gb)	// GB
		{
		$result = round($bytes / $gb, 2);
		$postfix = ' G';
		}
	elseif ($bytes >= $mb)	// MB
		{
		$result =  round($bytes / $mb, 2);
		$postfix = ' M';
		}
	elseif ($bytes >= $kb)	// KB
		{
		$result =  round($bytes / $kb, 2);
		$postfix = ' K';
		}
	else			//  B
		{
		$result =  $bytes;
		$postfix = ' ';
		}

	return $this->formatNumber($result, 2).$postfix;
	}

private function formatNumber($number, $decs=0)
	{
	return number_format($number, $decs, ',', '.');
	}

}

?>