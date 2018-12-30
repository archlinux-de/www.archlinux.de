<?php

namespace App\Tests\Entity;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use PHPUnit\Framework\TestCase;

class NewsItemTest extends TestCase
{
    public function testEntity()
    {
        $newsAuthor = new NewsAuthor();
        $this->assertSame($newsAuthor, $newsAuthor->setName('Bob'));
        $this->assertSame($newsAuthor, $newsAuthor->setUri('http://localhost'));

        $lastModified = new \DateTime();

        $newsItem = new NewsItem('1');
        $this->assertSame($newsItem, $newsItem->setDescription('description'));
        $this->assertSame($newsItem, $newsItem->setAuthor($newsAuthor));
        $this->assertSame($newsItem, $newsItem->setLastModified($lastModified));
        $this->assertSame($newsItem, $newsItem->setLink('link'));
        $this->assertSame($newsItem, $newsItem->setTitle('title'));

        $this->assertEquals(1, $newsItem->getId());
        $this->assertEquals('description', $newsItem->getDescription());
        $this->assertEquals('link', $newsItem->getLink());
        $this->assertEquals('title', $newsItem->getTitle());
        $this->assertEquals($lastModified, $newsItem->getLastModified());
        $this->assertEquals($newsAuthor, $newsItem->getAuthor());
        $this->assertEquals('Bob', $newsItem->getAuthor()->getName());
        $this->assertEquals('http://localhost', $newsItem->getAuthor()->getUri());
    }

    public function testJsonSerialize()
    {
        $newsAuthor = (new NewsAuthor())
            ->setName('Bob')
            ->setUri('http://localhost');
        $lastModified = new \DateTime('1982-02-05');
        $newsItem = (new NewsItem('1'))
            ->setAuthor($newsAuthor)
            ->setSlug('1-big-story')
            ->setTitle('Big Story')
            ->setDescription('Foo bar')
            ->setLink('https://www.archlinux.de/')
            ->setLastModified($lastModified);

        $json = json_encode($newsItem);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'slug' => '1-big-story',
                'title' => 'Big Story',
                'link' => 'https://www.archlinux.de/',
                'description' => 'Foo bar',
                'author' => [
                    'name' => 'Bob',
                    'uri' => 'http://localhost'
                ],
                'lastModified' => 'Fri, 05 Feb 1982 00:00:00 +0000'
            ],
            $jsonArray
        );
    }
}
