<?php

namespace archportal\lib;

class GeoIP
{
    /**
     * @param string $clientIp
     * @return null|string
     */
    public function getClientCountryCode(string $clientIp): ?string
    {
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

            return $countryCode;
        }

        return null;
    }
}
