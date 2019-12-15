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
        $oldItem = $this->createNewsItem('1', new \DateTime('- 2 day'));
        $newItem = $this->createNewsItem('2', new \DateTime('now'));

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

    /**
     * @param string $id
     * @param \DateTime $lastModified
     * @return NewsItem
     */
    private function createNewsItem(string $id, \DateTime $lastModified): NewsItem
    {
        return (new NewsItem($id))
            ->setLastModified($lastModified)
            ->setTitle('')
            ->setSlug($id . '-')
            ->setLink('')
            ->setDescription('')
            ->setAuthor((new NewsAuthor())->setName(''));
    }

    public function testFindLatestItemsAreLimited(): void
    {
        $oldItem = $this->createNewsItem('1', new \DateTime('- 2 day'));
        $newItem = $this->createNewsItem('2', new \DateTime('now'));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($newItem);
        $entityManager->persist($oldItem);
        $entityManager->flush();
        $entityManager->clear();

        /** @var NewsItemRepository $newsItemRepository */
        $newsItemRepository = $this->getRepository(NewsItem::class);
        $this->assertCount(1, $newsItemRepository->findLatest(1));
    }

    public function testFindAllExceptByIdsNewerThan(): void
    {
        $newsItemA = $this->createNewsItem('A', new \DateTime('- 2 day'));
        $newsItemB = $this->createNewsItem('B', new \DateTime('- 2 day'));
        $newsItemC = $this->createNewsItem('C', new \DateTime('- 4 day'));
        $entityManager = $this->getEntityManager();
        $entityManager->persist($newsItemA);
        $entityManager->persist($newsItemB);
        $entityManager->persist($newsItemC);
        $entityManager->flush();

        /** @var NewsItemRepository $newsItemRepository */
        $newsItemRepository = $this->getRepository(NewsItem::class);
        $newsItems = $newsItemRepository->findAllExceptByIdsNewerThan(['A'], new \DateTime('- 3 day'));

        $this->assertCount(1, $newsItems);
        $this->assertEquals('B', $newsItems[0]->getId());
    }

    public function testGetSize(): void
    {
        $oldItem = $this->createNewsItem('1', new \DateTime('now'));
        $newItem = $this->createNewsItem('2', new \DateTime('now'));

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
