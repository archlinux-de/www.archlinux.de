<?php

namespace App\Tests\ArchLinux;

use App\ArchLinux\PackageDatabaseReader;
use App\ArchLinux\TemporaryDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PackageDatabaseReaderTest extends TestCase
{
    public function testReaderExtractsTar()
    {
        $tarFileDirectory = new TemporaryDirectory();
        $tarFile = $tarFileDirectory->getPathname() . '/foo.tar.gz';
        $contentDirectory = new TemporaryDirectory();

        file_put_contents($contentDirectory->getPathname() . '/foo', 'bar');
        $tar = new Process(['bsdtar', '-cf', $tarFile, '--strip-components', '3', $contentDirectory->getPathname()]);
        $tar->mustRun();

        $reader = new PackageDatabaseReader(new \SplFileInfo($tarFile));
        $extractedFileDirectory = $reader->extract();
        $extractedFiles = iterator_to_array($extractedFileDirectory);

        $this->assertCount(1, $extractedFiles);
        $this->assertEquals('bar', file_get_contents(array_pop($extractedFiles)));
    }
}
