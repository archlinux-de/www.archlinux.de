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

class MirrorStatusJSON extends Page {

	private $json = '';

	public function prepare() {
		$mirrors = Database::query('
		SELECT
			url,
			country,
			lastsync,
			delay,
			durationAvg
		FROM
			mirrors
		WHERE
			protocol IN ("ftp", "http", "htttps")
		');
		$json = array(
			'status' => '200 OK',
			'location' => Input::getClientCountryName()
		);
		foreach ($mirrors as $mirror) {
			$json['servers'][] = array(
				'url' => $mirror['url'],
				'location' => $mirror['country'],
				'last update' => $mirror['lastsync'] > 0 ? gmdate('Y-m-d H:i', $mirror['lastsync']) : '',
				'average delay' => $mirror['delay'] ? : '',
				'average performance' => $mirror['durationAvg'] ? : ''
			);
		}
		$this->json = json_encode($json);
	}

	public function printPage() {
		$this->setContentType('application/json; charset=UTF-8');
		echo $this->json;
	}
}

?>
