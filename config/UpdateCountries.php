#!/usr/bin/php
<?php
/*
	Copyright 2002-2012 Pierre Schmitz <pierre@archlinux.de>

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

require (__DIR__.'/../lib/Config.php');
require (__DIR__.'/../lib/Exceptions.php');
require (__DIR__.'/../lib/AutoLoad.php');


$download = new Download('http://www.iso.org/iso/home/standards/country_codes/country_names_and_code_elements_xml.htm');
$content = file_get_contents($download->getFile());
// They send a utf8 encoded file but set the wrong encoding in the xml doctype
$content = str_replace('encoding="ISO-8859-1"', 'encoding="UTF-8"', $content);
$xml = new SimpleXMLElement($content);

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

?>
