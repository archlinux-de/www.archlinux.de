<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

class MirrorStatus extends Page implements IDBCachable {

private $orderby 	= 'country';
private $sort 		= 0;
private static $range	= 1209600; // two weeks
private static $orders	= array('host', 'country', 'lastsync', 'syncdelay', 'avgtime');
private static $sorts	= array('ASC', 'DESC');


public function prepare()
	{
	$this->setValue('title', $this->L10n->getText('Mirror status'));

	try
		{
		if (in_array($this->Input->Get->getString('orderby'), self::$orders))
			{
			$this->orderby = $this->Input->Get->getString('orderby');
			}
		}
	catch (RequestException $e)
		{
		}

	$this->sort = $this->Input->Get->getInt('sort', 0) > 0 ? 1 : 0;

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
		$problems = self::get('DB')->getRowSet
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
			HAVING
				errorcount > 6
			ORDER BY
				lasttime DESC,
				host
			');
		}
	catch (DBNoDataException $e)
		{
		$problems = array();
		}

	$list = '<table class="pretty-table">
		<tr>
			<th>'.self::get('L10n')->getText('Host').'</th>
			<th>'.self::get('L10n')->getText('Message').'</th>
			<th>'.self::get('L10n')->getText('First occurrence').'</th>
			<th>'.self::get('L10n')->getText('Last occurrence').'</th>
			<th>'.self::get('L10n')->getText('Number').'</th>
		</tr>';

	foreach ($problems as $problem)
		{
		$list .=
		'<tr>
			<td><a href="'.$problem['host'].'" rel="nofollow">'.$problem['host'].'</a></td>
			<td>'.$problem['error'].'</td>
			<td>'.self::get('L10n')->getDateTime($problem['firsttime']).'</td>
			<td>'.self::get('L10n')->getDateTime($problem['lasttime']).'</td>
			<td>'.$problem['errorcount'].'</td>
		</tr>';
		}

	return $list.'</table>';
	}

public static function updateDBCache()
	{
	$range = time() - self::$range;

	try
		{
		$int = self::get('DB')->getRow
			('
			SELECT
				MAX(totaltime) AS maxtimes,
				AVG(totaltime) AS avgtimes,
				MAX(time-lastsync) AS maxsyncdelay,
				AVG(time-lastsync) AS avgsyncdelay
			FROM
				mirror_log
			WHERE
				time >= '.$range.'
			');
		$int['count'] = self::get('DB')->getColumn
			('
			SELECT
				COUNT(host) AS count
			FROM
				mirrors
			');

		$problems = self::getCurrentProblems($range);

		foreach (self::$orders as $order)
			{
			foreach (self::$sorts as $sort)
				{
				$stm = self::get('DB')->prepare
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
					HAVING
						lastsync > 0
					ORDER BY
						'.$order.' '.$sort.',
						host
					');

				$mirrors = $stm->getRowSet();
				self::createBody($int, $mirrors, $order, $sort, $problems);
				}
			}
		}
	catch (DBNoDataException $e)
		{
		}
	}

private static function createBody($int, $mirrors, $order, $sort, $problems)
	{
	$sortint = ($sort == 'DESC' ? 1 : 0);

	$body = '<div class="box">
		<h2>'.self::get('L10n')->getText('Mirror status').'</h2>
		</div>
		<table class="pretty-table">
			<tr>
				<th><a href="?page=MirrorStatus;orderby=host;sort='.abs($sortint-1).'">'.self::get('L10n')->getText('Host').'</a></th>
				<th><a href="?page=MirrorStatus;orderby=country;sort='.abs($sortint-1).'">'.self::get('L10n')->getText('Country').'</a></th>
				<th style="width:140px;"><a href="?page=MirrorStatus;orderby=avgtime;sort='.abs($sortint-1).'">&empty;&nbsp;'.self::get('L10n')->getText('Response time').'</a></th>
				<th style="width:140px;"><a href="?page=MirrorStatus;orderby=syncdelay;sort='.abs($sortint-1).'">&empty;&nbsp;'.self::get('L10n')->getText('Delay').'</a></th>
				<th><a href="?page=MirrorStatus;orderby=lastsync;sort='.abs($sortint-1).'">'.self::get('L10n')->getText('Last update').'</a></th>
			</tr>';

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

		$body .= '<tr>
				<td><a href="'.$mirror['host'].'" rel="nofollow">'.$mirror['host'].'</a></td>
				<td>'.$mirror['country'].'</td>
				<td title="&empty;&nbsp;'.self::get('L10n')->getEpoch($mirror['avgtime']).'"><div style="background-color:'.$perfcolor.';width:'.$performance.'px;">&nbsp;</div></td>
				<td title="&empty;&nbsp;'.self::get('L10n')->getEpoch($mirror['syncdelay']).'"><div style="background-color:'.$synccolor.';width:'.$syncdelay.'px;">&nbsp;</div></td>
				<td'.$outofsync.'>'.self::get('L10n')->getDateTime($mirror['lastsync']).'</td>
			</tr>';
		}

	$body .= '</table>
		<div class="box" style="margin-top:2em;"><h3>'.self::get('L10n')->getText('Current problems').'</h3></div>
		'.$problems;

	self::get('PersistentCache')->addObject('MirrorStatus:'.$order.':'.$sort.':'.self::get('L10n')->getLocale(), $body);
	}

}

?>