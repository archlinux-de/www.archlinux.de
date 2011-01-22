#!/usr/bin/php
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

ini_set('max_execution_time', 0);
ini_set('include_path', ini_get('include_path') . ':../');
require ('modules/Modul.php');
require ('modules/Settings.php');
require ('modules/Exceptions.php');
require ('pages/abstract/Page.php');
require ('pages/PackageStatistics.php');
require ('pages/UserStatistics.php');
require ('pages/FunStatistics.php');

class UpdatePkgstats extends Modul {

	private function getTmpDir() {
		$tmp = ini_get('upload_tmp_dir');
		return empty($tmp) ? '/tmp' : $tmp;
	}

	private function getLockFile() {
		return $this->getTmpDir() . '/updateRunning.lock';
	}

	public function runUpdate() {
		if (file_exists($this->getLockFile())) {
			die('update still in progress');
		} else {
			touch($this->getLockFile());
			chmod($this->getLockFile() , 0600);
		}
		PackageStatistics::updateDBCache();
		UserStatistics::updateDBCache();
		FunStatistics::updateDBCache();
		unlink($this->getLockFile());
	}
}

$upd = new UpdatePkgstats();
$upd->runUpdate();

?>
