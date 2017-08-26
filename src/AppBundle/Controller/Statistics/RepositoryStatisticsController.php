<?php

namespace AppBundle\Controller\Statistics;

use archportal\lib\IDatabaseCachable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RepositoryStatisticsController extends Controller implements IDatabaseCachable
{
    use StatisticsControllerTrait;
    private const TITLE = 'Repository statistics';


    /**
     * @Route("/statistics/repository", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function repositoryAction(): Response
    {
        return $this->renderPage(self::TITLE);
    }

    public function updateDatabaseCache()
    {
        $data = $this->getCommonRepositoryStatistics();
        $body = '<table class="table table-sm">
                <colgroup>
                    <col class="w-25">
                    <col>
                </colgroup>
                <tr>
                    <th colspan="2" class="text-center">Overview</th>
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
                    <th colspan="2" class="text-center">Averages</th>
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
                    <th>Packages per packager</th>
                    <td>&empty; ' . number_format((float)$data['avgpkgperpackager'], 2) . '</td>
                </tr>
                <tr>
                    <th colspan="2" class="text-center">Repositories</th>
                </tr>
                    ' . $this->getRepositoryStatistics() . '
            </table>
            </div>
            ';
        $this->savePage(self::TITLE, $body);
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
            ) AS avgpkgperpackager
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
                <td>
                <div class="row">
                    <div class="col-2">Packages</div>
                    <div class="col-10">' . $this->getBar($data['packages'], $total['packages']) . '</div>
                </div>
                <div class="row">
                    <div class="col-2">Size</div>
                    <div class="col-10">' . $this->getBar((int)$data['size'], (int)$total['size']) . '</div>
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
