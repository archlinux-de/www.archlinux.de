<?php

namespace App\Tests\Controller;

use App\Controller\NewsController;
use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Tests\DatabaseSearchTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NewsController::class)]
class NewsControllerTest extends DatabaseSearchTestCase
{
    public function testFeedAction(): void
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem(1);
        $news->setTitle('Breaking News');
        $news->setDescription('Hell has frozen over!');
        $news->setLastModified(new \DateTime('2018-01-01'));
        $news->setAuthor(
            new NewsAuthor()
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
        $this->assertEquals('http://localhost/news/1-Breaking-News', (string)$xml->entry->link->attributes()->{'href'});
    }

    public function testNewsAction(): void
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem(1);
        $news->setTitle('Breaking News');
        $news->setDescription('Hell has frozen over!');
        $news->setLastModified(new \DateTime('2018-01-01'));
        $news->setAuthor(
            new NewsAuthor()
                ->setName('')
                ->setUri('')
        );
        $news->setLink('https://www.archlinux.de/');
        $entityManager->persist($news);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/api/news', ['query' => 'Hell']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEquals(1, $responseData['count']);
        $this->assertEquals(1, $responseData['total']);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('Breaking News', $responseData['items'][0]['title']);
    }

    public function testNewsItemAction(): void
    {
        $entityManager = $this->getEntityManager();
        $news = new NewsItem(1);
        $news->setTitle('Breaking News');
        $news->setDescription('Hell has frozen over!');
        $news->setLastModified(new \DateTime('2020-02-02'));
        $news->setAuthor(
            new NewsAuthor()
                ->setName('Bob')
                ->setUri('http://localhost/bob')
        );
        $news->setLink('https://www.archlinux.de/');
        $entityManager->persist($news);
        $entityManager->flush();

        $client = $this->getClient();

        $client->request('GET', '/api/news/1');

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $this->assertEquals(
            [
                'id' => 1,
                'author' => [
                    'name' => 'Bob',
                    'uri' => 'http://localhost/bob'
                ],
                'description' => 'Hell has frozen over!',
                'lastModified' => '2020-02-02T00:00:00+00:00',
                'link' => 'https://www.archlinux.de/',
                'title' => 'Breaking News',
                'slug' => 'Breaking-News'
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testNewsActionWithQuotes(): void
    {
        $entityManager = $this->getEntityManager();

        $newsAuthor = new NewsAuthor();
        $newsAuthor->setName('Gemini');
        $newsAuthor->setUri('http://localhost/gemini');

        $newsItem1 = new NewsItem(1);
        $newsItem1->setTitle('Important Security Update');
        $newsItem1->setDescription('A critical security patch for all systems');
        $newsItem1->setLastModified(new \DateTime('2023-01-01'));
        $newsItem1->setAuthor($newsAuthor);
        $newsItem1->setLink('https://www.archlinux.de/news/1');

        $newsItem2 = new NewsItem(2);
        $newsItem2->setTitle('General Update');
        $newsItem2->setDescription('Some general security fixes');
        $newsItem2->setLastModified(new \DateTime('2023-01-02'));
        $newsItem2->setAuthor($newsAuthor);
        $newsItem2->setLink('https://www.archlinux.de/news/2');

        $entityManager->persist($newsItem1);
        $entityManager->persist($newsItem2);
        $entityManager->flush();

        $client = $this->getClient();

        // Test quoted search for exact phrase in description
        $client->request('GET', '/api/news', ['query' => '"critical security patch"']);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals('Important Security Update', $responseData['items'][0]['title']);

        // Test unquoted search
        $client->request('GET', '/api/news', ['query' => 'security']);
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertIsString($client->getResponse()->getContent());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData['items']);
    }
}
