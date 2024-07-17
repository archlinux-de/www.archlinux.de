<?php

namespace App\Service;

use FFI;

/**
 * @method int alpm_pkg_vercmp(string $a, string $b)
 */
class Libalpm
{
    private FFI $ffi;

    private const LIB_LOCATIONS = ['/ust/lib/libalpm.so.15', '/usr/lib/libalpm.so.14'];

    public function __construct()
    {
        $this->ffi = FFI::cdef(
            '
            int alpm_pkg_vercmp(const char *a, const char *b);
            ',
            $this->getLibFileName()
        );
    }

    private function getLibFileName(): string
    {
        foreach (self::LIB_LOCATIONS as $fileName) {
            if (file_exists($fileName)) {
                return $fileName;
            }
        }

        throw new \RuntimeException('libalpm.so not found!');
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->ffi->$name(...$arguments);
    }
}
