<?php

namespace App\Tests\Service;

use App\Entity\Release;
use App\Service\ReleaseFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ReleaseFetcherTest extends TestCase
{
    public function testFetchReleases()
    {
        $responseMock = new MockResponse(
            (string)json_encode(
                [
                    'version' => 1,
                    'releases' => [
                        [
                            'available' => true,
                            'info' => '',
                            'iso_url' => '',
                            'md5_sum' => '',
                            'created' => '',
                            'kernel_version' => '',
                            'release_date' => '',
                            'torrent_url' => '',
                            'version' => '2018.01.01',
                            'sha1_sum' => '',
                            'torrent' => [
                                'comment' => '',
                                'info_hash' => '',
                                'piece_length' => 0,
                                'file_name' => '',
                                'announce' => '',
                                'file_length' => 0,
                                'piece_count' => 0,
                                'created_by' => '',
                                'creation_date' => ''
                            ],
                            'magnet_uri' => ''
                        ]
                    ]
                ]
            )
        );
        $httpClient = new MockHttpClient($responseMock);

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo');
        /** @var Release[] $releases */
        $releases = iterator_to_array($releaseFetcher);

        $this->assertCount(1, $releases);
        $this->assertEquals('2018.01.01', $releases[0]->getVersion());
        $this->assertTrue($releases[0]->isAvailable());
    }

    public function testExceptionOnEmptyResponse()
    {
        $httpClient = new MockHttpClient(new MockResponse(''));

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo');

        $this->expectException(\RuntimeException::class);
        iterator_to_array($releaseFetcher);
    }

    public function testExceptionOnInvalidResponse()
    {
        $httpClient = new MockHttpClient(new MockResponse('foo'));

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo');

        $this->expectException(\RuntimeException::class);
        iterator_to_array($releaseFetcher);
    }

    public function testExceptionOnUnknownVersion()
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

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo');

        $this->expectException(\RuntimeException::class);
        iterator_to_array($releaseFetcher);
    }

    public function testExceptionOnEmptyMirrorList()
    {
        $httpClient = new MockHttpClient(
            new MockResponse(
                (string)json_encode(
                    [
                        'version' => 1,
                        'releases' => []
                    ]
                )
            )
        );

        $releaseFetcher = new ReleaseFetcher($httpClient, 'http://foo');

        $this->expectException(\RuntimeException::class);
        iterator_to_array($releaseFetcher);
    }
}
