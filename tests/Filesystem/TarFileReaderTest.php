<?php

namespace App\Tests\Filesystem;

use App\Filesystem\TarFileReader;
use App\Filesystem\TemporaryDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class TarFileReaderTest extends TestCase
{
    public function testReaderExtractsTar(): void
    {
        $tarFileDirectory = new TemporaryDirectory();
        $tarFile = $tarFileDirectory->getPathname() . '/foo.tar.gz';
        $contentDirectory = new TemporaryDirectory();

        file_put_contents($contentDirectory->getPathname() . '/foo', 'bar');
        $tar = new Process(['bsdtar', '-cf', $tarFile, '--strip-components', '3', $contentDirectory->getPathname()]);
        $tar->mustRun();

        $reader = new TarFileReader();
        $extractedFileDirectory = $reader->extract(new \SplFileInfo($tarFile));
        $extractedFiles = iterator_to_array($extractedFileDirectory);

        $this->assertCount(1, $extractedFiles);
        $this->assertEquals('bar', file_get_contents(array_pop($extractedFiles)));
    }
}
