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

class MirrorStatus extends Page {

private $orderby 	= 'lastsync';
private $sort 		= 'desc';
private $range		= 604800; // 1 week
private $orders		= array('host', 'country', 'lastsync', 'delay', 'time');
private $sorts		= array('asc', 'desc');


public function prepare()
	{
	$this->setValue('title', $this->L10n->getText('Mirror status'));

	try
		{
		if (in_array($this->Input->Get->getString('orderby'), $this->orders))
			{
			$this->orderby = $this->Input->Get->getString('orderby');
			}
		}
	catch (RequestException $e)
		{
		}

	try
		{
		if (in_array($this->Input->Get->getString('sort'), $this->sorts))
			{
			$this->sort = $this->Input->Get->getString('sort');
			}
		}
	catch (RequestException $e)
		{
		}

	$reverseSort = ($this->sort == 'desc' ? 'asc' : 'desc');

	$body = '<div class="box">
		<h2>'.$this->L10n->getText('Mirror status').'</h2>
		</div>
		<table class="pretty-table">
			<tr>
				<th><a href="?page=MirrorStatus;orderby=host;sort='.$reverseSort.'">'.$this->L10n->getText('Host').'</a></th>
				<th><a href="?page=MirrorStatus;orderby=country;sort='.$reverseSort.'">'.$this->L10n->getText('Country').'</a></th>
				<th style="width:140px;"><a href="?page=MirrorStatus;orderby=time;sort='.$reverseSort.'">&empty;&nbsp;'.$this->L10n->getText('Response time').'</a></th>
				<th style="width:140px;"><a href="?page=MirrorStatus;orderby=delay;sort='.$reverseSort.'">&empty;&nbsp;'.$this->L10n->getText('Delay').'</a></th>
				<th><a href="?page=MirrorStatus;orderby=lastsync;sort='.$reverseSort.'">'.$this->L10n->getText('Last update').'</a></th>
			</tr>';

	try
		{
		$mirrors = $this->DB->getRowSet
			('
			SELECT
				host,
				country,
				lastsync,
				delay,
				time
			FROM
				mirrors
			WHERE
				lastsync >= '.($this->Input->getTime() - $this->range).'
			ORDER BY
				'.$this->orderby.' '.$this->sort.'
			');

		foreach ($mirrors as $mirror)
			{
			$body .= '<tr>
					<td><a href="'.$mirror['host'].'" rel="nofollow">'.$mirror['host'].'</a></td>
					<td>'.$mirror['country'].'</td>
					<td>'.$this->L10n->getEpoch($mirror['time']).'</td>
					<td>'.$this->L10n->getEpoch($mirror['delay']).'</td>
					<td>'.$this->L10n->getDateTime($mirror['lastsync']).'</td>
				</tr>';
			}
		}
	catch (DBNoDataException $e)
		{
		}

	$body .= '</table>';

	$this->setValue('body', $body);
	}

}

?>
