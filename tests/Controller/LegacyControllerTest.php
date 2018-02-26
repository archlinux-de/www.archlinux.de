<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Controller\LegacyController
 */
class LegacyControllerTest extends WebTestCase
{
    /**
     * @param string $legacyPage
     * @param array $parameters
     * @dataProvider provideLegacyPages
     */
    public function testLegacyPagesAreRedirected(string $legacyPage, array $parameters = [])
    {
        $client = static::createClient();

        $client->request('GET', '/', array_merge(['page' => $legacyPage], $parameters));

        $this->assertTrue($client->getResponse()->isRedirection());
        foreach ($parameters as $parameter) {
            $this->assertContains($parameter, $client->getResponse()->headers->get('Location'));
        }
    }

    /**
     * @return array
     */
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

    public function testUnknownPageWillReturnNotFoundStatus()
    {
        $client = static::createClient();

        $client->request('GET', '/', ['page' => 'UnknownPage']);

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @param string $legacyPage
     * @param array $parameters
     * @dataProvider provideInvalidLegacyPages
     */
    public function testInvalidParametersWillReturnNotFoundStatus(string $legacyPage, array $parameters = [])
    {
        $client = static::createClient();

        $client->request('GET', '/', array_merge(['page' => $legacyPage], $parameters));

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @return array
     */
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

    /**
     * @param string $url
     * @dataProvider providePkgstatsPostUrl
     */
    public function testPostPackageListIsRedirected(string $url)
    {
        $client = static::createClient();
        $client->request(
            'POST',
            $url,
            ['pkgstatsver' => '2.3', 'arch' => 'x86_64', 'packages' => 'pkgstats', 'modules' => 'snd']
        );

        $this->assertEquals(308, $client->getResponse()->getStatusCode());
        $this->assertEquals('https://pkgstats.archlinux.de/post', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @return array
     */
    public function providePkgstatsPostUrl(): array
    {
        return [
            ['/?page=PostPackageList'],
            ['/statistics']
        ];
    }

    public function testPostIsInvalid()
    {
        $client = static::createClient();
        $client->request('POST', '/?page=foo');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }
}
