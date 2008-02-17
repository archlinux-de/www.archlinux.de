<?php
/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
*/
class Settings{


private $config = array();


public function __construct()
	{
	$this->config['locale']				= 'de_DE.utf8';
	$this->config['timezone']			= 'Europe/Berlin';

	$this->config['sql_database'] 			= 'll';
	$this->config['sql_user']			= '';
	$this->config['sql_password']			= '';

	$this->config['update_secret']			= '';
	$this->config['update_host']			= 'localhost';
	$this->config['update_url']			= '';

	$this->config['debug']				= false;

	$this->config['mirrors']			= array();

	if (file_exists('LocalSettings.php'))
		{
		include ('LocalSettings.php');
		}

	setlocale(LC_ALL, $this->config['locale']);
	date_default_timezone_set($this->config['timezone']);
	}


public function getValue($key)
	{
	return $this->config[$key];
	}


}

?>