<?php
/*
	Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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

namespace archportal\lib;

class Input {

	private static $time = null;
	private static $host = null;
	private static $ip = null;
	private static $countryCode = null;
	private static $path = null;
	private static $relativePath = null;

	private function __construct() {}

	public static function __callStatic($name, $args) {
		return Request::getInstance($name);
	}

	public static function getTime() {
		if (self::$time == 0) {
			self::$time = self::server()->getInt('REQUEST_TIME', time());
		}
		return self::$time;
	}

	public static function getHost() {
		if (is_null(self::$host)) {
			self::$host = self::server()->getString('HTTP_HOST');
		}
		return self::$host;
	}

	public static function getClientIP() {
		if (is_null(self::$ip)) {
			self::$ip = self::server()->getString('REMOTE_ADDR', '127.0.0.1');
		}
		return self::$ip;
	}

	public static function getClientCountryCode() {
		if (is_null(self::$countryCode)) {
			if (function_exists('geoip_country_code_by_name')) {
				// remove ipv6 prefix
				$ip = ltrim(self::getClientIP() , ':a-f');
				if (!empty($ip)) {
					// let's ignore any lookup errors
					self::$countryCode = strtoupper(@geoip_country_code_by_name($ip)) ? : '';
				}
			}
		}
		return self::$countryCode;
	}

	public static function getClientArchitecture() {
		$userAgent = self::server()->getString('HTTP_USER_AGENT');
		if (preg_match('/x(86_)?64/', $userAgent)) {
			return 'x86_64';
		} elseif (preg_match('/i[3456]86/', $userAgent)) {
			return 'i686';
		} else {
			return '';
		}
	}

	public static function getPathPattern($pattern) {
		$pathInfo = self::server()->getString('PATH_INFO');
		$matches = array();
		if (preg_match($pattern, $pathInfo, $matches) == 1) {
			return $matches;
		} else {
			throw new RequestException($pattern);
		}
	}

	// FIXME: Rename function
	public static function getPath() {
		if (is_null(self::$path)) {
			$directory = dirname(self::server()->getString('SCRIPT_NAME'));
			self::$path = 'http'.(!self::server()->isString('HTTPS') ? '' : 's').'://'
				.self::getHost().($directory == '/' ? '' : $directory).'/';
		}
		return self::$path;
	}

	// FIXME: Rename function
	public static function getRelativePath() {
		if (is_null(self::$relativePath)) {
			$directory = dirname(self::server()->getString('SCRIPT_NAME'));
			self::$relativePath = ($directory == '/' ? '' : $directory) . '/';
		}
		return self::$relativePath;
	}
}

?>
