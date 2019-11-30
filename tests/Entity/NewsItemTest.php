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

        $json = (string)json_encode($newsItem);
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

    public function testUpdate()
    {
        $newsItem = (new NewsItem('abc'))
            ->setTitle('title')
            ->setSlug('slug')
            ->setLink('link')
            ->setLastModified(new \DateTime())
            ->setDescription('description')
            ->setAuthor(new NewsAuthor());

        $newsItem->update((new NewsItem('abc'))
            ->setTitle('foo')
            ->setSlug('bar')
            ->setLink('baz')
            ->setLastModified(new \DateTime('2019-01-01'))
            ->setDescription('another description')
            ->setAuthor((new NewsAuthor())->setName('bob')));

        $this->assertEquals('foo', $newsItem->getTitle());
        $this->assertEquals('bar', $newsItem->getSlug());
        $this->assertEquals('baz', $newsItem->getLink());
        $this->assertEquals(new \DateTime('2019-01-01'), $newsItem->getLastModified());
        $this->assertEquals('another description', $newsItem->getDescription());
        $this->assertEquals('bob', $newsItem->getAuthor()->getName());
    }

    public function testUpdateFailsOnMismatchedId()
    {
        $newsItem = new NewsItem('foo');
        $this->expectException(\InvalidArgumentException::class);
        $newsItem->update(new NewsItem('bar'));
    }
}
