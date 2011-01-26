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

class Request {

	private static $instances = array();
	private $request = array();

	private function __construct($type) {
		switch ($type) {
			case 'get': $this->request =& $_GET; break;
			case 'post': $this->request =& $_POST; break;
			case 'cookie': $this->request =& $_COOKIE; break;
			case 'request': $this->request =& $_REQUEST; break;
			case 'server': $this->request =& $_SERVER; break;
			case 'env': $this->request =& $_ENV; break;
		}
	}

	public static function getInstance($type) {
		if (!isset(self::$instances[$type])) {
			self::$instances[$type] = new self($type);
		}
		return self::$instances[$type];
	}

	// see http://w3.org/International/questions/qa-forms-utf-8.html
	private function is_unicode($input) {
		# long values will make pcre segfaulting...
		for ($i = 0;$i <= mb_strlen($input, 'UTF-8');$i+= 1000) {
			if (!preg_match('%^(?:
				[\x09\x0A\x0D\x20-\x7E]			# ASCII
				| [\xC2-\xDF][\x80-\xBF]		# non-overlong 2-byte
				|  \xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
				|  \xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
				|  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}		# planes 4-15
				|  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
				)*$%xs', mb_substr($input, $i, 1000, 'UTF-8'))) {
				return false;
			}
		}
		return true;
	}

	public function isString($name) {
		return isset($this->request[$name]) && $this->is_unicode($this->request[$name]);
	}

	public function isEmptyString($name) {
		return !$this->isString($name) || !$this->isRegex($name, '/\S+/');
	}

	public function isHex($name) {
		return $this->isRegex($name, '/^[a-f0-9]+$/i');
	}

	public function isInt($name) {
		return $this->isRegex($name, '/^-?[0-9]+$/');
	}

	public function isRegex($name, $regex) {
		return isset($this->request[$name]) && preg_match($regex, $this->request[$name]);
	}

	public function getLength($name) {
		return $this->isEmptyString($name) ? 0 : strlen($this->request[$name]);
	}

	public function getHtmlLength($name) {
		return $this->isEmptyString($name) ? 0 : strlen(htmlspecialchars($this->request[$name], ENT_COMPAT));
	}

	public function getString($name, $default = false) {
		if (!$this->isEmptyString($name)) {
			return $this->request[$name];
		} elseif ($default !== false) {
			return $default;
		} else {
			throw new RequestException($name);
		}
	}

	public function getInt($name, $default = false) {
		if ($this->isInt($name)) {
			return $this->request[$name];
		} elseif ($default !== false) {
			return $default;
		} else {
			throw new RequestException($name);
		}
	}

	public function getHex($name, $default = false) {
		if ($this->isHex($name)) {
			return $this->request[$name];
		} elseif ($default !== false) {
			return $default;
		} else {
			throw new RequestException($name);
		}
	}

	public function getHtml($name, $default = false) {
		return htmlspecialchars($this->getString($name, $default) , ENT_COMPAT);
	}

	private function checkArray(&$value, $key) {
		if (!$this->is_unicode($value) || !preg_match('/\S+/', $value)) {
			throw new RequestException($key);
		}
	}

	public function getArray($name, $default = false) {
		if (isset($this->request[$name]) && is_array($this->request[$name])) {
			array_walk_recursive($this->request[$name], array(
				$this,
				'checkArray'
			));
			return $this->request[$name];
		} elseif ($default !== false) {
			return $default;
		} else {
			throw new RequestException($name);
		}
	}
}

class RequestException extends RuntimeException {

	function __construct($message) {
		parent::__construct('Parameter "'.$message.'" could not be read');
	}
}

?>
