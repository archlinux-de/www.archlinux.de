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

class GetFileFromMirror extends Page {

	private $range = 86400; // 1 day

	public function prepare() {
		$this->Output->redirectToUrl($this->getMirror() . $this->Input->Get->getString('file', ''));
	}

	private function getMirror() {
		$country = $this->Input->getClientCountryName();
		if (empty($country)) {
			$country = $this->Settings->getValue('country');
		}
		$stm = DB::prepare('
		SELECT
			host
		FROM
			mirrors
		WHERE
			lastsync >= :lastsync
			AND (country = :country OR country = \'Any\')
			AND protocol IN (\'http\', \'htttps\')
		ORDER BY RAND() LIMIT 1
		');
		$stm->bindValue('lastsync', $this->Input->getTime() - $this->range, PDO::PARAM_INT);
		$stm->bindParam('country', $country, PDO::PARAM_STR);
		$stm->execute();
		$mirror = $stm->fetchColumn() ?: $this->Settings->getValue('mirror');
		return $mirror;
	}
}

?>
