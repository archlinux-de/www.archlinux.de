<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

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

	public function prepare() {
		try {
			$term = Input::get()->getString('term');
			if (strlen($term) < 2 || strlen($term) > 20) {
				return;
			}
			$arch = Input::get()->getInt('arch');
			$repo = Input::get()->getInt('repo');
			$field = Input::get()->getInt('field');
			switch ($field) {
				case 0:
					$stm = DB::prepare('
					SELECT DISTINCT
						name
					FROM
						packages
					WHERE
						name LIKE :name
						' . ($arch > 0 ? 'AND arch = :arch' : '') . '
						' . ($repo > 0 ? 'AND repository = :repository' : '') . '
					ORDER BY
						name ASC
					LIMIT 20
					');
					$stm->bindValue('name', $term.'%', PDO::PARAM_STR);
					$arch > 0 && $stm->bindParam('arch', $arch, PDO::PARAM_INT);
					$repo > 0 && $stm->bindParam('repository', $repo, PDO::PARAM_INT);
				break;
				case 2:
					$stm = DB::prepare('
					SELECT DISTINCT
						name
					FROM
						file_index
					WHERE
						name LIKE :name
					ORDER BY
						name ASC
					LIMIT 20
					');
					$stm->bindValue('name', $term.'%', PDO::PARAM_STR);
				break;
				default:
					return;
			}
			$stm->execute();
			while ($suggestion = $stm->fetchColumn()) {
				$this->suggestions[] = $suggestion;
			}
		} catch(RequestException $e) {
		}
	}

	public function printPage() {
		$this->setContentType('application/json; charset=UTF-8');
		echo json_encode($this->suggestions);
	}
}

?>
