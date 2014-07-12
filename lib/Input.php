<?php

/*
  Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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
 * @method static Request get
 * @method static Request post
 * @method static Request cookie
 * @method static Request request
 * @method static Request server
 * @method static Request env
 */
class Input
{

    private static $time = null;
    private static $host = null;
    private static $ip = null;
    private static $countryCode = null;
    private static $path = null;

    private function __construct()
    {
    }

    public static function __callStatic($name, $args)
    {
        return Request::getInstance($name);
    }

    public static function getTime()
    {
        if (self::$time == 0) {
            self::$time = self::server()->getInt('REQUEST_TIME', time());
        }

        return self::$time;
    }

    private static function getHost()
    {
        if (is_null(self::$host)) {
            self::$host = self::server()->getString('HTTP_HOST');
        }

        return self::$host;
    }

    public static function getClientIP()
    {
        if (is_null(self::$ip)) {
            self::$ip = self::server()->getString('REMOTE_ADDR', '127.0.0.1');
        }

        return self::$ip;
    }

    public static function getClientCountryCode()
    {
        if (is_null(self::$countryCode)) {
            $ip = self::getClientIP();
            $isIPv6 = strpos($ip, ':') !== false;
            $dbFile = '/usr/share/GeoIP/GeoIP' . ($isIPv6 ? 'v6' : '') . '.dat';

            $geoIp = geoip_open($dbFile, GEOIP_STANDARD);
            if ($isIPv6) {
                $countryCode = geoip_country_code_by_addr_v6($geoIp, $ip) ? : '';
            } else {
                $countryCode = geoip_country_code_by_addr($geoIp, $ip) ? : '';
            }
            geoip_close($geoIp);

            self::$countryCode = $countryCode;
        }

        return self::$countryCode;
    }

    public static function getClientArchitecture()
    {
        $userAgent = self::server()->getString('HTTP_USER_AGENT');
        if (preg_match('/x(86_)?64/', $userAgent)) {
            return 'x86_64';
        } elseif (preg_match('/i[3456]86/', $userAgent)) {
            return 'i686';
        } else {
            return '';
        }
    }

    // FIXME: Rename function
    public static function getPath()
    {
        if (is_null(self::$path)) {
            $directory = dirname(self::server()->getString('SCRIPT_NAME'));
            self::$path = 'http' . (!self::server()->isString('HTTPS') ? '' : 's') . '://' . self::getHost(
                ) . ($directory == '/' ? '' : $directory) . '/';
        }

        return self::$path;
    }
}
