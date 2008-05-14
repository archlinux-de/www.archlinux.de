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
ini_set('memory_limit', -1);

define('IN_LL', null);

require ('../LLPath.php');
require ('../modules/Modul.php');
require ('../modules/Settings.php');
require ('../modules/Exceptions.php');
require (LL_PATH.'modules/DB.php');
require ('PackageDB.php');


class UpdateFileDB extends Modul {

private $mirror 	= 'ftp://ftp.archlinux.org/';
private $curmtime	= array();
private $lastmtime	= array();

public function __construct()
	{
	self::__set('Settings', new Settings());
	self::__set('DB', new DB(
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database')
		));

// 	$this->mirror = $this->Settings->getValue('pkgdb_mirror');
	$this->mirror = 'http://dev.archlinux.org/~pierre/test-repo/';
	}

private function getLockFile()
	{
	return ini_get('session.save_path').'/updateRunning.lock';
	}

private function getLastRunFile($repo, $arch)
	{
	return 'lastfilerun-'.$repo.'-'.$arch.'.log';
	}

private function showFailure($message)
	{
	unlink($this->getLockFile());
	die($message);
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

// 	echo 'Updating repos...', "\n";
	foreach ($this->Settings->getValue('pkgdb_repositories') as $repo)
		{
		foreach ($this->Settings->getValue('pkgdb_architectures') as $arch)
			{
// 			echo "\t$repo - $arch\n";

			if (!file_exists($this->getLastRunFile($repo, $arch)))
				{
				file_put_contents($this->getLastRunFile($repo, $arch), $this->getCurMTime($repo, $arch));
				}
			else
				{
				$this->setLastMTime($repo, $arch, trim(file_get_contents($this->getLastRunFile($repo, $arch))));
				}
			$this->updateFiles($repo, $arch);
			file_put_contents($this->getLastRunFile($repo, $arch), $this->getCurMTime($repo, $arch));
			}
		}

// 	echo 'Removing unused entries...', "\n";
	$this->removeUnusedEntries();

	unlink($this->getLockFile());
// 	echo 'done', "\n";
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
	$dbtargz = tempnam($this->getTmpDir().'/', $arch.'-'.$repo.'-files.db.tar.gz-');
	$dbDir = tempnam($this->getTmpDir().'/', $arch.'-'.$repo.'-files.db-');
	unlink($dbDir);
	mkdir($dbDir, 0700);

	$fh = fopen($dbtargz, 'w');
	flock($fh, LOCK_EX);
	$curl = curl_init($this->mirror.$repo.'/os/'.$arch.'/files.db.tar.gz');
	curl_setopt($curl, CURLOPT_FILE, $fh);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_exec($curl);
	curl_close($curl);
	flock($fh, LOCK_UN);
	fclose($fh);

	exec('tar -xzf '.$dbtargz.' -C '.$dbDir);
	unlink($dbtargz);

// 	$this->DB->execute
// 		('
// 		LOCK TABLES
// 			pkgdb.packages READ,
// 			pkgdb.files WRITE,
// 			pkgdb.file_index WRITE,
// 			pkgdb.package_file_index WRITE,
// 			pkgdb.architectures READ,
// 			pkgdb.repositories READ
// 		');

	$dh = opendir($dbDir);
	while (false !== ($dir = readdir($dh)))
		{
		if (	$dir != '.' &&
			$dir != '..' &&
			file_exists($dbDir.'/'.$dir.'/files') &&
			filemtime($dbDir.'/'.$dir.'/files') >= $this->getLastMTime($repo, $arch)
			)
			{
			$this->insertFiles($repo, $arch, $dir, file($dbDir.'/'.$dir.'/files'));
			$this->setCurMTime($repo, $arch, filemtime($dbDir.'/'.$dir.'/files'));
			}
		}
	closedir($dh);
// 	$this->DB->execute('UNLOCK TABLES');

	$this->rmrf($dbDir);
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
				pkgdb.package_file_index
			WHERE
				package = ?
			');
		$stm->bindInteger($pkgid);
		$stm->execute();
		$stm->close();

		$stm = $this->DB->prepare
			('
			DELETE FROM
				pkgdb.files
			WHERE
				package = ?
			');
		$stm->bindInteger($pkgid);
		$stm->execute();
		$stm->close();

		$stm1 = $this->DB->prepare
			('
			INSERT INTO
				pkgdb.files
			SET
				package = ?,
				path = ?
			');

		$stm2 = $this->DB->prepare
			('
			INSERT INTO
				pkgdb.package_file_index
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
			if (strlen($filename) > 1)
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
				pkgdb.file_index
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
				pkgdb.file_index
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
			pkgdb.packages,
			pkgdb.architectures,
			pkgdb.repositories
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
// 	$this->DB->execute
// 		('
// 		LOCK TABLES
// 			pkgdb.package_file_index WRITE,
// 			pkgdb.file_index WRITE
// 		');

	$this->DB->execute
		('
		DELETE FROM
			pkgdb.file_index
		WHERE
			id NOT IN (SELECT file_index FROM pkgdb.package_file_index)
		');

// 	$this->DB->execute('UNLOCK TABLES');
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

// 	exec('rm -rf '.$dir);
	}

}

$upd = new UpdateFileDB();
$upd->runUpdate();

?>