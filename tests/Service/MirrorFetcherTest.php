<?php

namespace App\Tests\Service;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\CountryRepository;
use App\Service\MirrorFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MirrorFetcherTest extends TestCase
{
    public function testFetchMirrors(): void
    {
        $responseMock = new MockResponse(
            (string)json_encode(
                [
                    'version' => 3,
                    'urls' => [
                        [
                            'url' => 'https://127.0.0.1',
                            'protocol' => 'https',
                            'country_code' => 'DE',
                            'last_sync' => '2018-01-01',
                            'delay' => 1,
                            'duration_avg' => 2.1,
                            'score' => 3.2,
                            'completion_pct' => 4.3,
                            'duration_stddev' => 5.4,
                            'isos' => true,
                            'ipv4' => true,
                            'ipv6' => true,
                            'active' => true
                        ]
                    ]
                ]
            )
        );
        $httpClient = new MockHttpClient($responseMock);

        /** @var CountryRepository|MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);
        $countryRepository->method('find')->with('DE')->willReturn(new Country('DE'));

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $countryRepository);
        /** @var Mirror[] $mirrors */
        $mirrors = iterator_to_array($mirrorFetcher);

        $this->assertCount(1, $mirrors);
        $this->assertEquals('https://127.0.0.1', $mirrors[0]->getUrl());
        $this->assertEquals('https', $mirrors[0]->getProtocol());
        $this->assertNotNull($mirrors[0]->getCountry());
        $this->assertEquals('DE', $mirrors[0]->getCountry()->getCode());
        $this->assertEquals(new \DateTime('2018-01-01'), $mirrors[0]->getLastSync());
        $this->assertEquals(1, $mirrors[0]->getDelay());
        $this->assertEquals(2.1, $mirrors[0]->getDurationAvg());
        $this->assertEquals(3.2, $mirrors[0]->getScore());
        $this->assertEquals(4.3, $mirrors[0]->getCompletionPct());
        $this->assertEquals(5.4, $mirrors[0]->getDurationStddev());
    }

    public function testExceptionOnEmptyResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(''));

        /** @var CountryRepository|MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $countryRepository);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($mirrorFetcher);
    }

    public function testExceptionOnInvalidResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('foo'));

        /** @var CountryRepository|MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $countryRepository);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($mirrorFetcher);
    }

    public function testExceptionOnUnknownVersion(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(
                (string)json_encode(
                    [
                        'version' => 2
                    ]
                )
            )
        );

        /** @var CountryRepository|MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $countryRepository);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($mirrorFetcher);
    }

    public function testExceptionOnEmptyMirrorList(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(
                (string)json_encode(
                    [
                        'version' => 3,
                        'urls' => []
                    ]
                )
            )
        );

        /** @var CountryRepository|MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($httpClient, 'http://foo', $countryRepository);

        $this->expectException(\RuntimeException::class);
        iterator_to_array($mirrorFetcher);
    }
}
