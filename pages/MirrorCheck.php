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
		if (in_array($this->Io->getString('orderby'), array('host', 'country', 'lastsync', 'avgtime')))
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

	$range = time() - 60*60*24*14;

	try
		{
		$int = $this->DB->getRow
			('
			SELECT
				MIN(totaltime) AS mintimes,
				MAX(totaltime) AS maxtimes,
				AVG(totaltime) AS avgtimes
			FROM
				pkgdb.mirrors,
				pkgdb.mirror_log
			WHERE
				mirrors.official = 1
				AND mirrors.deleted = 0
				AND mirror_log.host = mirrors.host
				AND mirror_log.time >= '.$range.'
			');
		$int['count'] = $this->DB->getcolumn
			('
			SELECT
				COUNT(host) AS count
			FROM
				pkgdb.mirrors
			WHERE
				official = 1
				AND deleted = 0
			');

		$de = $this->DB->getRow
			('
			SELECT
				MIN(totaltime) AS mintimes,
				MAX(totaltime) AS maxtimes,
				AVG(totaltime) AS avgtimes
			FROM
				pkgdb.mirrors,
				pkgdb.mirror_log
			WHERE
				mirrors.official = 1
				AND mirrors.deleted = 0
				AND mirrors.country LIKE \'Germany\'
				AND mirror_log.host = mirrors.host
				AND mirror_log.time >= '.$range.'
			');
		$de['count'] = $this->DB->getcolumn
			('
			SELECT
				COUNT(host) AS count
			FROM
				pkgdb.mirrors
			WHERE
				official = 1
				AND deleted = 0
				AND country LIKE \'Germany\'
			');

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
				mirrors.ticketnr,
				MAX(lastsync) AS lastsync,
				AVG(totaltime) AS avgtime
			 FROM
			 	pkgdb.mirrors,
				pkgdb.mirror_log
			WHERE
				mirrors.official = 1
				AND mirrors.deleted = 0
				AND mirror_log.host = mirrors.host
				AND mirror_log.time >= '.$range.'
			GROUP BY
				mirrors.host
			ORDER BY
			 	'.$this->orderby.' '.($this->sort > 0 ? 'DESC' : 'ASC').'
			');

		$mirrors = $stm->getRowSet();
		}
	catch (DBNoDataException $e)
		{
		$mirrors = array();
		}

	$body = '<div class="greybox" id="searchbox">
			<h4 style="text-align: right">Server-Übersicht</h4>
			<table>
				<tr>
					<th>&nbsp;</th>
					<th>Deutschland</th>
					<th>International</th>
				</tr>

							<tr>
								<th>Anzahl der Server</th>
								<td>'.$de['count'].'</td>
								<td>'.$int['count'].'</td>
							</tr>
							<tr>
								<th>Minimale Antwortzeit</th>
								<td>'.round($de['mintimes'], 2).'s</td>
								<td>'.round($int['mintimes'], 2).'s</td>
							</tr>
							<tr>
								<th>Maximale Antwortzeit</th>
								<td>'.round($de['maxtimes'], 2).'s</td>
								<td>'.round($int['maxtimes'], 2).'s</td>
							</tr>
							<tr>
								<th>Durchschnittliche Antwortzeit</th>
								<td>'.round($de['avgtimes'], 2).'s</td>
								<td>'.round($int['avgtimes'], 2).'s</td>
							</tr>

			</table>

				
			<p style="font-size:12px;"></p>
		</div>
		<table id="packages">
			<tr>
				<th><a href="?page=MirrorCheck;orderby=host;sort='.abs($this->sort-1).'">Host</a></th>
				<th><a href="?page=MirrorCheck;orderby=country;sort='.abs($this->sort-1).'">Land</a></th>
				<th>FTP</th>
				<th>HTTP</th>
				<th>RSYNC</th>
				<th><a href="?page=MirrorCheck;orderby=avgtime;sort='.abs($this->sort-1).'">&empty; Antwortzeit</a></th>
				<th><a href="?page=MirrorCheck;orderby=lastsync;sort='.abs($this->sort-1).'">Letzte Aktualisierung</a></th>
			</tr>';

	$line = 0;

	foreach ($mirrors as $mirror)
		{
		if ($int['maxtimes']-$int['mintimes'] > 0)
			{
			$performance = nat( (($mirror['avgtime']-$int['mintimes']) / ($int['maxtimes']-$int['mintimes'])) * 200);
			}
		else
			{
			$performance = 200;
			}

		$color = $mirror['avgtime'] > $int['avgtimes'] ? 'darkred' : 'darkgreen';

		if (time() - $mirror['lastsync'] > 60*60*24*3)
			{
			$outofsync = ' style="color:darkred"';
			}
		elseif (time() - $mirror['lastsync'] < 60*60*3)
			{
			$outofsync = ' style="color:darkgreen"';
			}
		else
			{
			$outofsync = '';
			}
		
		$body .= '<tr class="packageline'.$line.'">
				<td>'.$mirror['host'].'</td>
				<td>'.$mirror['country'].'</td>
				<td>'.($mirror['ftp'] == 0 ? '' : '<a rel="nofollow" href="ftp://'.$mirror['host'].'/'.$mirror['path_ftp'].'">/'.$mirror['path_ftp'].'</a>').'</td>
				<td>'.($mirror['http'] == 0 ? '' : '<a rel="nofollow" href="http://'.$mirror['host'].'/'.$mirror['path_http'].'">/'.$mirror['path_http'].'</a>').'</td>
				<td>'.($mirror['rsync'] == 0 ? '' : '<a rel="nofollow" href="rsync://'.$mirror['host'].'/'.$mirror['path_rsync'].'">/'.$mirror['path_rsync'].'</a>').'</td>
				<td style="width:200px;" title="&empty; '.round($mirror['avgtime'], 2).'s"><div style="background-color:'.$color.';width:'.$performance.'px;">&nbsp;</div></td>
				<td'.$outofsync.'>'.formatDate($mirror['lastsync']).''.(!empty($mirror['ticketnr']) ? '<a rel="nofollow" href="http://bugs.archlinux.org/'.$mirror['ticketnr'].'">*</a>' : '').'</td>
			</tr>';

		$line = abs($line-1);
		}

	$body .= '</table>';

	$this->setValue('body', $body);
	}

}

?>