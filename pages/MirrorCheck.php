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

class MirrorCheck extends Page implements IDBCachable {

private $orderby 	= 'country';
private $sort 		= 0;
private static $range	= 1209600; // two weeks
private static $orders	= array('host', 'country', 'lastsync', 'syncdelay', 'avgtime');
private static $sorts	= array('ASC', 'DESC');

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
		if (in_array($this->Input->Request->getString('orderby'), self::$orders))
			{
			$this->orderby = $this->Input->Request->getString('orderby');
			}
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->sort = $this->Input->Request->getInt('sort') > 0 ? 1 : 0;
		}
	catch (RequestException $e)
		{
		}

	if (!($body = $this->PersistentCache->getObject('MirrorCheck:'.$this->orderby.':'.($this->sort > 0 ? 'DESC' : 'ASC'))))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure('Keine Daten vorhanden!');
		}

	$this->setValue('body', $body);
	}

private static function formatTime($seconds)
	{
	$minutes 	= 60;
	$hours 		= 60 * $minutes;
	$days 		= 24 * $hours;
	$weeks 		= 7 * $days;
	$months 	= 4 * $weeks;
	$years 		= 12 * $months;

	if ($seconds >= $years)
		{
		$result = round($seconds / $years, 2);
		$postfix = '&nbsp;Jahre';
		}
	elseif ($seconds >= $months)
		{
		$result =  round($seconds / $months, 2);
		$postfix = '&nbsp;Monate';
		}
	elseif ($seconds >= $weeks)
		{
		$result =  round($seconds / $weeks, 2);
		$postfix = '&nbsp;Wochen';
		}
	elseif ($seconds >= $days)
		{
		$result =  round($seconds / $days, 2);
		$postfix = '&nbsp;Tage';
		}
	elseif ($seconds >= $hours)
		{
		$result =  round($seconds / $hours, 2);
		$postfix = '&nbsp;Stunden';
		}
	elseif ($seconds >= $minutes)
		{
		$result =  round($seconds / $minutes, 2);
		$postfix = '&nbsp;Minuten';
		}
	else
		{
		$result =  round($seconds, 2);
		$postfix = '&nbsp;Sekunden';
		}

	return $result.$postfix;
	}

