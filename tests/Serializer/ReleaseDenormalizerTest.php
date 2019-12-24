<?php

namespace App\Tests\Serializer;

use App\Entity\Release;
use App\Serializer\ReleaseDenormalizer;
use PHPUnit\Framework\TestCase;

class ReleaseDenormalizerTest extends TestCase
{
    public function testSupportsDenormalization(): void
    {
        $releaseDenormalizer = new ReleaseDenormalizer();

        $this->assertTrue($releaseDenormalizer->supportsDenormalization(['releases' => []], Release::class . '[]'));
    }

    public function testDenormalize(): void
    {
        $releaseDenormalizer = new ReleaseDenormalizer();
        /** @var Release[] $releases */
        $releases = $releaseDenormalizer->denormalize(
            [
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
            ],
            Release::class . '[]'
        );

        $this->assertCount(1, $releases);
        $this->assertEquals('2018.01.01', $releases[0]->getVersion());
        $this->assertTrue($releases[0]->isAvailable());
    }
}
