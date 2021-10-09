<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\ByteString;

/**
 * @covers \App\Controller\LegacyController
 */
class LegacyControllerTest extends WebTestCase
{
    /**
     * @param string $legacyPage
     * @param string[] $parameters
     * @dataProvider provideLegacyPages
     */
    public function testLegacyPagesAreRedirected(string $legacyPage, array $parameters = []): void
    {
        $client = static::createClient();

        $client->request('GET', $this->createLegacyRequest($legacyPage, $parameters));

        $this->assertTrue($client->getResponse()->isRedirection(), $client->getResponse());
        foreach ($parameters as $parameter) {
            $this->assertStringContainsString($parameter, (string)$client->getResponse()->headers->get('Location'));
        }
    }

    public function provideLegacyPages(): array
    {
        return [
            ['GetFileFromMirror', ['file' => 'foo']],
            ['GetOpenSearch'],
            ['GetRecentNews'],
            ['GetRecentPackages'],
            ['MirrorStatus'],
            ['PackageDetails', ['repo' => 'core', 'arch' => 'x86_64', 'pkgname' => 'foo']],
            ['Packages'],
            ['PackagesSuggest'],
            ['Start'],
            ['FunStatistics'],
            ['ModuleStatistics'],
            ['PackageStatistics'],
            ['Statistics'],
            ['ArchitectureDifferences'],
            ['MirrorProblems'],
            ['MirrorStatusJSON']
        ];
    }

    public function testUnknownPageWillReturnNotFoundStatus(): void
    {
        $client = static::createClient();

        $client->request('GET', $this->createLegacyRequest('UnknownPage'));

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @param string $legacyPage
     * @param string[] $parameters
     * @dataProvider provideInvalidLegacyPages
     */
    public function testInvalidParametersWillReturnNotFoundStatus(string $legacyPage, array $parameters = []): void
    {
        $client = static::createClient();

        $client->request('GET', $this->createLegacyRequest($legacyPage, $parameters));

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function provideInvalidLegacyPages(): array
    {
        return [
            ['PackageDetails', ['package' => '123']],
            ['PackageDetails', ['package' => '123', 'showfiles']],
            [''],
            ['Start"'],
            ['Statistics%27'],
            ['GetFileFromMirror']
        ];
    }

    public function testPostIsInvalid(): void
    {
        $client = static::createClient();
        $client->request('POST', $this->createLegacyRequest('foo'));

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    private function createLegacyRequest(string $legacyPage, array $parameters = []): string
    {
        return (new ByteString(
            Request::create('/', 'GET', array_merge(['page' => $legacyPage], $parameters))->getUri()
        ))->replace('&', ';');
    }
}
