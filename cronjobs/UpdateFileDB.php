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
ini_set('include_path', ini_get('include_path').':'.LL_PATH.':../');

require ('modules/Modul.php');
require ('modules/Settings.php');
require ('modules/Exceptions.php');
require ('modules/DB.php');

class UpdateFileDB extends Modul {

private $mirror 	= 'ftp://ftp.archlinux.org/';
private $curmtime	= array();
private $lastmtime	= array();
private $changed	= false;

public function __construct()
	{
	self::__set('Settings', new Settings());
	self::__set('DB', new DB(
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database')
		));

 	$this->mirror = $this->Settings->getValue('pkgdb_mirror');
	}

private function getLockFile()
	{
	return $this->getTmpDir().'/updateRunning.lock';
	}

public function runUpdate()
	{
	if (file_exists($this->getLockFile()))
		{
		die('update still in progress');
		}
	else
		{
		touch($this->getLockFile());
		chmod($this->getLockFile(), 0600);
		}

	foreach ($this->Settings->getValue('pkgdb_repositories') as $repo)
		{
		foreach ($this->Settings->getValue('pkgdb_architectures') as $arch)
			{
			$this->updateFiles($repo, $arch);
			}
		}

	if ($this->changed)
		{
		$this->removeUnusedEntries();
		}

	unlink($this->getLockFile());
	}

private function setLogEntry($name, $time)
	{
	$stm = $this->DB->prepare
		('
		REPLACE INTO
			log
		SET
			name = ?,
			time = ?
		');
	$stm->bindString($name);
	$stm->bindInteger($time);
	$stm->execute();
	$stm->close();
	}

private function getLogEntry($name)
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				time
			FROM
				log
			WHERE
				name = ?
			');
		$stm->bindString($name);
		$time = $stm->GetColumn();
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		$time = 0;
		}

	return $time;
	}

private function setCurMTime($repo, $arch, $mtime)
	{
	if (!isset($this->curmtime["$repo-$arch"]))
		{
		$this->curmtime["$repo-$arch"] = $mtime;
		}
	elseif ($mtime > $this->curmtime["$repo-$arch"])
		{
		$this->curmtime["$repo-$arch"] = $mtime;
		}
	}

private function setLastMTime($repo, $arch, $mtime)
	{
	if (!isset($this->lastmtime["$repo-$arch"]))
		{
		$this->lastmtime["$repo-$arch"] = $mtime;
		}
	elseif ($mtime > $this->lastmtime["$repo-$arch"])
		{
		$this->lastmtime["$repo-$arch"] = $mtime;
		}
	}

private function getCurMTime($repo, $arch)
	{
	if (isset($this->curmtime["$repo-$arch"]))
		{
		return $this->curmtime["$repo-$arch"];
		}
	else
		{
		return 0;
		}
	}

private function getLastMTime($repo, $arch)
	{
	if (isset($this->lastmtime["$repo-$arch"]))
		{
		return $this->lastmtime["$repo-$arch"];
		}
	else
		{
		return 0;
		}
	}

