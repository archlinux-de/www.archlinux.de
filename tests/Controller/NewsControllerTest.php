<?php

namespace App\Tests\Controller;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use SymfonyDatabaseTest\DatabaseTestCase;

/**
 * @covers \App\Controller\NewsController
 */
class NewsControllerTest extends DatabaseTestCase
{
    public function testIndexAction(): void
    {
        $client = $this->getClient();

        $client->request('GET', '/news', ['search' => 'foo']);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testItemAction(): void
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem(1);
        $news->setTitle('Breaking News');
        $news->setDescription('Hell has frozen over!');
        $news->setLastModified(new \DateTime());
        $news->setAuthor(
            (new NewsAuthor())
                ->setName('')
                ->setUri('')
        );
        $news->setLink('https://www.archlinux.de/');
        $entityManager->persist($news);
        $entityManager->flush();

        $client = $this->getClient();

        $crawler = $client->request('GET', '/news/1-Breaking-News');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals('Breaking News', $crawler->filter('h1')->text());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertStringContainsString('Hell has frozen over!', $client->getResponse()->getContent());
    }

    public function testDatatablesAction(): void
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem(1);
        $news->setTitle('Breaking News');
        $news->setDescription('Hell has frozen over!');
        $news->setLastModified(new \DateTime());
        $news->setAuthor(
            (new NewsAuthor())
                ->setName('')
                ->setUri('')
        );
        $news->setLink('https://www.archlinux.de/');
        $entityManager->persist($news);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request(
            'GET',
            '/news/datatables',
            [
                'draw' => 1,
                'length' => 2,
                'columns' => [
                    [
                        'data' => 'title',
                        'name' => '',
                        'orderable' => false,
                        'search' => [
                            'regex' => false,
                            'value' => ''
                        ],
                        'searchable' => true
                    ]
                ],
                'search' => [
                    'regex' => false,
                    'value' => 'hell'
                ]
            ]
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('Breaking News', $responseData['data'][0]['title']);
    }

    public function testFeedAction(): void
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem(1);
        $news->setTitle('Breaking News');
        $news->setDescription('Hell has frozen over!');
        $news->setLastModified(new \DateTime());
        $news->setAuthor(
            (new NewsAuthor())
                ->setName('')
                ->setUri('')
        );
        $news->setLink('https://www.archlinux.de/');
        $entityManager->persist($news);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/news/feed');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringStartsWith(
            'application/atom+xml; charset=UTF-8',
            (string)$client->getResponse()->headers->get('Content-Type')
        );
        $this->assertEquals('UTF-8', $client->getResponse()->getCharset());
        $xml = \simplexml_load_string((string)($client->getResponse()->getContent()));
        $this->assertNotFalse($xml);
        $this->assertEmpty(\libxml_get_errors());
        $this->assertEquals($news->getTitle(), (string)$xml->entry->title);
        $this->assertEquals($news->getDescription(), (string)$xml->entry->content);
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertEquals('http://localhost/news/1-Breaking-News', (string)$xml->entry->link->attributes()->href);
    }

    public function testRedirectToCorrectSlug(): void
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem(1);
        $news->setTitle('Breaking News');
        $news->setDescription('Hell has frozen over!');
        $news->setLastModified(new \DateTime());
        $news->setAuthor(
            (new NewsAuthor())
                ->setName('')
                ->setUri('')
        );
        $news->setLink('https://www.archlinux.de/');
        $entityManager->persist($news);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/news/1-wrong');

        $this->assertTrue($client->getResponse()->isRedirect('/news/1-Breaking-News'));
    }
}
