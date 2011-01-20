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

interface ICache {

	public function addObject($key, $object, $ttl = 0);
	public function getObject($key);
	public function isObject($key);
}

class ObjectCache implements ICache {

	private $cache = null;

	public function __construct() {
		if (function_exists('apc_store')) {
			$this->cache = new APCObjectCache();
		} else {
			$this->cache = new NOOPObjectCache();
		}
	}

	public function addObject($key, $object, $ttl = 0) {
		return $this->cache->addObject($key, $object, $ttl);
	}

	public function getObject($key) {
		return $this->cache->getObject($key);
	}

	public function isObject($key) {
		return $this->cache->isObject($key);
	}
}

class NOOPObjectCache implements ICache {

	public function addObject($key, $object, $ttl = 0) {
		return false;
	}

	public function getObject($key) {
		return false;
	}

	public function isObject($key) {
		return false;
	}
}

class APCObjectCache implements ICache {

	public function addObject($key, $object, $ttl = 0) {
		return apc_store($key, $object, $ttl);
	}

	public function getObject($key) {
		return apc_fetch($key);
	}

	public function isObject($key) {
		apc_fetch($key, $success);
		return $success;
	}
}

class PersistentCache extends Modul implements ICache {

	private $time = 0;

	public function __construct() {
		$this->time = time();
	}

	public function addObject($key, $object, $ttl = 0) {
		$stm = DB::prepare('
		REPLACE INTO
			cache
		SET
			`key` = :key,
			value = :value,
			expires = :expires
		');
		$stm->bindParam('key', $key, PDO::PARAM_STR);
		$stm->bindValue('value', serialize($object), PDO::PARAM_STR);
		$stm->bindValue('expires', ($ttl > 0 ? $this->time + $ttl : null), PDO::PARAM_INT);
		$stm->execute();
	}

	public function getObject($key) {
		$this->collectGarbage();
		$stm = DB::prepare('
		SELECT
			value
		FROM
			cache
		WHERE
			`key` = :key
		');
		$stm->bindParam('key', $key, PDO::PARAM_STR);
		$stm->execute();
		$value = $stm->fetchColumn();
		if ($value !== false) {
			return unserialize($value);
		} else {
			return false;
		}
	}

	public function isObject($key) {
		$stm = DB::prepare('
		SELECT
			value
		FROM
			cache
		WHERE
			`key` = :key
		');
		$stm->bindParam('key', $key, PDO::PARAM_STR);
		$stm->execute();
		$value = $stm->fetchColumn();
		return $value !== false;
	}

	private function collectGarbage() {
		/* Ignore 49% of requests */
		if (!mt_rand(0, 50)) {
			$stm = DB::prepare('
			DELETE FROM
				cache
			WHERE
				expires < :expires
			');
			$stm->bindParam('expires', $this->time, PDO::PARAM_INT);
			$stm->execute();
		}
	}
}

?>
