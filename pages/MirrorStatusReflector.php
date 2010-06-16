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

class MirrorStatusReflector extends Page {

private $range	= 604800; // 1 week
private $page = '';


public function show()
	{
	$this->Output->setContentType('text/plain; charset=UTF-8');
	$this->Output->writeOutput($this->page);
	}

protected function showWarning($text)
	{
	$this->page = 'Warning: '.$text;
	$this->show();
	}

protected function showFailure($text)
	{
	$this->page = 'Error: '.$text;
	$this->show();
	}

public function prepare()
	{
	try
		{
		$mirrors = $this->DB->getRowSet
			('
			SELECT
				host,
				lastsync
			FROM
				mirrors
			WHERE
				lastsync >= '.($this->Input->getTime() - $this->range).'
			ORDER BY
				lastsync DESC
			');

		foreach($mirrors as $mirror)
			{
			$this->page .= gmdate('Y-m-d H:i', $mirror['lastsync']).' '.$mirror['host']."\n";
			}
		}
	catch (DBNoDataException $e)
		{
		$this->showFailure('No mirrors found');
		}
	}

}

?>
