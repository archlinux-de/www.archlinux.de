<?php

namespace App\Filesystem;

use Symfony\Component\Process\Process;

class TarFileReader
{
    /**
     * @return \FilesystemIterator
     */
    public function extract(\SplFileInfo $tarFile): \FilesystemIterator
    {
        $extractedDirectory = new TemporaryDirectory();
        $untar = new Process(
            [
                'bsdtar',
                '-xf',
                $tarFile->getRealPath(),
                '-C',
                $extractedDirectory->getPathname()
            ]
        );
        $untar->mustRun();

        return $extractedDirectory;
    }
}
