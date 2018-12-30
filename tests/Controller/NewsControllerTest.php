<?php

namespace App\Tests\Controller;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Tests\Util\DatabaseTestCase;

/**
 * @covers \App\Controller\NewsController
 */
class NewsControllerTest extends DatabaseTestCase
{
    public function testIndexAction()
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem('1');
        $news->setTitle('Breaking News');
        $news->setSlug('1-breaking-news');
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

        $client->request('GET', '/news', ['search' => 'foo']);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testItemAction()
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem('1');
        $news->setTitle('Breaking News');
        $news->setSlug('1-breaking-news');
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

        $crawler = $client->request('GET', '/news/1-breaking-news');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals('Breaking News', $crawler->filter('h1')->text());
        $this->assertContains('Hell has frozen over!', $client->getResponse()->getContent());
    }

    public function testDatatablesAction()
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem('1');
        $news->setTitle('Breaking News');
        $news->setSlug('1-breaking-news');
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
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('Breaking News', $responseData['data'][0]['title']);
    }
}
