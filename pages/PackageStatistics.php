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

class PackageStatistics extends Page implements IDBCachable {

private static $barColors = array();
private static $barColorArray = array('8B0000','FF8800','006400');

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
			<li><a href="?page=MirrorStatus">Server</a></li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li><a href="?page=Packages">Suche</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', $this->L10n->getText('Package statistics'));

	if (!($body = $this->PersistentCache->getObject('PackageStatistics:'.$this->L10n->getLocale())))
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
		$log = self::getCommonPackageUsageStatistics();

		$body = '<div id="box">
			<table id="packagedetails">
				<tr>
					<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">'.self::__get('L10n')->getText('Repositories').'</h1></th>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Overview').'</th>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Architectures').'</th>
					<td>'.$data['architectures'].'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Repositories').'</th>
					<td>'.$data['repositories'].'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Groups').'</th>
					<td>'.self::__get('L10n')->getNumber($data['groups']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Packages').'</th>
					<td>'.self::__get('L10n')->getNumber($data['packages']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Files').'</th>
					<td>'.self::__get('L10n')->getNumber($data['files']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Size of file index').'</th>
					<td>'.self::__get('L10n')->getNumber($data['file_index']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Licenses').'</th>
					<td>'.self::__get('L10n')->getNumber($data['licenses']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Dependencies').'</th>
					<td>'.self::__get('L10n')->getNumber($data['depends']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Optional dependencies').'</th>
					<td>'.self::__get('L10n')->getNumber($data['optdepends']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Provides').'</th>
					<td>'.self::__get('L10n')->getNumber($data['provides']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Conflicts').'</th>
					<td>'.self::__get('L10n')->getNumber($data['conflicts']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Replaces').'</th>
					<td>'.self::__get('L10n')->getNumber($data['replaces']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Total size of repositories').'</th>
					<td>'.self::formatBytes($data['csize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Total size of files').'</th>
					<td>'.self::formatBytes($data['isize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Packager').'</th>
					<td>'.$data['packagers'].'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Averages').'</th>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Size of packages').'</th>
					<td>&empty; '.self::formatBytes($data['avgcsize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Size of files').'</th>
					<td>&empty; '.self::formatBytes($data['avgisize']).'Byte</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Files per package').'</th>
					<td>&empty; '.self::__get('L10n')->getNumber($data['avgfiles'], 2).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Packages per packager').'</th>
					<td>&empty; '.self::__get('L10n')->getNumber($data['avgpkgperpackager'], 2).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Repositories').'</th>
				</tr>
					'.self::getRepositoryStatistics().'
				<tr>
					<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">'.self::__get('L10n')->getText('Package usage').'</h1></th>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Common statistics').'</th>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Submissions').'</th>
					<td>'.self::__get('L10n')->getNumber($log['submissions']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Different IPs').'</th>
					<td>'.self::__get('L10n')->getNumber($log['differentips']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('First entry').'</th>
					<td>'.self::__get('L10n')->getDateTime($log['minvisited']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Last entry').'</th>
					<td>'.self::__get('L10n')->getDateTime($log['maxvisited']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Sum of submitted packages').'</th>
					<td>'.self::__get('L10n')->getNumber($log['sumcount']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Number of different packages').'</th>
					<td>'.self::__get('L10n')->getNumber($log['diffcount']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Lowest number of installed packages').'</th>
					<td>'.self::__get('L10n')->getNumber($log['mincount']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Highest number of installed packages').'</th>
					<td>'.self::__get('L10n')->getNumber($log['maxcount']).'</td>
				</tr>
				<tr>
					<th>'.self::__get('L10n')->getText('Average number of installed packages').'</th>
					<td>'.self::__get('L10n')->getNumber($log['avgcount']).'</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Submissions per architectures').'</th>
				</tr>
				'.self::getSubmissionsPerArchitecture().'
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Installed packages per repository').'</th>
				</tr>
				'.self::getPackagesPerRepository().'
				'.self::getNumberOfUnofficialPackages().'
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Popular packages per repository').'</th>
				</tr>
				'.self::getPopularPackagesPerRepository().'
				<tr>
					<th colspan="2" class="packagedetailshead">'.self::__get('L10n')->getText('Popular unofficial packages').'</th>
				</tr>
				'.self::getPopularUnofficialPackages().'
			</table>
			</div>
			';

		self::__get('PersistentCache')->addObject('PackageStatistics:'.self::__get('L10n')->getLocale(), $body);
		}
	catch (DBNoDataException $e)
		{
		}
	}

