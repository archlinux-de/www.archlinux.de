<?php

namespace App\ArchLinux;

class TemporaryFile extends \SplFileObject
{
    /** @var string */
    private $fileName;
    /** @var int */
    private $mTime;

    /**
     * @param string $prefix
     */
    public function __construct(string $prefix)
    {
        $fileName = tempnam(sys_get_temp_dir(), $prefix);
        if (!$fileName) {
            throw new \RuntimeException(sprintf('Could not create temporyry file "%s".', $prefix));
        }
        $this->fileName = $fileName;
        parent::__construct($this->fileName);
        $this->mTime = parent::getMTime();
    }

    public function __destruct()
    {
        if (is_writable($this->fileName)) {
            unlink($this->fileName);
        }
    }

    /**
     * @return int
     */
    public function getMTime(): int
    {
        return $this->mTime;
    }

    /**
     * @param int $mtime
     */
    public function setMTime(int $mtime)
    {
        $filePath = $this->getRealPath();
        if (!$filePath) {
            throw new \RuntimeException(sprintf('Could not find file "%s".', $filePath));
        }
        touch($filePath, $mtime);
        $this->mTime = $mtime;
    }
}
