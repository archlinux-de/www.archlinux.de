<?php

namespace App\Service;

use MaxMind\Db\Reader;
use Psr\Log\LoggerInterface;

readonly class GeoIp
{
    public function __construct(private Reader $reader, private LoggerInterface $logger)
    {
    }

    public function getCountryCode(string $clientIp): ?string
    {
        try {
            $response = $this->reader->get($clientIp);
            if (
                is_array($response)
                && array_key_exists('country', $response) && is_array($response['country'])
                && array_key_exists('iso_code', $response['country']) && is_string($response['country']['iso_code'])
            ) {
                return $response['country']['iso_code'];
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return null;
    }
}
