<?php

namespace App\Tests\Entity;

use App\Entity\Country;
use App\Entity\Mirror;
use PHPUnit\Framework\TestCase;

class MirrorTest extends TestCase
{
    public function testJsonSerialize()
    {
        $mirror = new Mirror('localhost', 'https');
        $mirror->setCountry((new Country('de'))->setName('Germany'));
        $mirror->setDurationAvg(0.42);
        $mirror->setDelay(34);
        $mirror->setDurationStddev(53.1);
        $mirror->setCompletionPct(765.324);
        $mirror->setScore(234.2);
        $mirror->setLastSync(new \DateTime('2018-01-30'));
        $mirror->setIsos(true);
        $mirror->setIpv4(true);
        $mirror->setIpv6(true);
        $mirror->setActive(true);

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
}
