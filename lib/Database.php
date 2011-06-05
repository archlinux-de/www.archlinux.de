<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

class Database {

	private static $pdo = null;

	private function __construct() {}

	public static function __callStatic($name, $args) {
		if (is_null(self::$pdo)) {
			self::$pdo = new PDO('mysql:dbname='.Config::get('Database', 'database'),
				Config::get('Database', 'user'),
				Config::get('Database', 'password'),
				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
				      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
			);
			if (Config::get('common', 'debug')) {
				self::$pdo->query('SET sql_mode="STRICT_ALL_TABLES"');
			}
		}
		return call_user_func_array(array(self::$pdo, $name), $args);
	}
}

?>
