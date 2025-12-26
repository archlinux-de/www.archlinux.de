<?php

namespace App\Tests\Repository;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class MirrorRepositoryTest extends DatabaseTestCase
{
    public function testFindAllExceptByUrls(): void
    {
        $mirrorA = new Mirror('a')->setLastSync(new \DateTime('2020-01-01'));
        $mirrorB = new Mirror('b')->setLastSync(new \DateTime('2020-01-01'));
        $entityManager = $this->getEntityManager();
        $entityManager->persist($mirrorA);
        $entityManager->persist($mirrorB);
        $entityManager->flush();

        /** @var MirrorRepository $mirrorRepository */
        $mirrorRepository = $this->getRepository(Mirror::class);
        $mirrors = $mirrorRepository->findAllExceptByUrls(['a']);

        $this->assertCount(1, $mirrors);
        $this->assertEquals('b', $mirrors[0]->getUrl());
    }
}
