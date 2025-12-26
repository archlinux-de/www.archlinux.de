<?php

namespace App\Tests\Entity;

use App\Entity\Country;
use App\Entity\Mirror;
use PHPUnit\Framework\TestCase;

class MirrorTest extends TestCase
{
    public function testUpdate(): void
    {
        $mirror = new Mirror('localhost')
            ->setCountry(new Country('de')->setName('Germany'))
            ->setDurationAvg(0.42)
            ->setDelay(34)
            ->setDurationStddev(53.1)
            ->setCompletionPct(765.324)
            ->setScore(234.2)
            ->setLastSync(new \DateTime('2018-01-30'))
            ->setIpv4(true)
            ->setIpv6(true);

        $mirror->update(
            new Mirror('localhost')
                ->setCountry(new Country('fr')->setName('France'))
                ->setDurationAvg(1.42)
                ->setDelay(43)
                ->setDurationStddev(153.1)
                ->setCompletionPct(1765.324)
                ->setScore(1234.2)
                ->setLastSync(new \DateTime('2017-01-30'))
                ->setIpv4(false)
                ->setIpv6(false)
        );

        $this->assertNotNull($mirror->getCountry());
        $this->assertEquals('fr', $mirror->getCountry()->getCode());
        $this->assertEquals(1.42, $mirror->getDurationAvg());
        $this->assertEquals(43, $mirror->getDelay());
        $this->assertEquals(153.1, $mirror->getDurationStddev());
        $this->assertEquals(1765.324, $mirror->getCompletionPct());
        $this->assertEquals(1234.2, $mirror->getScore());
        $this->assertEquals(new \DateTime('2017-01-30'), $mirror->getLastSync());
        $this->assertFalse($mirror->hasIpv4());
        $this->assertFalse($mirror->hasIpv6());
    }

    public function testUpdateFailsOnMismatchedUrl(): void
    {
        $mirror = new Mirror('localhost');
        $this->expectException(\InvalidArgumentException::class);
        $mirror->update(new Mirror('myhost'));
    }
}
