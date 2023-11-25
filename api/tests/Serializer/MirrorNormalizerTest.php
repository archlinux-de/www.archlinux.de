<?php

namespace App\Tests\Serializer;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Entity\MirrorPopularity as Popularity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class MirrorNormalizerTest extends KernelTestCase
{
    private Serializer $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = static::getContainer()->get('serializer');
    }

    public function testNormalize(): void
    {
        $mirror = (new Mirror('localhost'))
            ->setCountry((new Country('de'))->setName('Germany'))
            ->setDurationAvg(0.42)
            ->setDelay(34)
            ->setDurationStddev(53.1)
            ->setCompletionPct(765.324)
            ->setScore(234.2)
            ->setLastSync(new \DateTime('2018-01-30'))
            ->setIpv4(true)
            ->setIpv6(true)
            ->setPopularity(new Popularity(12.34, 1234, 512));

        $json = $this->serializer->serialize($mirror, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'url' => 'localhost',
                'country' => [
                    'code' => 'de',
                    'name' => 'Germany'
                ],
                'durationAvg' => 0.42,
                'delay' => 34,
                'durationStddev' => 53.1,
                'completionPct' => 765.324,
                'score' => 234.2,
                'lastSync' => '2018-01-30T00:00:00+00:00',
                'ipv4' => true,
                'ipv6' => true,
                'host' => null,
                'popularity' => [
                    'popularity' => 12.34,
                    'samples' => 1234,
                    'count' => 512
                ]
            ],
            $jsonArray
        );
    }
}
