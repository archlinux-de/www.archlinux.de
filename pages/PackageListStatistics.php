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

class PackageListStatistics extends Page {


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
	$this->setValue('title', 'PackageListStatistics');

	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				name,
				arch,
				count
			FROM
				package_statistics
			ORDER BY
				count DESC,
				name ASC
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$packageList = '';
	$line = 0;

	foreach ($packages as $package)
		{
		$packageList .= '<tr class="packageline'.$line.'"><td>'.$package['name'].'</td><td>'.$package['arch'].'</td><td>'.$package['count'].'</td></tr>';
		$line = abs($line-1);
		}

	$body = '<div id="box">
	<h1 id="packagename">'.$this->getValue('title').'</h1>
	<table id="packages">
		<tr>
			<th>Name</th>
			<th>Architecture</th>
			<th>Count</th>
		</tr>
			'.$packageList.'
	</table>
	</div>
	';

	$this->setValue('body', $body);
	}

}

?>