<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

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

class PackageStatistics extends Page implements IDBCachable {

	private static $barColors = array();
	private static $barColorArray = array(
		'8B0000',
		'FF8800',
		'006400'
	);

	public function prepare() {
		$this->setValue('title', 'Package statistics');
		if (!($body = ObjectStore::getObject('PackageStatistics'))) {
			$this->setStatus(Output::NOT_FOUND);
			$this->showFailure('No data found!');
		}
		$this->setValue('body', $body);
	}

	public static function updateDBCache() {
		try {
			DB::beginTransaction();
			self::$barColors = self::MultiColorFade(self::$barColorArray);
			$log = self::getCommonPackageUsageStatistics();
			$body = '<div class="box">
			<table id="packagedetails">
				<tr>
					<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">Package usage</h1></th>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Common statistics</th>
				</tr>
				<tr>
					<th>Sum of submitted packages</th>
					<td>' . number_format($log['sumcount']) . '</td>
				</tr>
				<tr>
					<th>Number of different packages</th>
					<td>' . number_format($log['diffcount']) . '</td>
				</tr>
				<tr>
					<th>Lowest number of installed packages</th>
					<td>' . number_format($log['mincount']) . '</td>
				</tr>
				<tr>
					<th>Highest number of installed packages</th>
					<td>' . number_format($log['maxcount']) . '</td>
				</tr>
				<tr>
					<th>Average number of installed packages</th>
					<td>' . number_format($log['avgcount']) . '</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Submissions per architectures</th>
				</tr>
				' . self::getSubmissionsPerArchitecture() . '
				<tr>
					<th colspan="2" class="packagedetailshead">Installed packages per repository</th>
				</tr>
				' . self::getPackagesPerRepository() . '
				<tr>
					<th colspan="2" class="packagedetailshead">Popular packages per repository</th>
				</tr>
				' . self::getPopularPackagesPerRepository() . '
				<tr>
					<th colspan="2" class="packagedetailshead">Popular unofficial packages</th>
				</tr>
				' . self::getPopularUnofficialPackages() . '
			</table>
			</div>
			';
			ObjectStore::addObject('PackageStatistics', $body);
			DB::commit();
		} catch (RuntimeException $e) {
			DB::rollBack();
			echo 'PackageStatistics failed:'.$e->getMessage();
		}
	}

