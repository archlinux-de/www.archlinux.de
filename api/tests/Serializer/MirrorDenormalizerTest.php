<?php

namespace App\Tests\Serializer;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Repository\CountryRepository;
use App\Serializer\MirrorDenormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MirrorDenormalizerTest extends TestCase
{
    public function testSupportsDenormalization(): void
    {
        $mirrorDenormalizer = new MirrorDenormalizer($this->createMock(CountryRepository::class));

        $this->assertTrue($mirrorDenormalizer->supportsDenormalization([], Mirror::class . '[]'));
        $this->assertTrue($mirrorDenormalizer->hasCacheableSupportsMethod());
    }

    public function testDenormalize(): void
    {
        /** @var CountryRepository|MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);
        $countryRepository
            ->expects($this->once())
            ->method('find')
            ->with('DE')
            ->willReturn(new Country('DE'));

        $mirrorDenormalizer = new MirrorDenormalizer($countryRepository);
        /** @var Mirror[] $mirrors */
        $mirrors = $mirrorDenormalizer->denormalize(
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
            ],
            Mirror::class . '[]'
        );

        $this->assertCount(1, $mirrors);
        $this->assertEquals('https://127.0.0.1', $mirrors[0]->getUrl());
        $this->assertNotNull($mirrors[0]->getCountry());
        $this->assertEquals('DE', $mirrors[0]->getCountry()->getCode());
        $this->assertEquals(new \DateTime('2018-01-01'), $mirrors[0]->getLastSync());
        $this->assertEquals(1, $mirrors[0]->getDelay());
        $this->assertEquals(2.1, $mirrors[0]->getDurationAvg());
        $this->assertEquals(3.2, $mirrors[0]->getScore());
        $this->assertEquals(4.3, $mirrors[0]->getCompletionPct());
        $this->assertEquals(5.4, $mirrors[0]->getDurationStddev());
    }
}
