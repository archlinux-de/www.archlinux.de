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

class MirrorStatus extends Page implements IDBCachable {

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
	$this->setValue('title', $this->L10n->getText('Mirror status'));

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

	if (!($body = $this->PersistentCache->getObject('MirrorStatus:'.$this->orderby.':'.($this->sort > 0 ? 'DESC' : 'ASC').':'.$this->L10n->getLocale())))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure($this->L10n->getText('No data found!'));
		}

	$this->setValue('body', $body);
	}

private static function getCurrentProblems($range)
	{
	try
		{
		$problems = self::__get('DB')->getRowSet
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
			<th>'.self::__get('L10n')->getText('Host').'</th>
			<th>'.self::__get('L10n')->getText('Message').'</th>
			<th>'.self::__get('L10n')->getText('First occurrence').'</th>
			<th>'.self::__get('L10n')->getText('Last occurrence').'</th>
			<th>'.self::__get('L10n')->getText('Number').'</th>
		</tr>';
	$line = 0;

	foreach ($problems as $problem)
		{
		$list .=
		'<tr class="packageline'.$line.'">
			<td><a href="'.$problem['host'].'" rel="nofollow">'.$problem['host'].'</a></td>
			<td>'.$problem['error'].'</td>
			<td>'.self::__get('L10n')->getDateTime($problem['firsttime']).'</td>
			<td>'.self::__get('L10n')->getDateTime($problem['lasttime']).'</td>
			<td>'.$problem['errorcount'].'</td>
		</tr>';

		$line = abs($line-1);
		}

	return $list.'</table>';
	}

public static function updateDBCache()
	{
	$range = time() - self::$range;

	try
		{
		$int = self::__get('DB')->getRow
			('
			SELECT
				MIN(totaltime) AS mintimes,
				MAX(totaltime) AS maxtimes,
				AVG(totaltime) AS avgtimes,
				MAX(time-lastsync) AS maxsyncdelay,
				MIN(time-lastsync) AS minsyncdelay,
				AVG(time-lastsync) AS avgsyncdelay
			FROM
				mirror_log
			WHERE
				time >= '.$range.'
			');
		$int['count'] = self::__get('DB')->getColumn
			('
			SELECT
				COUNT(host) AS count
			FROM
				mirrors
			');

		$de = self::__get('DB')->getRow
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
				mirrors.country LIKE \'Germany\'
				AND mirror_log.host = mirrors.host
				AND mirror_log.time >= '.$range.'
			');
		$de['count'] = self::__get('DB')->getColumn
			('
			SELECT
				COUNT(host) AS count
			FROM
				mirrors
			WHERE
				country LIKE \'Germany\'
			');

		$problems = self::getCurrentProblems($range);

		foreach (self::$orders as $order)
			{
			foreach (self::$sorts as $sort)
				{
				$stm = self::__get('DB')->prepare
					('
					SELECT
						mirrors.host,
						mirrors.country,
						MAX(lastsync) AS lastsync,
						AVG(totaltime) AS avgtime,
						AVG(time-lastsync) AS syncdelay
					FROM
						mirrors,
						mirror_log
					WHERE
						mirror_log.host = mirrors.host
						AND mirror_log.time >= '.$range.'
					GROUP BY
						mirrors.host
					ORDER BY
						'.$order.' '.$sort.'
					');

				$mirrors = $stm->getRowSet();
				self::createBody($de, $int, $mirrors, $order, $sort, $problems);
				}
			}
		}
	catch (DBNoDataException $e)
		{
		}
	}

private static function createBody($de, $int, $mirrors, $order, $sort, $problems)
	{
	$sortint = ($sort == 'DESC' ? 1 : 0);

	$body = '<div class="greybox" id="searchbox">
		<h4 style="text-align: right">'.self::__get('L10n')->getText('Mirror status').'</h4>
		<table>
			<tr>
				<th>&nbsp;</th>
				<th>'.self::__get('L10n')->getText('Germany').'</th>
				<th>'.self::__get('L10n')->getText('International').'</th>
			</tr>
			<tr>
				<th>'.self::__get('L10n')->getText('Number of mirrors').'</th>
				<td>'.$de['count'].'</td>
				<td>'.$int['count'].'</td>
			</tr>
			<tr>
				<th>'.self::__get('L10n')->getText('Lowest response time').'</th>
				<td>'.self::__get('L10n')->getEpoch($de['mintimes']).'</td>
				<td>'.self::__get('L10n')->getEpoch($int['mintimes']).'</td>
			</tr>
			<tr>
				<th>'.self::__get('L10n')->getText('Highest response time').'</th>
				<td>'.self::__get('L10n')->getEpoch($de['maxtimes']).'</td>
				<td>'.self::__get('L10n')->getEpoch($int['maxtimes']).'</td>
			</tr>
			<tr>
				<th>'.self::__get('L10n')->getText('Average response time').'</th>
				<td>'.self::__get('L10n')->getEpoch($de['avgtimes']).'</td>
				<td>'.self::__get('L10n')->getEpoch($int['avgtimes']).'</td>
			</tr>
			<tr>
				<th>'.self::__get('L10n')->getText('Lowest delay').'</th>
				<td>'.self::__get('L10n')->getEpoch($de['minsyncdelay']).'</td>
				<td>'.self::__get('L10n')->getEpoch($int['minsyncdelay']).'</td>
			</tr>
			<tr>
				<th>'.self::__get('L10n')->getText('Highest delay').'</th>
				<td>'.self::__get('L10n')->getEpoch($de['maxsyncdelay']).'</td>
				<td>'.self::__get('L10n')->getEpoch($int['maxsyncdelay']).'</td>
			</tr>
			<tr>
				<th>'.self::__get('L10n')->getText('Average delay').'</th>
				<td>'.self::__get('L10n')->getEpoch($de['avgsyncdelay']).'</td>
				<td>'.self::__get('L10n')->getEpoch($int['avgsyncdelay']).'</td>
			</tr>
		</table>
		</div>
		<table id="packages">
			<tr>
				<th><a href="?page=MirrorStatus;orderby=host;sort='.abs($sortint-1).'">'.self::__get('L10n')->getText('Host').'</a></th>
				<th><a href="?page=MirrorStatus;orderby=country;sort='.abs($sortint-1).'">'.self::__get('L10n')->getText('Country').'</a></th>
				<th style="width:140px;"><a href="?page=MirrorStatus;orderby=avgtime;sort='.abs($sortint-1).'">&empty;&nbsp;'.self::__get('L10n')->getText('Response time').'</a></th>
				<th style="width:140px;"><a href="?page=MirrorStatus;orderby=syncdelay;sort='.abs($sortint-1).'">&empty;&nbsp;'.self::__get('L10n')->getText('Delay').'</a></th>
				<th><a href="?page=MirrorStatus;orderby=lastsync;sort='.abs($sortint-1).'">'.self::__get('L10n')->getText('Last update').'</a></th>
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
				<td><a href="'.$mirror['host'].'" rel="nofollow">'.$mirror['host'].'</a></td>
				<td>'.$mirror['country'].'</td>
				<td title="&empty;&nbsp;'.self::__get('L10n')->getEpoch($mirror['avgtime']).'"><div style="background-color:'.$perfcolor.';width:'.$performance.'px;">&nbsp;</div></td>
				<td title="&empty;&nbsp;'.self::__get('L10n')->getEpoch($mirror['syncdelay']).'"><div style="background-color:'.$synccolor.';width:'.$syncdelay.'px;">&nbsp;</div></td>
				<td'.$outofsync.'>'.self::__get('L10n')->getDateTime($mirror['lastsync']).'</td>
			</tr>';

		$line = abs($line-1);
		}

	$body .= '</table>
		<h4 style="text-align: right;border-bottom: 1px dotted #0771a6;margin-bottom: 4px;padding-bottom: 2px;font-size: 10px;">'.self::__get('L10n')->getText('Current problems').'</h4>
		'.$problems;

	self::__get('PersistentCache')->addObject('MirrorStatus:'.$order.':'.$sort.':'.self::__get('L10n')->getLocale(), $body);
	}

}

?>