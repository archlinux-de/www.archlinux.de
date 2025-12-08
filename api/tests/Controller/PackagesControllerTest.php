<?php

namespace App\Tests\Controller;

use App\Controller\PackagesController;
use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Repository;
use App\Tests\DatabaseSearchTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PackagesController::class)]
class PackagesControllerTest extends DatabaseSearchTestCase
{
    public function testOpenSearchAction(): void
    {
        $client = static::getClient();

        $client->request('GET', '/packages/opensearch');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $response = $client->getResponse()->getContent();
        $this->assertIsString($response);
        $this->assertNotFalse(\simplexml_load_string($response));
        $this->assertEmpty(\libxml_get_errors());
        $this->assertStringContainsString('{searchTerms}', $response);
    }

    public function testFeedAction(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $php->setPackager(new Packager('', ''));
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/packages/feed');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringStartsWith(
            'application/atom+xml; charset=UTF-8',
            (string)$client->getResponse()->headers->get('Content-Type')
        );
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
        $xml = \simplexml_load_string((string)($client->getResponse()->getContent()));
        $this->assertNotFalse($xml);
        $this->assertEmpty(\libxml_get_errors());
        $this->assertEquals($php->getName() . ' ' . $php->getVersion(), (string)$xml->entry->title);
        $this->assertEquals($php->getDescription(), (string)$xml->entry->content);
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertEquals(
            'http://localhost/packages/core/x86_64/php',
            (string)$xml->entry->link->attributes()->{'href'}
        );
    }

    #[DataProvider('provideInvalideSuggestTerms')]
    public function testSuggestRejectsInvalidTerms(string $term): void
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/suggest', ['term' => $term]);

        $this->assertTrue($client->getResponse()->isClientError());
    }

    /**
     * @return list<string[]>
     */
    public static function provideInvalideSuggestTerms(): array
    {
        return [
            ['${...}'],
        ];
    }

    public function testSuggest(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/packages/suggest', ['term' => 'pac']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData);
        $this->assertEquals('pacman', $responseData[0]);
    }

    public function testPackagesAction(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $entityManager->persist($coreRepository);
        $entityManager->persist($php);
        $entityManager->persist($pacman);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/api/packages', ['query' => 'pac']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('pacman', $responseData['items'][0]['name']);
    }

    public function testEmptyTerm(): void
    {
        $client = $this->getClient();

        $client->request('GET', '/packages/suggest', ['term' => '']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(0, $responseData);
    }
    public function testPackagesActionWithQuotes(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $phpFramework = new Package(
            $coreRepository,
            'php-framework',
            '1.0-1',
            Architecture::X86_64
        );
        $phpFramework->setDescription('A PHP framework for web development');

        $php = new Package(
            $coreRepository,
            'php',
            '7.3.1-1',
            Architecture::X86_64
        );
        $php->setDescription('The PHP language');

        $entityManager->persist($coreRepository);
        $entityManager->persist($phpFramework);
        $entityManager->persist($php);
        $entityManager->flush();

        $client = $this->getClient();

        // Test quoted search for exact phrase in description
        $client->request('GET', '/api/packages', ['query' => '"PHP framework"']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('php-framework', $responseData['items'][0]['name']);

        // Test unquoted search
        $client->request('GET', '/api/packages', ['query' => 'php']);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData['items']);
    }
}
