<?php

namespace AppBundle\Controller\Statistics;

use archportal\lib\IDatabaseCachable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ModuleStatisticsController extends Controller implements IDatabaseCachable
{
    use StatisticsControllerTrait;
    private const TITLE = 'Module statistics';

    /**
     * @Route("/statistics/module", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function moduleAction(): Response
    {
        return $this->renderPage(self::TITLE);
    }

    public function updateDatabaseCache()
    {
        $log = $this->getCommonModuleUsageStatistics();
        $body = '<div class="box">
            <table id="packagedetails">
                <tr>
                    <th colspan="2" style="margin:0;padding:0;"><h1 id="packagename">Module usage</h1></th>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Common statistics</th>
                </tr>
                <tr>
                    <th>Sum of submitted modules</th>
                    <td>' . number_format((float)$log['sumcount']) . '</td>
                </tr>
                <tr>
                    <th>Number of different modules</th>
                    <td>' . number_format((float)$log['diffcount']) . '</td>
                </tr>
                <tr>
                    <th>Lowest number of installed modules</th>
                    <td>' . number_format((float)$log['mincount']) . '</td>
                </tr>
                <tr>
                    <th>Highest number of installed modules</th>
                    <td>' . number_format((float)$log['maxcount']) . '</td>
                </tr>
                <tr>
                    <th>Average number of installed modules</th>
                    <td>' . number_format((float)$log['avgcount']) . '</td>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Popular modules</th>
                </tr>
                ' . $this->getPopularModules() . '
            </table>
            </div>
            ';
        $this->savePage(self::TITLE, $body);
    }

    /**
     * @return array
     */
    private function getCommonModuleUsageStatistics(): array
    {
        return $this->database->query('
        SELECT
            (SELECT COUNT(*)
                FROM pkgstats_users
                WHERE time >= ' . $this->getRangeTime() . ' AND modules IS NOT NULL) AS submissions,
            (SELECT COUNT(*)
                FROM (SELECT * FROM pkgstats_users
                WHERE time >= ' . $this->getRangeTime() . '
                 AND modules IS NOT NULL GROUP BY ip) AS temp) AS differentips,
            (SELECT MIN(time)
                FROM pkgstats_users
                WHERE time >= ' . $this->getRangeTime() . ' AND modules IS NOT NULL) AS minvisited,
            (SELECT MAX(time)
                FROM pkgstats_users
                WHERE time >= ' . $this->getRangeTime() . ' AND modules IS NOT NULL) AS maxvisited,
            (SELECT SUM(count)
                FROM pkgstats_modules
                WHERE month >= ' . $this->getRangeYearMonth() . ') AS sumcount,
            (SELECT COUNT(*)
                FROM (SELECT DISTINCT name FROM pkgstats_modules
                WHERE month >= ' . $this->getRangeYearMonth() . ') AS diffpkgs) AS diffcount,
            (SELECT MIN(modules)
                FROM pkgstats_users
                WHERE time >= ' . $this->getRangeTime() . ') AS mincount,
            (SELECT MAX(modules)
                FROM pkgstats_users
                WHERE time >= ' . $this->getRangeTime() . ') AS maxcount,
            (SELECT AVG(modules)
                FROM pkgstats_users
                WHERE time >= ' . $this->getRangeTime() . ') AS avgcount
        ')->fetch();
    }

    /**
     * @return string
     */
    private function getPopularModules(): string
    {
        $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . $this->getRangeTime() . '
                AND modules IS NOT NULL
        ')->fetchColumn();
        $modules = $this->database->query('
            SELECT
                name,
                SUM(count) AS count
            FROM
                pkgstats_modules
            WHERE
                month >= ' . $this->getRangeYearMonth() . '
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
            $list .= '<tr><td style="width: 200px;">' . $module['name'] . '</td><td>' .
                $this->getBar((int)$module['count'], $total) . '</td></tr>';
        }
        $list .= '</table></div></td></tr>';

        return $list;
    }
}
