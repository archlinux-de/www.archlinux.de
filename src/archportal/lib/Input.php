<?php

namespace archportal\lib;

use Symfony\Component\HttpFoundation\Request;

class Input
{
    /** @var Request */
    private static $request;
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
     * @return string
     */
    private static function getHost(): string
    {
        if (is_null(self::$host)) {
            self::$host = self::$request->getHttpHost();
        }

        return self::$host;
    }

    /**
     * @return string
     */
    private static function getClientIP(): string
    {
        if (is_null(self::$ip)) {
            self::$ip = self::$request->getClientIp() ?: '127.0.0.1';
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
            $directory = dirname(self::$request->getScriptName());
            self::$path = 'http'.(!self::$request->isSecure() ? '' : 's').'://'.self::getHost().($directory == '/' ? '' : $directory).'/';
        }

        return self::$path;
    }

    /**
     * @param Request $request
     */
    public static function setRequest(Request $request)
    {
        self::$request = $request;
    }
}
