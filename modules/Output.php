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

class Output extends Modul {

	private $contentType = 'text/html; charset=UTF-8';
	private $status = 'HTTP/1.1 200 OK';
	private $modified = false;
	private $compression = true;
	private $outputSeparator = '&';
	private $outputSeparatorHtml = '&amp;';
	const NOT_FOUND = 'HTTP/1.1 404 Not Found';
	const OK = 'HTTP/1.1 200 OK';
	const FOUND = 'HTTP/1.1 302 Found';
	const BAD_REQUEST = 'HTTP/1.1 400 Bad Request';
	const INTERNAL_SERVER_ERROR = 'HTTP/1.1 500 Internal Server Error';

	function __construct() {
		$this->outputSeparator = ini_get('arg_separator.output');
		$this->outputSeparatorHtml = htmlspecialchars($this->outputSeparator);
	}

	public function setStatus($code) {
		$this->status = $code;
	}

	public function setModified($modified = true) {
		$this->modified = $modified;
	}

	public function setCompression($compression = true) {
		$this->compression = $compression;
	}

	public function setContentType($type) {
		$this->contentType = $type;
	}

	public function getContentType() {
		return $this->contentType;
	}

	public function setOutputHandler($handler) {
		$this->outputHandler = $handler;
	}

	public function writeHeader($string) {
		if (@header($string) != 0) {
			throw new OutputException($string);
		}
	}

	public function setCookie($key, $value, $expire = 0) {
		setcookie($key, $value, $expire, '', '', $this->Input->Server->isString('HTTPS') , true);
	}

	public function writeOutput(&$text) {
		$this->writeHeader($this->status);
		if ($this->modified) {
			$this->writeHeader('Last-Modified: ' . date('r', $this->Input->getTime()));
		}
		$this->writeHeader('Content-Type: ' . $this->contentType);
		if ($this->compression) {
			try {
				if (strpos($this->Input->Server->getString('HTTP_ACCEPT_ENCODING') , 'gzip') !== false) {
					$this->writeHeader('Content-Encoding: gzip');
					$this->writeHeader('Vary: Accept-Encoding');
					$text = gzencode($text, 3);
				}
			} catch(RequestException $e) {
			}
		}
		$this->writeHeader('Content-Length: ' . strlen($text));
		echo $text;
		exit();
	}

	public function redirect($page, $options = array()) {
		$this->redirectToUrl($this->createUrl($page, $options, true, false));
	}

	/** FIXME: XSS->alle Zeilenumbrüche entfernen */
	public function redirectToUrl($url) {
		$this->writeHeader(Output::FOUND);
		$this->writeHeader('Location: ' . $url);
		exit();
	}

	public function createUrl($page, $options = array() , $absolute = false, $html = true) {
		$separator = ($html ? $this->outputSeparatorHtml : $this->outputSeparator);
		$params = array();
		foreach (array_merge(array(
			'page' => $page
		) , $options) as $key => $value) {
			$params[] = $key . '=' . urlencode($value);
		}
		return ($absolute ? $this->Input->getPath() : '') . '?' . implode(';', $params);
	}
}

class OutputException extends RuntimeException {

	function __construct($message) {
		parent::__construct($message, 0);
	}
}

?>
