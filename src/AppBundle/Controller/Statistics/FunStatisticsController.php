<?php

namespace AppBundle\Controller\Statistics;

use archportal\lib\ObjectStore;
use archportal\lib\StatisticsPage;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use archportal\lib\IDatabaseCachable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class FunStatisticsController extends Controller implements IDatabaseCachable
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
     * @Route("/statistics/fun", methods={"GET"})
     * @return Response
     */
    public function funAction(): Response
    {
        if (!($body = $this->objectStore->getObject('FunStatistics'))) {
            throw new NotFoundHttpException('No data found!');
        }
        return $this->render('statistics/statistic.html.twig', [
            'title' => 'Fun statistics',
            'body' => $body
        ]);
    }

    public function updateDatabaseCache()
    {
        try {
            $this->database->beginTransaction();
            $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . $this->statisticsPage->getRangeTime() . '
            ')->fetchColumn();
            $stm = $this->database->prepare('
            SELECT
                SUM(count)
            FROM
                pkgstats_packages
            WHERE
                pkgname = :pkgname
                AND month >= ' . $this->statisticsPage->getRangeYearMonth() . '
            GROUP BY
                pkgname
            ');
            $body = '<div class="box">
            <table id="packagedetails">
                <tr>
                    <th colspan="2" class="packagedetailshead">Browsers</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'Mozilla Firefox' => 'firefox',
                    'Chromium' => 'chromium',
                    'Konqueror' => 'kdebase-konqueror',
                    'Midori' => 'midori',
                    'Epiphany' => 'epiphany',
                    'Opera' => 'opera',
                )) . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Editors</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'Vim' => array(
                        'vim',
                        'gvim',
                    ),
                    'Emacs' => array(
                        'emacs',
                        'xemacs',
                    ),
                    'Nano' => 'nano',
                    'Gedit' => 'gedit',
                    'Kate' => array('kdesdk-kate', 'kate'),
                    'Kwrite' => array('kdebase-kwrite', 'kwrite'),
                    'Vi' => 'vi',
                    'Mousepad' => 'mousepad',
                    'Leafpad' => 'leafpad',
                    'Geany' => 'geany',
                    'Pluma' => 'pluma',
                )) . '
                    <th colspan="2" class="packagedetailshead">Desktop Environments</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'KDE SC' => array('kdebase-workspace', 'plasma-workspace'),
                    'GNOME' => 'gnome-shell',
                    'LXDE' => 'lxde-common',
                    'Xfce' => 'xfdesktop',
                    'Enlightenment' => array('enlightenment', 'enlightenment16'),
                    'MATE' => 'mate-panel',
                    'Cinnamon' => 'cinnamon',
                )) . '
                    <th colspan="2" class="packagedetailshead">File Managers</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'Dolphin' => 'kdebase-dolphin',
                    'Konqueror' => 'kdebase-konqueror',
                    'MC' => 'mc',
                    'Nautilus' => 'nautilus',
                    'Pcmanfm' => 'pcmanfm',
                    'Thunar' => 'thunar',
                    'Caja' => 'caja',
                )) . '
                    <th colspan="2" class="packagedetailshead">Window Managers</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'Openbox' => 'openbox',
                    'Fluxbox' => 'fluxbox',
                    'I3' => 'i3-wm',
                    'awesome' => 'awesome',
                )) . '
                    <th colspan="2" class="packagedetailshead">Media Players</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'Mplayer' => 'mplayer',
                    'Xine' => 'xine-lib',
                    'VLC' => 'vlc',
                )) . '
                    <th colspan="2" class="packagedetailshead">Shells</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'Bash' => 'bash',
                    'Dash' => 'dash',
                    'Zsh' => 'zsh',
                    'Fish' => 'fish',
                    'Tcsh' => 'tcsh',
                )) . '
                    <th colspan="2" class="packagedetailshead">Graphic Chipsets</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                    'ATI' => array(
                        'xf86-video-ati',
                        'xf86-video-r128',
                        'xf86-video-mach64',
                    ),
                    'NVIDIA' => array(
                        'nvidia-304xx-utils',
                        'nvidia-utils',
                        'xf86-video-nouveau',
                        'xf86-video-nv',
                    ),
                    'Intel' => array(
                        'xf86-video-intel',
                        'xf86-video-i740',
                    ),
                )) . '
            </table>
            </div>
            ';
            $this->objectStore->addObject('FunStatistics', $body);
            $this->database->commit();
        } catch (\RuntimeException $e) {
            $this->database->rollBack();
            echo 'FunStatistics failed:' . $e->getMessage();
        }
    }

    /**
     * @param int $total
     * @param Statement $stm
     * @param array $packages
     *
     * @return string
     */
    private function getPackageStatistics(int $total, Statement $stm, array $packages): string
    {
        $packageArray = array();
        $list = '';
        foreach ($packages as $package => $pkgnames) {
            if (!is_array($pkgnames)) {
                $pkgnames = array(
                    $pkgnames,
                );
            }
            foreach ($pkgnames as $pkgname) {
                $stm->bindValue('pkgname', htmlspecialchars($pkgname), \PDO::PARAM_STR);
                $stm->execute();
                $count = $stm->fetchColumn() ?: 0;
                if (isset($packageArray[htmlspecialchars($package)])) {
                    $packageArray[htmlspecialchars($package)] += $count;
                } else {
                    $packageArray[htmlspecialchars($package)] = $count;
                }
            }
        }
        arsort($packageArray);
        foreach ($packageArray as $name => $count) {
            // FIXME: calculation of totals is not that accurate
            // e.g. one person might have installed several nvidia drivers
            $count = (int)min($count, $total);
            $list .= '<tr><th>' . $name . '</th><td>' . $this->statisticsPage->getBar($count, $total) . '</td></tr>';
        }

        return $list;
    }
}
