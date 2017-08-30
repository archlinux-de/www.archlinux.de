<?php

namespace AppBundle\Service;

use Symfony\Component\Process\Process;

class TarExtractor
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
        $extractedDirectory = $this->createTemporaryDirectory();
        $untar = new Process(['bsdtar', '-xf', $this->tarFile->getRealPath(), '-C', $extractedDirectory->getPath()]);
        $untar->mustRun();

        return $extractedDirectory;
    }

    /**
     * Temporary directory which will be removed by the garbage collector
     * @return \FilesystemIterator
     */
    private function createTemporaryDirectory(): \FilesystemIterator
    {
        return new class() extends \FilesystemIterator
        {
            /** @var string */
            private $directory;

            public function __construct()
            {
                $mktemp = new Process(['mktemp', '-d']);
                $mktemp->mustRun();
                $this->directory = trim($mktemp->getOutput());

                parent::__construct($this->directory);
            }

            public function __destruct()
            {
                $rmdir = new Process(['rm', '-rf', $this->directory]);
                $rmdir->mustRun();
            }
        };
    }
}
