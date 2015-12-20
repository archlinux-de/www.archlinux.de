<?php

/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

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

namespace archportal\pages\statistics;

use archportal\lib\Database;
use archportal\lib\ObjectStore;
use archportal\lib\Output;
use archportal\lib\StatisticsPage;
use PDO;
use RuntimeException;

class PackageStatistics extends StatisticsPage
{

    public function prepare()
    {
        $this->setTitle('Package statistics');
        if (!($body = ObjectStore::getObject('PackageStatistics'))) {
            $this->setStatus(Output::NOT_FOUND);
            $this->showFailure('No data found!');
        }
        $this->setBody($body);
    }

    public static function updateDatabaseCache()
    {
        try {
            Database::beginTransaction();
            self::$barColors = self::MultiColorFade(self::$barColorArray);
            $log = self::getCommonPackageUsageStatistics();
            $body = '<div class="box">
            <table id="packagedetails">
                <tr>
                    <th colspan="2" style="margin:0;padding:0;"><h1 id="packagename">Package usage</h1></th>
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
                    <th colspan="2" class="packagedetailshead">Submissions per CPU architectures</th>
                </tr>
                ' . self::getSubmissionsPerCpuArchitecture() . '
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
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            echo 'PackageStatistics failed:' . $e->getMessage();
        }
    }

