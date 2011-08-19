#!/usr/bin/php
<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

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

system('mysqldump -d --compact -u'
	.'\''.escapeshellcmd(Config::get('Database', 'user')).'\''
	.' '
	.escapeshellcmd(strlen(Config::get('Database', 'password')) > 0 ? '-p\''.Config::get('Database', 'password').'\'' : '')
	.' '
	.'\''.escapeshellcmd(Config::get('Database', 'database')).'\''
	.' | sed  \'s/ AUTO_INCREMENT=[0-9]*//g\' > '.__DIR__.'/archportal_schema.sql'
);

?>
