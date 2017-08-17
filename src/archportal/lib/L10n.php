<?php
/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\lib;

class L10n
{
    /** @var array */
    private $localeInfo = array();
    /** @var string */
    private $locale = '';

    public function __construct()
    {
        $this->locale = Config::get('L10n', 'locale');

        putenv('LC_ALL='.$this->locale);
        setlocale(LC_ALL, $this->locale);
        date_default_timezone_set(Config::get('L10n', 'timezone'));
        $this->localeInfo = localeconv();
        bindtextdomain('archportal', __DIR__.'/../l10n');
        textdomain('archportal');
    }

    /**
     * @return string
     */
    private function getLocalePath(): string
    {
        return __DIR__.'/../l10n/'.strtok($this->locale, '.');
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function getText(string $text): string
    {
        return gettext($text);
    }

    /**
     * @param string $singular
     * @param string $plural
     * @param int    $count
     *
     * @return string
     */
    public function ngetText(string $singular, string $plural, int $count): string
    {
        return ngettext($singular, $plural, $count);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getTextFile(string $name):string
    {
        return file_get_contents($this->getLocalePath().'/'.$name.'.html');
    }

    /**
     * @param int|null $timestamp
     *
     * @return string
     */
    public function getDate($timestamp = null): string
    {
        return date($this->getText('Y-m-d'), $timestamp);
    }

    /**
     * @param int|null $timestamp
     *
     * @return string
     */
    public function getDateTime($timestamp = null): string
    {
        return date($this->getText('Y-m-d H:i'), $timestamp);
    }

    /**
     * @param int|null $timestamp
     *
     * @return string
     */
    public function getGmDateTime($timestamp = null): string
    {
        return gmdate('Y-m-d H:i', $timestamp);
    }

    /**
     * @param int $seconds
     *
     * @return string
     */
    public function getEpoch(int $seconds): string
    {
        $minutes = 60;
        $hours = 60 * $minutes;
        $days = 24 * $hours;
        $weeks = 7 * $days;
        $months = 4 * $weeks;
        $years = 12 * $months;
        if ($seconds >= $years) {
            $result = intval(round($seconds / $years));

            return sprintf($this->ngetText('%d year', '%d years', $result), $result);
        } elseif ($seconds >= $months) {
            $result = (int) round($seconds / $months);

            return sprintf($this->ngetText('%d month', '%d months', $result), $result);
        } elseif ($seconds >= $weeks) {
            $result = (int) round($seconds / $weeks);

            return sprintf($this->ngetText('%d week', '%d weeks', $result), $result);
        } elseif ($seconds >= $days) {
            $result = (int) round($seconds / $days);

            return sprintf($this->ngetText('%d day', '%d days', $result), $result);
        } elseif ($seconds >= $hours) {
            $result = (int) round($seconds / $hours, 2);

            return sprintf($this->getText('%.2f hours'), $result);
        } elseif ($seconds >= $minutes) {
            $result = (int) round($seconds / $minutes, 2);

            return sprintf($this->getText('%.2f minutes'), $result);
        } else {
            $result = (int) round($seconds, 2);

            return sprintf($this->getText('%.2f seconds'), $result);
        }
    }
}
