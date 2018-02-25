<?php

namespace App\Tests\Service;

use App\Service\ReleaseFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ReleaseFetcherTest extends TestCase
{
    public function testFetchReleases()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], json_encode([
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
            ]))
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        $releaseFetcher = new ReleaseFetcher($guzzleClient, '');
        $releases = $releaseFetcher->fetchReleases();

        $this->assertCount(1, $releases);
        $this->assertEquals('2018.01.01', $releases[0]->getVersion());
        $this->assertTrue($releases[0]->isAvailable());
    }

    public function testExceptionOnEmptyResponse()
    {
        $guzzleMock = new MockHandler([
            new Response()
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        $releaseFetcher = new ReleaseFetcher($guzzleClient, '');

        $this->expectException(\RuntimeException::class);
        $releaseFetcher->fetchReleases();
    }

    public function testExceptionOnInvalidResponse()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], 'foo')
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        $releaseFetcher = new ReleaseFetcher($guzzleClient, '');

        $this->expectException(\RuntimeException::class);
        $releaseFetcher->fetchReleases();
    }

    public function testExceptionOnUnknownVersion()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], json_encode(['version' => 2]))
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        $releaseFetcher = new ReleaseFetcher($guzzleClient, '');

        $this->expectException(\RuntimeException::class);
        $releaseFetcher->fetchReleases();
    }

    public function testExceptionOnEmptyMirrorList()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], json_encode(['version' => 1, 'releases' => []]))
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        $releaseFetcher = new ReleaseFetcher($guzzleClient, '');

        $this->expectException(\RuntimeException::class);
        $releaseFetcher->fetchReleases();
    }
}
