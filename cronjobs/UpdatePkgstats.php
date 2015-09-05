#!/usr/bin/env php
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

require(__DIR__ . '/../vendor/autoload.php');

use archportal\lib\Config;
use archportal\lib\CronJob;
use archportal\lib\Routing;
use archportal\lib\StatisticsPage;

set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

class UpdatePkgstats extends CronJob
{

    public function execute()
    {
        if (Config::get('common', 'statistics')) {
            foreach (array(
        'RepositoryStatistics',
        'PackageStatistics',
        'ModuleStatistics',
        'UserStatistics',
        'FunStatistics'
            ) as $page) {
                /** @var StatisticsPage $pageClass */
                $pageClass = Routing::getPageClass($page);
                $pageClass::updateDatabaseCache();
            }
        }
    }

}

UpdatePkgstats::run();
