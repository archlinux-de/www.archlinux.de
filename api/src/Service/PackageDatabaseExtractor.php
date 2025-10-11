<?php

namespace App\Service;

use FFI;
use Symfony\Component\String\ByteString;

class PackageDatabaseExtractor
{
    private const array DESCRIPTION_FILES = ['desc', 'files'];

    public function __construct(private readonly Libarchive $libarchive)
    {
    }

    /**
     * @return \Traversable<string>
     */
    public function extractPackageDescriptions(string $packageDatabase): \Traversable
    {
        $entry = $this->libarchive->new($this->libarchive->type('struct archive_entry*'));

        $archive = $this->libarchive->archive_read_new();
        $this->libarchive->archive_read_support_filter_all($archive);
        $this->libarchive->archive_read_support_format_all($archive);

        $this->libarchive->archive_read_open_memory($archive, $packageDatabase, strlen($packageDatabase));

        $packageDescriptions = [];
        $descriptionFileTotalCount = count(self::DESCRIPTION_FILES);

        while ($this->libarchive->archive_read_next_header($archive, FFI::addr($entry)) === 0) {
            $filePath = new ByteString($this->libarchive->archive_entry_pathname($entry));

            $pathName = $filePath->beforeLast('/')->toString();
            $fileName = $filePath->afterLast('/')->toString();

            if (in_array($fileName, self::DESCRIPTION_FILES)) {
                $entrySize = $this->libarchive->archive_entry_size($entry) + 1;
                $entryBuffer = $this->libarchive->new($this->libarchive->type('char[' . $entrySize . ']'));
                $this->libarchive->archive_read_data($archive, FFI::addr($entryBuffer), $entrySize);

                $packageDescriptions[$pathName][$fileName] = FFI::string($entryBuffer);

                if ($descriptionFileTotalCount === count($packageDescriptions[$pathName])) {
                    yield implode("\n", $packageDescriptions[$pathName]);
                    unset($packageDescriptions[$pathName]);
                }
            }
        }
        $this->libarchive->archive_read_free($archive);
    }
}
