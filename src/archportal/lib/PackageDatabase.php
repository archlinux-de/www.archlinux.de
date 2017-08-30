<?php

namespace archportal\lib;

class PackageDatabase implements \Iterator
{
    /** @var int */
    private $currentKey = 0;
    /** @var bool */
    private $currentDir = false;
    private $dbHandle = null;
    /** @var null|string */
    private $dbDir = null;

    /**
     * @param \SplFileInfo $packageDatabaseFile
     */
    public function __construct(\SplFileInfo $packageDatabaseFile)
    {
        $this->dbDir = $this->makeTempDir();
        $this->dbHandle = opendir($this->dbDir);

        system('bsdtar -xf ' . $packageDatabaseFile->getRealPath() . ' -C ' . $this->dbDir, $return);
        if ($return !== 0) {
            throw new \RuntimeException('Could not extract Database');
        }
    }

    /**
     * @return string
     */
    private function makeTempDir(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), strtolower(str_replace('\\', '/', get_class($this))));
        unlink($tmp);
        mkdir($tmp, 0700);

        return $tmp;
    }

    public function __destruct()
    {
        closedir($this->dbHandle);
        if (is_dir($this->dbDir)) {
            $this->rmrf($this->dbDir);
        }
    }

    /**
     * @return Package
     */
    public function current(): Package
    {
        return new Package($this->dbDir . '/' . $this->currentDir);
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->currentKey;
    }

    public function next()
    {
        do {
            $this->currentDir = readdir($this->dbHandle);
        } while ($this->currentDir == '.'
        || $this->currentDir == '..'
        );
        ++$this->currentKey;
    }

    public function rewind()
    {
        rewinddir($this->dbHandle);
        $this->currentKey = 0;
        $this->currentDir = false;
        $this->next();
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->currentDir !== false;
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    private function rmrf(string $dir): bool
    {
        if (is_dir($dir) && !is_link($dir)) {
            $dh = opendir($dir);
            while (false !== ($file = readdir($dh))) {
                if ($file != '.' && $file != '..') {
                    if (!$this->rmrf($dir . '/' . $file)) {
                        throw new \RuntimeException('Could not remove ' . $dir . '/' . $file);
                    }
                }
            }
            closedir($dh);

            return rmdir($dir);
        } else {
            return unlink($dir);
        }
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        $packages = 0;
        if (is_dir($this->dbDir)) {
            $dh = opendir($this->dbDir);
            while (false !== ($dir = readdir($dh))) {
                if (is_dir($this->dbDir . '/' . $dir)
                    && $dir != '.'
                    && $dir != '..'
                ) {
                    ++$packages;
                }
            }
            closedir($dh);
        }

        return $packages;
    }
}
