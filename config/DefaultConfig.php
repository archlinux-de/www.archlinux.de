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

Config::set('common', 'debug', true);
Config::set('common', 'email', 'webmaster@localhost');
Config::set('common', 'tmpdir', '/tmp');
Config::set('common', 'sitename', 'archportal');

Config::set('DB', 'database', 'pkgdb');
Config::set('DB', 'user', 'root');
Config::set('DB', 'password', '');

Config::set('L10n', 'locale', 'en_US.utf8');
Config::set('L10n', 'timezone', 'UTC');

Config::set('packages', 'mirror', 'http://mirrors.kernel.org/archlinux/');
Config::set('packages', 'repositories', array(
			'core',
			'extra',
			'testing',
			'community',
			'community-testing',
			'multilib',
			'multilib-testing'
		));
Config::set('packages', 'architectures', array(
			'i686',
			'x86_64'
		));

Config::set('mirrors', 'status', 'http://www.archlinux.org/mirrors/status/json/');
Config::set('mirrors', 'default', 'http://mirrors.kernel.org/archlinux/');
Config::set('mirrors', 'country', 'Any');

Config::set('news', 'feed', '');
Config::set('news', 'archive', '');

?>
