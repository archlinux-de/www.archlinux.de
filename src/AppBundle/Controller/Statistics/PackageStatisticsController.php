<?php

namespace AppBundle\Controller\Statistics;

use archportal\lib\ObjectStore;
use archportal\lib\StatisticsPage;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use archportal\lib\IDatabaseCachable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PackageStatisticsController extends Controller implements IDatabaseCachable
{
    /** @var Connection */
    private $database;
    /** @var ObjectStore */
    private $objectStore;
    /** @var StatisticsPage */
    private $statisticsPage;

    /**
     * @param Connection $connection
     * @param ObjectStore $objectStore
     * @param StatisticsPage $statisticsPage
     */
    public function __construct(Connection $connection, ObjectStore $objectStore, StatisticsPage $statisticsPage)
    {
        $this->database = $connection;
        $this->objectStore = $objectStore;
        $this->statisticsPage = $statisticsPage;
    }

    /**
     * @Route("/statistics/package", methods={"GET"})
     * @return Response
     */
    public function packageAction(): Response
    {
        if (!($body = $this->objectStore->getObject('PackageStatistics'))) {
            throw new NotFoundHttpException('No data found!');
        }
        return $this->render('statistics/statistic.html.twig', [
            'title' => 'Package statistics',
            'body' => $body
        ]);
    }

    public function updateDatabaseCache()
    {
        try {
            $this->database->beginTransaction();
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
        } catch (\RuntimeException $e) {
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
            (SELECT COUNT(*)
                FROM pkgstats_users
                WHERE time >= ' . $this->statisticsPage->getRangeTime() . ') AS submissions,
            (SELECT COUNT(*)
                FROM (SELECT *
                    FROM pkgstats_users
                    WHERE time >= ' . $this->statisticsPage->getRangeTime() . ' GROUP BY ip) AS temp) AS differentips,
            (SELECT MIN(time)
                FROM pkgstats_users
                WHERE time >= ' . $this->statisticsPage->getRangeTime() . ') AS minvisited,
            (SELECT MAX(time)
                FROM pkgstats_users
                WHERE time >= ' . $this->statisticsPage->getRangeTime() . ') AS maxvisited,
            (SELECT SUM(count)
                FROM pkgstats_packages
                WHERE month >= ' . $this->statisticsPage->getRangeYearMonth() . ') AS sumcount,
            (SELECT COUNT(*)
                FROM (SELECT DISTINCT pkgname
                    FROM pkgstats_packages
                    WHERE month >= ' . $this->statisticsPage->getRangeYearMonth() . ') AS diffpkgs) AS diffcount,
            (SELECT MIN(packages)
                FROM pkgstats_users
                WHERE time >= ' . $this->statisticsPage->getRangeTime() . ') AS mincount,
            (SELECT MAX(packages)
                FROM pkgstats_users
                WHERE time >= ' . $this->statisticsPage->getRangeTime() . ') AS maxcount,
            (SELECT AVG(packages)
                FROM pkgstats_users
                WHERE time >= ' . $this->statisticsPage->getRangeTime() . ') AS avgcount
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
            time >= ' . $this->statisticsPage->getRangeTime() . '
        ')->fetchColumn();
        $arches = $this->database->query('
        SELECT
            COUNT(*) AS count,
            arch AS name
        FROM
            pkgstats_users
        WHERE
            time >= ' . $this->statisticsPage->getRangeTime() . '
        GROUP BY
            arch
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($arches as $arch) {
            $list .= '<tr><th>' . $arch['name'] . '</th><td>'
                . $this->statisticsPage->getBar($arch['count'], $total) . '</td></tr>';
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
            time >= ' . $this->statisticsPage->getRangeTime() . '
            AND cpuarch IS NOT NULL
        ')->fetchColumn();
        $arches = $this->database->query('
        SELECT
            COUNT(cpuarch) AS count,
            cpuarch AS name
        FROM
            pkgstats_users
        WHERE
            time >= ' . $this->statisticsPage->getRangeTime() . '
            AND cpuarch IS NOT NULL
        GROUP BY
            cpuarch
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($arches as $arch) {
            $list .= '<tr><th>' . $arch['name'] . '</th><td>'
                . $this->statisticsPage->getBar($arch['count'], $total) . '</td></tr>';
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
            ')->fetchAll(\PDO::FETCH_COLUMN);
        $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . $this->statisticsPage->getRangeTime() . '
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
                    month >= ' . $this->statisticsPage->getRangeYearMonth() . '
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
            $countStm->bindParam('repositoryName', $repo, \PDO::PARAM_STR);
            $countStm->execute();
            $count = $countStm->fetchColumn();
            $totalStm->bindParam('repositoryName', $repo, \PDO::PARAM_STR);
            $totalStm->execute();
            $total = $totalStm->fetchColumn();
            $sortList[$id] = $count / $total;
            $list[$id++] = '<tr><th>' . $repo . '</th><td>'
                . $this->statisticsPage->getBar($count, $total) . '</td></tr>';
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
            ')->fetchAll(\PDO::FETCH_COLUMN);
        $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . $this->statisticsPage->getRangeTime() . '
        ')->fetchColumn();
        $packages = $this->database->prepare('
            SELECT
                pkgname,
                SUM(count) AS count
            FROM
                pkgstats_packages
            WHERE
                month >= ' . $this->statisticsPage->getRangeYearMonth() . '
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
            $packages->bindParam('repositoryName', $repo, \PDO::PARAM_STR);
            $packages->execute();
            if ($currentRepo != $repo) {
                $list .= '<tr><th>' . $repo . '</th>'
                    . '<td><div style="overflow:auto; max-height: 800px;">'
                    . '<table class="pretty-table" style="border:none;">';
            }
            foreach ($packages as $package) {
                $list .= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>'
                    . $this->statisticsPage->getBar((int)$package['count'], $total)
                    . '</td></tr>';
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
                time >= ' . $this->statisticsPage->getRangeTime() . '
        ')->fetchColumn();
        $packages = $this->database->query('
            SELECT
                pkgname,
                SUM(count) AS count
            FROM
                pkgstats_packages
            WHERE
                month >= ' . $this->statisticsPage->getRangeYearMonth() . '
                AND pkgname NOT IN (SELECT name FROM packages)
            GROUP BY
                pkgname
            HAVING
                count >= ' . (floor($total / 100)) . '
            ORDER BY
                count DESC,
                pkgname ASC
        ');
        $list = '<tr><th>unknown</th>'
            . '<td><div style="overflow:auto; max-height: 800px;"><table class="pretty-table" style="border:none;">';
        foreach ($packages as $package) {
            $list .= '<tr><td style="width: 200px;">' . $package['pkgname'] . '</td><td>'
                . $this->statisticsPage->getBar((int)$package['count'], $total)
                . '</td></tr>';
        }
        $list .= '</table></div></td></tr>';

        return $list;
    }
}
