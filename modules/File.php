<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

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

abstract class File extends Modul {

	abstract public function getFileName();
	abstract public function getFileSize();
	abstract public function getFileType();
	abstract public function getFileContent();

	protected function isAllowedType($type) {
		foreach ($this->Settings->getValue('allowed_mime_types') as $allowedType) {
			// prüfe keine exakte Übereinstimmung
			if (strpos($type, $allowedType) === 0) {
				return true;
			}
		}
		return false;
	}
}

class FileException extends RuntimeException {

	function __construct($message) {
		parent::__construct($message, 0);
	}
}

?>
