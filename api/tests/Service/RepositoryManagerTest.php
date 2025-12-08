<?php

namespace App\Tests\Service;

use App\Entity\Packages\Repository;
use App\Repository\RepositoryRepository;
use App\Service\RepositoryManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RepositoryManagerTest extends TestCase
{
    public function testRemoveObsoleteRepositories(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($this->callback(function (Repository $repository): true {
                $this->assertEquals('core', $repository->getName());
                $this->assertEquals('x86_64', $repository->getArchitecture());
                return true;
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        /** @var RepositoryRepository&MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([new Repository('core', 'x86_64')]);

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');

        $repositoryManager = new RepositoryManager($entityManager, [], $repositoryRepository, $validator);

        $this->assertTrue($repositoryManager->removeObsoleteRepositories());
    }

    public function testConfiguredRepositoriesAreNotRemoved(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('remove');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        /** @var RepositoryRepository&MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([new Repository('core', 'x86_64')]);

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');

        $repositoryManager = new RepositoryManager(
            $entityManager,
            ['core' => ['x86_64']],
            $repositoryRepository,
            $validator
        );

        $this->assertFalse($repositoryManager->removeObsoleteRepositories());
    }

    #[DataProvider('provideRepositories')]
    public function testCreateNewRepositories(string $repositoryName, bool $isTesting): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Repository $repository) use ($repositoryName, $isTesting): true {
                $this->assertEquals($repositoryName, $repository->getName());
                $this->assertEquals('x86_64', $repository->getArchitecture());
                $this->assertEquals($isTesting, $repository->isTesting());
                return true;
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        /** @var RepositoryRepository&MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findByNameAndArchitecture')
            ->with($repositoryName, 'x86_64')
            ->willReturn(null);

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());

        $repositoryManager = new RepositoryManager(
            $entityManager,
            [$repositoryName => ['x86_64']],
            $repositoryRepository,
            $validator
        );

        $this->assertTrue($repositoryManager->createNewRepositories());
    }

    public function testCreateNewRepositoriesIsSkippedForExistingRepositories(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        /** @var RepositoryRepository&MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findByNameAndArchitecture')
            ->with('core', 'x86_64')
            ->willReturn(new Repository('core', 'x86_64'));

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->never())->method('validate');

        $repositoryManager = new RepositoryManager(
            $entityManager,
            ['core' => ['x86_64']],
            $repositoryRepository,
            $validator
        );

        $this->assertFalse($repositoryManager->createNewRepositories());
    }

    public function testFailOnInvalidConfiguration(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        /** @var RepositoryRepository&MockObject $repositoryRepository */
        $repositoryRepository = $this->createMock(RepositoryRepository::class);
        $repositoryRepository
            ->expects($this->once())
            ->method('findByNameAndArchitecture')
            ->willReturn(null);

        /** @var ValidatorInterface&MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->expects($this->atLeastOnce())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$this->createStub(ConstraintViolation::class)]));

        $repositoryManager = new RepositoryManager(
            $entityManager,
            ['%invalid' => ['x86_64']],
            $repositoryRepository,
            $validator
        );

        $this->expectException(ValidationFailedException::class);
        $repositoryManager->createNewRepositories();
    }

    /**
     * @return list<mixed[]>
     */
    public static function provideRepositories(): array
    {
        return [
            ['core', false],
            ['extra', false],
            ['core-testing', true],
            ['extra-testing', true]
        ];
    }
}
