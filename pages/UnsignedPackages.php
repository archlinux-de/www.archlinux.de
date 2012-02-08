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

class UnsignedPackages extends Page {


	public function prepare() {
		$this->setValue('title','Unsigned Packages');

		$unsignedPackageBases = Database::query('
			SELECT DISTINCT
				a.base AS pkgbase,
				repositories.name AS repository
			FROM
				packages a
					JOIN repositories
					ON repositories.id = a.repository
			WHERE
				a.pgpsig IS NULL
				AND NOT EXISTS (
					SELECT
						*
					FROM
						packages b
					WHERE
						a.name = b.name
						AND a.arch = b.arch
						AND a.repository <> b.repository
						AND b.pgpsig IS NOT NULL
				)
			ORDER BY
				repositories.id ASC,
				a.base ASC
			');

		$body = '<h1>'.$this->getValue('title').': '.$unsignedPackageBases->rowCount().'</h1>';
		$repository = '';
		foreach ($unsignedPackageBases as $unsignedPackageBase) {
			if ($unsignedPackageBase['repository'] != $repository) {
				$repository = $unsignedPackageBase['repository'];
				$body .= '<h2>'.$repository.'</h2>';
			}
			$body .= $unsignedPackageBase['pkgbase'].'<br />';
		}

		$this->setValue('body', $body);
	}
}

?>
