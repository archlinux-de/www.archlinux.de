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

class RepositoryStatistics extends Page implements IDatabaseCachable {

	private static $barColors = array();
	private static $barColorArray = array(
		'8B0000',
		'FF8800',
		'006400'
	);

	public function prepare() {
		$this->setValue('title', 'Repository statistics');
		if (!($body = ObjectStore::getObject('RepositoryStatistics'))) {
			$this->setStatus(Output::NOT_FOUND);
			$this->showFailure('No data found!');
		}
		$this->setValue('body', $body);
	}

	public static function updateDatabaseCache() {
		try {
			Database::beginTransaction();
			self::$barColors = self::MultiColorFade(self::$barColorArray);
			$data = self::getCommonRepositoryStatistics();
			$body = '<div class="box">
			<table id="packagedetails">
				<tr>
					<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">Repositories</h1></th>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Overview</th>
				</tr>
				<tr>
					<th>Architectures</th>
					<td>' . $data['architectures'] . '</td>
				</tr>
				<tr>
					<th>Repositories</th>
					<td>' . $data['repositories'] . '</td>
				</tr>
				<tr>
					<th>Groups</th>
					<td>' . number_format($data['groups']) . '</td>
				</tr>
				<tr>
					<th>Packages</th>
					<td>' . number_format($data['packages']) . '</td>
				</tr>
				<tr>
					<th>Files</th>
					<td>' . number_format($data['files']) . '</td>
				</tr>
				<tr>
					<th>Size of file index</th>
					<td>' . number_format($data['file_index']) . '</td>
				</tr>
				<tr>
					<th>Licenses</th>
					<td>' . number_format($data['licenses']) . '</td>
				</tr>
				<tr>
					<th>Dependencies</th>
					<td>' . number_format($data['depends']) . '</td>
				</tr>
				<tr>
					<th>Optional dependencies</th>
					<td>' . number_format($data['optdepends']) . '</td>
				</tr>
				<tr>
					<th>Provides</th>
					<td>' . number_format($data['provides']) . '</td>
				</tr>
				<tr>
					<th>Conflicts</th>
					<td>' . number_format($data['conflicts']) . '</td>
				</tr>
				<tr>
					<th>Replaces</th>
					<td>' . number_format($data['replaces']) . '</td>
				</tr>
				<tr>
					<th>Total size of repositories</th>
					<td>' . self::formatBytes($data['csize']) . 'Byte</td>
				</tr>
				<tr>
					<th>Total size of files</th>
					<td>' . self::formatBytes($data['isize']) . 'Byte</td>
				</tr>
				<tr>
					<th>Packager</th>
					<td>' . $data['packagers'] . '</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Averages</th>
				</tr>
				<tr>
					<th>Size of packages</th>
					<td>&empty; ' . self::formatBytes($data['avgcsize']) . 'Byte</td>
				</tr>
				<tr>
					<th>Size of files</th>
					<td>&empty; ' . self::formatBytes($data['avgisize']) . 'Byte</td>
				</tr>
				<tr>
					<th>Files per package</th>
					<td>&empty; ' . number_format($data['avgfiles'], 2) . '</td>
				</tr>
				<tr>
					<th>Packages per packager</th>
					<td>&empty; ' . number_format($data['avgpkgperpackager'], 2) . '</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Repositories</th>
				</tr>
					' . self::getRepositoryStatistics() . '
			</table>
			</div>
			';
			ObjectStore::addObject('RepositoryStatistics', $body);
			Database::commit();
		} catch (RuntimeException $e) {
			Database::rollBack();
			echo 'RepositoryStatistics failed:'.$e->getMessage();
		}
	}

	private static function getCommonRepositoryStatistics() {
		return Database::query('
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
			(SELECT COUNT(*) FROM package_relation WHERE type = "depends") AS depends,
			(SELECT COUNT(*) FROM package_relation WHERE type = "optdepends") AS optdepends,
			(SELECT COUNT(*) FROM package_relation WHERE type = "conflicts") AS conflicts,
			(SELECT COUNT(*) FROM package_relation WHERE type = "replaces") AS replaces,
			(SELECT COUNT(*) FROM package_relation WHERE type = "provides") AS provides,
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
					COUNT(*) AS pkgfiles
				FROM
					files
				GROUP BY package
				) AS temp2
			) AS avgfiles
		')->fetch();
	}

	private static function getRepositoryStatistics() {
		$repos = Database::query('SELECT DISTINCT name FROM repositories')->fetchALL(PDO::FETCH_COLUMN);
		$total = Database::query('
			SELECT
				COUNT(id) AS packages,
				SUM(csize) AS size
			FROM
				packages
			')->fetch();
		$stm = Database::prepare('
			SELECT
				COUNT(packages.id) AS packages,
				SUM(packages.csize) AS size
			FROM
				packages
					JOIN repositories
					ON packages.repository = repositories.id
			WHERE
				repositories.name = :repositoryName
			');
		$list = '';
		foreach ($repos as $repo) {
			$stm->bindParam('repositoryName', $repo, PDO::PARAM_STR);
			$stm->execute();
			$data = $stm->fetch();
			$list.= '<tr>
				<th>' . $repo . '</th>
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
}

?>
