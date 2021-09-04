<?php

namespace App\Tests\Repository;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class NewsItemRepositoryTest extends DatabaseTestCase
{
    public function testFindLatestItemIsReturned(): void
    {
        $oldItem = $this->createNewsItem(1, new \DateTime('2018-01-01'));
        $newItem = $this->createNewsItem(2, new \DateTime('2018-01-02'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($newItem);
        $entityManager->persist($oldItem);
        $entityManager->flush();
        $entityManager->clear();

        /** @var NewsItemRepository $newsItemRepository */
        $newsItemRepository = $this->getRepository(NewsItem::class);
        $newsItems = $newsItemRepository->findLatest(2);
        $this->assertCount(2, $newsItems);
        $this->assertEquals($newItem->getId(), $newsItems[0]->getId());
    }

    private function createNewsItem(int $id, \DateTime $lastModified): NewsItem
    {
        return (new NewsItem($id))
            ->setLastModified($lastModified)
            ->setTitle('')
            ->setLink('http://localhost/news/' . $id)
            ->setDescription('')
            ->setAuthor((new NewsAuthor())->setName(''));
    }

    public function testFindLatestItemsAreLimited(): void
    {
        $oldItem = $this->createNewsItem(1, new \DateTime('2018-01-01'));
        $newItem = $this->createNewsItem(2, new \DateTime('2018-01-02'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($newItem);
        $entityManager->persist($oldItem);
        $entityManager->flush();
        $entityManager->clear();

        /** @var NewsItemRepository $newsItemRepository */
        $newsItemRepository = $this->getRepository(NewsItem::class);
        $this->assertCount(1, $newsItemRepository->findLatest(1));
    }

    public function testFindAllExceptByIds(): void
    {
        $newsItemA = $this->createNewsItem(1, new \DateTime('2018-01-03'));
        $newsItemB = $this->createNewsItem(2, new \DateTime('2018-01-03'));
        $newsItemC = $this->createNewsItem(3, new \DateTime('2018-01-01'));
        $entityManager = $this->getEntityManager();
        $entityManager->persist($newsItemA);
        $entityManager->persist($newsItemB);
        $entityManager->persist($newsItemC);
        $entityManager->flush();

        /** @var NewsItemRepository $newsItemRepository */
        $newsItemRepository = $this->getRepository(NewsItem::class);
        $newsItems = $newsItemRepository->findAllExceptByIds([1, 3]);

        $this->assertCount(1, $newsItems);
        $this->assertEquals(2, $newsItems[0]->getId());
    }

    public function testGetSize(): void
    {
        $oldItem = $this->createNewsItem(1, new \DateTime('2018-01-01'));
        $newItem = $this->createNewsItem(2, new \DateTime('2018-01-01'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($newItem);
        $entityManager->persist($oldItem);
        $entityManager->flush();
        $entityManager->clear();

        /** @var NewsItemRepository $newsItemRepository */
        $newsItemRepository = $this->getRepository(NewsItem::class);
        $this->assertEquals(2, $newsItemRepository->getSize());
    }
}
