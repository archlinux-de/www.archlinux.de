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

require_once ('pages/abstract/IDBCachable.php');

class UserStatistics extends Page implements IDBCachable {

private static $barColors = array();
private static $barColorArray = array('8B0000','FF8800','006400');


public function prepare()
	{
	$this->setValue('title', $this->L10n->getText('User statistics'));

	if (!($body = $this->PersistentCache->getObject('UserStatistics:'.$this->L10n->getLocale())))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure($this->L10n->getText('No data found!'));
		}

	$this->setValue('body', $body);
	}

public static function updateDBCache()
	{
	self::$barColors = self::MultiColorFade(self::$barColorArray);

	try
		{
		$log = self::getCommonPackageUsageStatistics();

		$body = '<div class="box">
			<table id="packagedetails">
				<tr>
					<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">'.self::get('L10n')->getText('User statistics').'</h1></th>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Common statistics').'</th>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Submissions').'</th>
					<td>'.self::get('L10n')->getNumber($log['submissions']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Different IPs').'</th>
					<td>'.self::get('L10n')->getNumber($log['differentips']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('First entry').'</th>
					<td>'.self::get('L10n')->getGMDateTime($log['minvisited']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Last entry').'</th>
					<td>'.self::get('L10n')->getGMDateTime($log['maxvisited']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Last update').'</th>
					<td>'.self::get('L10n')->getGMDateTime(self::get('Input')->getTime()).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Countries').'</th>
				</tr>
					'.self::getCountryStatistics().'
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Mirrors').'</th>
				</tr>
					'.self::getMirrorStatistics().'
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Submissions per architectures').'</th>
				</tr>
					'.self::getSubmissionsPerArchitecture().'
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Common statistics').'</th>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Sum of submitted packages').'</th>
					<td>'.self::get('L10n')->getNumber($log['sumcount']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Number of different packages').'</th>
					<td>'.self::get('L10n')->getNumber($log['diffcount']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Lowest number of installed packages').'</th>
					<td>'.self::get('L10n')->getNumber($log['mincount']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Highest number of installed packages').'</th>
					<td>'.self::get('L10n')->getNumber($log['maxcount']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Average number of installed packages').'</th>
					<td>'.self::get('L10n')->getNumber($log['avgcount']).'</td>
				</tr>
			</table>
			</div>
			';

		self::get('PersistentCache')->addObject('UserStatistics:'.self::get('L10n')->getLocale(), $body);
		}
	catch (DBNoDataException $e)
		{
		}
	}

