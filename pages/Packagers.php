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

class Packagers extends Page{

private $orderby 	= 'name';
private $sort 		= 0;

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
			<li class="selected">Packer</li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li><a href="?page=Packages">Suche</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', 'Packer');

	try
		{
		if (in_array($this->Io->getString('orderby'), array('name', 'lastbuilddate', 'packages')))
			{
			$this->orderby = $this->Io->getString('orderby');
			}
		}
	catch (IoRequestException $e)
		{
		}

	try
		{
		$this->sort = $this->Io->getInt('sort') > 0 ? 1 : 0;
		}
	catch (IoRequestException $e)
		{
		}

	$packages = $this->DB->getColumn('SELECT COUNT(*) FROM pkgdb.packages');

	try
		{
		$stm = $this->DB->prepare
			('
			 SELECT
			 	packagers.id,
			 	packagers.name,
			 	packagers.email,
			 	(
					SELECT
						COUNT(packages.id)
					FROM
						pkgdb.packages
					WHERE
						packages.packager = packagers.id
			 	) AS packages,
			 	(
					SELECT
						MAX(packages.builddate)
					FROM
						pkgdb.packages
					WHERE
						packages.packager = packagers.id
			 	) AS lastbuilddate
			 FROM
			 	pkgdb.packagers
			 ORDER BY
			 	'.$this->orderby.' '.($this->sort > 0 ? 'DESC' : 'ASC').'
			');

		$packagers = $stm->getRowSet();
		}
	catch (DBNoDataException $e)
		{
		$packagers = array();
		}

	$body = '
		<table id="packages">
			<tr>
				<th><a href="?page=Packagers;orderby=name;sort='.abs($this->sort-1).'">Name</a></th>
				<th>E-Mail</th>
				<th colspan="2"><a href="?page=Packagers;orderby=packages;sort='.abs($this->sort-1).'">Pakete</a></th>
				<th><a href="?page=Packagers;orderby=lastbuilddate;sort='.abs($this->sort-1).'">Letzte Aktualisierung</a></th>
			</tr>';

	$line = 0;

	foreach ($packagers as $packager)
		{
		$percent = round(($packager['packages'] / $packages) * 100);

		$body .= '<tr class="packageline'.$line.'">
				<td>'.$packager['name'].'</td>
				<td>'.(empty($packager['email']) ? '' : '<a href="mailto:'.$packager['email'].'">'.$packager['email'].'</a>').'</td>
				<td style="text-align:right;"><a href="?page=Packages;packager='.$packager['id'].'">'.$packager['packages'].'</a></td>
				<td style="width:100px;"><div style="background-color:#1793d1;width:'.$percent.'px;">&nbsp;</div></td>
				<td>'.formatDate($packager['lastbuilddate']).'</td>
			</tr>';

		$line = abs($line-1);
		}

	$body .= '</table>';

	$this->setValue('body', $body);
	}

}

?>