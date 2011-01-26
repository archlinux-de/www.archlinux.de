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

class UserStatistics extends Page implements IDBCachable {

	private static $barColors = array();
	private static $barColorArray = array(
		'8B0000',
		'FF8800',
		'006400'
	);

	public function prepare() {
		$this->setValue('title', 'User statistics');
		if (!($body = ObjectStore::getObject('UserStatistics'))) {
			$this->setStatus(Output::NOT_FOUND);
			$this->showFailure('No data found!');
		}
		$this->setValue('body', $body);
	}

	public static function updateDBCache() {
		self::$barColors = self::MultiColorFade(self::$barColorArray);
		$log = self::getCommonPackageUsageStatistics();
		$body = '<div class="box">
		<table id="packagedetails">
			<tr>
				<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">User statistics</h1></th>
			</tr>
			<tr>
				<th colspan="2" class="packagedetailshead">Common statistics</th>
			</tr>
			<tr>
				<th>Submissions</th>
				<td>' . number_format($log['submissions']) . '</td>
			</tr>
			<tr>
				<th>Different IPs</th>
				<td>' . number_format($log['differentips']) . '</td>
			</tr>
			<tr>
				<th colspan="2" class="packagedetailshead">Countries</th>
			</tr>
				' . self::getCountryStatistics() . '
			<tr>
				<th colspan="2" class="packagedetailshead">Countries (relative to population)</th>
			</tr>
				' . self::getRelativeCountryStatistics() . '
			<tr>
				<th colspan="2" class="packagedetailshead">Mirrors</th>
			</tr>
				' . self::getMirrorStatistics() . '
			<tr>
				<th colspan="2" class="packagedetailshead">Mirror protocolls</th>
			</tr>
				' . self::getMirrorProtocollStatistics() . '
			<tr>
				<th colspan="2" class="packagedetailshead">Submissions per architectures</th>
			</tr>
				' . self::getSubmissionsPerArchitecture() . '
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
		</table>
		</div>
		';
		ObjectStore::addObject('UserStatistics', $body);
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

	private static function getCountryStatistics() {
		$total = DB::query('
		SELECT
			COUNT(country)
		FROM
			pkgstats_users
		')->fetchColumn();
		$countries = DB::query('
		SELECT
			country,
			COUNT(country) AS count
		FROM
			pkgstats_users
		GROUP BY
			country
		HAVING
			count >= ' . (floor($total / 100)) . '
		ORDER BY
			count DESC
		');
		$list = '';
		foreach ($countries as $country) {
			$list.= '<tr><th>' . $country['country'] . '</th><td>' . self::getBar($country['count'], $total) . '</td></tr>';
		}
		return $list;
	}

	private static function getRelativeCountryStatistics() {
		$relativeCountries = array();
		$total = DB::query('
		SELECT
			COUNT(country)
		FROM
			pkgstats_users
		')->fetchColumn();
		$countries = DB::query('
		SELECT
			country,
			COUNT(country) AS count
		FROM
			pkgstats_users
		GROUP BY
			country
		');
		$population = self::getPopulationPerCountry();
		$totalPopulation = array_sum($population);
		foreach ($countries as $country) {
			if (isset($population[$country['country']])) {
				$density = $country['count'] / ($population[$country['country']] / $totalPopulation);
				if ($density > (floor($total / 100) / ($population[$country['country']] / $totalPopulation))) {
					$relativeCountries[$country['country']] = $density;
				}
			}
		}
		arsort($relativeCountries);
		$list = '';
		foreach ($relativeCountries as $countryName => $density) {
			$list.= '<tr><th>' . $countryName . '</th><td>' . self::getBar($density, array_sum($relativeCountries)) . '</td></tr>';
		}
		return $list;
	}

	private static function getPopulationPerCountry() {
		if (!($countryarray = ObjectStore::getObject('UserStatistics:PopulationPerCountry'))) {
			if (false === ($curl = curl_init('https://www.cia.gov/library/publications/the-world-factbook/rankorder/rawdata_2119.text'))) {
				throw new RuntimeException('failed to init curl: ' . htmlspecialchars($url));
			}
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
			curl_setopt($curl, CURLOPT_TIMEOUT, 120);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl, CURLOPT_USERAGENT, 'bob@archlinux.de');
			curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:bob@archlinux.de');
			$content = curl_exec($curl);
			if (curl_errno($curl) > 0 || false === $content) {
				$error = htmlspecialchars(curl_error($curl));
				curl_close($curl);
				throw new RuntimeException($error, 1);
			} elseif (empty($content)) {
				curl_close($curl);
				throw new RuntimeException('empty country list', 1);
			}
			curl_close($curl);
			$countrylist = explode("\r", $content);
			$countryarray = array();
			foreach ($countrylist as $country) {
				preg_match("/^\d+\t([\w, \(\)]+)\t\s*([\d,]+)$/", $country, $matches);
				if (!empty($matches[1]) && !empty($matches[2])) {
					$countryarray[$matches[1]] = str_replace(',', '', $matches[2]);
				}
			}
			if (count($countryarray) == 0) {
				throw new RuntimeException('empty country list', 1);
			}
			ObjectStore::addObject('UserStatistics:PopulationPerCountry', $countryarray, (60 * 60 * 24 * 30));
		}
		return $countryarray;
	}

	private static function getMirrorStatistics() {
		$total = DB::query('
		SELECT
			COUNT(mirror)
		FROM
			pkgstats_users
		')->fetchColumn();
		$mirrors = DB::query('
		SELECT
			mirror,
			COUNT(mirror) AS count
		FROM
			pkgstats_users
		GROUP BY
			mirror
		HAVING
			count >= ' . (floor($total / 100)) . '
		');
		$hosts = array();
		foreach ($mirrors as $mirror) {
			$host = parse_url($mirror['mirror'], PHP_URL_HOST);
			if ($host === false || empty($host)) {
				$host = 'unknown';
			}
			if (isset($hosts[$host])) {
				$hosts[$host]+= $mirror['count'];
			} else {
				$hosts[$host] = $mirror['count'];
			}
		}
		arsort($hosts);
		$list = '';
		foreach ($hosts as $host => $count) {
			$list.= '<tr><th>' . $host . '</th><td>' . self::getBar($count, $total) . '</td></tr>';
		}
		return $list;
	}

	private static function getMirrorProtocollStatistics() {
		$protocolls = array(
			'http' => 0,
			'ftp' => 0
		);
		$total = DB::query('
		SELECT
			COUNT(mirror)
		FROM
			pkgstats_users
		')->fetchColumn();
		foreach ($protocolls as $protocoll => $count) {
			$protocolls[$protocoll] = DB::query('
			SELECT
				COUNT(mirror)
			FROM
				pkgstats_users
			WHERE
				mirror LIKE \'' . $protocoll . '%\'
			')->fetchColumn();
		}
		arsort($protocolls);
		$list = '';
		foreach ($protocolls as $protocoll => $count) {
			$list.= '<tr><th>' . $protocoll . '</th><td>' . self::getBar($count, $total) . '</td></tr>';
		}
		return $list;
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
		ORDER BY
			count DESC
		');
		$list = '';
		foreach ($arches as $arch) {
			$list.= '<tr><th>' . $arch['name'] . '</th><td>' . self::getBar($arch['count'], $total) . '</td></tr>';
		}
		return $list;
	}
}

?>
