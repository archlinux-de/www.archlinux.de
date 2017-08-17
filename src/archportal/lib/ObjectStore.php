<?php
/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

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

namespace archportal\lib;

use Doctrine\DBAL\Driver\Connection;
use PDO;

class ObjectStore
{
    /** @var Connection */
    private $database;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->database = $connection;
    }

    /**
     * @param string $key
     * @param mixed $object
     * @param int $ttl
     */
    public function addObject(string $key, $object, int $ttl = 0)
    {
        $stm = $this->database->prepare('
        REPLACE INTO
            cache
        SET
            `key` = :key,
            value = :value,
            expires = :expires
        ');
        $stm->bindParam('key', $key, PDO::PARAM_STR);
        $stm->bindValue('value', serialize($object), PDO::PARAM_STR);
        $stm->bindValue('expires', ($ttl > 0 ? Input::getTime() + $ttl : null), PDO::PARAM_INT);
        $stm->execute();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getObject(string $key)
    {
        $this->collectGarbage();
        $stm = $this->database->prepare('
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

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isObject(string $key): bool
    {
        $stm = $this->database->prepare('
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

    private function collectGarbage()
    {
        /* Ignore 49% of requests */
        if (!mt_rand(0, 50)) {
            $stm = $this->database->prepare('
            DELETE FROM
                cache
            WHERE
                expires < :expires
            ');
            $stm->bindValue('expires', Input::getTime(), PDO::PARAM_INT);
            $stm->execute();
        }
    }
}
