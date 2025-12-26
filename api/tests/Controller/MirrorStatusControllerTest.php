<?php

namespace App\Tests\Controller;

use App\Controller\MirrorStatusController;
use App\Entity\Country;
use App\Entity\Mirror;
use App\Entity\MirrorPopularity as Popularity;
use App\Tests\DatabaseSearchTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MirrorStatusController::class)]
class MirrorStatusControllerTest extends DatabaseSearchTestCase
{
    public function testMirrorsAction(): void
    {
        $entityManager = $this->getEntityManager();
        $mirror = new Mirror('https://127.0.0.2/')
            ->setScore(1)
            ->setLastSync(new \DateTime('2020-02-02'));
        $entityManager->persist($mirror);
        $entityManager->flush();

        $client = $this->getClient();
        $client->request('GET', '/api/mirrors', ['query' => '127']);
        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
        $jsonArray = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonArray);
        $this->assertEquals(1, $jsonArray['count']);
        $this->assertEquals(1, $jsonArray['total']);
        $this->assertCount(1, $jsonArray['items']);
        $this->assertEquals('https://127.0.0.2/', $jsonArray['items'][0]['url']);
    }

    public function testMirrorAction(): void
    {
        $entityManager = $this->getEntityManager();
        $country = new Country('DE')->setName('Germany');
        $mirror = new Mirror('https://127.0.0.2/')
            ->setCountry($country)
            ->setDurationAvg(1.2)
            ->setDelay(2)
            ->setDurationStddev(3.4)
            ->setCompletionPct(5.6)
            ->setScore(7.8)
            ->setLastSync(new \DateTime('2020-02-02'))
            ->setIpv4(true)
            ->setIpv6(true)
            ->setPopularity(new Popularity(12.34, 1234, 512));
        $entityManager->persist($country);
        $entityManager->persist($mirror);
        $entityManager->flush();

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
                'popularity' => [
                    'popularity' => 12.34,
                    'samples' => 1234,
                    'count' => 512
                ],
                'host' => '127.0.0.2'
            ],
            $jsonArray
        );
    }

    public function testMirrorsActionWithQuotes(): void
    {
        $entityManager = $this->getEntityManager();

        $countryDE = new Country('DE')->setName('Germany');
        $countryUS = new Country('US')->setName('United States');
        $countryUK = new Country('UK')->setName('United Kingdom');

        $mirror1 = new Mirror('https://mirror.example.de/')
            ->setCountry($countryDE)
            ->setScore(1)
            ->setLastSync(new \DateTime('2023-01-01'));

        $mirror2 = new Mirror('https://mirror.example.us/')
            ->setCountry($countryUS)
            ->setScore(1)
            ->setLastSync(new \DateTime('2023-01-01'));

        $mirror3 = new Mirror('https://mirror.example.uk/')
            ->setCountry($countryUK)
            ->setScore(1)
            ->setLastSync(new \DateTime('2023-01-01'));

        $entityManager->persist($countryDE);
        $entityManager->persist($countryUS);
        $entityManager->persist($countryUK);
        $entityManager->persist($mirror1);
        $entityManager->persist($mirror2);
        $entityManager->persist($mirror3);
        $entityManager->flush();

        $client = $this->getClient();

        // Test quoted search for exact phrase in country name
        $client->request('GET', '/api/mirrors', ['query' => '"United States"']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('https://mirror.example.us/', $responseData['items'][0]['url']);

        // Test unquoted search
        $client->request('GET', '/api/mirrors', ['query' => 'United']);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData['items']);
    }
}
