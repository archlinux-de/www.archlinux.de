<?php

namespace archportal\lib;

use RuntimeException;

class Package
{
    /** @var string */
    private $packageDir = '';
    /** @var array */
    private $desc = array();

    /**
     * @param string $packageDir
     */
    public function __construct(string $packageDir)
    {
        $this->packageDir = $packageDir;

        if (!file_exists($this->packageDir . '/desc')) {
            throw new RuntimeException('Invalid package data');
        }
        $this->desc = $this->loadInfo($this->packageDir . '/desc');
        if (file_exists($this->packageDir . '/depends')) {
            $this->desc = array_merge($this->desc, $this->loadInfo($this->packageDir . '/depends'));
        }
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function loadInfo(string $file): array
    {
        $index = '';
        $data = array();
        foreach (file($file, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES) as $line) {
            if (substr($line, 0, 1) == '%' && substr($line, -1) == '%') {
                $index = substr($line, 1, -1);
                $data[$index] = array();
            } else {
                $data[$index][] = $line;
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->desc['FILENAME'][0];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->desc['NAME'][0];
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return isset($this->desc['BASE'][0]) ? $this->desc['BASE'][0] : $this->getName();
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->desc['VERSION'][0];
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return isset($this->desc['DESC'][0]) ? $this->desc['DESC'][0] : '';
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return isset($this->desc['GROUPS']) ? $this->desc['GROUPS'] : array();
    }

    /**
     * @return int
     */
    public function getCompressedSize(): int
    {
        return isset($this->desc['CSIZE'][0]) ? (int)$this->desc['CSIZE'][0] : 0;
    }

    /**
     * @return int
     */
    public function getInstalledSize(): int
    {
        return isset($this->desc['ISIZE'][0]) ? (int)$this->desc['ISIZE'][0] : 0;
    }

    /**
     * @return string
     */
    public function getMD5SUM(): string
    {
        return $this->desc['MD5SUM'][0];
    }

    /**
     * @return string
     */
    public function getSHA256SUM(): string
    {
        return isset($this->desc['SHA256SUM'][0]) ? $this->desc['SHA256SUM'][0] : null;
    }

    /**
     * @return string
     */
    public function getPGPSignature(): string
    {
        return isset($this->desc['PGPSIG'][0]) ? $this->desc['PGPSIG'][0] : null;
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        if (isset($this->desc['URL'][0])) {
            if (!preg_match('#^(https?|ftp)://#', $this->desc['URL'][0])) {
                return 'http://' . $this->desc['URL'][0];
            } else {
                return $this->desc['URL'][0];
            }
        } else {
            return '';
        }
    }

    /**
     * @return array
     */
    public function getLicenses(): array
    {
        return isset($this->desc['LICENSE']) ? $this->desc['LICENSE'] : array();
    }

    /**
     * @return string
     */
    public function getArch(): string
    {
        return isset($this->desc['ARCH'][0]) ? $this->desc['ARCH'][0] : '';
    }

    /**
     * @return int
     */
    public function getBuildDate(): int
    {
        return isset($this->desc['BUILDDATE'][0]) ? (int)$this->desc['BUILDDATE'][0] : 0;
    }

    /**
     * @return string
     */
    public function getPackager(): string
    {
        return isset($this->desc['PACKAGER'][0]) ? $this->desc['PACKAGER'][0] : '';
    }

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        return isset($this->desc['REPLACES']) ? $this->desc['REPLACES'] : array();
    }

    /**
     * @return array
     */
    public function getDepends(): array
    {
        return isset($this->desc['DEPENDS']) ? $this->desc['DEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getConflicts(): array
    {
        return isset($this->desc['CONFLICTS']) ? $this->desc['CONFLICTS'] : array();
    }

    /**
     * @return array
     */
    public function getProvides(): array
    {
        return isset($this->desc['PROVIDES']) ? $this->desc['PROVIDES'] : array();
    }

    /**
     * @return array
     */
    public function getOptDepends(): array
    {
        return isset($this->desc['OPTDEPENDS']) ? $this->desc['OPTDEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getMakeDepends(): array
    {
        return isset($this->desc['MAKEDEPENDS']) ? $this->desc['MAKEDEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getCheckDepends(): array
    {
        return isset($this->desc['CHECKDEPENDS']) ? $this->desc['CHECKDEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        if (file_exists($this->packageDir . '/files')) {
            $data = $this->loadInfo($this->packageDir . '/files');

            return $data['FILES'];
        } else {
            return array();
        }
    }

    /**
     * @return int
     */
    public function getMTime(): int
    {
        return filemtime($this->packageDir . '/desc');
    }
}
