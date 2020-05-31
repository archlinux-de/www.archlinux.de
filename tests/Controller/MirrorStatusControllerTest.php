<?php

namespace App\Tests\Controller;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Tests\DatabaseSearchTestCase;

/**
 * @covers \App\Controller\MirrorStatusController
 */
class MirrorStatusControllerTest extends DatabaseSearchTestCase
{
    public function testMirrorsAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/', 'https');
        $mirror->setScore(1);
        $entityManager->persist($mirror);
        $entityManager->flush();

        sleep(1);

        $client = $this->getClient();
        $client->request('GET', '/api/mirrors', ['query' => '127']);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
        $jsonArray = json_decode($response->getContent(), true);
        $this->assertEquals(1, $jsonArray['count']);
        $this->assertEquals(1, $jsonArray['total']);
        $this->assertCount(1, $jsonArray['items']);
        $this->assertEquals('https://127.0.0.2/', $jsonArray['items'][0]['url']);
        $this->assertEquals('https', $jsonArray['items'][0]['protocol']);
    }

    public function testMirrorAction(): void
    {
        $entityManager = $this->getEntityManager();
        $country = (new Country('DE'))->setName('Germany');
        $mirror = (new Mirror('https://127.0.0.2/', 'https'))
            ->setCountry($country)
            ->setDurationAvg(1.2)
            ->setDelay(2)
            ->setDurationStddev(3.4)
            ->setCompletionPct(5.6)
            ->setScore(7.8)
            ->setLastSync(new \DateTime('2020-02-02'))
            ->setIpv4(true)
            ->setIpv6(true);
        $entityManager->persist($country);
        $entityManager->persist($mirror);
        $entityManager->flush();

        sleep(1);

        $client = $this->getClient();
        $client->request('GET', '/api/mirrors/' . urlencode($mirror->getUrl()));
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
        $jsonArray = json_decode($response->getContent(), true);
        $this->assertEquals(
            [
                'url' => 'https://127.0.0.2/',
                'protocol' => 'https',
                'country' => [
                    'code' => 'DE',
                    'name' => 'Germany'
                ],
                'durationAvg' => 1.2,
                'delay' => 2,
                'durationStddev' => 3.4,
                'completionPct' => 5.6,
                'score' => 7.8,
                'lastSync' => '2020-02-02T00:00:00+00:00',
                'ipv4' => true,
                'ipv6' => true,
                'host' => '127.0.0.2'
            ],
            $jsonArray
        );
    }
}
