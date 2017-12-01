<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    /**
     * @param string $url
     * @dataProvider provideUrls
     */
    public function testRequestIsSuccessful(string $url)
    {
        $client = static::createClient();

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * @param string $url
     * @dataProvider provideRedirectUrls
     */
    public function testRequestIsRedirect(string $url)
    {
        $client = static::createClient();

        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isRedirection());
    }

    public function testUnknownUrlFails()
    {
        $client = static::createClient();

        $client->request('GET', '/unknown');

        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @return array
     */
    public function provideUrls(): array
    {
        return [
            ['/packages/opensearch'],
            ['/'],
            ['/mirrors'],
            ['/packages/core/x86_64/pacman'],
            ['/packages'],
            ['/packages/suggest?term=foo'],
            ['/packages/feed'],
            ['/statistics/fun'],
            ['/statistics/module'],
            ['/statistics/package'],
            ['/statistics/module/datatables?draw=1&length=1'],
            ['/statistics/package/datatables?draw=1&length=1'],
            ['/statistics/module.json'],
            ['/statistics/package.json'],
            ['/statistics'],
            ['/download'],
            ['/packages/datatables?draw=1&length=1'],
            ['/sitemap.xml'],
            ['/news/feed'],
            ['/impressum'],
            ['/privacy-policy'],
            ['/mirrors/datatables']
        ];
    }

    /**
     * @return array
     */
    public function provideRedirectUrls(): array
    {
        return [
            ['/download/foo'],
            ['/download/iso/2017.11.01/archlinux-2017.11.01-x86_64.iso'],
            ['/download/core/os/x86_64/pacman-5.0.2-2-x86_64.pkg.tar.xz']
        ];
    }
}
