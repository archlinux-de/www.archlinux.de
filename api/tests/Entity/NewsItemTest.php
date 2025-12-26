<?php

namespace App\Tests\Entity;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use PHPUnit\Framework\TestCase;

class NewsItemTest extends TestCase
{
    public function testEntity(): void
    {
        $newsAuthor = new NewsAuthor();
        $this->assertSame($newsAuthor, $newsAuthor->setName('Bob'));
        $this->assertSame($newsAuthor, $newsAuthor->setUri('http://localhost'));

        $lastModified = new \DateTime('2018-01-01');

        $newsItem = new NewsItem(1);
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

    public function testUpdate(): void
    {
        $newsItem = new NewsItem(1)
            ->setTitle('title')
            ->setLink('link')
            ->setLastModified(new \DateTime('2018-01-01'))
            ->setDescription('description')
            ->setAuthor(new NewsAuthor());

        $newsItem->update(new NewsItem(1)
            ->setTitle('foo')
            ->setLink('baz')
            ->setLastModified(new \DateTime('2019-01-01'))
            ->setDescription('another description')
            ->setAuthor(new NewsAuthor()->setName('bob')));

        $this->assertEquals('foo', $newsItem->getTitle());
        $this->assertEquals('baz', $newsItem->getLink());
        $this->assertEquals(new \DateTime('2019-01-01'), $newsItem->getLastModified());
        $this->assertEquals('another description', $newsItem->getDescription());
        $this->assertEquals('bob', $newsItem->getAuthor()->getName());
    }

    public function testUpdateFailsOnMismatchedId(): void
    {
        $newsItem = new NewsItem(1);
        $this->expectException(\InvalidArgumentException::class);
        $newsItem->update(new NewsItem(2));
    }
}
