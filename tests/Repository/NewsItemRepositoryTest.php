<?php

namespace App\Tests\Repository;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\Tests\Util\DatabaseTestCase;

class NewsItemRepositoryTest extends DatabaseTestCase
{
    public function testFindLatestItemIsReturned()
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
        $this->assertEquals($newItem->getId(), array_shift($newsItems)->getId());
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
            ->setLink('')
            ->setDescription('')
            ->setAuthor((new NewsAuthor())->setName(''));
    }

    public function testFindLatestItemsAreLimited()
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
}
