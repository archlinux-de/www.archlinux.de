<?php

namespace archportal\lib;

class Config
{
    private static $config = array();

    private function __construct()
    {
    }

    /**
     * @param string $section
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $section, string $key, $value)
    {
        self::$config[$section][$key] = $value;
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return mixed
     */
    public static function get(string $section, string $key)
    {
        if (isset(self::$config[$section][$key])) {
            return self::$config[$section][$key];
        } else {
            throw new \RuntimeException('No configuration entry was found for key "' . $key . '" in section "' . $section . '"');
        }
    }
}