    /**
     * @return array
     */
    private static function getCommonPackageUsageStatistics(): array
    {
        return Database::query('
        SELECT
            (SELECT COUNT(*) FROM pkgstats_users WHERE time >= ' . self::getRangeTime() . ') AS submissions,
            (SELECT COUNT(*) FROM (SELECT * FROM pkgstats_users WHERE time >= ' . self::getRangeTime() . ' GROUP BY ip) AS temp) AS differentips,
            (SELECT MIN(time) FROM pkgstats_users WHERE time >= ' . self::getRangeTime() . ') AS minvisited,
            (SELECT MAX(time) FROM pkgstats_users WHERE time >= ' . self::getRangeTime() . ') AS maxvisited,
            (SELECT SUM(count) FROM pkgstats_packages WHERE month >= ' . self::getRangeYearMonth() . ') AS sumcount,
            (SELECT COUNT(*) FROM (SELECT DISTINCT pkgname FROM pkgstats_packages WHERE month >= ' . self::getRangeYearMonth() . ') AS diffpkgs) AS diffcount,
            (SELECT MIN(packages) FROM pkgstats_users WHERE time >= ' . self::getRangeTime() . ') AS mincount,
            (SELECT MAX(packages) FROM pkgstats_users WHERE time >= ' . self::getRangeTime() . ') AS maxcount,
            (SELECT AVG(packages) FROM pkgstats_users WHERE time >= ' . self::getRangeTime() . ') AS avgcount
        ')->fetch();
    }

    /**
     * @return string
     */
    private static function getSubmissionsPerArchitecture(): string
    {
        $total = Database::query('
        SELECT
            COUNT(*)
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $arches = Database::query('
        SELECT
            COUNT(*) AS count,
            arch AS name
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
        GROUP BY
            arch
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($arches as $arch) {
            $list .= '<tr><th>' . $arch['name'] . '</th><td>' . self::getBar($arch['count'], $total) . '</td></tr>';
        }

        return $list;
    }

    /**
     * @return string
     */
    private static function getSubmissionsPerCpuArchitecture(): string
    {
        $total = Database::query('
        SELECT
            COUNT(*)
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
            AND cpuarch IS NOT NULL
        ')->fetchColumn();
        $arches = Database::query('
        SELECT
            COUNT(cpuarch) AS count,
            cpuarch AS name
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
            AND cpuarch IS NOT NULL
        GROUP BY
            cpuarch
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($arches as $arch) {
            $list .= '<tr><th>' . $arch['name'] . '</th><td>' . self::getBar($arch['count'], $total) . '</td></tr>';
        }

        return $list;
    }

    /**
     * @return string
     */
    private static function getPackagesPerRepository(): string
    {
        $repos = Database::query('
            SELECT DISTINCT
                name
            FROM
                repositories
            WHERE
                testing = 0
                AND name NOT LIKE "%unstable"
                AND name NOT LIKE "%staging"
            ')->fetchAll(PDO::FETCH_COLUMN);
        $total = Database::query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $countStm = Database::prepare('
            SELECT
                COUNT(*)
            FROM
                (
                SELECT DISTINCT
                    packages.name
                FROM
                    packages
                        JOIN repositories
                        ON packages.repository = repositories.id
                WHERE
                    repositories.name = :repositoryName
                ) AS total
                JOIN
                (
                SELECT DISTINCT
                    pkgname
                FROM
                    pkgstats_packages
                WHERE
                    month >= ' . self::getRangeYearMonth() . '
                    AND count >= ' . (floor($total / 100)) . '
                ) AS used
                ON total.name = used.pkgname
        ');
        $totalStm = Database::prepare('
            SELECT
                COUNT(*)
            FROM
                (
                SELECT DISTINCT
                    packages.name
                FROM
                    packages
                        JOIN repositories
                        ON packages.repository = repositories.id
                WHERE
                    repositories.name = :repositoryName
                ) AS total
        ');
        $result = '';
        $list = array();
        $sortList = array();
        $id = 0;
        foreach ($repos as $repo) {
            $countStm->bindParam('repositoryName', $repo, PDO::PARAM_STR);
            $countStm->execute();
            $count = $countStm->fetchColumn();
            $totalStm->bindParam('repositoryName', $repo, PDO::PARAM_STR);
            $totalStm->execute();
            $total = $totalStm->fetchColumn();
            $sortList[$id] = $count / $total;
            $list[$id++] = '<tr><th>' . $repo . '</th><td>' . self::getBar($count, $total) . '</td></tr>';
        }
        arsort($sortList);
        foreach (array_keys($sortList) as $id) {
            $result .= $list[$id];
        }

        return $result;
    }

    /**
     * @return string
     */
    private static function getPopularPackagesPerRepository(): string
    {
        $repos = Database::query('
            SELECT DISTINCT
                name
            FROM
                repositories
            WHERE
                testing = 0
                AND name NOT LIKE "%unstable"
                AND name NOT LIKE "%staging"
            ORDER BY
                id
            ')->fetchAll(PDO::FETCH_COLUMN);
        $total = Database::query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $packages = Database::prepare('
            SELECT
                pkgname,
                SUM(count) AS count
            FROM
                pkgstats_packages
            WHERE
                month >= ' . self::getRangeYearMonth() . '
                AND pkgname IN (
                    SELECT
                        packages.name
                    FROM
                        packages
                            JOIN repositories
                            ON packages.repository = repositories.id
                    WHERE
                        repositories.name = :repositoryName
                )
            GROUP BY
                pkgname
            HAVING
                count >= ' . (floor($total / 100)) . '
            ORDER BY
                count DESC,
                pkgname ASC
        ');
        $list = '';
        $currentRepo = '';
        foreach ($repos as $repo) {
            $packages->bindParam('repositoryName', $repo, PDO::PARAM_STR);
            $packages->execute();
            if ($currentRepo != $repo) {
                $list .= '<tr><th>' . $repo . '</th><td><div style="overflow:auto; max-height: 800px;"><table class="pretty-table" style="border:none;">';
            }
            foreach ($packages as $package) {
                $list .= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>' . self::getBar($package['count'],
                        $total) . '</td></tr>';
            }
            $list .= '</table></div></td></tr>';
            $currentRepo = $repo;
        }

        return $list;
    }

    /**
     * @return string
     */
    private static function getPopularUnofficialPackages(): string
    {
        $total = Database::query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $packages = Database::query('
            SELECT
                pkgname,
                SUM(count) AS count
            FROM
                pkgstats_packages
            WHERE
                month >= ' . self::getRangeYearMonth() . '
                AND pkgname NOT IN (SELECT name FROM packages)
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
            $list .= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>' . self::getBar($package['count'],
                    $total) . '</td></tr>';
        }
        $list .= '</table></div></td></tr>';

        return $list;
    }
}
