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

use RuntimeException;

class Download {

	private $downloaded = false;
	private $url = '';
	private $tmpFile = '';
	private $mtime = 0;

	public function __construct($url) {
		$this->url = $url;
		$this->tmpFile = tempnam(Config::get('common', 'tmpdir'), strtolower(get_class($this)));
	}

	public function __destruct() {
		if (file_exists($this->tmpFile)) {
			unlink($this->tmpFile);
		}
	}

	public function getMTime() {
		if (!$this->downloaded && $this->mtime == 0) {
			$curl = $this->curlInit($this->url);
			curl_setopt($curl, \CURLOPT_NOBODY, true);
			curl_setopt($curl, \CURLOPT_FILETIME, true);
			$ret = curl_exec($curl);
			if ($ret === false) {
				throw new RuntimeException(curl_error($curl), curl_errno($curl));
			}
			$mtime = curl_getinfo($curl, \CURLINFO_FILETIME);
			curl_close($curl);
			if ($mtime < 1) {
				throw new RuntimeException('Invalid filetime "'.$mtime.'" for "'.$this->url.'"');
			} else {
				$this->mtime = $mtime;
			}
		}
		return $this->mtime;
	}

	public function getFile() {
		if (!$this->downloaded) {
			$fh = fopen($this->tmpFile, 'w');
			flock($fh, \LOCK_EX);

			$curl = $this->curlInit($this->url);
			curl_setopt($curl, \CURLOPT_FILE, $fh);
			$ret = curl_exec($curl);
			if ($ret === false) {
				throw new RuntimeException(curl_error($curl), curl_errno($curl));
			}
			$this->mtime = curl_getinfo($curl, \CURLINFO_FILETIME);
			curl_close($curl);

			flock($fh, \LOCK_UN);
			fclose($fh);

			$this->downloaded = true;
		}

		return $this->tmpFile;
	}

	private function curlInit($url) {
		$curlVersion = curl_version();
		$curl = curl_init($url);
		curl_setopt($curl, \CURLOPT_FAILONERROR, true);
		curl_setopt($curl, \CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, \CURLOPT_MAXREDIRS, 3);
		curl_setopt($curl, \CURLOPT_TIMEOUT, 1800);
		curl_setopt($curl, \CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($curl, \CURLOPT_LOW_SPEED_LIMIT, 5000);
		curl_setopt($curl, \CURLOPT_LOW_SPEED_TIME, 600);
		curl_setopt($curl, \CURLOPT_ENCODING, '');
		curl_setopt($curl, \CURLOPT_USERAGENT, 'archportal/curl-'.$curlVersion['version']);
		return $curl;
	}
}

?>
