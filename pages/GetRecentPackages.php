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

class GetRecentPackages extends GetFile {

	public function show() {
		$lastdate = 0;
		$entries = '';
		try {
			$packages = DB::query('
			SELECT
				packages.id,
				packages.name,
				packages.builddate,
				packages.version,
				packages.desc,
				packagers.id AS packagerid,
				packagers.name AS packager,
				architectures.name AS architecture,
				repositories.name AS repository
			FROM
				packages
					JOIN
						packagers
					ON
						packages.packager = packagers.id
					JOIN
						architectures
					ON
						packages.arch = architectures.id
					JOIN
						repositories
					ON
						packages.repository = repositories.id
			ORDER BY
				packages.builddate DESC
			LIMIT
				25
			');
			foreach ($packages as $package) {
				if ($package['builddate'] > $lastdate) {
					$lastdate = $package['builddate'];
				}
				$entries.= '
			<entry>
				<id>https://www.archlinux.de/?page=PackageDetails;repo=' . $package['repository'] . ';arch=' . $package['architecture'] . ';pkgname=' . $package['name'] . '</id>
				<title>' . $package['name'] . ' ' . $package['version'] . ' (' . $package['architecture'] . ')</title>
				<link rel="alternate" type="text/html" href="https://www.archlinux.de/?page=PackageDetails;repo=' . $package['repository'] . ';arch=' . $package['architecture'] . ';pkgname=' . $package['name'] . '" />
				<updated>' . date('c', $package['builddate']) . '</updated>
				<summary>' . $package['desc'] . '</summary>
				<author>
					<name>' . $package['packager'] . '</name>
					<uri>https://www.archlinux.de/?page=Packages;packager=' . $package['packagerid'] . '</uri>
				</author>
			</entry>
			';
			}
		} catch(DBNoDataException $e) {
		}
		$content = '<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="de">
	<id>https://www.archlinux.de/?page=Packages</id>
	<title>archlinux.de :: Aktualisierte Pakete</title>
	<link rel="self" type="application/atom+xml" href="https://www.archlinux.de/?page=Packages" />
	<link rel="alternate" type="text/html" href="https://www.archlinux.de/" />
	<updated>' . date('c', $lastdate) . '</updated>
	' . $entries . '
</feed>';
		$this->sendInlineFile('application/atom+xml; charset=UTF-8', 'packages.xml', $content);
	}
}

?>
