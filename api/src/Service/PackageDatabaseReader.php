<?php

namespace App\Service;

use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use Symfony\Component\Serializer\SerializerInterface;

class PackageDatabaseReader
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly PackageDatabaseExtractor $packageDatabaseExtractor
    ) {
    }

    public function readPackages(Repository $repository, string $packageDatabase): \Generator
    {
        foreach ($this->packageDatabaseExtractor->extractPackageDescriptions($packageDatabase) as $packageDescription) {
            yield $this->serializer->deserialize(
                $packageDescription,
                Package::class,
                'pacman-database',
                ['repository' => $repository]
            );
        }
    }
}
