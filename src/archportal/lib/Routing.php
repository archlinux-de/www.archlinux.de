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

class Routing
{

    /** @var array */
    private static $pages = array(
        'GetFileFromMirror' => 'pages\GetFileFromMirror',
        'GetOpenSearch' => 'pages\GetOpenSearch',
        'GetRecentNews' => 'pages\GetRecentNews',
        'GetRecentPackages' => 'pages\GetRecentPackages',
        'MirrorStatus' => 'pages\MirrorStatus',
        'NotFound' => 'pages\NotFound',
        'PackageDetails' => 'pages\PackageDetails',
        'Packagers' => 'pages\Packagers',
        'Packages' => 'pages\Packages',
        'PackagesSuggest' => 'pages\PackagesSuggest',
        'Start' => 'pages\Start'
    );
    /** @var array */
    private static $statisticsPages = array(
        'FunStatistics' => 'pages\statistics\FunStatistics',
        'ModuleStatistics' => 'pages\statistics\ModuleStatistics',
        'PackageStatistics' => 'pages\statistics\PackageStatistics',
        'PostPackageList' => 'pages\statistics\PostPackageList',
        'RepositoryStatistics' => 'pages\statistics\RepositoryStatistics',
        'Statistics' => 'pages\statistics\Statistics',
        'UserStatistics' => 'pages\statistics\UserStatistics'
    );
    /** @var array */
    private static $legacyPages = array(
        'ArchitectureDifferences' => 'pages\legacy\ArchitectureDifferences',
        'MirrorProblems' => 'pages\legacy\MirrorProblems',
        'MirrorStatusJSON' => 'pages\legacy\MirrorStatusJSON',
        'MirrorStatusReflector' => 'pages\legacy\MirrorStatusReflector'
    );

    /**
     * @param string $page
     * @return string
     */
    public static function getPageClass(string $page): string
    {
        if (Config::get('common', 'statistics')) {
            self::$pages = array_merge(self::$pages, self::$statisticsPages);
        }
        if (Config::get('common', 'legacysites')) {
            self::$pages = array_merge(self::$pages, self::$legacyPages);
        }

        if (isset(self::$pages[$page])) {
            $class = self::$pages[$page];
        } else {
            $class = self::$pages['NotFound'];
        }

        return 'archportal\\' . $class;
    }
}
