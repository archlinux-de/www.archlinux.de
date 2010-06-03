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
private static $range	= 1209600; // two weeks


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

	$this->mirrors = $this->getMirrors();

	if (count($this->mirrors) == 0)
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure('keine Spiegel-Server gefunden!');
		}

	$this->setValue('title', basename($file));

	$mirror = $this->getRandomMirror($file);
	$url = $mirror.$file;

	$body = '<div class="box">
			<h2>'.basename($file).'</h2>
			<h3>Aktueller Server:</h3>
			<p><ul><li><a href="'.$url.'">'.$mirror.'</a></li></ul></p>
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

private function getMirrors()
	{
	if (!($mirrors = $this->PersistentCache->getObject('GetFileFromMirror')))
		{
		$mirrors = array();
		}

	return $mirrors;
	}

private function getAlternateMirrorList($url, $file)
	{
	$list = '<ul>';
	$mirrors = $this->mirrors;
	arsort($mirrors);

	foreach ($mirrors as $mirror => $probability)
		{
		if ($probability == 0 || $mirror.$file == $url)
			{
			continue;
			}
		$list .= '<li><a href="'.$mirror.$file.'">'.$mirror.'</a></li>';
		}

	return $list.'</ul>';
	}

private function getRandomMirror($file)
	{
	$tempMirrors = array();

	foreach ($this->mirrors as $mirror => $probability)
		{
		for ($i = 0; $i < $probability; $i++)
			{
			$tempMirrors[] = $mirror;
			}
		}

	$randomIndex = array_rand($tempMirrors);

	return $tempMirrors[$randomIndex];
	}

public static function updateDBCache()
	{
	try
		{
		$stm = self::get('DB')->prepare
			('
			SELECT
				mirrors.host,
				MAX(lastsync) AS lastsync,
				AVG(totaltime) AS avgtime
			FROM
				mirrors,
				mirror_log
			WHERE
				mirror_log.host = mirrors.host
				AND mirror_log.time >= ?
			GROUP BY
				mirrors.host
			HAVING
				lastsync > 0
			ORDER BY
				avgtime ASC,
				lastsync DESC,
				host
			');
		$stm->bindInteger(time() - self::$range);

		$mirrors = array();

		foreach ($stm->getRowSet() as $mirror)
			{
			$mirrors[$mirror['host']] = round(3/$mirror['avgtime']);
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
