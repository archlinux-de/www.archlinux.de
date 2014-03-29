<?php

/*
  Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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

use Iterator;
use RuntimeException;

class PackageDatabase implements Iterator
{

    private $dbext = '.db';
    private $mtime = 0;
    private $repoMinMTime = 0;
    private $packageMinMTime = 0;
    private $currentKey = 0;
    private $currentDir = false;
    private $dbHandle = null;
    private $dbDir = null;
    private $packageCount = null;

    public function __construct($repository, $architecture, $repoMinMTime = 0, $packageMinMTime = 0)
    {
        if (Config::get('packages', 'files')) {
            $this->dbext = '.files';
        }
        $this->repoMinMTime = $repoMinMTime;
        $this->packageMinMTime = $packageMinMTime;
        $download = new Download(Config::get('packages', 'mirror') . $repository . '/os/' . $architecture . '/' . $repository . $this->dbext);
        $this->mtime = $download->getMTime();

        $this->dbDir = $this->makeTempDir();
        $this->dbHandle = opendir($this->dbDir);

        if ($this->mtime > $this->repoMinMTime && Input::getTime() - $this->mtime > Config::get('packages', 'delay')) {
            system('bsdtar -xf ' . $download->getFile() . ' -C ' . $this->dbDir, $return);
            if ($return !== 0) {
                throw new RuntimeException('Could not extract Database');
            }
        }
    }

    private function makeTempDir()
    {
        $tmp = tempnam(Config::get('common', 'tmpdir'), strtolower(str_replace('\\', '/', get_class($this))));
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

    public function current()
    {
        return new Package($this->dbDir . '/' . $this->currentDir);
    }

    public function key()
    {
        return $this->currentKey;
    }

    public function next()
    {
        do {
            $this->currentDir = readdir($this->dbHandle);
        } while ($this->currentDir == '.' || $this->currentDir == '..' || filemtime($this->dbDir . '/' . $this->currentDir) <= $this->packageMinMTime
        );
        $this->currentKey++;
    }

    public function rewind()
    {
        rewinddir($this->dbHandle);
        $this->currentKey = 0;
        $this->currentDir = false;
        $this->next();
    }

    public function valid()
    {
        return $this->currentDir !== false;
    }

    public function getMTime()
    {
        return $this->mtime;
    }

    private function rmrf($dir)
    {
        if (is_dir($dir) && !is_link($dir)) {
            $dh = opendir($dir);
            while (false !== ($file = readdir($dh))) {
                if ($file != '.' && $file != '..') {
                    if (!$this->rmrf($dir . '/' . $file)) {
                        throw new RuntimeException('Could not remove ' . $dir . '/' . $file);
                    }
                }
            }
            closedir($dh);

            return rmdir($dir);
        } else {
            return unlink($dir);
        }
    }

    public function getNewPackageCount()
    {
        if (is_null($this->packageCount)) {
            $packages = 0;
            if (is_dir($this->dbDir)) {
                $dh = opendir($this->dbDir);
                while (false !== ($dir = readdir($dh))) {
                    if (is_dir($this->dbDir . '/' . $dir) && $dir != '.' && $dir != '..' && filemtime($this->dbDir . '/' . $dir) > $this->packageMinMTime
                    ) {
                        $packages++;
                    }
                }
                closedir($dh);
            }
            $this->packageCount = $packages;
        }

        return $this->packageCount;
    }

    public function getOldPackageNames()
    {
        $packages = array();
        if (is_dir($this->dbDir)) {
            $dh = opendir($this->dbDir);
            while (false !== ($dir = readdir($dh))) {
                if (is_dir($this->dbDir . '/' . $dir) && $dir != '.' && $dir != '..' && filemtime($this->dbDir . '/' . $dir) <= $this->packageMinMTime
                ) {
                    $matches = array();
                    if (preg_match('/^([^\-].*)-[^\-]+?-[^\-]+?$/', $dir, $matches) == 1) {
                        $packages[] = $matches[1];
                    } else {
                        throw new RuntimeException('Could not read package ' . $dir);
                    }
                }
            }
            closedir($dh);
        }

        return $packages;
    }

}
