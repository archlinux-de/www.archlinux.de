<?php

namespace App\Serializer;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\CountryRepository;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

readonly class MirrorDenormalizer implements DenormalizerInterface
{
    public function __construct(private CountryRepository $countryRepository)
    {
    }

    /**
     * @param mixed[] $data
     * @return Mirror[]
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): array
    {
        return [
            ...(function () use ($data) {
                assert(is_array($data['urls']));
                foreach ($data['urls'] as $mirrorData) {
                    if (
                        !$mirrorData['active'] ||
                        !$mirrorData['score'] ||
                        !$mirrorData['last_sync'] ||
                        !$mirrorData['delay'] ||
                        !$mirrorData['duration_avg'] ||
                        !$mirrorData['duration_stddev'] ||
                        !$mirrorData['isos'] ||
                        $mirrorData['protocol'] !== 'https' // filter out http, rsync and ftp mirrors
                    ) {
                        continue;
                    }

                    $mirror = (new Mirror($mirrorData['url']))
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

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type === Mirror::class . '[]';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Mirror::class . '[]' => true];
    }
}
