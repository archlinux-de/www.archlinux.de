<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

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

class PackagesSuggest extends Page {

	private $suggestions = array();

	public function show() {
		$this->Output->setContentType('application/json; charset=UTF-8');
		$output = json_encode($this->suggestions);
		$this->Output->writeOutput($output);
	}

	public function prepare() {
		try {
			$term = $this->Input->Get->getString('term');
			$arch = $this->Input->Get->getInt('arch');
			$repo = $this->Input->Get->getInt('repo');
			$field = $this->Input->Get->getInt('field');
			try {
				switch ($field) {
					case 0:
						if (strlen($term) < 2 || strlen($term) > 10) {
							return;
						}
						$stm = $this->DB->prepare('
						SELECT
							name
						FROM
							packages
						WHERE
							name LIKE ?
							' . ($arch > 0 ? 'AND arch = ?' : '') . '
							' . ($repo > 0 ? 'AND repository = ?' : '') . '
						ORDER BY
							name ASC
						LIMIT 15
						');
						$stm->bindString($term . '%');
						$arch > 0 && $stm->bindInteger($arch);
						$repo > 0 && $stm->bindInteger($repo);
					break;
					case 2:
						if (strlen($term) < 2 || strlen($term) > 15) {
							return;
						}
						$stm = $this->DB->prepare('
						SELECT
							name
						FROM
							file_index
						WHERE
							name LIKE ?
						ORDER BY
							name ASC
						LIMIT 15
						');
						$stm->bindString($term . '%');
					break;
					default:
						return;
				}
				foreach ($stm->getColumnSet() as $suggestion) {
					$this->suggestions[] = $suggestion;
				}
				$stm->close();
			} catch(DBNoDataException $e) {
				$stm->close();
			}
		} catch(RequestException $e) {
		}
	}
}

?>
