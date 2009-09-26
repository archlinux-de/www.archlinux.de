<?php
/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
*/

class GetFileFromMirror extends Page implements IDBCachable {

private $mirrors	= array();
private static $range	= 1209600; // two weeks

protected function makeMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=Spenden">Spenden</a></li>
			<li class="selected">Pakete</li>
			<li><a href="http://wiki.archlinux.de">Wiki</a></li>
			<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li><a href="?page=Start">Start</a></li>
		</ul>';
	}

protected function makeSubMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
			<li><a href="?page=PackageStatistics">Statistiken</a></li>
			<li><a href="?page=MirrorStatus">Server</a></li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li class="selected"><a href="?page=Packages">Suche</a></li>
		</ul>';
	}

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

	$body = '<div id="box">
			<h2>'.basename($file).'</h2>
			<p>Aktueller Server: <strong><a href="'.$url.'">'.$mirror.'</a></strong></p>
			Alternative Server:'.$this->getAlternateMirrorList($url, $file).'
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
