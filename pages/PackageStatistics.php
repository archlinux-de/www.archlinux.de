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
			<li><a href="?page=MirrorCheck">Server</a></li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li><a href="?page=Packages">Suche</a></li>
		</ul>';
	}


public function prepare()
	{
	$this->setValue('title', 'Paket-Statistiken');

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
					(SELECT COUNT(*) FROM pkgdb.optdepends) AS optdepends,
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
					<th>Optionale Abhängigkeiten</th>
					<td>'.$this->formatNumber($data['optdepends']).'</td>
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
					'.$this->getPositoryStatistics($data['packages'], $data['csize']).'
			</table>
			</div>
			';

	$this->setValue('body', $body);
	}

private function getPositoryStatistics($packages, $size)
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
		$repolist .= '<tr><th><a href="?page=Packages;repository='.$repo['id'].'">['.$repo['name'].']</a></th><td style="padding:0px;"><table style="padding:0px;">';

		foreach ($arches as $arch)
			{
			$repolist .= '<tr><th style="width:50px;padding:0px;"><a href="?page=Packages;repository='.$repo['id'].';architecture='.$arch['id'].'">'.$arch['name'].'</a></th>';

			$stm->bindInteger($repo['id']);
			$stm->bindInteger($arch['id']);
			$data = $stm->getRow();

			$pkgpercent = round(($data['packages'] / $packages) * 200);
			$sizepercent = round(($data['size'] / $size) * 200);

			$repolist .= '<td style="width:100px;text-align:right;padding:0px;">'.$this->formatNumber($data['packages']).' Pakete</td>
			<td style="width:100px;padding:0px;"><div style="background-color:#1793d1;width:'.$pkgpercent.'px;">&nbsp;</div></td>
			<td style="width:100px;text-align:right;padding:0px;">'.$this->formatBytes($data['size']).'Byte</td>
			<td style="width:100px;padding:0px;"><div style="background-color:#1793d1;width:'.$sizepercent.'px;">&nbsp;</div></td>';

			$repolist .= '</tr>';
			}

		$repolist .='</table></td></tr>';
		}

	$stm->close();

	return $repolist;
	}

private function formatBytes($bytes)
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

	return $this->formatNumber($result, 2).$postfix;
	}

private function formatNumber($number, $decs=0)
	{
	return number_format($number, $decs, ',', '.');
	}

}

?>
