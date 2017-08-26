<?php

namespace AppBundle\Controller\Statistics;

use Doctrine\DBAL\Driver\Statement;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use archportal\lib\IDatabaseCachable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class FunStatisticsController extends Controller implements IDatabaseCachable
{
    use StatisticsControllerTrait;
    private const TITLE = 'Fun statistics';

    /**
     * @Route("/statistics/fun", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function funAction(): Response
    {
        return $this->renderPage(self::TITLE);
    }

    public function updateDatabaseCache()
    {
        $total = $this->database->query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . $this->getRangeTime() . '
            ')->fetchColumn();
        $stm = $this->database->prepare('
            SELECT
                SUM(count)
            FROM
                pkgstats_packages
            WHERE
                pkgname = :pkgname
                AND month >= ' . $this->getRangeYearMonth() . '
            GROUP BY
                pkgname
            ');
        $body = '<table class="table table-sm">
                <colgroup>
                    <col class="w-25">
                    <col>
                </colgroup>
                <tr>
                    <th colspan="2" class="text-center">Browsers</th>
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
                    <th colspan="2" class="text-center">Editors</th>
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
                    <th colspan="2" class="text-center">Desktop Environments</th>
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
                    <th colspan="2" class="text-center">File Managers</th>
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
                    <th colspan="2" class="text-center">Window Managers</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                'Openbox' => 'openbox',
                'Fluxbox' => 'fluxbox',
                'I3' => 'i3-wm',
                'awesome' => 'awesome',
            )) . '
                    <th colspan="2" class="text-center">Media Players</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                'Mplayer' => 'mplayer',
                'Xine' => 'xine-lib',
                'VLC' => 'vlc',
            )) . '
                    <th colspan="2" class="text-center">Shells</th>
                </tr>
                    ' . $this->getPackageStatistics($total, $stm, array(
                'Bash' => 'bash',
                'Dash' => 'dash',
                'Zsh' => 'zsh',
                'Fish' => 'fish',
                'Tcsh' => 'tcsh',
            )) . '
                    <th colspan="2" class="text-center">Graphic Chipsets</th>
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
            ';
        $this->savePage(self::TITLE, $body);
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
            $list .= '<tr><th>' . $name . '</th><td>' . $this->getBar($count, $total) . '</td></tr>';
        }

        return $list;
    }
}
