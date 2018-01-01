<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @coversNothing
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

    public function testUnknownPageWillFail()
    {
        $client = static::createClient();

        $client->request('GET', '/', ['page' => 'UnknownPage']);

        $this->assertTrue($client->getResponse()->isNotFound());
    }
}
