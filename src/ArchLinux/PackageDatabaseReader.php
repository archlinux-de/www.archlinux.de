<?php

namespace App\ArchLinux;

use Symfony\Component\Process\Process;

class PackageDatabaseReader
{
    /** @var \SplFileInfo */
    private $tarFile;

    /**
     * @param \SplFileInfo $tarFile
     */
    public function __construct(\SplFileInfo $tarFile)
    {
        $this->tarFile = $tarFile;
    }

    /**
     * @return \FilesystemIterator
     */
    public function extract(): \FilesystemIterator
    {
        $extractedDirectory = new TemporaryDirectory();
        $untar = new Process([
            'bsdtar',
            '-xf',
            $this->tarFile->getRealPath(),
            '-C',
            $extractedDirectory->getPathname()
        ]);
        $untar->mustRun();

        return $extractedDirectory;
    }
}
