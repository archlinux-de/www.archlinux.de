<?php

namespace App\Tests\Service;

use App\Entity\Packages\Repository;
use App\Repository\RepositoryRepository;
use App\Service\RepositoryManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepositoryManagerTest extends TestCase
{
    public function testRemoveObsoleteRepositories()
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($this->callback(function (Repository $repository) {
                $this->assertEquals('core', $repository->getName());
                $this->assertEquals('x86_64', $repository->getArchitecture());
                return true;
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        /** @var RepositoryRepository|MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([new Repository('core', 'x86_64')]);

        $repositoryManager = new RepositoryManager($entityManager, [], $repositoryRepository);

        $this->assertTrue($repositoryManager->removeObsoleteRepositories());
    }

    public function testConfiguredRepositoriesAreNotRemoved()
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('remove');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        /** @var RepositoryRepository|MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([new Repository('core', 'x86_64')]);

        $repositoryManager = new RepositoryManager($entityManager, ['core' => ['x86_64']], $repositoryRepository);

        $this->assertFalse($repositoryManager->removeObsoleteRepositories());
    }

    /**
     * @param string $repositoryName
     * @param bool $isTesting
     * @dataProvider  provideRepositories
     */
    public function testCreateNewRepositories(string $repositoryName, bool $isTesting)
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Repository $repository) use ($repositoryName, $isTesting) {
                $this->assertEquals($repositoryName, $repository->getName());
                $this->assertEquals('x86_64', $repository->getArchitecture());
                $this->assertEquals($isTesting, $repository->isTesting());
                return true;
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        /** @var RepositoryRepository|MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findByNameAndArchitecture')
            ->with($repositoryName, 'x86_64')
            ->willReturn(null);

        $repositoryManager = new RepositoryManager(
            $entityManager,
            [$repositoryName => ['x86_64']],
            $repositoryRepository
        );

        $this->assertTrue($repositoryManager->createNewRepositories());
    }

    public function testCreateNewRepositoriesIsSkippedForExistingRepositories()
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        /** @var RepositoryRepository|MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findByNameAndArchitecture')
            ->with('core', 'x86_64')
            ->willReturn(new Repository('core', 'x86_64'));

        $repositoryManager = new RepositoryManager($entityManager, ['core' => ['x86_64']], $repositoryRepository);

        $this->assertFalse($repositoryManager->createNewRepositories());
    }

    /**
     * @return array
     */
    public function provideRepositories(): array
    {
        return [
            ['core', false],
            ['community', false],
            ['testing', true],
            ['community-testing', true]
        ];
    }
}