private function updateFiles($repo, $arch)
	{
	// get remote mtime
	$curl = curl_init($this->mirror.$repo.'/os/'.$arch.'/'.$repo.'.files.tar.gz');
	curl_setopt($curl, CURLOPT_NOBODY, true);
	curl_setopt($curl, CURLOPT_FILETIME, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 20);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($curl, CURLOPT_ENCODING, '');
	curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:bob@archlinux.de');
	curl_setopt($curl, CURLOPT_FTP_USE_EPSV, false);
	curl_exec($curl);
	$mtime = curl_getinfo($curl, CURLINFO_FILETIME);
	curl_close($curl);

	if ($mtime > $this->getLogEntry('UpdateFileDB-mtime-'.$repo.'-'.$arch))
		{
		$this->setLastMTime($repo, $arch, $this->getLogEntry('UpdateFileDB-'.$repo.'-'.$arch));
		$this->changed = true;

		$dbtargz = tempnam($this->getTmpDir().'/', $arch.'-'.$repo.'-files.tar.gz-');
		$dbDir = tempnam($this->getTmpDir().'/', $arch.'-'.$repo.'-files.db-');
		unlink($dbDir);
		mkdir($dbDir, 0700);

		$fh = fopen($dbtargz, 'w');
		flock($fh, LOCK_EX);
		$curl = curl_init($this->mirror.$repo.'/os/'.$arch.'/'.$repo.'.files.tar.gz');
		curl_setopt($curl, CURLOPT_FILE, $fh);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 120);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:bob@archlinux.de');
		curl_setopt($curl, CURLOPT_FTP_USE_EPSV, false);
		curl_exec($curl);
		curl_close($curl);
		flock($fh, LOCK_UN);
		fclose($fh);

		exec('bsdtar -xf '.$dbtargz.' -C '.$dbDir, $output, $return);
		unlink($dbtargz);

		if ($return == 0)
			{
			$dh = opendir($dbDir);
			while (false !== ($dir = readdir($dh)))
				{
				if (	$dir != '.' &&
					$dir != '..' &&
					file_exists($dbDir.'/'.$dir.'/files') &&
					filemtime($dbDir.'/'.$dir.'/files') >= $this->getLastMTime($repo, $arch)
					)
					{
					$this->insertFiles($repo, $arch, $dir, file($dbDir.'/'.$dir.'/files', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
					$this->setCurMTime($repo, $arch, filemtime($dbDir.'/'.$dir.'/files'));
					}
				}
			closedir($dh);

			$this->rmrf($dbDir);
			$this->setLogEntry('UpdateFileDB-'.$repo.'-'.$arch, $this->getCurMTime($repo, $arch));
			$this->setLogEntry('UpdateFileDB-mtime-'.$repo.'-'.$arch, $mtime);
			}
		else
			{
			$this->rmrf($dbDir);
			}
		}
	}

private function getTmpDir()
	{
	$tmp = ini_get('upload_tmp_dir');
	return empty($tmp) ? '/tmp' : $tmp;
	}

private function insertFiles($repo, $arch, $package, $files)
	{
	try
		{
		$pkgid = $this->getPackageID($repo, $arch, $package);
		$stm = $this->DB->prepare
			('
			DELETE FROM
				package_file_index
			WHERE
				package = ?
			');
		$stm->bindInteger($pkgid);
		$stm->execute();
		$stm->close();

		$stm = $this->DB->prepare
			('
			DELETE FROM
				files
			WHERE
				package = ?
			');
		$stm->bindInteger($pkgid);
		$stm->execute();
		$stm->close();

		$stm1 = $this->DB->prepare
			('
			INSERT INTO
				files
			SET
				package = ?,
				path = ?
			');

		$stm2 = $this->DB->prepare
			('
			INSERT INTO
				package_file_index
			SET
				package = ?,
				file_index = ?
			');

		for ($file = 1; $file < count($files); $file++)
			{
			$stm1->bindInteger($pkgid);
			$stm1->bindString(mb_substr(htmlspecialchars($files[$file]), 0, 255, 'UTF-8'));
			$stm1->execute();

			$filename = mb_substr(htmlspecialchars(basename($files[$file])), 0, 100, 'UTF-8');
			if (strlen($filename) > 2)
				{
				$stm2->bindInteger($pkgid);
				$stm2->bindInteger($this->getFileIndexID($filename));
				$stm2->execute();
				}
			}
		$stm1->close();
		$stm2->close();
		}
	catch (DBNoDataException $e)
		{
		}
	}

private function getFileIndexID($file)
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				id
			FROM
				file_index
			WHERE
				name = ?
			');
		$stm->bindString($file);
		$id = $stm->getColumn();
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();

		$stm = $this->DB->prepare
			('
			INSERT INTO
				file_index
			SET
				name = ?
			');
		$stm->bindString($file);
		$stm->execute();
		$id = $this->DB->getInsertId();
		$stm->close();
		}

	return $id;
	}

private function getPackageID($repo, $arch, $package)
	{
	$stm = $this->DB->prepare
		('
		SELECT
			packages.id
		FROM
			packages,
			architectures,
			repositories
		WHERE
			packages.name = ?
			AND repositories.name = ?
			AND architectures.name = ?
			AND packages.arch = architectures.id
			AND packages.repository = repositories.id
		');
	$stm->bindString(htmlspecialchars(preg_replace('/^(.+)-.+?-.+?$/', '$1', $package)));
	$stm->bindString(htmlspecialchars($repo));
	$stm->bindString(htmlspecialchars($arch));

	$id = $stm->getColumn();
	$stm->close();

	return $id;
	}

private function removeUnusedEntries()
	{
	$this->DB->execute
		('
		DELETE FROM
			file_index
		WHERE
			id NOT IN (SELECT file_index FROM package_file_index)
		');
	}

private function rmrf($dir)
	{
	if (is_dir($dir) && !is_link($dir))
		{
		$dh = opendir($dir);
		while (false !== ($file = readdir($dh)))
			{
			if ($file != '.' && $file != '..')
				{
				if (!$this->rmrf($dir.'/'.$file))
					{
					trigger_error('Could not remove '.$dir.'/'.$file);
					}
				}
			}
		closedir($dh);

		return rmdir($dir);
		}
	else
		{
		return unlink($dir);
		}
	}

}

$upd = new UpdateFileDB();
$upd->runUpdate();

?>