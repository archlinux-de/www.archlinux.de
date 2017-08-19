<?php

namespace archportal\lib;

class Input
{
    /** @var string|null */
    private static $countryCode = null;

    private function __construct()
    {
    }

    /**
     * @return string|null
     */
    public static function getClientCountryCode(string $clientIp)
    {
        if (is_null(self::$countryCode)) {
            $isIPv6 = strpos($clientIp, ':') !== false;
            $dbFile = '/usr/share/GeoIP/GeoIP' . ($isIPv6 ? 'v6' : '') . '.dat';

            if (file_exists($dbFile)) {
                $geoIp = geoip_open($dbFile, GEOIP_STANDARD);
                if ($isIPv6) {
                    $countryCode = geoip_country_code_by_addr_v6($geoIp, $clientIp) ?: null;
                } else {
                    $countryCode = geoip_country_code_by_addr($geoIp, $clientIp) ?: null;
                }
                geoip_close($geoIp);

                self::$countryCode = $countryCode;
            }
        }

        return self::$countryCode;
    }
}
