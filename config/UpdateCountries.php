#!/usr/bin/php
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

namespace archportal\config;

require (__DIR__ . '/../lib/AutoLoad.php');

use archportal\lib\Database;
use PDO;
use SimpleXMLElement;

spl_autoload_register('archportal\lib\AutoLoad::loadClass');
set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

$xml = new SimpleXMLElement(__DIR__ . '/country_names_and_code_elements_xml.xml', 0, true);

Database::beginTransaction();
Database::query('DELETE FROM countries');

$insertCountry = Database::prepare('
    INSERT INTO
        countries
    SET
        code = :code,
        name = :name
    ');

foreach ($xml->{'ISO_3166-1_Entry'} as $entry) {
    $insertCountry->bindValue('code', $entry->{'ISO_3166-1_Alpha-2_Code_element'}, PDO::PARAM_STR);
    $insertCountry->bindValue('name', $entry->{'ISO_3166-1_Country_name'}, PDO::PARAM_STR);
    $insertCountry->execute();
}

Database::commit();
