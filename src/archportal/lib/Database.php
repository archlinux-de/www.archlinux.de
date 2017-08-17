<?php

namespace archportal\lib;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;

/**
 * @method static Statement prepare(string $statement, array $driver_options = array())
 * @method static int exec(string $statement)
 * @method static Statement query(string $statement)
 * @method static mixed lastInsertId()
 * @method static bool beginTransaction()
 * @method static bool commit()
 * @method static bool rollBack()
 */
class Database
{
    /** @var Connection */
    private static $connection = null;

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
        return call_user_func_array(array(self::$connection, $name), $args);
    }

    /**
     * @param Connection $connection
     */
    public static function setConnection(Connection $connection)
    {
        self::$connection = $connection;
    }
}
