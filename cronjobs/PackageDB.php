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

require_once('Package.php');
// require_once('Archive/Tar.php');

class PackageDB {

private $mirror 	= 'ftp://ftp.archlinux.org/';
private $repository	= 'core';
private $architecture	= 'i686';
private $DBtargz	= '/tmp/db.tar.gz';
private $DBDir		= '/tmp/dbdir';


public function __construct($mirror, $repository, $architecture)
	{
	$this->mirror = $mirror;
	$this->repository = $repository;
	$this->architecture = $architecture;
	$this->DBtargz = tempnam(ini_get('upload_tmp_dir').'/', $this->architecture.'-'.$this->repository.'-pkgdb.tar.gz-');
	$this->DBDir = tempnam(ini_get('upload_tmp_dir').'/', $this->architecture.'-'.$this->repository.'-pkgdb-');
	unlink($this->DBDir);
	mkdir($this->DBDir, 0700);

	$this->update();
	}

public function __destruct()
	{
	$this->rmrf($this->DBDir);
	}

private function update()
	{
	$fh = fopen($this->DBtargz, 'w');
	flock($fh, LOCK_EX);
	$curl = curl_init($this->mirror.$this->repository.'/os/'.$this->architecture.'/'.$this->repository.'.db.tar.gz');
	curl_setopt($curl, CURLOPT_FILE, $fh);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_exec($curl);
	curl_close($curl);
	flock($fh, LOCK_UN);
	fclose($fh);

// 	try
// 		{
// 		$errorlevel = error_reporting(0);
// 		$targz = new Archive_Tar($this->DBtargz, 'gz');
// 		$targz->setErrorHandling(PEAR_ERROR_RETURN);
// 		$targz->extract($this->DBDir);
// 		error_reporting($errorlevel);
// 		}
// 	catch (Exception $e)
// 		{
// 		}

	exec('tar -xzf '.$this->DBtargz.' -C '.$this->DBDir);
	unlink($this->DBtargz);
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

public function getUpdatedPackages($timestamp)
	{
	$packages = array();

	$dh = opendir($this->DBDir);

	while (false !== ($dir = readdir($dh)))
		{
		if (	$dir != '.' &&
			$dir != '..' &&
			file_exists($this->DBDir.'/'.$dir.'/desc') &&
			file_exists($this->DBDir.'/'.$dir.'/depends') &&
			filemtime($this->DBDir.'/'.$dir.'/desc') >= $timestamp)
			{
			$packages[] = new Package(
				file_get_contents($this->DBDir.'/'.$dir.'/desc'),
				file_get_contents($this->DBDir.'/'.$dir.'/depends')
				);
			}
		}

	closedir($dh);

	return $packages;
	}

public function getPackageNames()
	{
	$packages = array();

	$dh = opendir($this->DBDir);

	while (false !== ($dir = readdir($dh)))
		{
		if (is_dir($this->DBDir.'/'.$dir) && $dir != '.' && $dir != '..')
			{
			$packages[] = preg_replace('/^(.+)-.+?-.+?$/', '$1', $dir);
			}
		}

	closedir($dh);

	return $packages;
	}

}

?>