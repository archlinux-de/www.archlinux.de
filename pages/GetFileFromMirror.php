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

class GetFileFromMirror extends Page {

	public function prepare() {
		$file = Input::get()->getString('file', '');
		if (!preg_match('#^[a-zA-Z0-9\.\-\+_/]{1,255}$#', $file)) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure($this->l10n->getText('Invalid file name'));
		}
		$repositories = implode('|', array_keys(Config::get('packages', 'repositories')));
		$architectures = implode('|', $this->getAvailableArchitectures());
		$pkgextension = '(?:'.$architectures.'|any).pkg.tar.(?:g|x)z';
		if (preg_match('#('.$repositories.')/os/('.$architectures.')/([^-]+.*)-[^-]+-[^-]+-'.$pkgextension.'#', $file, $matches)) {
			$pkgdate = Database::prepare('
				SELECT
					packages.mtime
				FROM
					packages
					LEFT JOIN repositories
					ON packages.repository = repositories.id
					LEFT JOIN architectures
					ON repositories.arch = architectures.id
				WHERE
					packages.name = :pkgname
					AND repositories.name = :repository
					AND architectures.name = :architecture
				');
			$pkgdate->bindParam('pkgname', $matches[3], PDO::PARAM_STR);
			$pkgdate->bindParam('repository', $matches[1], PDO::PARAM_STR);
			$pkgdate->bindParam('architecture', $matches[2], PDO::PARAM_STR);
			$pkgdate->execute();
			if ($pkgdate->rowCount() == 0) {
				$this->setStatus(Output::NOT_FOUND);
				$this->showFailure($this->l10n->getText('Package was not found'));
			}
			$lastsync = $pkgdate->fetchColumn();
		} else {
			$lastsync = Input::getTime() - (60 * 60 * 24);
		}
		$this->redirectToUrl($this->getMirror($lastsync).$file);
	}

	private function getAvailableArchitectures() {
		$uniqueArchitectures = array();
		foreach (Config::get('packages', 'repositories') as $architectures) {
			foreach ($architectures as $architecture) {
				$uniqueArchitectures[$architecture] = 1;
			}
		}
		return array_keys($uniqueArchitectures);
	}

	private function getMirror($lastsync) {
		$country = Input::getClientCountryName();
		if (empty($country)) {
			$country = Config::get('mirrors', 'country');
		}
		$stm = Database::prepare('
			SELECT
				host
			FROM
				mirrors
			WHERE
				lastsync > :lastsync
				AND (country = :country OR country = "Any")
				AND protocol IN ("http", "htttps")
			ORDER BY RAND() LIMIT 1
			');
		$stm->bindParam('lastsync', $lastsync, PDO::PARAM_INT);
		$stm->bindParam('country', $country, PDO::PARAM_STR);
		$stm->execute();
		if ($stm->rowCount() == 0) {
			// Let's see if any mirror is recent enough
			$stm = Database::prepare('
				SELECT
					host
				FROM
					mirrors
				WHERE
					lastsync > :lastsync
					AND protocol IN ("http", "htttps")
				ORDER BY RAND() LIMIT 1
				');
			$stm->bindParam('lastsync', $lastsync, PDO::PARAM_INT);
			$stm->execute();
			if ($stm->rowCount() == 0) {
				$this->setStatus(Output::NOT_FOUND);
				$this->showFailure($this->l10n->getText('File was not found'));
			}
		}
		return $stm->fetchColumn();
	}
}

?>
