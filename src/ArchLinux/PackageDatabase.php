<?php

namespace App\ArchLinux;

class PackageDatabase implements \IteratorAggregate
{
    /** @var PackageDatabaseReader */
    private $databaseReader;
    /** @var \FilesystemIterator */
    private $databaseDirectory;

    /**
     * @param PackageDatabaseReader $databaseReader
     */
    public function __construct(PackageDatabaseReader $databaseReader)
    {
        $this->databaseReader = $databaseReader;
    }

    /**
     * @return iterable
     */
    public function getIterator(): iterable
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
