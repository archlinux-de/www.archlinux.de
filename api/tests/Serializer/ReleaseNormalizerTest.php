<?php

namespace App\Tests\Serializer;

use App\Entity\Release;
use App\Serializer\ReleaseNormalizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class ReleaseNormalizerTest extends KernelTestCase
{
    /** @var Serializer */
    private $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = self::$container->get('serializer');
    }

    public function testSupportsNormalization(): void
    {
        $this->assertTrue(
            self::$container->get(ReleaseNormalizer::class)->supportsNormalization(
                new Release(''),
                'json'
            )
        );
    }

    public function testNormalize(): void
    {
        $releaseDate = new \DateTime('2018-01-01');

        $release = (new Release('2018.01.01'))
            ->setReleaseDate($releaseDate)
            ->setAvailable(true)
            ->setKernelVersion('3.11')
            ->setInfo('foo bar')
            ->setFileName('2018.01.01.iso')
            ->setTorrentUrl('/releases/2018.01.01.iso.torrent');

        $json = $this->serializer->serialize($release, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'version' => '2018.01.01',
                'kernelVersion' => '3.11',
                'releaseDate' => '2018-01-01T00:00:00+00:00',
                'available' => true,
                'info' => 'foo bar',
                'isoUrl' => 'http://localhost/download/iso/2018.01.01/2018.01.01.iso',
                'sha1Sum' => null,
                'torrentUrl' => 'https://archlinux.org/releases/2018.01.01.iso.torrent',
                'fileSize' => null,
                'magnetUri' => null,
                'isoPath' => '/iso/2018.01.01/2018.01.01.iso',
                'isoSigUrl' => 'http://localhost/download/iso/2018.01.01/2018.01.01.iso.sig',
                'fileName' => '2018.01.01.iso'
            ],
            $jsonArray
        );
    }
}
