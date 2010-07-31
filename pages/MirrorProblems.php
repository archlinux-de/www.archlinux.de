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

class MirrorProblems extends Page {

private $maxdelay = 259200; // 3 days

public function prepare()
	{
	$this->setValue('title', $this->L10n->getText('Mirror problems'));

	$body = '<div class="box">
		<h2>'.$this->L10n->getText('Mirror problems').'</h2>
		</div>
		<table class="pretty-table">
			<tr>
				<th>'.$this->L10n->getText('Host').'</th>
				<th style="width:140px;">&empty;&nbsp;'.$this->L10n->getText('Response time').'</th>
				<th style="width:140px;">&empty;&nbsp;'.$this->L10n->getText('Delay').'</th>
				<th>'.$this->L10n->getText('Last update').'</th>
			</tr>
			<tr><th colspan="4">Not synced for more than '.$this->L10n->getEpoch($this->maxdelay).'</th></tr>
			'.$this->getOutdatedMirrors().'
			<tr><th colspan="4">Synced within the last '.$this->L10n->getEpoch($this->maxdelay).'</th></tr>
			'.$this->getUptodateMirrors().'
		</table>
		<h3>Error log</h3>
		'.$this->getErrors().'
		';


	$this->setValue('body', $body);
	}

private function getOutdatedMirrors()
	{
	$body = '';

	try
		{
		$mirrors = $this->DB->getRowSet
			('
			SELECT
				host,
				lastsync,
				delay,
				time
			FROM
				mirrors
			WHERE
				lastsync < '.($this->Input->getTime() - $this->maxdelay).'
				OR lastsync IS NULL
			ORDER BY
				lastsync ASC,
				host ASC
			');

		foreach ($mirrors as $mirror)
			{
			$body .= '<tr>
					<td><a href="'.$mirror['host'].'" rel="nofollow">'.$mirror['host'].'</a></td>
					<td>'.($mirror['time'] > 0 ? $this->L10n->getEpoch($mirror['time']) : '<em>unknown</em>').'</td>
					<td>'.($mirror['delay'] > 0 ? $this->L10n->getEpoch($mirror['delay']) : '<em>unknown</em>').'</td>
					<td>'.($mirror['lastsync'] > 0 ? $this->L10n->getDateTime($mirror['lastsync']) : '<em>unknown</em>').'</td>
				</tr>';
			}
		}
	catch (DBNoDataException $e)
		{
		}

	return $body;
	}

private function getUptodateMirrors()
	{
	$body = '';

	try
		{
		$mirrors = $this->DB->getRowSet
			('
			SELECT
				host,
				lastsync,
				delay,
				time
			FROM
				mirrors
			WHERE
				lastsync >= '.($this->Input->getTime() - $this->maxdelay).'
			ORDER BY
				lastsync ASC,
				host ASC
			');

		foreach ($mirrors as $mirror)
			{
			$body .= '<tr>
					<td><a href="'.$mirror['host'].'" rel="nofollow">'.$mirror['host'].'</a></td>
					<td>'.$this->L10n->getEpoch($mirror['time']).'</td>
					<td>'.$this->L10n->getEpoch($mirror['delay']).'</td>
					<td>'.$this->L10n->getDateTime($mirror['lastsync']).'</td>
				</tr>';
			}
		}
	catch (DBNoDataException $e)
		{
		}

	return $body;
	}

private function getErrors()
	{
	try
		{
		$problems = $this->DB->getRowSet
			('
			SELECT
				host,
				error,
				min(time) as firsttime,
				max(time) as lasttime,
				count(host) as errorcount
			FROM
				mirror_log
			WHERE
				error IS NOT NULL
			GROUP BY
				host, error
			HAVING
				errorcount > 6
			ORDER BY
				lasttime DESC,
				host ASC
			');
		}
	catch (DBNoDataException $e)
		{
		$problems = array();
		}

	$list = '<table class="pretty-table">
		<tr>
			<th>'.$this->L10n->getText('Host').'</th>
			<th>'.$this->L10n->getText('Message').'</th>
			<th>'.$this->L10n->getText('First occurrence').'</th>
			<th>'.$this->L10n->getText('Last occurrence').'</th>
			<th>'.$this->L10n->getText('Number').'</th>
		</tr>';

	foreach ($problems as $problem)
		{
		$list .=
		'<tr>
			<td><a href="'.$problem['host'].'" rel="nofollow">'.$problem['host'].'</a></td>
			<td>'.$problem['error'].'</td>
			<td>'.$this->L10n->getDateTime($problem['firsttime']).'</td>
			<td>'.$this->L10n->getDateTime($problem['lasttime']).'</td>
			<td>'.$problem['errorcount'].'</td>
		</tr>';
		}

	return $list.'</table>';
	}

}

?>
