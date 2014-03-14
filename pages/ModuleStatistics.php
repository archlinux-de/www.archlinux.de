<?php
/*
	Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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

class ModuleStatistics extends StatisticsPage {

	public function prepare() {
		$this->setTitle('Module statistics');
		if (!($body = ObjectStore::getObject('ModuleStatistics'))) {
			$this->setStatus(Output::NOT_FOUND);
			$this->showFailure('No data found!');
		}
		$this->setBody($body);
	}

	public static function updateDatabaseCache() {
		try {
			Database::beginTransaction();
			self::$barColors = self::MultiColorFade(self::$barColorArray);
			$log = self::getCommonModuleUsageStatistics();
			$body = '<div class="box">
			<table id="packagedetails">
				<tr>
					<th colspan="2" style="margin:0px;padding:0px;"><h1 id="packagename">Module usage</h1></th>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Common statistics</th>
				</tr>
				<tr>
					<th>Sum of submitted modules</th>
					<td>' . number_format($log['sumcount']) . '</td>
				</tr>
				<tr>
					<th>Number of different modules</th>
					<td>' . number_format($log['diffcount']) . '</td>
				</tr>
				<tr>
					<th>Lowest number of installed modules</th>
					<td>' . number_format($log['mincount']) . '</td>
				</tr>
				<tr>
					<th>Highest number of installed modules</th>
					<td>' . number_format($log['maxcount']) . '</td>
				</tr>
				<tr>
					<th>Average number of installed modules</th>
					<td>' . number_format($log['avgcount']) . '</td>
				</tr>
				<tr>
					<th colspan="2" class="packagedetailshead">Popular modules</th>
				</tr>
				'.self::getPopularModules().'
			</table>
			</div>
			';
			ObjectStore::addObject('ModuleStatistics', $body);
			Database::commit();
		} catch (RuntimeException $e) {
			Database::rollBack();
			echo 'ModuleStatistics failed:'.$e->getMessage();
		}
	}

	private static function getCommonModuleUsageStatistics() {
		return Database::query('
		SELECT
			(SELECT COUNT(*) FROM pkgstats_users WHERE time >= '.self::getRangeTime().' AND modules IS NOT NULL) AS submissions,
			(SELECT COUNT(*) FROM (SELECT * FROM pkgstats_users WHERE time >= '.self::getRangeTime().' AND modules IS NOT NULL GROUP BY ip) AS temp) AS differentips,
			(SELECT MIN(time) FROM pkgstats_users WHERE time >= '.self::getRangeTime().' AND modules IS NOT NULL) AS minvisited,
			(SELECT MAX(time) FROM pkgstats_users WHERE time >= '.self::getRangeTime().' AND modules IS NOT NULL) AS maxvisited,
			(SELECT SUM(count) FROM pkgstats_modules WHERE month >= '.self::getRangeYearMonth().') AS sumcount,
			(SELECT COUNT(*) FROM (SELECT DISTINCT name FROM pkgstats_modules WHERE month >= '.self::getRangeYearMonth().') AS diffpkgs) AS diffcount,
			(SELECT MIN(modules) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS mincount,
			(SELECT MAX(modules) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS maxcount,
			(SELECT AVG(modules) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS avgcount
		')->fetch();
	}

	private static function getPopularModules() {
		$total = Database::query('
			SELECT
				COUNT(*)
			FROM
				pkgstats_users
			WHERE
				time >= '.self::getRangeTime().'
				AND modules IS NOT NULL
		')->fetchColumn();
		$modules = Database::query('
			SELECT
				name,
				SUM(count) AS count
			FROM
				pkgstats_modules
			WHERE
				month >= '.self::getRangeYearMonth().'
			GROUP BY
				name
			HAVING
				count >= ' . (floor($total / 100)) . '
			ORDER BY
				count DESC,
				name ASC
		');
		$list = '<tr><td colspan="2"><div><table class="pretty-table" style="border:none;">';
		foreach ($modules as $module) {
			$list.= '<tr><td style="width: 200px;">' . $module['name'] . '</td><td>' . self::getBar($module['count'], $total) . '</td></tr>';
		}
		$list.= '</table></div></td></tr>';
		return $list;
	}
}

?>
