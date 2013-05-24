<?php
/*
	Copyright 2002-2013 Pierre Schmitz <pierre@archlinux.de>

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

class Config {

	private static $config = array();

	private function __construct() {}

	public static function set($section, $key, $value) {
		self::$config[$section][$key] = $value;
	}

	public static function get($section, $key) {
		if (isset(self::$config[$section][$key])) {
			return self::$config[$section][$key];
		} else {
			throw new RuntimeException('No configuration entry was found for key "'.$key.'" in section "'.$section.'"');
		}
	}

}

require (__DIR__.'/../config/DefaultConfig.php');

if (file_exists(__DIR__.'/../config/LocalConfig.php')) {
	include (__DIR__.'/../config/LocalConfig.php');
}

?>
