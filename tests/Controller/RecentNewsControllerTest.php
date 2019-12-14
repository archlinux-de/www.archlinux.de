<?php

namespace App\Tests\Controller;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use SymfonyDatabaseTest\DatabaseTestCase;

/**
 * @covers \App\Controller\RecentNewsController
 */
class RecentNewsControllerTest extends DatabaseTestCase
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
        $this->assertEquals($news->getTitle(), $xml->entry->title->__toString());
        $this->assertEquals($news->getDescription(), $xml->entry->content->__toString());
        $this->assertNotNull($xml->entry->link->attributes());
        $this->assertEquals($news->getLink(), $xml->entry->link->attributes()->href->__toString());
    }
}
