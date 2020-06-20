<?php

namespace App\Service;

use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use Symfony\Component\Serializer\SerializerInterface;

class PackageDatabaseReader
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var PackageDatabaseExtractor */
    private $packageDatabaseExtractor;

    /**
     * @param SerializerInterface $serializer
     * @param PackageDatabaseExtractor $packageDatabaseExtractor
     */
    public function __construct(SerializerInterface $serializer, PackageDatabaseExtractor $packageDatabaseExtractor)
    {
        $this->serializer = $serializer;
        $this->packageDatabaseExtractor = $packageDatabaseExtractor;
    }

    /**
     * @param Repository $repository
     * @param string $packageDatabase
     * @return \Generator
     */
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
