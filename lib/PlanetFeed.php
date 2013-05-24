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

class PlanetFeed implements Iterator {

	private $entries = array();
	private $currentKey = 0;
	private $title = '';
	private $link = '';

	public function __construct($feedURL) {
		$download = new Download($feedURL);
		$xmlFeed = new SimpleXMLElement($download->getFile(), 0, true);

		if ($xmlFeed->entry->count() > 0) {
			$this->title = $xmlFeed->title;
			if ($xmlFeed->link->count() == 0) {
				$this->link = $xmlFeed->id;
			} else {
				$this->link = $xmlFeed->link;
			}
			foreach ($xmlFeed->entry as $xmlEntry) {
				$this->entries[] = new PlanetAtomEntry($xmlEntry);
			}
		} elseif ($xmlFeed->channel->item->count() > 0) {
			$this->title = $xmlFeed->channel->title;
			$this->link = $xmlFeed->channel->link;
			foreach ($xmlFeed->channel->item as $xmlEntry) {
				$this->entries[] = new PlanetRSSEntry($xmlEntry);
			}
		} else {
			throw new RuntimeException('Unknown feed format');
		}
	}

	public function __destruct() {
	}

	public function current() {
		return $this->entries[$this->currentKey];
	}

	public function key() {
		return $this->currentKey;
	}

	public function next() {
		$this->currentKey++;
	}

	public function rewind() {
		$this->currentKey = 0;
	}

	public function valid() {
		return $this->currentKey < count($this->entries);
	}

	public function getTitle() {
		return $this->title;
	}

	public function getLink() {
		return $this->link;
	}
}

?>
