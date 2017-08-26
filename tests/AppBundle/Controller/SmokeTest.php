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
            ['/packages/packagers'],
            ['/packages'],
            ['/packages/suggest?term=foo'],
            ['/packages/feed'],
            ['/statistics/fun'],
            ['/statistics/module'],
            ['/statistics/package'],
            ['/statistics'],
            ['/statistics/repository'],
            ['/statistics/user'],
            ['/download'],
            ['/packages/datatables?draw=1&length=1'],
            ['/sitemap.xml'],
            ['/news/feed'],
            ['/impressum'],
            ['/privacy-policy'],
            ['/packages/packagers/datatables'],
            ['/mirrors/datatables']
        ];
    }

    /**
     * @return array
     */
    public function provideRedirectUrls(): array
    {
        return [
            ['/download/foo']
        ];
    }
}
