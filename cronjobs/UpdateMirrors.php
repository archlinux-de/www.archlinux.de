#!/usr/bin/php
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

ini_set('max_execution_time', 0);
ini_set('include_path', ini_get('include_path') . ':../');
require ('modules/Modul.php');
require ('modules/Settings.php');
require ('modules/Exceptions.php');
require ('modules/DB.php');

class UpdateMirrors extends Modul {

	private function getTmpDir() {
		$tmp = ini_get('upload_tmp_dir');
		return empty($tmp) ? '/tmp' : $tmp;
	}

	private function getLockFile() {
		return $this->getTmpDir() . '/MirrorCheckRunning.lock';
	}

	public function runUpdate() {
		if (file_exists($this->getLockFile())) {
			die('UpdateMirrors still in progress');
		} else {
			touch($this->getLockFile());
			chmod($this->getLockFile() , 0600);
		}
		try {
			$status = $this->getMirrorStatus();
			if ($status['version'] != 1) {
				throw new RuntimeException('incompatible mirrorstatus version');
			}
			$mirrors = $status['urls'];
			if (empty($mirrors)) {
				throw new RuntimeException('mirrorlist is empty');
			}
			$this->updateMirrorlist($mirrors);
		} catch(RuntimeException $e) {
			echo ('Warning: UpdateMirrors failed: ' . $e->getMessage());
		}
		unlink($this->getLockFile());
	}

	private function updateMirrorlist($mirrors) {
		try {
			DB::query('CREATE TEMPORARY TABLE tmirrors LIKE mirrors');
			$stm = DB::prepare('
			INSERT INTO
				tmirrors
			SET
				host = :host,
				protocol = :protocol,
				country = :country,
				lastsync = :lastsync,
				delay = :delay,
				time = :time
			');
			foreach ($mirrors as $mirror) {
				$stm->bindParam('host', $mirror['url'], PDO::PARAM_STR);
				$stm->bindParam('protocol', $mirror['protocol'], PDO::PARAM_STR);
				$stm->bindParam('country', $mirror['country'], PDO::PARAM_STR);
				$last_sync = date_parse($mirror['last_sync']);
				$last_sync = $last_sync['error_count'] > 0 
					? null 
					: gmmktime($last_sync['hour'],
						$last_sync['minute'],
						$last_sync['second'],
						$last_sync['month'],
						$last_sync['day'],
						$last_sync['year']);
				$stm->bindParam('lastsync', $last_sync, PDO::PARAM_INT);
				$stm->bindParam('delay', $mirror['delay'], PDO::PARAM_INT);
				$stm->bindParam('time', $mirror['duration_avg'], PDO::PARAM_STR);
				$stm->execute();
			}
			DB::query('TRUNCATE mirrors');
			DB::query('INSERT INTO mirrors SELECT * FROM tmirrors');
		} catch(RuntimeException $e) {
			echo ('Warning: updateMirrorlist failed: ' . $e->getMessage());
		}
	}

	private function getMirrorStatus() {
		if (false === ($curl = curl_init($this->Settings->getValue('mirrorstatus')))) {
			throw new RuntimeException('failed to init curl: ' . htmlspecialchars($url));
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
		curl_setopt($curl, CURLOPT_TIMEOUT, 120);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_USERAGENT, 'bob@archlinux.de');
		curl_setopt($curl, CURLOPT_USERPWD, 'anonymous:bob@archlinux.de');
		$content = curl_exec($curl);
		if (curl_errno($curl) > 0 || false === $content) {
			$error = htmlspecialchars(curl_error($curl));
			curl_close($curl);
			throw new RuntimeException($error, 1);
		} elseif (empty($content)) {
			curl_close($curl);
			throw new RuntimeException('empty mirrorstatus', 1);
		}
		curl_close($curl);
		$mirrors = json_decode($content, true);
		if (json_last_error() != JSON_ERROR_NONE) {
			throw new RuntimeException('could not decode mirrorstatus', 1);
		}
		return $mirrors;
	}
}

$upd = new UpdateMirrors();
$upd->runUpdate();

?>
