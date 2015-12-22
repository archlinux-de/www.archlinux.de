<?php

declare (strict_types = 1);

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

use RuntimeException;

class Config
{
    private static $config = array();

    private function __construct()
    {
    }

    /**
     * @param string $section
     * @param string $key
     * @param mixed  $value
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
            throw new RuntimeException('No configuration entry was found for key "'.$key.'" in section "'.$section.'"');
        }
    }
}

require __DIR__.'/../../../config/DefaultConfig.php';

if (file_exists(__DIR__.'/../../../config/LocalConfig.php')) {
    include __DIR__.'/../../../config/LocalConfig.php';
}
