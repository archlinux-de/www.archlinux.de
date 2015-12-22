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
use RuntimeException;

class UserStatistics extends StatisticsPage
{

    public function prepare()
    {
        $this->setTitle('User statistics');
        if (!($body = ObjectStore::getObject('UserStatistics'))) {
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
                    <th colspan="2" style="margin:0;padding:0;"><h1 id="packagename">User statistics</h1></th>
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
                    <th colspan="2" class="packagedetailshead">Mirrors</th>
                </tr>
                    ' . self::getMirrorStatistics() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Mirrors per Country</th>
                </tr>
                    ' . self::getMirrorCountryStatistics() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Mirror protocolls</th>
                </tr>
                    ' . self::getMirrorProtocollStatistics() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Submissions per architectures</th>
                </tr>
                    ' . self::getSubmissionsPerArchitecture() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Submissions per CPU architectures</th>
                </tr>
                    ' . self::getSubmissionsPerCpuArchitecture() . '
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
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            echo 'UserStatistics failed:' . $e->getMessage();
        }
    }

    private static function getCommonPackageUsageStatistics()
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

    private static function getCountryStatistics()
    {
        $total = Database::query('
        SELECT
            COUNT(countryCode)
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $countries = Database::query('
        SELECT
            countries.name AS country,
            COUNT(countryCode) AS count
        FROM
            pkgstats_users
            JOIN countries
            ON pkgstats_users.countryCode = countries.code
        WHERE
            pkgstats_users.time >= ' . self::getRangeTime() . '
        GROUP BY
            pkgstats_users.countryCode
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

    private static function getMirrorStatistics()
    {
        $total = Database::query('
        SELECT
            COUNT(mirror)
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $mirrors = Database::query('
        SELECT
            mirror,
            COUNT(mirror) AS count
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
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

    private static function getMirrorCountryStatistics()
    {
        $total = Database::query('
        SELECT
            COUNT(countryCode)
        FROM
            mirrors
        ')->fetchColumn();
        $countries = Database::query('
        SELECT
            countries.name AS country,
            COUNT(countryCode) AS count
        FROM
            mirrors
            JOIN countries
            ON mirrors.countryCode = countries.code
        GROUP BY
            mirrors.countryCode
        HAVING
            count > ' . (floor($total / 100)) . '
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($countries as $country) {
            $list.= '<tr><th>' . $country['country'] . '</th><td>' . self::getBar($country['count'], $total) . '</td></tr>';
        }

        return $list;
    }

    private static function getMirrorProtocollStatistics()
    {
        $protocolls = array(
            'http' => 0,
            'ftp' => 0
        );
        $total = Database::query('
        SELECT
            COUNT(mirror)
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        foreach ($protocolls as $protocoll => $count) {
            $protocolls[$protocoll] = Database::query('
            SELECT
                COUNT(mirror)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
                AND mirror LIKE \'' . $protocoll . '%\'
            ')->fetchColumn();
        }
        arsort($protocolls);
        $list = '';
        foreach ($protocolls as $protocoll => $count) {
            $list.= '<tr><th>' . $protocoll . '</th><td>' . self::getBar($count, $total) . '</td></tr>';
        }

        return $list;
    }

    private static function getSubmissionsPerArchitecture()
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
            $list.= '<tr><th>' . $arch['name'] . '</th><td>' . self::getBar($arch['count'], $total) . '</td></tr>';
        }

        return $list;
    }

    private static function getSubmissionsPerCpuArchitecture()
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
            $list.= '<tr><th>' . $arch['name'] . '</th><td>' . self::getBar($arch['count'], $total) . '</td></tr>';
        }

        return $list;
    }

}
