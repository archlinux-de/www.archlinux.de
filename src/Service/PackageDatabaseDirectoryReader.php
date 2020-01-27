<?php

namespace App\Service;

use App\Entity\Packages\Package;
use App\Entity\Packages\Repository;
use App\Filesystem\TarFileReader;
use Symfony\Component\Serializer\SerializerInterface;

class PackageDatabaseDirectoryReader
{
    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param Repository $repository
     * @param \SplFileInfo $packageDatabaseFile
     * @return \Generator<Package>
     */
    public function readPackages(Repository $repository, \SplFileInfo $packageDatabaseFile): \Generator
    {
        /** @var \SplFileInfo $packageDirectory */
        foreach ((new TarFileReader())->extract($packageDatabaseFile) as $packageDirectory) {
            $descContent = file_get_contents($packageDirectory->getRealPath() . '/desc');
            $filesContent = file_get_contents($packageDirectory->getRealPath() . '/files');
            yield $this->serializer->deserialize(
                $descContent . "\n" . $filesContent,
                Package::class,
                'pacman-database',
                ['repository' => $repository]
            );
        }
    }
}
