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

namespace archportal\lib;

abstract class StatisticsPage extends Page implements IDatabaseCachable
{

    private static $rangeMonths = 3;
    protected static $barColors = array();
    protected static $barColorArray = array(
        '8B0000',
        'FF8800',
        '006400'
    );

    /**
     * @param array $hexarray
     * @return array
     *
     * see http://at.php.net/manual/de/function.hexdec.php#66780
     */
    protected static function MultiColorFade($hexarray)
    {
        $steps = 101;
        $total = count($hexarray);
        $gradient = array();
        $fixend = 2;
        $passages = $total - 1;
        $stepsforpassage = floor($steps / $passages);
        $stepsremain = $steps - ($stepsforpassage * $passages);
        $stepsforthis = 0;
        for ($pointer = 0; $pointer < $total - 1; $pointer++) {
            $hexstart = $hexarray[$pointer];
            $hexend = $hexarray[$pointer + 1];
            if ($stepsremain > 0) {
                if ($stepsremain--) {
                    $stepsforthis = $stepsforpassage + 1;
                }
            } else {
                $stepsforthis = $stepsforpassage;
            }
            if ($pointer > 0) {
                $fixend = 1;
            }
            $start['r'] = hexdec(substr($hexstart, 0, 2));
            $start['g'] = hexdec(substr($hexstart, 2, 2));
            $start['b'] = hexdec(substr($hexstart, 4, 2));
            $end['r'] = hexdec(substr($hexend, 0, 2));
            $end['g'] = hexdec(substr($hexend, 2, 2));
            $end['b'] = hexdec(substr($hexend, 4, 2));
            $step['r'] = ($start['r'] - $end['r']) / ($stepsforthis);
            $step['g'] = ($start['g'] - $end['g']) / ($stepsforthis);
            $step['b'] = ($start['b'] - $end['b']) / ($stepsforthis);
            for ($i = 0; $i <= $stepsforthis - $fixend; $i++) {
                $rgb['r'] = floor($start['r'] - ($step['r'] * $i));
                $rgb['g'] = floor($start['g'] - ($step['g'] * $i));
                $rgb['b'] = floor($start['b'] - ($step['b'] * $i));
                $hex['r'] = sprintf('%02x', ($rgb['r']));
                $hex['g'] = sprintf('%02x', ($rgb['g']));
                $hex['b'] = sprintf('%02x', ($rgb['b']));
                $gradient[] = strtoupper(implode(NULL, $hex));
            }
        }
        $gradient[] = $hexarray[$total - 1];

        return $gradient;
    }

    /**
     * @param int $value
     * @param int $total
     * @return string
     */
    protected static function getBar($value, $total)
    {
        if ($total <= 0) {
            return '';
        }
        $percent = ($value / $total) * 100;
        if ($percent > 100) {
            return '';
        }
        $color = self::$barColors[(int) round($percent)];

        return '<table style="width:100%;">
            <tr>
                <td style="padding:0px;margin:0px;">
                    <div style="background-color:#' . $color . ';width:' . round($percent) . '%;"
        title="' . number_format($value) . ' of ' . number_format($total) . '">
            &nbsp;
                </div>
                </td>
                <td style="padding:0px;margin:0px;width:80px;text-align:right;color:#' . $color . ';">
                    ' . number_format($percent, 2) . '&nbsp;%
                </td>
            </tr>
        </table>';
    }

    /**
     * @return int
     */
    protected static function getRangeTime()
    {
        return strtotime(date('1-m-Y', strtotime('now -' . self::$rangeMonths . ' months')));
    }

    /**
     * @return string
     */
    protected static function getRangeYearMonth()
    {
        return date('Ym', self::getRangeTime());
    }

}
