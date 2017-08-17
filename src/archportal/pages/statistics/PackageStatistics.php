<?php

namespace archportal\pages\statistics;

use archportal\lib\ObjectStore;
use archportal\lib\StatisticsPage;
use Doctrine\DBAL\Driver\Connection;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PackageStatistics extends StatisticsPage
{
    /** @var Connection */
    private $database;
    /** @var ObjectStore */
    private $objectStore;

    /**
     * @param Connection $connection
     * @param ObjectStore $objectStore
     */
    public function __construct(Connection $connection, ObjectStore $objectStore)
    {
        parent::__construct();
        $this->database = $connection;
        $this->objectStore = $objectStore;
    }

    public function prepare()
    {
        $this->setTitle('Package statistics');
        if (!($body = $this->objectStore->getObject('PackageStatistics'))) {
            throw new NotFoundHttpException('No data found!');
        }
        $this->setBody($body);
    }

    public function updateDatabaseCache()
    {
        try {
            $this->database->beginTransaction();
            self::$barColors = self::MultiColorFade(self::$barColorArray);
            $log = $this->getCommonPackageUsageStatistics();
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
                    <td>' . number_format((float)$log['sumcount']) . '</td>
                </tr>
                <tr>
                    <th>Number of different packages</th>
                    <td>' . number_format((float)$log['diffcount']) . '</td>
                </tr>
                <tr>
                    <th>Lowest number of installed packages</th>
                    <td>' . number_format((float)$log['mincount']) . '</td>
                </tr>
                <tr>
                    <th>Highest number of installed packages</th>
                    <td>' . number_format((float)$log['maxcount']) . '</td>
                </tr>
                <tr>
                    <th>Average number of installed packages</th>
                    <td>' . number_format((float)$log['avgcount']) . '</td>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Submissions per architectures</th>
                </tr>
                ' . $this->getSubmissionsPerArchitecture() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Submissions per CPU architectures</th>
                </tr>
                ' . $this->getSubmissionsPerCpuArchitecture() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Installed packages per repository</th>
                </tr>
                ' . $this->getPackagesPerRepository() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Popular packages per repository</th>
                </tr>
                ' . $this->getPopularPackagesPerRepository() . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Popular unofficial packages</th>
                </tr>
                ' . $this->getPopularUnofficialPackages() . '
            </table>
            </div>
            ';
            $this->objectStore->addObject('PackageStatistics', $body);
            $this->database->commit();
        } catch (RuntimeException $e) {
            $this->database->rollBack();
            echo 'PackageStatistics failed:' . $e->getMessage();
        }
    }

    /**
     * @return array
     */
    private function getCommonPackageUsageStatistics(): array
    {
        return $this->database->query('
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
    private function getSubmissionsPerArchitecture(): string
    {
        $total = $this->database->query('
        SELECT
            COUNT(*)
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $arches = $this->database->query('
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
    private function getSubmissionsPerCpuArchitecture(): string
    {
        $total = $this->database->query('
        SELECT
            COUNT(*)
        FROM
            pkgstats_users
        WHERE
            time >= ' . self::getRangeTime() . '
            AND cpuarch IS NOT NULL
        ')->fetchColumn();
        $arches = $this->database->query('
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
    private function getPackagesPerRepository(): string
    {
        $repos = $this->database->query('
            SELECT DISTINCT
                name
            FROM
                repositories
            WHERE
                testing = 0
                AND name NOT LIKE "%unstable"
                AND name NOT LIKE "%staging"
            ')->fetchAll(PDO::FETCH_COLUMN);
        $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $countStm = $this->database->prepare('
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
        $totalStm = $this->database->prepare('
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
    private function getPopularPackagesPerRepository(): string
    {
        $repos = $this->database->query('
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
        $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $packages = $this->database->prepare('
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
                $list .= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>' . self::getBar((int)$package['count'],
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
    private function getPopularUnofficialPackages(): string
    {
        $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
        ')->fetchColumn();
        $packages = $this->database->query('
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
            $list .= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>' . self::getBar((int)$package['count'],
                    $total) . '</td></tr>';
        }
        $list .= '</table></div></td></tr>';

        return $list;
    }
}
