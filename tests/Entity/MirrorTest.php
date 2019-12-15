<?php

namespace App\Tests\Entity;

use App\Entity\Country;
use App\Entity\Mirror;
use PHPUnit\Framework\TestCase;

class MirrorTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $mirror = (new Mirror('localhost', 'https'))
            ->setCountry((new Country('de'))->setName('Germany'))
            ->setDurationAvg(0.42)
            ->setDelay(34)
            ->setDurationStddev(53.1)
            ->setCompletionPct(765.324)
            ->setScore(234.2)
            ->setLastSync(new \DateTime('2018-01-30'))
            ->setIsos(true)
            ->setIpv4(true)
            ->setIpv6(true)
            ->setActive(true);

        $json = (string)json_encode($mirror);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'url' => 'localhost',
                'protocol' => 'https',
                'country' => [
                    'code' => 'de',
                    'name' => 'Germany'
                ],
                'durationAvg' => 0.42,
                'delay' => 34,
                'durationStddev' => 53.1,
                'completionPct' => 765.324,
                'score' => 234.2,
                'lastsync' => 'Tue, 30 Jan 2018 00:00:00 +0000',
                'isos' => true,
                'ipv4' => true,
                'ipv6' => true,
                'active' => true
            ],
            $jsonArray
        );
    }

    public function testUpdate(): void
    {
        $mirror = (new Mirror('localhost', 'https'))
            ->setCountry((new Country('de'))->setName('Germany'))
            ->setDurationAvg(0.42)
            ->setDelay(34)
            ->setDurationStddev(53.1)
            ->setCompletionPct(765.324)
            ->setScore(234.2)
            ->setLastSync(new \DateTime('2018-01-30'))
            ->setIsos(true)
            ->setIpv4(true)
            ->setIpv6(true)
            ->setActive(true);

        $mirror->update(
            (new Mirror('localhost', 'http'))
                ->setCountry((new Country('fr'))->setName('France'))
                ->setDurationAvg(1.42)
                ->setDelay(43)
                ->setDurationStddev(153.1)
                ->setCompletionPct(1765.324)
                ->setScore(1234.2)
                ->setLastSync(new \DateTime('2017-01-30'))
                ->setIsos(false)
                ->setIpv4(false)
                ->setIpv6(false)
                ->setActive(false)
        );

        $this->assertEquals('http', $mirror->getProtocol());
        $this->assertNotNull($mirror->getCountry());
        $this->assertEquals('fr', $mirror->getCountry()->getCode());
        $this->assertEquals(1.42, $mirror->getDurationAvg());
        $this->assertEquals(43, $mirror->getDelay());
        $this->assertEquals(153.1, $mirror->getDurationStddev());
        $this->assertEquals(1765.324, $mirror->getCompletionPct());
        $this->assertEquals(1234.2, $mirror->getScore());
        $this->assertEquals(new \DateTime('2017-01-30'), $mirror->getLastSync());
        $this->assertFalse($mirror->hasIsos());
        $this->assertFalse($mirror->hasIpv4());
        $this->assertFalse($mirror->hasIpv6());
        $this->assertFalse($mirror->isActive());
    }

    public function testUpdateFailsOnMismatchedUrl(): void
    {
        $mirror = new Mirror('localhost', 'https');
        $this->expectException(\InvalidArgumentException::class);
        $mirror->update(new Mirror('myhost', 'https'));
    }
}
