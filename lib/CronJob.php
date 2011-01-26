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

abstract class CronJob {

	private $lockFile = '/tmp/cronjob.lck';
	private $waitForLock = 600;
	private $waitInterval = 10;

	public static function run() {
		$class = get_called_class();
		$instance = new $class();
		$instance->execute();
	}

	abstract public function execute();

	public function __construct() {
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '256M');
		$this->lockFile = Config::get('common', 'tmpdir').'/cronjob.lck';
		$this->aquireLock();
	}

	public function __destruct() {
		$this->releaseLock();
	}

	private function aquireLock() {
		$waited = 0;
		while ($waited < $this->waitForLock) {
			if (!file_exists($this->lockFile)) {
				touch($this->lockFile);
				chmod($this->lockFile , 0600);
				return;
			} else {
				sleep($this->waitInterval);
				$waited += $this->waitInterval;
			}
		}
		throw new Exception('Another cron job is still running');	
	}

	private function releaseLock() {
		if (file_exists($this->lockFile)) {
			unlink($this->lockFile);
		}
	}
}

?>