	private static function getCommonPackageUsageStatistics() {
		return DB::query('
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
		')->fetch();
	}

	private static function getRepositoryStatistics() {
		$repos = DB::query('SELECT id, name FROM repositories');
		$total = DB::query('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				packages
			')->fetch();
		$stm = DB::prepare('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				packages
			WHERE
				repository = :repository
			');
		$list = '';
		foreach ($repos as $repo) {
			$stm->bindParam('repository', $repo['id'], PDO::PARAM_INT);
			$stm->execute();
			$data = $stm->fetch();
			if ($data !== false) {
				$list.= '<tr>
					<th>' . $repo['name'] . '</th>
					<td style="padding:0px;margin:0px;">
						<div style="overflow:auto; max-height: 800px;">
						<table class="pretty-table" style="border:none;">
						<tr>
							<td style="width: 50px;">Packages</td>
							<td>' . self::getBar($data['packages'], $total['packages']) . '</td>
						</tr>
						<tr>
							<td style="width: 50px;">Size</td>
							<td>' . self::getBar($data['size'], $total['size']) . '</td>
						</tr>
						</table>
						</div>
					</td>
				</tr>';
			}
		}
		return $list;
	}

	private static function formatBytes($bytes) {
		$kb = 1024;
		$mb = $kb * 1024;
		$gb = $mb * 1024;
		if ($bytes >= $gb) // GB
		{
			$result = round($bytes / $gb, 2);
			$postfix = '&nbsp;G';
		} elseif ($bytes >= $mb) // MB
		{
			$result = round($bytes / $mb, 2);
			$postfix = '&nbsp;M';
		} elseif ($bytes >= $kb) // KB
		{
			$result = round($bytes / $kb, 2);
			$postfix = '&nbsp;K';
		} else
		//  B
		{
			$result = $bytes;
			$postfix = '&nbsp;';
		}
		return number_format($result, 2) . $postfix;
	}

	private static function getBar($value, $total) {
		if ($total <= 0) {
			return '';
		}
		$percent = ($value / $total) * 100;
		$color = self::$barColors[round($percent) ];
		return '<table style="width:100%;">
			<tr>
				<td style="padding:0px;margin:0px;">
					<div style="background-color:#' . $color . ';width:' . round($percent) . '%;"
		title="' . number_format($value) . ' of ' . number_format($total) . '">
			&nbsp;
				</div>
				</td>
				<td style="padding:0px;margin:0px;width:80px;text-align:right;color:#' . $color . '">
					' . number_format($percent, 2) . '&nbsp;%
				</td>
			</tr>
		</table>';
	}

	// see http://at.php.net/manual/de/function.hexdec.php#66780
	private static function MultiColorFade($hexarray) {
		$steps = 101;
		$total = count($hexarray);
		$gradient = array();
		$fixend = 2;
		$passages = $total - 1;
		$stepsforpassage = floor($steps / $passages);
		$stepsremain = $steps - ($stepsforpassage * $passages);
		for ($pointer = 0;$pointer < $total - 1;$pointer++) {
			$hexstart = $hexarray[$pointer];
			$hexend = $hexarray[$pointer + 1];
			if ($stepsremain > 0) {
				if ($stepsremain--) {
					$stepsforthis = $stepsforpassage + 1;
				}
			} else {
				$stepsforthis = $stepsforpassage;
			}
			if ($pointer > 0) {
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
			for ($i = 0;$i <= $stepsforthis - $fixend;$i++) {
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

	private static function getSubmissionsPerArchitecture() {
		$total = DB::query('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		')->fetchColumn();
		$arches = DB::query('
		SELECT
			COUNT(*) AS count,
			arch AS name
		FROM
			pkgstats_users
		GROUP BY
			arch
		');
		$list = '';
		foreach ($arches as $arch) {
			$list.= '<tr><th>' . $arch['name'] . '</th><td>' . self::getBar($arch['count'], $total) . '</td></tr>';
		}
		return $list;
	}

	private static function getPackagesPerRepository() {
		$repos = DB::query('SELECT id, name FROM repositories WHERE name NOT IN (\'testing\', \'community-testing\', \'multilib-testing\', \'staging\', \'community-staging\', \'kde-unstable\', \'gnome-unstable\')');
		$total = DB::query('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		')->fetchColumn();
		$countStm = DB::prepare('
		SELECT
			COUNT(*)
		FROM
			(
			SELECT DISTINCT
				name
			FROM
				packages
			WHERE
				repository = :repository
			) AS total
			JOIN
			(
			SELECT DISTINCT
				pkgname
			FROM
				pkgstats_packages
			WHERE
				count >= ' . (floor($total / 100)) . '
			) AS used
			ON total.name = used.pkgname
		');
		$totalStm = DB::prepare('
		SELECT
			COUNT(*)
		FROM
			(
			SELECT DISTINCT
				name
			FROM
				packages
			WHERE
				repository = :repository
			) AS total
		');
		$list = '';
		foreach ($repos as $repo) {
			$countStm->bindParam('repository', $repo['id'], PDO::PARAM_INT);
			$countStm->execute();
			$count = $countStm->fetchColumn();
			$totalStm->bindParam('repository', $repo['id'], PDO::PARAM_INT);
			$totalStm->execute();
			$total = $totalStm->fetchColumn();
			$list.= '<tr><th>' . $repo['name'] . '</th><td>' . self::getBar($count, $total) . '</td></tr>';
		}
		return $list;
	}

	private static function getPopularPackagesPerRepository() {
		$total = DB::query('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		')->fetchColumn();
		$repos = DB::query('
		SELECT
			name,
			id
		FROM
			repositories
		WHERE
			name NOT IN (\'testing\', \'community-testing\', \'multilib-testing\', \'staging\', \'community-staging\', \'kde-unstable\', \'gnome-unstable\')
		');
		$packages = DB::prepare('
		SELECT
			pkgname,
			SUM(count) AS count
		FROM
			pkgstats_packages
		WHERE
			pkgname IN (SELECT name FROM packages WHERE repository = :repository)
		GROUP BY
			pkgname
		HAVING
			count >= ' . (floor($total / 100)) . '
		ORDER BY
			count DESC,
			pkgname ASC
		');
		$list = '';
		$repoid = 0;
		foreach ($repos as $repo) {
			$packages->bindParam('repository', $repo['id'], PDO::PARAM_INT);
			$packages->execute();
			if ($repoid != $repo['id']) {
				$list.= '<tr><th>' . $repo['name'] . '</th><td><div style="overflow:auto; max-height: 800px;"><table class="pretty-table" style="border:none;">';
			}
			foreach ($packages as $package) {
				$list.= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>' . self::getBar($package['count'], $total) . '</td></tr>';
			}
			$list.= '</table></div></td></tr>';
			$repoid = $repo['id'];
		}
		return $list;
	}

	private static function getPopularUnofficialPackages() {
		$total = DB::query('
		SELECT
			COUNT(*)
		FROM
			pkgstats_users
		')->fetchColumn();
		$packages = DB::query('
		SELECT
			pkgname,
			SUM(count) AS count
		FROM
			pkgstats_packages
		WHERE
			pkgname NOT IN (SELECT name FROM packages)
		GROUP BY
			pkgname
		HAVING
			count >= ' . (floor($total / 100)) . '
		ORDER BY
			count DESC,
			pkgname ASC
		');
		$list = '<tr><th>unknown</th><td><div style="overflow:auto; max-height: 800px;"><table class="pretty-table" style="border:none;">';
		foreach ($packages as $package) {
			$list.= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>' . self::getBar($package['count'], $total) . '</td></tr>';
		}
		$list.= '</table></div></td></tr>';
		return $list;
	}
}

?>
