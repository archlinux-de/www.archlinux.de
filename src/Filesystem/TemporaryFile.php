<?php

namespace App\Filesystem;

class TemporaryFile extends \SplFileObject
{
    /** @var string */
    private $fileName;

    /**
     * @param string $prefix
     */
    public function __construct(string $prefix)
    {
        $fileName = tempnam(sys_get_temp_dir(), $prefix);
        if (!$fileName) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Could not create temporary file "%s".', $prefix));
            // @codeCoverageIgnoreEnd
        }
        $this->fileName = $fileName;
        parent::__construct($this->fileName, 'w+');
    }

    public function __destruct()
    {
        if (is_writable($this->fileName)) {
            unlink($this->fileName);
        }
    }
}
