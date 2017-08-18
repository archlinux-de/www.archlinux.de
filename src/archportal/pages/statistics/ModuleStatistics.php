<?php

namespace archportal\pages\statistics;

use archportal\lib\ObjectStore;
use archportal\lib\StatisticsPage;
use Doctrine\DBAL\Driver\Connection;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleStatistics extends StatisticsPage
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
        $this->setTitle('Module statistics');
        if (!($body = $this->objectStore->getObject('ModuleStatistics'))) {
            throw new NotFoundHttpException('No data found!');
        }
        $this->setBody($body);
    }

    public function updateDatabaseCache()
    {
        try {
            $this->database->beginTransaction();
            self::$barColors = self::MultiColorFade(self::$barColorArray);
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
                    <td>'.number_format((float) $log['sumcount']).'</td>
                </tr>
                <tr>
                    <th>Number of different modules</th>
                    <td>'.number_format((float) $log['diffcount']).'</td>
                </tr>
                <tr>
                    <th>Lowest number of installed modules</th>
                    <td>'.number_format((float) $log['mincount']).'</td>
                </tr>
                <tr>
                    <th>Highest number of installed modules</th>
                    <td>'.number_format((float) $log['maxcount']).'</td>
                </tr>
                <tr>
                    <th>Average number of installed modules</th>
                    <td>'.number_format((float) $log['avgcount']).'</td>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Popular modules</th>
                </tr>
                '.$this->getPopularModules().'
            </table>
            </div>
            ';
            $this->objectStore->addObject('ModuleStatistics', $body);
            $this->database->commit();
        } catch (RuntimeException $e) {
            $this->database->rollBack();
            echo 'ModuleStatistics failed:'.$e->getMessage();
        }
    }

    /**
     * @return array
     */
    private function getCommonModuleUsageStatistics(): array
    {
        return $this->database->query('
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
                time >= '.self::getRangeTime().'
                AND modules IS NOT NULL
        ')->fetchColumn();
        $modules = $this->database->query('
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
                count >= '.(floor($total / 100)).'
            ORDER BY
                count DESC,
                name ASC
        ');
        $list = '<tr><td colspan="2"><div><table class="pretty-table" style="border:none;">';
        foreach ($modules as $module) {
            $list .= '<tr><td style="width: 200px;">'.$module['name'].'</td><td>'.self::getBar((int) $module['count'],
                    $total).'</td></tr>';
        }
        $list .= '</table></div></td></tr>';

        return $list;
    }
}
