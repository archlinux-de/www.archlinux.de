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

class RepositoryStatistics extends Page implements IDBCachable {

private static $barColors = array();
private static $barColorArray = array('8B0000','FF8800','006400');


public function prepare()
	{
	$this->setValue('title', $this->L10n->getText('Repository statistics'));

	if (!($body = $this->PersistentCache->getObject('RepositoryStatistics:'.$this->L10n->getLocale())))
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
		$data = self::getCommonRepositoryStatistics();

		$body = '<div class="box">
			<table id="packagedetails">
				<tr>
					<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">'.self::get('L10n')->getText('Repositories').'</h1></th>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Overview').'</th>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Architectures').'</th>
					<td>'.$data['architectures'].'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Repositories').'</th>
					<td>'.$data['repositories'].'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Groups').'</th>
					<td>'.self::get('L10n')->getNumber($data['groups']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Packages').'</th>
					<td>'.self::get('L10n')->getNumber($data['packages']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Files').'</th>
					<td>'.self::get('L10n')->getNumber($data['files']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Size of file index').'</th>
					<td>'.self::get('L10n')->getNumber($data['file_index']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Licenses').'</th>
					<td>'.self::get('L10n')->getNumber($data['licenses']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Dependencies').'</th>
					<td>'.self::get('L10n')->getNumber($data['depends']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Optional dependencies').'</th>
					<td>'.self::get('L10n')->getNumber($data['optdepends']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Provides').'</th>
					<td>'.self::get('L10n')->getNumber($data['provides']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Conflicts').'</th>
					<td>'.self::get('L10n')->getNumber($data['conflicts']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Replaces').'</th>
					<td>'.self::get('L10n')->getNumber($data['replaces']).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Total size of repositories').'</th>
					<td>'.self::formatBytes($data['csize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Total size of files').'</th>
					<td>'.self::formatBytes($data['isize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Packager').'</th>
					<td>'.$data['packagers'].'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Last update').'</th>
					<td>'.self::get('L10n')->getGMDateTime(self::get('Input')->getTime()).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Averages').'</th>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Size of packages').'</th>
					<td>&empty; '.self::formatBytes($data['avgcsize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Size of files').'</th>
					<td>&empty; '.self::formatBytes($data['avgisize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Files per package').'</th>
					<td>&empty; '.self::get('L10n')->getNumber($data['avgfiles'], 2).'</td>
				</tr>
				<tr>
					<th>'.self::get('L10n')->getText('Packages per packager').'</th>
					<td>&empty; '.self::get('L10n')->getNumber($data['avgpkgperpackager'], 2).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::get('L10n')->getText('Repositories').'</th>
				</tr>
					'.self::getRepositoryStatistics().'
			</table>
			</div>
			';

		self::get('PersistentCache')->addObject('RepositoryStatistics:'.self::get('L10n')->getLocale(), $body);
		}
	catch (DBNoDataException $e)
		{
		}
	}

private static function getCommonRepositoryStatistics()
	{
	return self::get('DB')->getRow
		('
		SELECT
			(SELECT COUNT(*) FROM architectures) AS architectures,
			(SELECT COUNT(*) FROM repositories) AS repositories,
			(SELECT COUNT(*) FROM packages) AS packages,
			(SELECT COUNT(*) FROM files) AS files,
			(SELECT SUM(csize) FROM packages) AS csize,
			(SELECT SUM(isize) FROM packages) AS isize,
			(SELECT COUNT(*) FROM packagers) AS packagers,
			(SELECT COUNT(*) FROM groups) AS groups,
			(SELECT COUNT(*) FROM licenses) AS licenses,
			(SELECT COUNT(*) FROM depends) AS depends,
			(SELECT COUNT(*) FROM optdepends) AS optdepends,
			(SELECT COUNT(*) FROM conflicts) AS conflicts,
			(SELECT COUNT(*) FROM replaces) AS replaces,
			(SELECT COUNT(*) FROM provides) AS provides,
			(SELECT COUNT(*) FROM file_index) AS file_index,
			(SELECT AVG(csize) FROM packages) AS avgcsize,
			(SELECT AVG(isize) FROM packages) AS avgisize,
			(SELECT
				AVG(pkgs)
			FROM
				(
				SELECT
					COUNT(packages.id) AS pkgs
				FROM
					packages
						JOIN
							packagers
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
					files
				GROUP BY package
				) AS temp2
			) AS avgfiles
		');
	}

private static function getRepositoryStatistics()
	{
	try
		{
		$repos = self::get('DB')->getRowSet('SELECT id, name FROM repositories')->toArray();
		}
	catch (DBNoDataException $e)
		{
		$repos = array();
		}

	$total = self::get('DB')->getRow
			('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				packages
			');

	$stm = self::get('DB')->prepare
			('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				packages
			WHERE
				repository = ?
			');

	$list = '';

	foreach ($repos as $repo)
		{
		try
			{
			$stm->bindInteger($repo['id']);
			$data = $stm->getRow();

			$list .= '<tr>
					<th>'.$repo['name'].'</th>
					<td style="padding:0px;margin:0px;">
						<div style="overflow:auto; max-height: 800px;">
						<table class="pretty-table" style="border:none;">
						<tr>
							<td style="width: 50px;">'.self::get('L10n')->getText('Packages').'</td>
							<td>'.self::getBar($data['packages'], $total['packages']).'</td>
						</tr>
						<tr>
							<td style="width: 50px;">'.self::get('L10n')->getText('Size').'</td>
							<td>'.self::getBar($data['size'], $total['size']).'</td>
						</tr>
						</table>
						</div>
					</td>
				</tr>';
			}
		catch (DBNoDataException $e)
			{
			}
		}

	$stm->close();

	return $list;
	}

private static function formatBytes($bytes)
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

	return self::get('L10n')->getNumber($result, 2).$postfix;
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

}

?>
