<?php

/*
  Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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
use PDO;
use RuntimeException;

class FunStatistics extends StatisticsPage
{

    public function prepare()
    {
        $this->setTitle('Fun statistics');
        if (!($body = ObjectStore::getObject('FunStatistics'))) {
            $this->setStatus(Output::NOT_FOUND);
            $this->showFailure('No data found!');
        }
        $this->setBody($body);
    }

    public static function updateDatabaseCache()
    {
        try {
            Database::beginTransaction();
            $total = Database::query('
            SELECT
                COUNT(*)
            FROM
                pkgstats_users
            WHERE
                time >= ' . self::getRangeTime() . '
            ')->fetchColumn();
            $stm = Database::prepare('
            SELECT
                SUM(count)
            FROM
                pkgstats_packages
            WHERE
                pkgname = :pkgname
                AND month >= ' . self::getRangeYearMonth() . '
            GROUP BY
                pkgname
            ');
            self::$barColors = self::MultiColorFade(self::$barColorArray);
            $body = '<div class="box">
            <table id="packagedetails">
                <tr>
                    <th colspan="2" class="packagedetailshead">Browsers</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'Mozilla Firefox' => 'firefox',
                        'Chromium' => 'chromium',
                        'Konqueror' => 'kdebase-konqueror',
                        'Midori' => 'midori',
                        'Arora' => 'arora',
                        'Epiphany' => 'epiphany',
                        'Rekonq' => 'rekonq',
                        'Uzbl' => 'uzbl-core',
                        'Netsurf' => 'netsurf',
                        'Dillo' => 'dillo',
                        'Opera' => 'opera',
                        'luakit' => 'luakit'
                    )) . '
                <tr>
                    <th colspan="2" class="packagedetailshead">Editors</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'Vim' => array(
                            'vim',
                            'gvim'
                        ),
                        'Emacs' => array(
                            'emacs',
                            'xemacs'
                        ),
                        'Nano' => 'nano',
                        'Gedit' => 'gedit',
                        'Kate' => 'kdesdk-kate',
                        'Kwrite' => 'kdebase-kwrite',
                        'Jedit' => 'jedit',
                        'Ne' => 'ne',
                        'Jed' => 'jed',
                        'Medit' => 'medit',
                        'Vi' => 'vi',
                        'Mousepad' => 'mousepad',
                        'Nedit' => 'nedit',
                        'Joe' => 'joe',
                        'Leafpad' => 'leafpad',
                        'Geany' => 'geany'
                    )) . '
                    <th colspan="2" class="packagedetailshead">Desktop Environments</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'KDE SC' => 'kdebase-workspace',
                        'GNOME' => 'gnome-session',
                        'LXDE' => 'lxde-common',
                        'Xfce' => 'xfdesktop',
                        'Enlightenment' => 'enlightenment',
                        'MATE' => 'mate-desktop',
                        'Cinnamon' => 'cinnamon-desktop'
                    )) . '
                    <th colspan="2" class="packagedetailshead">File Managers</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'Dolphin' => 'kdebase-dolphin',
                        'Konqueror' => 'kdebase-konqueror',
                        'MC' => 'mc',
                        'Nautilus' => 'nautilus',
                        'Gnome-Commander' => 'gnome-commander',
                        'Krusader' => 'krusader',
                        'Pcmanfm' => 'pcmanfm',
                        'Thunar' => 'thunar'
                    )) . '
                    <th colspan="2" class="packagedetailshead">Window Managers</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'Openbox' => 'openbox',
                        'Fluxbox' => 'fluxbox',
                        'I3' => 'i3-wm',
                        'Compiz' => 'compiz-core',
                        'FVWM' => 'fvwm',
                        'Ratpoison' => 'ratpoison',
                        'Wmii' => 'wmii',
                        'Xmonad' => 'xmonad',
                        'Window Maker' => 'windowmaker',
                        'subtle' => 'subtle',
                        'awesome' => 'awesome'
                    )) . '
                    <th colspan="2" class="packagedetailshead">Media Players</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'Mplayer' => 'mplayer',
                        'Xine' => 'xine-lib',
                        'VLC' => 'vlc'
                    )) . '
                    <th colspan="2" class="packagedetailshead">Shells</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'Bash' => 'bash',
                        'Dash' => 'dash',
                        'Zsh' => 'zsh',
                        'Fish' => 'fish',
                        'Tcsh' => 'tcsh',
                        'Pdksh' => 'pdksh'
                    )) . '
                    <th colspan="2" class="packagedetailshead">Graphic Chipsets</th>
                </tr>
                    ' . self::getPackageStatistics($total, $stm, array(
                        'ATI' => array(
                            'xf86-video-ati',
                            'xf86-video-r128',
                            'xf86-video-mach64'
                        ),
                        'NVIDIA' => array(
                            'nvidia-304xx-utils',
                            'nvidia-utils',
                            'xf86-video-nouveau',
                            'xf86-video-nv'
                        ),
                        'Intel' => array(
                            'xf86-video-intel',
                            'xf86-video-i740'
                        )
                    )) . '
            </table>
            </div>
            ';
            ObjectStore::addObject('FunStatistics', $body);
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            echo 'FunStatistics failed:' . $e->getMessage();
        }
    }

    private static function getPackageStatistics($total, \PDOStatement $stm, $packages)
    {
        $packageArray = array();
        $list = '';
        foreach ($packages as $package => $pkgnames) {
            if (!is_array($pkgnames)) {
                $pkgnames = array(
                    $pkgnames
                );
            }
            foreach ($pkgnames as $pkgname) {
                $stm->bindValue('pkgname', htmlspecialchars($pkgname), PDO::PARAM_STR);
                $stm->execute();
                $count = $stm->fetchColumn() ? : 0;
                if (isset($packageArray[htmlspecialchars($package)])) {
                    $packageArray[htmlspecialchars($package)]+= $count;
                } else {
                    $packageArray[htmlspecialchars($package)] = $count;
                }
            }
        }
        arsort($packageArray);
        foreach ($packageArray as $name => $count) {
            // FIXME: calculation of totals is not that accurate
            // e.g. one person might have installed several nvidia drivers
            $count = min($count, $total);
            $list.= '<tr><th>' . $name . '</th><td>' . self::getBar($count, $total) . '</td></tr>';
        }

        return $list;
    }

}