private static function getCommonPackageUsageStatistics()
	{
	return self::get('DB')->getRow
		('
		SELECT
			(SELECT COUNT(*) FROM pkgstats_users) AS submissions,
			(SELECT COUNT(*) FROM (SELECT * FROM pkgstats_users GROUP BY ip) AS temp) AS differentips,
			(SELECT MIN(time) FROM pkgstats_users) AS minvisited,
			(SELECT MAX(time) FROM pkgstats_users) AS maxvisited,
			(SELECT SUM(count) FROM pkgstats_packages) AS sumcount,
			(SELECT COUNT(*) FROM (SELECT DISTINCT pkgname FROM pkgstats_packages) AS diffpkgs) AS diffcount,
			(SELECT MIN(packages) FROM pkgstats_users) AS mincount,
			(SELECT MAX(packages) FROM pkgstats_users) AS maxcount,
			(SELECT AVG(packages) FROM pkgstats_users) AS avgcount
		');
	}

private static function getCountryStatistics()
	{
	$total = self::get('DB')->getColumn
		('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		');

	$countries = self::get('DB')->getRowSet
		('
		SELECT
			COALESCE(country, \'unknown\') AS name,
			COUNT(*) AS count
		FROM
			pkgstats_users
		GROUP BY
			country
		HAVING
			count >= '.(floor($total / 100)).'
		ORDER BY
			count DESC
		');

	$list = '';

	foreach ($countries as $country)
		{
		$list .= '<tr><th>'.$country['name'].'</th><td>'.self::getBar($country['count'], $total).'</td></tr>';
		}

	return $list;
	}

private static function getMirrorStatistics()
	{
	$total = self::get('DB')->getColumn
		('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		');

	$mirrors = self::get('DB')->getRowSet
		('
		SELECT
			COALESCE(mirror, \'unknown\') AS name,
			COUNT(*) AS count
		FROM
			pkgstats_users
		GROUP BY
			mirror
		HAVING
			count >= '.(floor($total / 100)).'
		');

	$hosts = array();
	foreach ($mirrors as $mirror)
		{
		$host = parse_url($mirror['name'], PHP_URL_HOST);
		if ($host === false || empty($host))
			{
			$host = 'unknown';
			}

		if (isset($hosts[$host]))
			{
			$hosts[$host] += $mirror['count'];
			}
		else
			{
			$hosts[$host] = $mirror['count'];
			}
		}
	arsort($hosts);

	$list = '';

	foreach ($hosts as $host => $count)
		{
		$list .= '<tr><th>'.$host.'</th><td>'.self::getBar($count, $total).'</td></tr>';
		}

	return $list;
	}

private static function getBar($value, $total)
	{
	if ($total <= 0)
		{
		return '';
		}

	$percent = ($value / $total) * 100;
	
	$color = self::$barColors[round($percent)];

	return '<table style="width:100%;">
			<tr>
				<td style="padding:0px;margin:0px;">
					<div style="background-color:#'.$color.';width:'.round($percent).'%;"
		title="'.self::get('L10n')->getNumber($value).' '.self::get('L10n')->getText('of').' '.self::get('L10n')->getNumber($total).'">
			&nbsp;
				</div>
				</td>
				<td style="padding:0px;margin:0px;width:80px;text-align:right;color:#'.$color.'">
					'.self::get('L10n')->getNumber($percent, 2).'&nbsp;%
				</td>
			</tr>
		</table>';
	}

// see http://at.php.net/manual/de/function.hexdec.php#66780
private static function MultiColorFade($hexarray)
	{
	$steps = 101;
	$total = count($hexarray);
	$gradient = array();
	$fixend = 2;
	$passages = $total - 1;
	$stepsforpassage = floor($steps / $passages);
	$stepsremain = $steps - ($stepsforpassage * $passages);

	for ($pointer = 0; $pointer < $total - 1 ; $pointer++)
		{

		$hexstart = $hexarray[$pointer];
		$hexend = $hexarray[$pointer + 1];

		if ($stepsremain > 0)
			{
			if ($stepsremain--)
				{
				$stepsforthis = $stepsforpassage + 1;
				}
			}
		else
			{
			$stepsforthis = $stepsforpassage;
			}

		if ($pointer > 0)
			{
			$fixend = 1;
			}

		$start['r'] = hexdec(substr($hexstart, 0, 2));
		$start['g'] = hexdec(substr($hexstart, 2, 2));
		$start['b'] = hexdec(substr($hexstart, 4, 2));

		$end['r'] = hexdec(substr($hexend, 0, 2));
		$end['g'] = hexdec(substr($hexend, 2, 2));
		$end['b'] = hexdec(substr($hexend, 4, 2));

		$step['r'] = ($start['r'] - $end['r']) / ($stepsforthis);
		$step['g'] = ($start['g'] - $end['g']) / ($stepsforthis);
		$step['b'] = ($start['b'] - $end['b']) / ($stepsforthis);

		for($i = 0; $i <= $stepsforthis - $fixend; $i++)
			{
			$rgb['r'] = floor($start['r'] - ($step['r'] * $i));
			$rgb['g'] = floor($start['g'] - ($step['g'] * $i));
			$rgb['b'] = floor($start['b'] - ($step['b'] * $i));

			$hex['r'] = sprintf('%02x', ($rgb['r']));
			$hex['g'] = sprintf('%02x', ($rgb['g']));
			$hex['b'] = sprintf('%02x', ($rgb['b']));

			$gradient[] = strtoupper(implode(NULL, $hex));
			}
		}

	$gradient[] = $hexarray[$total - 1];

	return $gradient;
	}

private static function getSubmissionsPerArchitecture()
	{
	$total = self::get('DB')->getColumn
		('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		');

	$arches = self::get('DB')->getRowSet
		('
		SELECT
			COUNT(*) AS count,
			arch AS name
		FROM
			pkgstats_users
		GROUP BY
			arch
		');

	$list = '';

	foreach ($arches as $arch)
		{
		$list .= '<tr><th>'.$arch['name'].'</th><td>'.self::getBar($arch['count'], $total).'</td></tr>';
		}

	return $list;
	}

}

?>
