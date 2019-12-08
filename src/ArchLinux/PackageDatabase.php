<?php

namespace App\ArchLinux;

/**
 * @phpstan-implements \IteratorAggregate<Package>
 */
class PackageDatabase implements \IteratorAggregate
{
    /** @var PackageDatabaseReader */
    private $databaseReader;
    /** @var \FilesystemIterator|null */
    private $databaseDirectory;

    /**
     * @param PackageDatabaseReader $databaseReader
     */
    public function __construct(PackageDatabaseReader $databaseReader)
    {
        $this->databaseReader = $databaseReader;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        /** @var \SplFileInfo $packageDirectory */
        foreach ($this->getDatabaseDirectory() as $packageDirectory) {
            yield new Package($packageDirectory);
        }
    }

    /**
     * @return \FilesystemIterator
     */
    private function getDatabaseDirectory(): \FilesystemIterator
    {
        if (is_null($this->databaseDirectory)) {
            $this->databaseDirectory = $this->databaseReader->extract();
        }

        return $this->databaseDirectory;
    }
}
