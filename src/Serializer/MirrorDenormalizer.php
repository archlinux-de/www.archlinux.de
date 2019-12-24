<?php

namespace App\Serializer;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\CountryRepository;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MirrorDenormalizer implements DenormalizerInterface
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
     * @param array<mixed> $data
     * @param string $type
     * @param string|null $format
     * @param array<mixed> $context
     * @return Mirror[]
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return [
            ...(function () use ($data) {
                foreach ($data['urls'] as $mirrorData) {
                    $mirror = new Mirror($mirrorData['url'], $mirrorData['protocol']);

                    if ($mirrorData['country_code'] !== null) {
                        /** @var Country $country */
                        $country = $this->countryRepository->find($mirrorData['country_code']);
                        $mirror->setCountry($country);
                    }
                    if ($mirrorData['last_sync'] !== null) {
                        $mirror->setLastSync(new \DateTime($mirrorData['last_sync']));
                    }
                    $mirror->setDelay($mirrorData['delay']);
                    $mirror->setDurationAvg($mirrorData['duration_avg']);
                    $mirror->setScore($mirrorData['score']);
                    $mirror->setCompletionPct($mirrorData['completion_pct']);
                    $mirror->setDurationStddev($mirrorData['duration_stddev']);
                    $mirror->setIsos($mirrorData['isos']);
                    $mirror->setIpv4($mirrorData['ipv4']);
                    $mirror->setIpv6($mirrorData['ipv6']);
                    $mirror->setActive($mirrorData['active']);

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
        return $type == Mirror::class . '[]' && isset($data['urls']);
    }
}
