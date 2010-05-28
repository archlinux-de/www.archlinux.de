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

class MirrorStatusReflector extends Page implements IDBCachable {

private static $range	= 1209600; // two weeks
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
	if (!($this->page = $this->PersistentCache->getObject('MirrorStatusReflector')))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure('No data found!');
		}
	}

public static function updateDBCache()
	{
	$range = time() - self::$range;

	try
		{
		$mirrors = self::get('DB')->getRowSet
			('
			SELECT
				MAX(lastsync) AS lastsync,
				mirrors.host
			FROM
				mirrors,
				mirror_log
			WHERE
				mirror_log.host = mirrors.host
				AND mirror_log.time >= '.$range.'
				AND mirror_log.lastsync >= '.$range.'
			GROUP BY
				mirrors.host
			ORDER BY
				lastsync DESC,
				host ASC
			');

		$page = '';

		foreach($mirrors as $mirror)
			{
			$page .= gmdate('Y-m-d H:i', $mirror['lastsync']).' '.$mirror['host']."\n";
			}
		
		self::get('PersistentCache')->addObject('MirrorStatusReflector', $page);
		}
	catch (DBNoDataException $e)
		{
		}
	}

}

?>
