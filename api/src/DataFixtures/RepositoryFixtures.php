<?php

namespace App\DataFixtures;

use App\Entity\Packages\Repository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RepositoryFixtures extends Fixture
{
    /**
     * @param array<string, string[]> $repositoryConfiguration
     */
    public function __construct(
        private readonly array $repositoryConfiguration,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->repositoryConfiguration as $name => [$architecture]) {
            $repository = new Repository($name, $architecture);

            $errors = $this->validator->validate($repository);
            if (count($errors) > 0) {
                throw new \RuntimeException((string) $errors);
            }

            $manager->persist($repository);
            $this->addReference(sprintf('repository-%s-%s', $name, $architecture), $repository);
        }

        $manager->flush();
    }
}
