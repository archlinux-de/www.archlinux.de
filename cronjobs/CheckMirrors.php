#!/usr/bin/php
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

ini_set('max_execution_time', 0);
define('IN_LL', null);

require ('../LLPath.php');
require ('../modules/Modul.php');
require ('../modules/Settings.php');
require ('../modules/Exceptions.php');
require (LL_PATH.'modules/DB.php');

class CheckMirrors extends Modul {


public function __construct()
	{
	self::__set('Settings', new Settings());
	self::__set('DB', new DB(
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database')
		));
	}

private function getLockFile()
	{
	return ini_get('session.save_path').'/MirrorCheckRunning.lock';
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

	$this->removeOldEntries();

	try
		{
		$mirrors = $this->DB->getRowSet
			('
			SELECT
				host,
				ftp,
				http,
				path_ftp,
				path_http,
				i686,
				x86_64
			FROM
				pkgdb.mirrors
			WHERE
				official = 1
				AND deleted = 0
				AND (ftp = 1 OR http = 1)
				AND (i686 = 1 OR x86_64 = 1)
			')->toArray();
		}
	catch (DBNoDataException $e)
		{
		$mirrors = array();
		}

		$this->curlHandles = array();
		
	foreach ($mirrors as $mirror)
		{
		$arch = isset($mirror['i686']) ? 'i686' : 'x86_64';
		$repo = 'core';

		if ($mirror['ftp'] == 1)
			{
			$protocoll = 'ftp';
			$path = $mirror['path_ftp'];
			}
		else
			{
			$protocoll = 'http';
			$path = $mirror['path_http'];
			}

		try
			{
			$result = $this->getLastsyncFromMirror($protocoll.'://'.$mirror['host'].'/'.$path.'/'.$repo.'/os/'.$arch);
			$this->insertLogEntry($mirror['host'], $result['lastsync'], $result['totaltime']);
			}
		catch (RuntimeException $e)
			{
			$this->insertErrorEntry($mirror['host'], $e->getMessage());
			}

		}


	unlink($this->getLockFile());
	}

private function getLastsyncFromMirror($url)
	{
	if (false === ($curl = curl_init($url.'/lastsync')))
		{
		throw new RuntimeException('faild to init curl: '.htmlspecialchars($url));
		}

	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 120);
	curl_setopt($curl, CURLOPT_ENCODING, '');
	curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:support@laber-land.de');
	curl_setopt($curl, CURLOPT_FTP_USE_EPSV, false);

	$content = curl_exec($curl);

	if (false === $content)
		{
		throw new RuntimeException(htmlspecialchars(curl_error($curl)), curl_errno($curl));
		}

	$totaltime = curl_getinfo($curlHandle, CURLINFO_TOTAL_TIME);

	curl_close($curl);

	return array('lastsync' => intval(trim($content)), 'totaltime' => $totaltime);
	}

private function insertLogEntry($host, $lastsync, $totaltime)
	{
	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.mirror_log
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
			pkgdb.mirror_log
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
			pkgdb.mirror_log
		WHERE
			time < ?
		');
	$stm->bindInteger(time() - 60*60*24*30*6);
	$stm->execute();
	$stm->close();
	}

}

$upd = new CheckMirrors();
$upd->runUpdate();

?>