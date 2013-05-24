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

class PlanetAtomEntry extends PlanetEntry {

	private $xmlEntry = null;

	public function __construct($xmlEntry) {
		$this->xmlEntry = $xmlEntry;
	}

	public function __destruct() {
	}

	public function getEntryId() {
		return $this->xmlEntry->id;
	}

	public function getTitle() {
		return $this->xmlEntry->title;
	}

	public function getLink() {
		return $this->xmlEntry->link;
	}

	public function getContent() {
		return $this->xmlEntry->content;
	}

	public function getAuthor() {
		return $this->xmlEntry->author;
	}

	public function getUpdateTime() {
		return strtotime($this->xmlEntry->updated);
	}
}

?>
