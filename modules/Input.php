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

require ('modules/Request.php');

class Input extends Modul {

	public $Get = null;
	public $Post = null;
	public $Cookie = null;
	public $Env = null;
	public $Server = null;
	private $time = 0;

	public function __construct() {
		$this->time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
		$this->Get = new Request($_GET);
		$this->Post = new Request($_POST);
		$this->Cookie = new Request($_COOKIE);
		$this->Env = new Request($_ENV);
		$this->Server = new Request($_SERVER);
	}

	public function getTime() {
		return $this->time;
	}

	public function getHost() {
		return $this->Server->getString('HTTP_HOST');
	}

	public function getClientIP() {
		return $this->Input->Server->getString('REMOTE_ADDR', '127.0.0.1');
	}

	public function getClientCountryName() {
		$country = '';
		if (function_exists('geoip_country_name_by_name')) {
			// remove ipv6 prefix
			$ip = ltrim($this->getClientIP() , ':a-f');
			if (!empty($ip)) {
				// let's ignore any lookup errors
				$errorReporting = error_reporting(E_ALL ^ E_NOTICE);
				restore_error_handler();
				$country = geoip_country_name_by_name($ip) ? : '';
				set_error_handler('ErrorHandler');
				error_reporting($errorReporting);
			}
		}
		return $country;
	}

	public function getPath() {
		$directory = dirname($this->Server->getString('SCRIPT_NAME'));
		return 'http'.(!$this->Server->isString('HTTPS') ? '' : 's').'://'
			.$this->getHost().($directory == '/' ? '' : $directory).'/';
	}

	public function getRelativePath() {
		$directory = dirname($this->Server->getString('SCRIPT_NAME'));
		return ($directory == '/' ? '' : $directory) . '/';
	}
}

?>
