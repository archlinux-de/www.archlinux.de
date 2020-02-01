<?php

namespace App\Tests\Service;

use App\Service\Libarchive;
use App\Service\PackageDatabaseExtractor;
use PHPUnit\Framework\TestCase;

class PackageDatabaseExtractorTest extends TestCase
{
    /** @var string */
    private $archiveFile;

    public function testExtractPackageDescriptions(): void
    {
        $this->assertFileNotExists($this->archiveFile);

        $archive = new \PharData($this->archiveFile);
        $archive->addEmptyDir('pacman-1.2.3-1');
        $archive->addFromString('php-1.2.3-1/files', "a\n");
        $archive->addEmptyDir('glibc-1.2.3-1');
        $archive->addFromString('pacman-1.2.3-1/desc', "b\n");
        $archive->addEmptyDir('php-1.2.3-1');
        $archive->addFromString('php-1.2.3-1/desc', "c\n");
        $archive->addFromString('pacman-1.2.3-1/files', "d\n");

        $packageDatabaseExtractor = new PackageDatabaseExtractor(new Libarchive());
        $packageDescriptions = $packageDatabaseExtractor
            ->extractPackageDescriptions((string)file_get_contents($this->archiveFile));

        $this->assertEquals(["a\n\nc\n", "b\n\nd\n"], iterator_to_array($packageDescriptions));
    }

    protected function setUp(): void
    {
        $this->archiveFile = sys_get_temp_dir() . '/' . hash('sha256', __CLASS__ . random_int(0, PHP_INT_MAX)) . '.tar';
    }

    protected function tearDown(): void
    {
        unlink($this->archiveFile);
    }
}
