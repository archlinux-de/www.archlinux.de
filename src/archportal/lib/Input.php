<?php

declare (strict_types = 1);

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

/**
 * @method static Request get()
 * @method static Request post()
 * @method static Request cookie()
 * @method static Request request()
 * @method static Request server()
 * @method static Request env()
 */
class Input
{
    /** @var int|null */
    private static $time = null;
    /** @var string|null */
    private static $host = null;
    /** @var string|null */
    private static $ip = null;
    /** @var string|null */
    private static $countryCode = null;
    /** @var string|null */
    private static $path = null;

    private function __construct()
    {
    }

    /**
     * @param string $name
     * @param array  $args
     *
     * @return Request
     */
    public static function __callStatic(string $name, array $args): Request
    {
        return Request::getInstance($name);
    }

    /**
     * @return int
     */
    public static function getTime(): int
    {
        if (self::$time == 0) {
            self::$time = self::server()->getInt('REQUEST_TIME', time());
        }

        return self::$time;
    }

    /**
     * @return string
     */
    private static function getHost(): string
    {
        if (is_null(self::$host)) {
            self::$host = self::server()->getString('HTTP_HOST');
        }

        return self::$host;
    }

    /**
     * @return string
     */
    public static function getClientIP(): string
    {
        if (is_null(self::$ip)) {
            self::$ip = self::server()->getString('REMOTE_ADDR', '127.0.0.1');
        }

        return self::$ip;
    }

    /**
     * @return string|null
     */
    public static function getClientCountryCode()
    {
        if (is_null(self::$countryCode)) {
            $ip = self::getClientIP();
            $isIPv6 = strpos($ip, ':') !== false;
            $dbFile = '/usr/share/GeoIP/GeoIP'.($isIPv6 ? 'v6' : '').'.dat';

            if (file_exists($dbFile)) {
                $geoIp = geoip_open($dbFile, GEOIP_STANDARD);
                if ($isIPv6) {
                    $countryCode = geoip_country_code_by_addr_v6($geoIp, $ip) ?: null;
                } else {
                    $countryCode = geoip_country_code_by_addr($geoIp, $ip) ?: null;
                }
                geoip_close($geoIp);

                self::$countryCode = $countryCode;
            }
        }

        return self::$countryCode;
    }

    /**
     * @return string
     */
    public static function getPath(): string
    {
        if (is_null(self::$path)) {
            $directory = dirname(self::server()->getString('SCRIPT_NAME'));
            self::$path = 'http'.(!self::server()->isString('HTTPS') ? '' : 's').'://'.self::getHost().($directory == '/' ? '' : $directory).'/';
        }

        return self::$path;
    }
}
