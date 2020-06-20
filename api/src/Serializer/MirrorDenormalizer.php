<?php

namespace App\Serializer;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\CountryRepository;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MirrorDenormalizer implements DenormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var CountryRepository */
    private $countryRepository;

    /**
     * @param CountryRepository $countryRepository
     */
    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    /**
     * @param array $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return Mirror[]
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return [
            ...(function () use ($data) {
                foreach ($data['urls'] as $mirrorData) {
                    if (
                        !$mirrorData['active'] ||
                        !$mirrorData['score'] ||
                        !$mirrorData['last_sync'] ||
                        !$mirrorData['delay'] ||
                        !$mirrorData['duration_avg'] ||
                        !$mirrorData['duration_stddev'] ||
                        !$mirrorData['isos']
                    ) {
                        continue;
                    }

                    $mirror = (new Mirror($mirrorData['url'], $mirrorData['protocol']))
                        ->setDelay($mirrorData['delay'])
                        ->setDurationAvg($mirrorData['duration_avg'])
                        ->setScore($mirrorData['score'])
                        ->setCompletionPct($mirrorData['completion_pct'])
                        ->setDurationStddev($mirrorData['duration_stddev'])
                        ->setIpv4($mirrorData['ipv4'])
                        ->setIpv6($mirrorData['ipv6'])
                        ->setLastSync(new \DateTime($mirrorData['last_sync']));

                    if ($mirrorData['country_code'] !== null) {
                        /** @var Country $country */
                        $country = $this->countryRepository->find($mirrorData['country_code']);
                        $mirror->setCountry($country);
                    }

                    yield $mirror;
                }
            })()
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type == Mirror::class . '[]';
    }

    /**
     * @return bool
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
