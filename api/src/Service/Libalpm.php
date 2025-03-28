<?php

namespace App\Service;

use FFI;

/**
 * @method int alpm_pkg_vercmp(string $a, string $b)
 */
readonly class Libalpm
{
    private FFI $ffi;

    public function __construct()
    {
        $this->ffi = FFI::cdef(
            '
            int alpm_pkg_vercmp(const char *a, const char *b);
            ',
            'libalpm.so'
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
