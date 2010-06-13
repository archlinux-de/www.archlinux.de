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

class GetFileFromMirror extends Modul implements IOutput {

private $mirror = '';
private $file = '';
private $range = 86400; // 1 day


public function prepare()
	{
	try
		{
		$this->file = htmlspecialchars($this->Input->Get->getString('file'));

		if (!($this->mirror = $this->getMirror()))
			{
			header(Output::NOT_FOUND);
			echo '404 NOT FOUND';
			exit();
			}
		}
	catch (RequestException $e)
		{
		header(Output::NOT_FOUND);
		echo '404 NOT FOUND';
		exit();
		}
	}

public function show()
	{
	$this->Output->redirectToUrl($this->mirror.$this->file);
	}

public function getMirror()
	{
	$mirror = false;

	if (function_exists('geoip_country_name_by_name'))
		{
		// let's ignore any lookup errors
		restore_error_handler();
		// remove ipv6 prefix
		$ip = ltrim($this->Input->Server->getString('REMOTE_ADDR', ''), ':a-f');
		$country = geoip_country_name_by_name($ip);
		if ($country === false)
			{
			$country = 'Germany';
			}
		set_error_handler('ErrorHandler');
		}
	else
		{
		$country = 'Germany';
		}

	$this->DB->connect(
		$this->Settings->getValue('sql_host'),
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database')
		);

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
			');
		$stm->bindInteger($this->Input->getTime() - $this->range);
		$stm->bindString($country);

		$mirrors = $stm->getColumnSet()->toArray();
		$mirror = $mirrors[array_rand($mirrors)];

		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		}

	return $mirror;
	}

}

?>
