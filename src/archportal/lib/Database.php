<?php

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
     * @param PDO $pdo
     */
    public static function setPdo(PDO $pdo)
    {
        self::$pdo = $pdo;
    }
}
