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

class ObjectCache
{

    /**
     * @return string
     */
    private static function getPrefix(): string
    {
        return Config::get('Database', 'database') . ':';
    }

    /**
     * @param string $key
     * @param mixed $object
     * @param int $ttl
     * @return bool
     */
    public static function addObject(string $key, $object, int $ttl = 0): bool
    {
        $key = self::getPrefix() . $key;
        if (function_exists('apc_store')) {
            return apc_store($key, $object, $ttl);
        } elseif (function_exists('xcache_set')) {
            return xcache_set($key, $object, $ttl);
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function getObject(string $key)
    {
        $key = self::getPrefix() . $key;
        if (function_exists('apc_fetch')) {
            return apc_fetch($key);
        } elseif (function_exists('xcache_get')) {
            $result = xcache_get($key);
            if (is_null($result)) {
                return false;
            }

            return $result;
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function isObject(string $key): bool
    {
        $key = self::getPrefix() . $key;
        if (function_exists('apc_exists')) {
            return apc_exists($key);
        } elseif (function_exists('apc_fetch')) {
            apc_fetch($key, $success);

            return $success;
        } elseif (function_exists('xcache_isset')) {
            return xcache_isset($key);
        } else {
            return false;
        }
    }
}
