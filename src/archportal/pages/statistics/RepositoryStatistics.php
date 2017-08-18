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

use archportal\lib\ObjectStore;
use archportal\lib\StatisticsPage;
use Doctrine\DBAL\Driver\Connection;
use PDO;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RepositoryStatistics extends StatisticsPage
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

    public function prepare(Request $request)
    {
        $this->setTitle('Repository statistics');
        if (!($body = $this->objectStore->getObject('RepositoryStatistics'))) {
            throw new NotFoundHttpException('No data found!');
        }
        $this->setBody($body);
    }

    public function updateDatabaseCache()
    {
        try {
            $this->database->beginTransaction();
            self::$barColors = self::MultiColorFade(self::$barColorArray);
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
                    <td>'.$data['architectures'].'</td>
                </tr>
                <tr>
                    <th>Repositories</th>
                    <td>'.$data['repositories'].'</td>
                </tr>
                <tr>
                    <th>Groups</th>
                    <td>'.number_format((float) $data['groups']).'</td>
                </tr>
                <tr>
                    <th>Packages</th>
                    <td>'.number_format((float) $data['packages']).'</td>
                </tr>
                <tr>
                    <th>Files</th>
                    <td>'.number_format((float) $data['files']).'</td>
                </tr>
                <tr>
                    <th>Size of file index</th>
                    <td>'.number_format((float) $data['file_index']).'</td>
                </tr>
                <tr>
                    <th>Licenses</th>
                    <td>'.number_format((float) $data['licenses']).'</td>
                </tr>
                <tr>
                    <th>Dependencies</th>
                    <td>'.number_format((float) $data['depends']).'</td>
                </tr>
                <tr>
                    <th>Optional dependencies</th>
                    <td>'.number_format((float) $data['optdepends']).'</td>
                </tr>
                <tr>
                    <th>Provides</th>
                    <td>'.number_format((float) $data['provides']).'</td>
                </tr>
                <tr>
                    <th>Conflicts</th>
                    <td>'.number_format((float) $data['conflicts']).'</td>
                </tr>
                <tr>
                    <th>Replaces</th>
                    <td>'.number_format((float) $data['replaces']).'</td>
                </tr>
                <tr>
                    <th>Total size of repositories</th>
                    <td>'.$this->formatBytes((int) $data['csize']).'Byte</td>
                </tr>
                <tr>
                    <th>Total size of files</th>
                    <td>'.$this->formatBytes((int) $data['isize']).'Byte</td>
                </tr>
                <tr>
                    <th>Packager</th>
                    <td>'.$data['packagers'].'</td>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Averages</th>
                </tr>
                <tr>
                    <th>Size of packages</th>
                    <td>&empty; '.$this->formatBytes((int) $data['avgcsize']).'Byte</td>
                </tr>
                <tr>
                    <th>Size of files</th>
                    <td>&empty; '.$this->formatBytes((int) $data['avgisize']).'Byte</td>
                </tr>
                <tr>
                    <th>Files per package</th>
                    <td>&empty; '.number_format((float) $data['avgfiles'], 2).'</td>
                </tr>
                <tr>
                    <th>Packages per packager</th>
                    <td>&empty; '.number_format((float) $data['avgpkgperpackager'], 2).'</td>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Repositories</th>
                </tr>
                    '.$this->getRepositoryStatistics().'
            </table>
            </div>
            ';
            $this->objectStore->addObject('RepositoryStatistics', $body);
            $this->database->commit();
        } catch (RuntimeException $e) {
            $this->database->rollBack();
            echo 'RepositoryStatistics failed:'.$e->getMessage();
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
            ')->fetchAll(PDO::FETCH_COLUMN);
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
            $stm->bindParam('repositoryName', $repo, PDO::PARAM_STR);
            $stm->execute();
            $data = $stm->fetch();
            $list .= '<tr>
                <th>'.$repo.'</th>
                <td style="padding:0;margin:0;">
                    <div style="overflow:auto; max-height: 800px;">
                    <table class="pretty-table" style="border:none;">
                    <tr>
                        <td style="width: 50px;">Packages</td>
                        <td>'.self::getBar($data['packages'], $total['packages']).'</td>
                    </tr>
                    <tr>
                        <td style="width: 50px;">Size</td>
                        <td>'.self::getBar((int) $data['size'], (int) $total['size']).'</td>
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

        return number_format($result, 2).$postfix;
    }
}
