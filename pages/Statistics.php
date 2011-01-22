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

class Statistics extends Page {

	public function prepare() {
		$this->setValue('title', 'Statistics');
		$body = '
		<div class="box">
			<h2>Statistics</h2>
			<ul>
				<li><a href="' . $this->Output->createUrl('RepositoryStatistics') . '">Repository statistics</a></li>
				<li><a href="' . $this->Output->createUrl('UserStatistics') . '">User statistics</a></li>
				<li><a href="' . $this->Output->createUrl('PackageStatistics') . '">Package statistics</a></li>
				<li><a href="' . $this->Output->createUrl('FunStatistics') . '">Fun statistics</a></li>
			</ul>
		</div>
		';
		$this->setValue('body', $body);
	}
}

?>
