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
        $this->fileName = tempnam(sys_get_temp_dir(), $prefix);
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
        touch($this->getRealPath(), $mtime);
        $this->mTime = $mtime;
    }
}
