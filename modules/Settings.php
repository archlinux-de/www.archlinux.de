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
class Settings{


private $config = array();


public function __construct()
	{
	$this->config['locales']			= array('de' => 'de_DE.utf8',
							        'en' => 'en_US.utf8');
	$this->config['timezone']			= 'Europe/Berlin';
	$this->config['email']				= 'root@localhost';

	$this->config['sql_host'] 			= 'localhost';
	$this->config['sql_database'] 			= 'pkgdb';
	$this->config['sql_user']			= '';
	$this->config['sql_password']			= '';

	$this->config['pkgdb_mirror']			= 'http://mirrors.kernel.org/archlinux/';
	$this->config['pkgdb_repositories']		= array('core', 'extra', 'testing', 'community', 'community-testing');
	$this->config['pkgdb_architectures']		= array('i686', 'x86_64');

	$this->config['news_feed']			= '';
	$this->config['bbs_feed']			= '';

	$this->config['file_refresh']			= 60*60; //1 hour
	$this->config['allowed_mime_types']		= array('text/plain', 'text/xml', 'application/xml',
								'application/x-gzip', 'application/x-xz');
	$this->config['mirrorlist_url']			= 'http://www.archlinux.org/mirrorlist/i686/';

	$this->config['debug']				= false;
	$this->config['log_dir']			= '';

	if (file_exists('LocalSettings.php'))
		{
		include ('LocalSettings.php');
		}
	}


public function getValue($key)
	{
	return $this->config[$key];
	}


}

?>
