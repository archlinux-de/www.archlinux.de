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

	const OK = 'HTTP/1.1 200 OK';
	const FOUND = 'HTTP/1.1 302 Found';
	const MOVED_PERMANENTLY = 'HTTP/1.1 301 Moved Permanently';
	const BAD_REQUEST = 'HTTP/1.1 400 Bad Request';
	const NOT_FOUND = 'HTTP/1.1 404 Not Found';
	const INTERNAL_SERVER_ERROR = 'HTTP/1.1 500 Internal Server Error';

	private $contentType = 'text/html; charset=UTF-8';
	private $status = Output::OK;
	private $outputSeparator = '&';
	private $outputSeparatorHtml = '&amp;';

	public function __construct() {
		$this->outputSeparator = ini_get('arg_separator.output');
		$this->outputSeparatorHtml = htmlspecialchars($this->outputSeparator);

		if (!ini_get('zlib.output_compression')) {
			ob_start('ob_gzhandler');
		}
	}

	public function __destruct() {
		header($this->status);
		header('Content-Type: '.$this->contentType);
	}

	public function setStatus($code) {
		$this->status = $code;
	}

	public function setContentType($type) {
		$this->contentType = $type;
	}

	public function setCookie($key, $value, $expire = 0) {
		setcookie($key, $value, $expire, '', '', $this->Input->Server->isString('HTTPS'), true);
	}

	public function redirect($page, $options = array()) {
		$this->redirectToUrl($this->createUrl($page, $options, true, false));
	}

	public function redirectPermanently($page, $options = array()) {
		$this->redirectPermanentlyToUrl($this->createUrl($page, $options, true, false));
	}

	public function redirectToUrl($url) {
		$this->setStatus(Output::FOUND);
		header('Location: '.$url);
		exit();
	}

	public function redirectPermanentlyToUrl($url) {
		$this->setStatus(Output::MOVED_PERMANENTLY);
		header('Location: '.$url);
		exit();
	}

	public function createUrl($page, $options = array(), $absolute = false, $html = true) {
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

?>
