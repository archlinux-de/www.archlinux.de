#!/usr/bin/php
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

require (__DIR__.'/../lib/Config.php');
require (__DIR__.'/../lib/Exceptions.php');
require (__DIR__.'/../lib/AutoLoad.php');

class UpdatePlanet extends CronJob {

	public function execute() {
		$feed = new PlanetFeed('https://bbs.archlinux.de/extern.php?action=feed&fid=257&type=atom&order=posted&show=6');
		echo $feed->getTitle(), "\n";
		foreach ($feed as $entry) {
			echo $entry->getTitle(), "\n";
		}
	}

}

UpdatePlanet::run();

?>
