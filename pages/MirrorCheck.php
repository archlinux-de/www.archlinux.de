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

class MirrorCheck extends Page{

private $orderby 	= 'country';
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
			<li class="selected">Server</li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li><a href="?page=Packages">Suche</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', 'Server');

	try
		{
		if (in_array($this->Io->getString('orderby'), array('host', 'country', 'lastsync')))
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

	try
		{
		$stm = $this->DB->prepare
			('
			 SELECT
				mirrors.host,
				mirrors.ftp,
				mirrors.http,
				mirrors.rsync,
				mirrors.path_ftp,
				mirrors.path_http,
				mirrors.path_rsync,
				mirrors.country,
				(SELECT MAX(lastsync) FROM pkgdb.mirror_log WHERE mirror_log.host = mirrors.host) AS lastsync
			 FROM
			 	pkgdb.mirrors
			 ORDER BY
			 	'.$this->orderby.' '.($this->sort > 0 ? 'DESC' : 'ASC').'
			');

		$mirrors = $stm->getRowSet();
		}
	catch (DBNoDataException $e)
		{
		$mirrors = array();
		}

	$body = '
		<table id="packages">
			<tr>
				<th><a href="?page=MirrorCheck;orderby=host;sort='.abs($this->sort-1).'">Host</a></th>
				<th><a href="?page=MirrorCheck;orderby=country;sort='.abs($this->sort-1).'">Land</a></th>
				<th>FTP</th>
				<th>HTTP</th>
				<th>RSYNC</th>
				<th><a href="?page=MirrorCheck;orderby=lastsync;sort='.abs($this->sort-1).'">Letzte Aktualisierung</a></th>
			</tr>';

	$line = 0;

	foreach ($mirrors as $mirror)
		{
		$body .= '<tr class="packageline'.$line.'">
				<td>'.$mirror['host'].'</td>
				<td>'.$mirror['country'].'</td>
				<td>'.($mirror['ftp'] == 0 ? '' : '<a href="ftp://'.$mirror['host'].'/'.$mirror['path_ftp'].'">/'.$mirror['path_ftp'].'</a>').'</td>
				<td>'.($mirror['http'] == 0 ? '' : '<a href="http://'.$mirror['host'].'/'.$mirror['path_http'].'">/'.$mirror['path_http'].'</a>').'</td>
				<td>'.($mirror['rsync'] == 0 ? '' : '<a href="rsync://'.$mirror['host'].'/'.$mirror['path_rsync'].'">/'.$mirror['path_rsync'].'</a>').'</td>
				<td>'.formatDate($mirror['lastsync']).'</td>
			</tr>';

		$line = abs($line-1);
		}

	$body .= '</table>';

	$this->setValue('body', $body);
	}

}

?>