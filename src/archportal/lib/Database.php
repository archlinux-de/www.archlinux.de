<?php
/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\lib;

use PDO;
use PDOStatement;

/**
 * @method static PDOStatement prepare(string $statement, array $driver_options = array())
 * @method static int exec(string $statement)
 * @method static PDOStatement query(string $statement)
 * @method static mixed lastInsertId()
 * @method static bool beginTransaction()
 * @method static bool commit()
 * @method static bool rollBack()
 */
class Database
{
    /** @var PDO */
    private static $pdo = null;
    /** @var string */
    private static $database = '';

    private function __construct()
    {
    }

    /**
     * @param string $name
     * @param array $args
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $args)
    {
        return call_user_func_array(array(self::$pdo, $name), $args);
    }

    /**
     * @param string $name
     * @param int $timeout
     *
     * @return bool
     */
    public static function aquireLock(string $name, int $timeout = 0): bool
    {
        $stm = self::prepare('SELECT GET_LOCK(:name, :timeout)');
        $stm->bindValue('name', self::$database . ':' . $name, PDO::PARAM_STR);
        $stm->bindParam('timeout', $timeout, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchColumn() == 1;
    }

    /**
     * @param string $name
     */
    public static function releaseLock(string $name)
    {
        $stm = self::prepare('DO RELEASE_LOCK(:name)');
        $stm->bindValue('name', self::$database . ':' . $name, PDO::PARAM_STR);
        $stm->execute();
    }

    /**
     * @param PDO $pdo
     */
    public static function setPdo(PDO $pdo)
    {
        self::$pdo = $pdo;
    }

    /**
     * @param string $database
     */
    public static function setDatabase(string $database)
    {
        self::$database = $database;
    }
}
