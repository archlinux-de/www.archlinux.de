<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\CountryRepository;
use GuzzleHttp\Client;

class MirrorFetcher implements \IteratorAggregate
{
    /** @var Client */
    private $guzzleClient;

    /** @var string */
    private $mirrorStatusUrl;

    /** @var CountryRepository */
    private $countryRepository;

    /**
     * @param Client $guzzleClient
     * @param string $mirrorStatusUrl
     * @param CountryRepository $countryRepository
     */
    public function __construct(Client $guzzleClient, string $mirrorStatusUrl, CountryRepository $countryRepository)
    {
        $this->guzzleClient = $guzzleClient;
        $this->mirrorStatusUrl = $mirrorStatusUrl;
        $this->countryRepository = $countryRepository;
    }

    /**
     * @return iterable
     */
    public function getIterator(): iterable
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

            yield $mirror;
        }
    }

    private function fetchMirrorStatusUrls(): array
    {
        $response = $this->guzzleClient->request('GET', $this->mirrorStatusUrl);
        $content = $response->getBody()->getContents();
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
