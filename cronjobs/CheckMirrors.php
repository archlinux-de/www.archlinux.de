#!/usr/bin/php
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

ini_set('max_execution_time', 0);
ini_set('include_path', ini_get('include_path').':../');

require ('modules/Functions.php');
require ('modules/Modul.php');
require ('modules/Settings.php');
require ('modules/Exceptions.php');

class CheckMirrors extends Modul {


private $range	= 2419200; // 4 weeks


private function getTmpDir()
	{
	$tmp = ini_get('upload_tmp_dir');
	return empty($tmp) ? '/tmp' : $tmp;
	}

private function getLockFile()
	{
	return $this->getTmpDir().'/MirrorCheckRunning.lock';
	}

public function runUpdate()
	{
	if (file_exists($this->getLockFile()))
		{
		die('MirrorCheck still in progress');
		}
	else
		{
		touch($this->getLockFile());
		chmod($this->getLockFile(), 0600);
		}

	$this->DB->connect(
		$this->Settings->getValue('sql_host'),
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database'));

	$this->DB->execute('CREATE TEMPORARY TABLE tmirrors LIKE mirrors');

	try
		{
		$this->updateMirrorlist();
		}
	catch (RuntimeException $e)
		{
		echo('Warning: updateMirrorlist failed: '.$e->getMessage());
		}

	$this->removeOldEntries();

	try
		{
		$mirrors = $this->DB->getColumnSet
			('
			SELECT
				host
			FROM
				tmirrors
			');
		}
	catch (DBNoDataException $e)
		{
		$mirrors = array();
		}

	foreach ($mirrors as $host)
		{
		try
			{
			$result = $this->getLastsyncFromMirror($host.'core/os/i686');
			$this->insertLogEntry($host, $result['lastsync'], $result['totaltime']);
			}
		catch (RuntimeException $e)
			{
			$this->insertErrorEntry($host, $e->getMessage());
			}
		}

	$this->updateCache();
	$this->DB->execute('TRUNCATE mirrors');
	$this->DB->execute('INSERT INTO mirrors SELECT * FROM tmirrors');
	$this->DB->execute('DROP TEMPORARY TABLE tmirrors');

	unlink($this->getLockFile());
	}

private function updateMirrorlist()
	{
	$mirrors = $this->getMirrorlist();

	$stm = $this->DB->prepare
		('
		INSERT INTO
			tmirrors
		SET
			host = ?,
			country = ?
		');

	foreach ($mirrors as $mirror => $country)
		{
		$stm->bindString($mirror);
		$stm->bindString($country);
		$stm->execute();
		}

	$stm->close();
	}

private function getMirrorlist()
	{
	if (false === ($curl = curl_init($this->Settings->getValue('mirrorlist_url'))))
		{
		throw new RuntimeException('failed to init curl: '.htmlspecialchars($url));
		}

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 120);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl, CURLOPT_USERAGENT, 'bob@archlinux.de');
	curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:bob@archlinux.de');
	$content = curl_exec($curl);

	if (false === $content)
		{
		throw new RuntimeException(htmlspecialchars(curl_error($curl)), curl_errno($curl));
		}
	elseif (empty($content))
		{
		throw new RuntimeException('empty mirrorlist', 1);
		}

	curl_close($curl);

	$mirrorlist = explode("\n", $content);

	#preg_match('/(\d{4})-(\d{2})-(\d{2})/', $mirrorlist[2], $matches);
	#$date = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);

	$mirrorarray = array();

	for ($line = 4; $line < count($mirrorlist); $line++)
		{
		if (preg_match('/^# ([\w ]+)$/', $mirrorlist[$line], $matches))
			{
			$country = $matches[1];
			}
		elseif (preg_match('/^#Server = (.+)\$repo\/os\/i686$/', $mirrorlist[$line], $matches))
			{
			$mirrorarray[$matches[1]] = $country;
			}
		}

	return $mirrorarray;
	}

private function getLastsyncFromMirror($url)
	{
	if (false === ($curl = curl_init($url.'/lastsync')))
		{
		throw new RuntimeException('failed to init curl: '.htmlspecialchars($url));
		}

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 120);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl, CURLOPT_USERAGENT, 'bob@archlinux.de');
	curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:bob@archlinux.de');

	$content = curl_exec($curl);

	if (false === $content)
		{
		throw new RuntimeException(htmlspecialchars(curl_error($curl)), curl_errno($curl));
		}

	$totaltime = curl_getinfo($curl, CURLINFO_TOTAL_TIME);

	curl_close($curl);

	$lastsync = intval(trim($content));

	if (0 == $lastsync)
		{
		throw new RuntimeException('invalid lastsync time', 1);
		}

	return array('lastsync' => $lastsync, 'totaltime' => $totaltime);
	}

private function insertLogEntry($host, $lastsync, $totaltime)
	{
	$stm = $this->DB->prepare
		('
		INSERT INTO
			mirror_log
		SET
			host = ?,
			time = ?,
			lastsync = ?,
			totaltime = ?
		');
	$stm->bindString($host);
	$stm->bindInteger(time());
	$stm->bindInteger($lastsync);
	$stm->bindDouble($totaltime);
	$stm->execute();
	$stm->close();
	}

private function insertErrorEntry($host, $error)
	{
	$stm = $this->DB->prepare
		('
		INSERT INTO
			mirror_log
		SET
			host = ?,
			time = ?,
			error = ?
		');
	$stm->bindString($host);
	$stm->bindInteger(time());
	$stm->bindString($error);
	$stm->execute();
	$stm->close();
	}

private function removeOldEntries()
	{
	$stm = $this->DB->prepare
		('
		DELETE FROM
			mirror_log
		WHERE
			time < ?
		');
	$stm->bindInteger(time() - 60*60*24*30*6);
	$stm->execute();
	$stm->close();

	$this->DB->execute
		('
		DELETE FROM
			mirror_log
		WHERE
			host NOT IN (SELECT host FROM mirrors)
		');
	}

private function updateCache()
	{
	$range = time() - $this->range;

	$stm = self::get('DB')->execute
		('
		UPDATE
			tmirrors
		SET
			lastsync = (SELECT MAX(mirror_log.lastsync) FROM mirror_log WHERE mirror_log.host = tmirrors.host AND mirror_log.time >= '.$range.'),
			time = (SELECT AVG(mirror_log.totaltime) FROM mirror_log WHERE mirror_log.host = tmirrors.host AND mirror_log.time >= '.$range.'),
			delay = (SELECT AVG(mirror_log.time-mirror_log.lastsync) FROM mirror_log WHERE mirror_log.host = tmirrors.host AND mirror_log.time >= '.$range.')
		');
	}

}

$upd = new CheckMirrors();
$upd->runUpdate();

?>
