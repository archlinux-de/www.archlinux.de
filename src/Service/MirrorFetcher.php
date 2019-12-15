<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\CountryRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-implements \IteratorAggregate<Mirror>
 */
class MirrorFetcher implements \IteratorAggregate
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $mirrorStatusUrl;

    /** @var CountryRepository */
    private $countryRepository;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $mirrorStatusUrl
     * @param CountryRepository $countryRepository
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $mirrorStatusUrl,
        CountryRepository $countryRepository
    ) {
        $this->httpClient = $httpClient;
        $this->mirrorStatusUrl = $mirrorStatusUrl;
        $this->countryRepository = $countryRepository;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->fetchMirrorStatusUrls() as $mirrorData) {
            $mirror = new Mirror($mirrorData['url'], $mirrorData['protocol']);

            if (!is_null($mirrorData['country_code'])) {
                /** @var Country $country */
                $country = $this->countryRepository->find($mirrorData['country_code']);
                $mirror->setCountry($country);
            }
            if (!is_null($mirrorData['last_sync'])) {
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
    }

    private function fetchMirrorStatusUrls(): array
    {
        $response = $this->httpClient->request('GET', $this->mirrorStatusUrl);
        $content = $response->getContent();
        if (empty($content)) {
            throw new \RuntimeException('empty mirrorstatus');
        }
        $data = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException('could not decode mirrorstatus');
        }
        if ($data['version'] != 3) {
            throw new \RuntimeException('incompatible mirrorstatus version');
        }
        if (empty($data['urls'])) {
            throw new \RuntimeException('mirrorlist is empty');
        }
        return $data['urls'];
    }
}