private static function getCommonRepositoryStatistics()
	{
	return self::__get('DB')->getRow
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

private static function getCommonPackageUsageStatistics()
	{
	return self::__get('DB')->getRow
		('
		SELECT
			(SELECT COUNT(*) FROM package_statistics_log) AS submissions,
			(SELECT COUNT(*) FROM (SELECT * FROM package_statistics_log GROUP BY ip) AS temp) AS differentips,
			(SELECT MIN(visited) FROM package_statistics_log) AS minvisited,
			(SELECT MAX(visited) FROM package_statistics_log) AS maxvisited,
			(SELECT SUM(count) FROM package_statistics_log) AS sumcount,
			(SELECT COUNT(*) FROM package_statistics) AS diffcount,
			(SELECT MIN(count) FROM package_statistics_log) AS mincount,
			(SELECT MAX(count) FROM package_statistics_log) AS maxcount,
			(SELECT AVG(count) FROM package_statistics_log) AS avgcount
		');
	}

private static function getRepositoryStatistics()
	{
	try
		{
		$repos = self::__get('DB')->getRowSet('SELECT id, name FROM repositories')->toArray();
		}
	catch (DBNoDataException $e)
		{
		$repos = array();
		}

	$total = self::__get('DB')->getRow
			('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				packages
			');

	$stm = self::__get('DB')->prepare
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
						<table id="packages" style="border:none;">
						<tr>
							<td style="width: 50px;">'.self::__get('L10n')->getText('Packages').'</td>
							<td>'.self::getBar($data['packages'], $total['packages']).'</td>
						</tr>
						<tr>
							<td style="width: 50px;">'.self::__get('L10n')->getText('Size').'</td>
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

	return self::__get('L10n')->getNumber($result, 2).$postfix;
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
		title="'.self::__get('L10n')->getNumber($value).' '.self::__get('L10n')->getText('of').' '.self::__get('L10n')->getNumber($total).'">
			&nbsp;
				</div>
				</td>
				<td style="padding:0px;margin:0px;width:80px;text-align:right;color:#'.$color.'">
					'.self::__get('L10n')->getNumber($percent, 2).'&nbsp;%
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
	$total = self::__get('DB')->getColumn
		('
		SELECT
			COUNT(arch)
		FROM
			package_statistics_log
		');

	$arches = self::__get('DB')->getRowSet
		('
		SELECT
			COUNT(arch) AS count,
			arch AS name
		FROM
			package_statistics_log
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

private static function getPackagesPerRepository()
	{
	$total = self::__get('DB')->getColumn
		('
		SELECT
			SUM(package_statistics.count)
		FROM
			package_statistics
		');

	$repos = self::__get('DB')->getRowSet
		('
		SELECT
			SUM(package_statistics.count) AS count,
			repositories.name
		FROM
			package_statistics,
			packages,
			repositories,
			architectures
		WHERE
			package_statistics.name = packages.name
			AND package_statistics.arch = architectures.name
			AND packages.repository = repositories.id
			AND repositories.name <> "testing"
			AND packages.arch = architectures.id
		GROUP BY
			repositories.id
		');

	$list = '';

	foreach ($repos as $repo)
		{
		$list .= '<tr><th>'.$repo['name'].'</th><td>'.self::getBar($repo['count'], $total).'</td></tr>';
		}

	return $list;
	}

private static function getPopularPackagesPerRepository()
	{
	$total = self::__get('DB')->getColumn
		('
		SELECT
			COUNT(*)
		FROM
			package_statistics_log
		');

	$repos = self::__get('DB')->getRowSet
		('
		SELECT
			name,
			id
		FROM
			repositories
		WHERE
			name <> "testing"
		');

	$stm = self::__get('DB')->prepare
		('
		SELECT
			name,
			SUM(count) AS count
		FROM
		(
			(
			SELECT
				packages.name,
				package_statistics.count
			FROM
				packages,
				package_statistics,
				architectures
			WHERE
				packages.repository = ?
				AND packages.name = package_statistics.name
				AND package_statistics.arch = architectures.name
				AND packages.arch = architectures.id
			)
			UNION
			(
			SELECT
				name,
				0
			FROM
				packages
			WHERE
				repository = ?
				AND name NOT IN (SELECT name FROM package_statistics)
			)
		) AS temp
		GROUP BY
			name
		ORDER BY
			count DESC,
			name ASC
		');

	$list = '';
	$repoid = 0;
	$line = 0;

	foreach ($repos as $repo)
		{
		try
			{
			$stm->bindInteger($repo['id']);
			$stm->bindInteger($repo['id']);
			$packages = $stm->getRowSet();

			if ($repoid != $repo['id'])
				{
				$list .= '<tr><th>'.$repo['name'].'</th><td><div style="overflow:auto; max-height: 800px;"><table id="packages" style="border:none;">';
				}

			foreach ($packages as $package)
				{
				$list .= '<tr class="packageline'.$line.'"><td style="width: 200px;">'.$package['name'].'</td><td>'.self::getBar($package['count'], $total).'</td></tr>';
				$line = abs($line-1);
				}

			$list .= '</table></div></td></tr>';
			$repoid = $repo['id'];
			}
		catch (DBNoDataException $e)
			{
			}
		}

	$stm->close();

	return $list;
	}

private static function getNumberOfUnofficialPackages()
	{
	$total = self::__get('DB')->getColumn
		('
		SELECT
			SUM(package_statistics.count)
		FROM
			package_statistics
		');

	$packages = self::__get('DB')->getColumn
		('
		SELECT
			SUM(package_statistics.count)
		FROM
			package_statistics
		WHERE
			package_statistics.name NOT IN (SELECT name FROM packages)
		');

	return '<tr><th>'.self::__get('L10n')->getText('Unofficial repositories').'</th><td>'.self::getBar($packages, $total).'</td></tr>';
	}

private static function getPopularUnofficialPackages()
	{
	$total = self::__get('DB')->getColumn
		('
		SELECT
			COUNT(*)
		FROM
			package_statistics_log
		');

	$packages = self::__get('DB')->getRowSet
		('
		SELECT
			package_statistics.name,
			SUM(package_statistics.count) AS count
		FROM
			package_statistics
		WHERE
			package_statistics.name NOT IN (SELECT name FROM packages)
		GROUP BY
			package_statistics.name
		ORDER BY
			count DESC,
			name ASC
		LIMIT
			1000
		');

	$list = '';
	$line = 0;

	try
		{
		$list = '<tr><th>unknown</th><td><div style="overflow:auto; max-height: 800px;"><table id="packages" style="border:none;">';

		foreach ($packages as $package)
			{
			$list .= '<tr class="packageline'.$line.'"><td style="width: 200px;">'.$package['name'].'</td><td>'.self::getBar($package['count'], $total).'</td></tr>';
			$line = abs($line-1);
			}

		$list .= '</table></div></td></tr>';
		}
	catch (DBNoDataException $e)
		{
		}

	return $list;
	}

}

?>
