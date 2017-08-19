<?php

namespace AppBundle\Controller\Statistics;

use archportal\lib\IDatabaseCachable;
use archportal\lib\ObjectStore;
use archportal\lib\StatisticsPage;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RepositoryStatisticsController extends Controller implements IDatabaseCachable
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
     * @Route("/statistics/repository")
     * @return Response
     */
    public function repositoryAction(): Response
    {
        if (!($body = $this->objectStore->getObject('RepositoryStatistics'))) {
            throw new NotFoundHttpException('No data found!');
        }
        return $this->render('statistics/statistic.html.twig', [
            'title' => 'Repository statistics',
            'body' => $body
        ]);
    }

    public function updateDatabaseCache()
    {
        try {
            $this->database->beginTransaction();
            $data = $this->getCommonRepositoryStatistics();
            $body = '<div class="box">
            <table id="packagedetails">
                <tr>
                    <th colspan="2" style="margin:0;padding:0;"><h1 id="packagename">Repositories</h1></th>
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
                    <td>' . number_format((float)$data['groups']) . '</td>
                </tr>
                <tr>
                    <th>Packages</th>
                    <td>' . number_format((float)$data['packages']) . '</td>
                </tr>
                <tr>
                    <th>Files</th>
                    <td>' . number_format((float)$data['files']) . '</td>
                </tr>
                <tr>
                    <th>Size of file index</th>
                    <td>' . number_format((float)$data['file_index']) . '</td>
                </tr>
                <tr>
                    <th>Licenses</th>
                    <td>' . number_format((float)$data['licenses']) . '</td>
                </tr>
                <tr>
                    <th>Dependencies</th>
                    <td>' . number_format((float)$data['depends']) . '</td>
                </tr>
                <tr>
                    <th>Optional dependencies</th>
                    <td>' . number_format((float)$data['optdepends']) . '</td>
                </tr>
                <tr>
                    <th>Provides</th>
                    <td>' . number_format((float)$data['provides']) . '</td>
                </tr>
                <tr>
                    <th>Conflicts</th>
                    <td>' . number_format((float)$data['conflicts']) . '</td>
                </tr>
                <tr>
                    <th>Replaces</th>
                    <td>' . number_format((float)$data['replaces']) . '</td>
                </tr>
                <tr>
                    <th>Total size of repositories</th>
                    <td>' . $this->formatBytes((int)$data['csize']) . 'Byte</td>
                </tr>
                <tr>
                    <th>Total size of files</th>
                    <td>' . $this->formatBytes((int)$data['isize']) . 'Byte</td>
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
                    <td>&empty; ' . $this->formatBytes((int)$data['avgcsize']) . 'Byte</td>
                </tr>
                <tr>
                    <th>Size of files</th>
                    <td>&empty; ' . $this->formatBytes((int)$data['avgisize']) . 'Byte</td>
                </tr>
                <tr>
                    <th>Files per package</th>
                    <td>&empty; ' . number_format((float)$data['avgfiles'], 2) . '</td>
                </tr>
                <tr>
                    <th>Packages per packager</th>
                    <td>&empty; ' . number_format((float)$data['avgpkgperpackager'], 2) . '</td>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Repositories</th>
                </tr>
                    ' . $this->getRepositoryStatistics() . '
            </table>
            </div>
            ';
            $this->objectStore->addObject('RepositoryStatistics', $body);
            $this->database->commit();
        } catch (\RuntimeException $e) {
            $this->database->rollBack();
            echo 'RepositoryStatistics failed:' . $e->getMessage();
        }
    }

    /**
     * @return array
     */
    private function getCommonRepositoryStatistics(): array
    {
        return $this->database->query('
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

    /**
     * @return string
     */
    private function getRepositoryStatistics(): string
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
                COUNT(id) AS packages,
                SUM(csize) AS size
            FROM
                packages
            ')->fetch();
        $stm = $this->database->prepare('
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
            $stm->bindParam('repositoryName', $repo, \PDO::PARAM_STR);
            $stm->execute();
            $data = $stm->fetch();
            $list .= '<tr>
                <th>' . $repo . '</th>
                <td style="padding:0;margin:0;">
                    <div style="overflow:auto; max-height: 800px;">
                    <table class="pretty-table" style="border:none;">
                    <tr>
                        <td style="width: 50px;">Packages</td>
                        <td>' . $this->statisticsPage->getBar($data['packages'], $total['packages']) . '</td>
                    </tr>
                    <tr>
                        <td style="width: 50px;">Size</td>
                        <td>' . $this->statisticsPage->getBar((int)$data['size'], (int)$total['size']) . '</td>
                    </tr>
                    </table>
                    </div>
                </td>
            </tr>';
        }

        return $list;
    }

    /**
     * @param int $bytes
     *
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        if ($bytes >= $gb) { // GB
            $result = round($bytes / $gb, 2);
            $postfix = '&nbsp;G';
        } elseif ($bytes >= $mb) { // MB
            $result = round($bytes / $mb, 2);
            $postfix = '&nbsp;M';
        } elseif ($bytes >= $kb) { // KB
            $result = round($bytes / $kb, 2);
            $postfix = '&nbsp;K';
        } else {
            //  B
            $result = $bytes;
            $postfix = '&nbsp;';
        }

        return number_format($result, 2) . $postfix;
    }
}