private static function getCurrentProblems($db, $range)
	{
	try
		{
		$problems = $db->getRowSet
			('
			SELECT
				host,
				error,
				min(time) as firsttime,
				max(time) as lasttime,
				count(host) as errorcount
			FROM
				mirror_log
			WHERE
				error IS NOT NULL
				AND mirror_log.time >= '.$range.'
			GROUP BY
				host, error
			ORDER BY
				lasttime DESC
			');
		}
	catch (DBNoDataException $e)
		{
		$problems = array();
		}

	$list = '<table id="packages">
		<tr>
			<th>Host</th>
			<th>Meldung</th>
			<th>erstes Auftreten</th>
			<th>Letztes Auftreten</th>
			<th>Anzahl</th>
		</tr>';
	$line = 0;

	foreach ($problems as $problem)
		{
		$list .=
		'<tr class="packageline'.$line.'">
			<td>'.$problem['host'].'</td>
			<td>'.$problem['error'].'</td>
			<td>'.formatDate($problem['firsttime']).'</td>
			<td>'.formatDate($problem['lasttime']).'</td>
			<td>'.$problem['errorcount'].'</td>
		</tr>';

		$line = abs($line-1);
		}

	return $list.'</table>';
	}

public static function updateDBCache(DB $db, PersistentCache $cache)
	{
	$range = time() - self::$range;

	try
		{
		$int = $db->getRow
			('
			SELECT
				MIN(totaltime) AS mintimes,
				MAX(totaltime) AS maxtimes,
				AVG(totaltime) AS avgtimes,
				MAX(time-lastsync) AS maxsyncdelay,
				MIN(time-lastsync) AS minsyncdelay,
				AVG(time-lastsync) AS avgsyncdelay
			FROM
				mirrors,
				mirror_log
			WHERE
				mirrors.official = 1
				AND mirrors.deleted = 0
				AND mirror_log.host = mirrors.host
				AND mirror_log.time >= '.$range.'
			');
		$int['count'] = $db->getColumn
			('
			SELECT
				COUNT(host) AS count
			FROM
				mirrors
			WHERE
				official = 1
				AND deleted = 0
			');

		$de = $db->getRow
			('
			SELECT
				MIN(totaltime) AS mintimes,
				MAX(totaltime) AS maxtimes,
				AVG(totaltime) AS avgtimes,
				MAX(time-lastsync) AS maxsyncdelay,
				MIN(time-lastsync) AS minsyncdelay,
				AVG(time-lastsync) AS avgsyncdelay
			FROM
				mirrors,
				mirror_log
			WHERE
				mirrors.official = 1
				AND mirrors.deleted = 0
				AND mirrors.country LIKE \'Germany\'
				AND mirror_log.host = mirrors.host
				AND mirror_log.time >= '.$range.'
			');
		$de['count'] = $db->getColumn
			('
			SELECT
				COUNT(host) AS count
			FROM
				mirrors
			WHERE
				official = 1
				AND deleted = 0
				AND country LIKE \'Germany\'
			');

		$problems = self::getCurrentProblems($db, $range);

		foreach (self::$orders as $order)
			{
			foreach (self::$sorts as $sort)
				{
				$stm = $db->prepare
					('
					SELECT
						mirrors.host,
						mirrors.ftp,
						mirrors.http,
						mirrors.rsync,
						mirrors.country,
						mirrors.ticketnr,
						MAX(lastsync) AS lastsync,
						AVG(totaltime) AS avgtime,
						AVG(time-lastsync) AS syncdelay
					FROM
						mirrors,
						mirror_log
					WHERE
						mirrors.official = 1
						AND mirrors.deleted = 0
						AND mirror_log.host = mirrors.host
						AND mirror_log.time >= '.$range.'
					GROUP BY
						mirrors.host
					ORDER BY
						'.$order.' '.$sort.'
					');

				$mirrors = $stm->getRowSet();
				self::createBody($de, $int, $mirrors, $order, $sort, $problems, $cache);
				}
			}
		}
	catch (DBNoDataException $e)
		{
		}
	}

private static function createBody($de, $int, $mirrors, $order, $sort, $problems, $cache)
	{
	$sortint = ($sort == 'DESC' ? 1 : 0);

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
				<td>'.self::formatTime($de['mintimes']).'</td>
				<td>'.self::formatTime($int['mintimes']).'</td>
			</tr>
			<tr>
				<th>Maximale Antwortzeit</th>
				<td>'.self::formatTime($de['maxtimes']).'</td>
				<td>'.self::formatTime($int['maxtimes']).'</td>
			</tr>
			<tr>
				<th>Durchschnittliche Antwortzeit</th>
				<td>'.self::formatTime($de['avgtimes']).'</td>
				<td>'.self::formatTime($int['avgtimes']).'</td>
			</tr>
			<tr>
				<th>Minimale Verzögerung</th>
				<td>'.self::formatTime($de['minsyncdelay']).'</td>
				<td>'.self::formatTime($int['minsyncdelay']).'</td>
			</tr>
			<tr>
				<th>Maximale Verzögerung</th>
				<td>'.self::formatTime($de['maxsyncdelay']).'</td>
				<td>'.self::formatTime($int['maxsyncdelay']).'</td>
			</tr>
			<tr>
				<th>Durchschnittliche Verzögerung</th>
				<td>'.self::formatTime($de['avgsyncdelay']).'</td>
				<td>'.self::formatTime($int['avgsyncdelay']).'</td>
			</tr>
		</table>
		</div>
		<table id="packages">
			<tr>
				<th><a href="?page=MirrorCheck;orderby=host;sort='.abs($sortint-1).'">Host</a></th>
				<th><a href="?page=MirrorCheck;orderby=country;sort='.abs($sortint-1).'">Land</a></th>
				<th style="text-align:center;">FTP</th>
				<th style="text-align:center;">HTTP</th>
				<th style="text-align:center;">RSYNC</th>
				<th><a href="?page=MirrorCheck;orderby=avgtime;sort='.abs($sortint-1).'">&empty;&nbsp;Antwortzeit</a></th>
				<th><a href="?page=MirrorCheck;orderby=syncdelay;sort='.abs($sortint-1).'">&empty;&nbsp;Verzögerung</a></th>
				<th><a href="?page=MirrorCheck;orderby=lastsync;sort='.abs($sortint-1).'">Letzte Aktualisierung</a></th>
			</tr>';

	$line = 0;

	foreach ($mirrors as $mirror)
		{
		$performance = $int['maxtimes'] > 0 ? round(($mirror['avgtime'] / $int['maxtimes']) * 100) : 100;
		$perfcolor = $mirror['avgtime'] > $int['avgtimes'] ? 'darkred' : 'darkgreen';

		$syncdelay = $int['maxsyncdelay'] > 0 ? round(($mirror['syncdelay'] / $int['maxsyncdelay']) * 100) : 100;
		$synccolor = $mirror['syncdelay'] > $int['avgsyncdelay'] ? 'darkred' : 'darkgreen';

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
				<td style="text-align:center;">'.(strlen($mirror['ftp']) == 0 ? '' : '<a rel="nofollow" href="ftp://'.$mirror['ftp'].'">&radic;</a>').'</td>
				<td style="text-align:center;">'.(strlen($mirror['http']) == 0 ? '' : '<a rel="nofollow" href="http://'.$mirror['http'].'">&radic;</a>').'</td>
				<td style="text-align:center;">'.(strlen($mirror['rsync']) == 0 ? '' : '<a rel="nofollow" href="rsync://'.$mirror['rsync'].'">&radic;</a>').'</td>
				<td style="width:100px;" title="&empty;&nbsp;'.self::formatTime($mirror['avgtime']).'"><div style="background-color:'.$perfcolor.';width:'.$performance.'px;">&nbsp;</div></td>
				<td style="width:100px;" title="&empty;&nbsp;'.self::formatTime($mirror['syncdelay']).'"><div style="background-color:'.$synccolor.';width:'.$syncdelay.'px;">&nbsp;</div></td>
				<td'.$outofsync.'>'.formatDate($mirror['lastsync']).''.(!empty($mirror['ticketnr']) ? '<a rel="nofollow" href="http://bugs.archlinux.org/'.$mirror['ticketnr'].'">*</a>' : '').'</td>
			</tr>';

		$line = abs($line-1);
		}

	$body .= '</table>
		<h4 style="text-align: right;border-bottom: 1px dotted #0771a6;margin-bottom: 4px;padding-bottom: 2px;font-size: 10px;">Aktuelle Probleme</h4>
		'.$problems;

	$cache->addObject('MirrorCheck:'.$order.':'.$sort, $body);
	}

}

?>