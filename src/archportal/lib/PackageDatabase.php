<?php

namespace archportal\lib;

class PackageDatabase implements \Iterator
{
    /** @var int */
    private $packageMinMTime = 0;
    /** @var int */
    private $currentKey = 0;
    /** @var bool */
    private $currentDir = false;
    private $dbHandle = null;
    /** @var null|string */
    private $dbDir = null;
    /** @var null|int */
    private $packageCount = null;

    /**
     * @param \SplFileInfo $packageDatabaseFile
     * @param int $packageMinMTime
     */
    public function __construct(\SplFileInfo $packageDatabaseFile, int $packageMinMTime)
    {
        $this->packageMinMTime = $packageMinMTime;

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
        || filemtime($this->dbDir . '/' . $this->currentDir) <= $this->packageMinMTime
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
    public function getNewPackageCount(): int
    {
        if (is_null($this->packageCount)) {
            $packages = 0;
            if (is_dir($this->dbDir)) {
                $dh = opendir($this->dbDir);
                while (false !== ($dir = readdir($dh))) {
                    if (is_dir($this->dbDir . '/' . $dir)
                        && $dir != '.'
                        && $dir != '..'
                        && filemtime($this->dbDir . '/' . $dir) > $this->packageMinMTime
                    ) {
                        ++$packages;
                    }
                }
                closedir($dh);
            }
            $this->packageCount = $packages;
        }

        return $this->packageCount;
    }

    /**
     * @return array
     */
    public function getOldPackageNames(): array
    {
        $packages = array();
        if (is_dir($this->dbDir)) {
            $dh = opendir($this->dbDir);
            while (false !== ($dir = readdir($dh))) {
                if (is_dir($this->dbDir . '/' . $dir)
                    && $dir != '.'
                    && $dir != '..'
                    && filemtime($this->dbDir . '/' . $dir) <= $this->packageMinMTime
                ) {
                    $matches = array();
                    if (preg_match('/^([^\-].*)-[^\-]+?-[^\-]+?$/', $dir, $matches) == 1) {
                        $packages[] = $matches[1];
                    } else {
                        throw new \RuntimeException('Could not read package ' . $dir);
                    }
                }
            }
            closedir($dh);
        }

        return $packages;
    }
}
