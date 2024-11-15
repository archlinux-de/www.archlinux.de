<?php

namespace App\Service;

use FFI;
use FFI\CData;
use FFI\CType;

/**
 * @method CData new(mixed $type, bool $owned = true, bool $persistent = false)
 * @method CType type(mixed $type)
 *
 * @method CData archive_read_new()
 * @method int archive_read_support_filter_all(CData $archive)
 * @method int archive_read_support_format_all(CData $archive)
 * @method int archive_read_open_memory(CData $archive, string $buffer, int $size)
 * @method int archive_read_next_header(CData $archive, CData $archiveEntry)
 * @method int archive_read_free(CData $archive)
 * @method string archive_entry_pathname(CData $archiveEntry)
 * @method int archive_read_data(CData $archive, CData $buffer, int $size)
 * @method int archive_entry_size(CData $archiveEntry)
 */
readonly class Libarchive
{
    private FFI $ffi;

    public function __construct()
    {
        $this->ffi = FFI::cdef(
            '
            struct archive *archive_read_new(void);
            int archive_read_support_filter_all(struct archive *);
            int archive_read_support_format_all(struct archive *);
            int archive_read_open_memory(struct archive *, const void * buff, size_t size);
            int archive_read_next_header(struct archive *, struct archive_entry **);
            int archive_read_free(struct archive *);
            const char *archive_entry_pathname(struct archive_entry *);
            size_t archive_read_data(struct archive *, void *, size_t);
            int64_t archive_entry_size(struct archive_entry *);
            ',
            'libarchive.so'
        );
    }

    /**
     * @param mixed[] $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->ffi->$name(...$arguments);
    }
}
