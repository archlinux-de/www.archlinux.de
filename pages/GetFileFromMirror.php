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

class GetFileFromMirror extends Page implements IDBCachable {

private $mirrors	= array();
private static $range	= 172800; // 2 days


public function prepare()
	{
	$this->setValue('title', 'Lade Datei von einem Spiegel-Server');
	$this->setValue('meta.robots', 'noindex,nofollow');

	try
		{
		$file = htmlspecialchars($this->Input->Get->getString('file'));
		}
	catch (RequestException $e)
		{
		$this->showFailure('keine Datei angegeben!');
		}

	if (!($this->mirrors = $this->PersistentCache->getObject('GetFileFromMirror')))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure('keine Spiegel-Server gefunden!');
		}

	$this->setValue('title', basename($file));

	$mirror = $this->mirrors[array_rand($this->mirrors)];
	$url = $mirror['host'].$file;

	$body = '<div class="box">
			<h2>'.basename($file).'</h2>
			<h3>Aktueller Server:</h3>
			<p><ul><li>'.$mirror['country'].'<ul><li><a href="'.$url.'">'.$mirror['host'].'</a> <em>('.$this->L10n->getDateTime($mirror['lastsync']).')</em></li></ul></ul></p>
			<h3>Alternative Server:</h3>
			<p>'.$this->getAlternateMirrorList($url, $file).'</p>
		</div>
		<script type="text/javascript">
			/* <![CDATA[ */
				setTimeout(\'location.href="'.$url.'"\', 2000);
			/* ]]> */
		</script>';

	$this->setValue('body', $body);
	}

private function getAlternateMirrorList($url, $file)
	{
	$list = '<ul>';
	$mirrors = $this->mirrors;
	$country = '';

	foreach ($mirrors as $mirror)
		{
		if ($country != $mirror['country'])
			{
			if ($country != '')
				{
				$list .= '</ul>';
				}
			$list .= '<li>'.$mirror['country'].'<ul>';
			}
		$country = $mirror['country'];

		$list .= '<li><a href="'.$mirror['host'].$file.'">'.$mirror['host'].'</a></li>';
		}

	return $list.'</ul></ul>';
	}

public static function updateDBCache()
	{
	try
		{
		$stm = self::get('DB')->prepare
			('
			SELECT
				mirrors.host,
				mirrors.country,
				MAX(mirror_log.lastsync) AS lastsync
			FROM
				mirrors,
				mirror_log
			WHERE
				mirror_log.host = mirrors.host
				AND mirror_log.lastsync >= ?
			GROUP BY
				mirrors.host
			ORDER BY
				country ASC,
				lastsync DESC,
				host
			');
		$stm->bindInteger(time() - self::$range);

		$mirrors = array();

		foreach ($stm->getRowSet() as $mirror)
			{
			$mirrors[] = array('host' => $mirror['host'], 'country' => $mirror['country'], 'lastsync' => $mirror['lastsync']);
			}

		$stm->close();
		self::get('PersistentCache')->addObject('GetFileFromMirror', $mirrors);
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		}
	}

}

?>
