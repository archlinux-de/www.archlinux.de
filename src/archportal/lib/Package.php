<?php

/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\lib;

use RuntimeException;

class Package
{

    /** @var string */
    private $packageDir = '';
    /** @var array */
    private $desc = array();
    /** @var array */
    private $depends = array();

    /**
     * @param string $packageDir
     */
    public function __construct(string $packageDir)
    {
        $this->packageDir = $packageDir;

        if (!file_exists($this->packageDir . '/desc') || !file_exists($this->packageDir . '/depends')) {
            throw new RuntimeException('Invalid package data');
        }
        $this->desc = $this->loadInfo($this->packageDir . '/desc');
        $this->depends = $this->loadInfo($this->packageDir . '/depends');
    }

    /**
     * @param string $file
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
        return isset($this->desc['CSIZE'][0]) ? $this->desc['CSIZE'][0] : 0;
    }

    /**
     * @return int
     */
    public function getInstalledSize(): int
    {
        return isset($this->desc['ISIZE'][0]) ? $this->desc['ISIZE'][0] : 0;
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
        return isset($this->desc['BUILDDATE'][0]) ? $this->desc['BUILDDATE'][0] : 0;
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
        return isset($this->depends['DEPENDS']) ? $this->depends['DEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getConflicts(): array
    {
        return isset($this->depends['CONFLICTS']) ? $this->depends['CONFLICTS'] : array();
    }

    /**
     * @return array
     */
    public function getProvides(): array
    {
        return isset($this->depends['PROVIDES']) ? $this->depends['PROVIDES'] : array();
    }

    /**
     * @return array
     */
    public function getOptDepends(): array
    {
        return isset($this->depends['OPTDEPENDS']) ? $this->depends['OPTDEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getMakeDepends(): array
    {
        return isset($this->depends['MAKEDEPENDS']) ? $this->depends['MAKEDEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getCheckDepends(): array
    {
        return isset($this->depends['CHECKDEPENDS']) ? $this->depends['CHECKDEPENDS'] : array();
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        if (Config::get('packages', 'files') && file_exists($this->packageDir . '/files')) {
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
