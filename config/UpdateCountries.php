#!/usr/bin/env php
<?php

declare (strict_types = 1);

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

require __DIR__.'/../vendor/autoload.php';

use archportal\lib\Database;

set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

$geoIP = new \GeoIP();
$countries = array_combine($geoIP->GEOIP_COUNTRY_CODES, $geoIP->GEOIP_COUNTRY_NAMES);

Database::beginTransaction();
Database::query('DELETE FROM countries');

$insertCountry = Database::prepare(
    '
        INSERT INTO
            countries
        SET
            code = :code,
            name = :name
        '
);

foreach ($countries as $code => $name) {
    $insertCountry->bindValue('code', $code, PDO::PARAM_STR);
    $insertCountry->bindValue('name', $name, PDO::PARAM_STR);
    $insertCountry->execute();
}

Database::commit();
