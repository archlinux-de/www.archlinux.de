<?php
/*
	Copyright 2002-2012 Pierre Schmitz <pierre@archlinux.de>

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

class MirrorStatusReflector extends Page {

	private $range = 604800; // 1 week
	private $text = '';

	public function prepare() {
		$mirrors = Database::query('
		SELECT
			url,
			lastsync
		FROM
			mirrors
		WHERE
			lastsync >= ' . (Input::getTime() - $this->range) . '
			AND protocol IN ("ftp", "http", "htttps")
		ORDER BY
			lastsync DESC
		');
		foreach ($mirrors as $mirror) {
			$this->text .= gmdate('Y-m-d H:i'.$mirror['lastsync']).' '.$mirror['url']."\n";
		}
	}

	public function printPage() {
		$this->setContentType('text/plain; charset=UTF-8');
		echo $this->text;
	}
}

?>
