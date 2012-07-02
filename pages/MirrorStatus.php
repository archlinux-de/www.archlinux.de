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

class MirrorStatus extends Page {

	private $orderby = 'lastsync';
	private $sort = 'desc';
	private $range = 604800; // 1 week
	private $orders = array(
		'url',
		'country',
		'lastsync',
		'delay',
		'durationAvg'
	);
	private $sorts = array(
		'asc',
		'desc'
	);

	public function prepare() {
		$this->setValue('title', $this->l10n->getText('Mirror status'));
		try {
			if (in_array(Input::get()->getString('orderby') , $this->orders)) {
				$this->orderby = Input::get()->getString('orderby');
			}
		} catch(RequestException $e) {
		}
		try {
			if (in_array(Input::get()->getString('sort') , $this->sorts)) {
				$this->sort = Input::get()->getString('sort');
			}
		} catch(RequestException $e) {
		}
		$reverseSort = ($this->sort == 'desc' ? 'asc' : 'desc');
		$body = '<div class="box">
		<h2>' . $this->l10n->getText('Mirror status') . '</h2>
		</div>
		<table class="pretty-table">
			<tr>
				<th><a href="'.$this->createUrl('MirrorStatus', array('orderby' => 'url', 'sort' => $reverseSort)).'">' . $this->l10n->getText('url') . '</a></th>
				<th><a href="'.$this->createUrl('MirrorStatus', array('orderby' => 'country', 'sort' => $reverseSort)).'">' . $this->l10n->getText('Country') . '</a></th>
				<th style="width:140px;"><a href="'.$this->createUrl('MirrorStatus', array('orderby' => 'durationAvg', 'sort' => $reverseSort)).'">&empty;&nbsp;' . $this->l10n->getText('Response time') . '</a></th>
				<th style="width:140px;"><a href="'.$this->createUrl('MirrorStatus', array('orderby' => 'delay', 'sort' => $reverseSort)).'">&empty;&nbsp;' . $this->l10n->getText('Delay') . '</a></th>
				<th><a href="'.$this->createUrl('MirrorStatus', array('orderby' => 'lastsync', 'sort' => $reverseSort)).'">' . $this->l10n->getText('Last update') . '</a></th>
			</tr>';
		$mirrors = Database::query('
		SELECT
			url,
			country,
			lastsync,
			delay,
			durationAvg
		FROM
			mirrors
		WHERE
			lastsync >= ' . (Input::getTime() - $this->range) . '
		ORDER BY
			' . $this->orderby . ' ' . $this->sort . '
		');
		foreach ($mirrors as $mirror) {
			$body.= '<tr>
				<td><a href="' . $mirror['url'] . '" rel="nofollow">' . $mirror['url'] . '</a></td>
				<td>' . $mirror['country'] . '</td>
				<td>' . $this->l10n->getEpoch($mirror['durationAvg']) . '</td>
				<td>' . $this->l10n->getEpoch($mirror['delay']) . '</td>
				<td>' . $this->l10n->getGmDateTime($mirror['lastsync']) . '</td>
			</tr>';
		}
		$body.= '</table>';
		$this->setValue('body', $body);
	}
}

?>
