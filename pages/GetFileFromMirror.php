<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

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

class GetFileFromMirror extends Page {

private $range = 86400; // 1 day


public function prepare()
	{
	$this->Output->redirectToUrl(
		$this->getMirror().
		$this->Input->Get->getString('file', '')
		);
	}

private function getMirror()
	{
	$country = '';
	$mirror = '';
	// remove ipv6 prefix
	$ip = ltrim($this->Input->Server->getString('REMOTE_ADDR', ''), ':a-f');

	if (function_exists('geoip_country_name_by_name') && !empty($ip))
		{
		// let's ignore any lookup errors
		$errorReporting = error_reporting(E_ALL ^ E_NOTICE);
		restore_error_handler();
		$country = geoip_country_name_by_name($ip);
		set_error_handler('ErrorHandler');
		error_reporting($errorReporting);
		}

	if (empty($country))
		{
		$country = $this->Settings->getValue('country');
		}

	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				host
			FROM
				mirrors
			WHERE
				lastsync >= ?
				AND (country = ? OR country = \'Any\')
			ORDER BY RAND() LIMIT 1
			');
		$stm->bindInteger($this->Input->getTime() - $this->range);
		$stm->bindString($country);

		$mirror = $stm->getColumn();

		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		}

	if (empty($mirror))
		{
		$mirror = $this->Settings->getValue('mirror');
		}

	return $mirror;
	}

}

?>
