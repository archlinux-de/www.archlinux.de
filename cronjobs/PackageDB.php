<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

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

require_once ('Package.php');

class PackageDB {

	private $mirror = 'http://mirrors.kernel.org/archlinux/';
	private $repository = 'core';
	private $architecture = 'i686';
	private $DBDir = '/tmp/dbdir';
	private $mtime = 0;
	private $updated = false;

	public function __construct($mirror, $repository, $architecture, $lastmtime) {
		$this->mirror = $mirror;
		$this->repository = $repository;
		$this->architecture = $architecture;
		$this->update($lastmtime);
	}

	private function getTmpDir() {
		$tmp = ini_get('upload_tmp_dir');
		return empty($tmp) ? '/tmp' : $tmp;
	}

	public function __destruct() {
		if ($this->updated && is_dir($this->DBDir)) {
			$this->rmrf($this->DBDir);
		}
	}

	public function getMTime() {
		return $this->mtime;
	}

	private function update($lastmtime) {
		$download = new Download($this->mirror.$this->repository.'/os/'.$this->architecture.'/'.$this->repository.'.db.tar.gz');
		$this->mtime = $download->getMTime();

		if ($this->mtime > $lastmtime) {
			$this->DBDir = tempnam($this->getTmpDir().'/', $this->architecture.'-'.$this->repository.'-pkgdb-');
			unlink($this->DBDir);
			mkdir($this->DBDir, 0700);

			system('bsdtar -xf '.$download->getFile().' -C '.$this->DBDir, $return);
			if ($return == 0) {
				$this->updated = true;
			} else {
				$this->mtime = 0;
				$this->updated = false;
				$this->rmrf($this->DBDir);
			}
		}
	}

	private function rmrf($dir) {
		if (is_dir($dir) && !is_link($dir)) {
			$dh = opendir($dir);
			while (false !== ($file = readdir($dh))) {
				if ($file != '.' && $file != '..') {
					if (!$this->rmrf($dir . '/' . $file)) {
						trigger_error('Could not remove ' . $dir . '/' . $file);
					}
				}
			}
			closedir($dh);
			return rmdir($dir);
		} else {
			return unlink($dir);
		}
	}

	public function getUpdatedPackages($timestamp) {
		$packages = array();
		if ($this->updated && is_dir($this->DBDir)) {
			$dh = opendir($this->DBDir);
			while (false !== ($dir = readdir($dh))) {
				if ($dir != '.' && $dir != '..' 
					&& file_exists($this->DBDir . '/' . $dir . '/desc') 
					&& file_exists($this->DBDir . '/' . $dir . '/depends') 
					&& filemtime($this->DBDir . '/' . $dir . '/desc') >= $timestamp) {
					$packages[] = new Package(file_get_contents($this->DBDir.'/'.$dir.'/desc'), 
						file_get_contents($this->DBDir.'/'.$dir.'/depends'),
						filemtime($this->DBDir.'/'.$dir.'/desc'));
				}
			}
			closedir($dh);
		}
		return $packages;
	}

	public function getPackageNames() {
		$packages = null;
		if ($this->updated && is_dir($this->DBDir)) {
			$packages = array();
			$dh = opendir($this->DBDir);
			while (false !== ($dir = readdir($dh))) {
				if (is_dir($this->DBDir . '/' . $dir) && $dir != '.' && $dir != '..') {
					$packages[] = preg_replace('/^(.+)-.+?-.+?$/', '$1', $dir);
				}
			}
			closedir($dh);
		}
		return $packages;
	}
}

?>
