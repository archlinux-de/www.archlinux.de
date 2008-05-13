<?php

/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
*/

class Package {

private $desc 		= array();
private $depends 	= array();
private $filemtime	= 0;


public function __construct($desc, $depends = '', $filemtime = 0)
	{
	$this->filemtime = $filemtime;
	$this->desc = $this->loadInfo($desc);
	if (!empty($depends))
		{
		$this->depends = $this->loadInfo($depends);
		}
	}

private function loadInfo($file)
	{
	$index = '';
	$data = array();

	foreach (explode("\n", $file) as $line)
		{
		if (!empty($line))
			{
			if ($line[0] == '%' && $line[strlen($line) - 1] == '%')
				{
				$index = substr($line, 1, -1);
				}
			else
				{
				$data[$index][] = $line;
				}
			}
		}

	return $data;
	}

public function getFileName()
	{
	return isset($this->desc['FILENAME'][0]) ? $this->desc['FILENAME'][0] : $this->getName().'-'.$this->getVersion().'.pkg.tar.gz';
	}

public function getName()
	{
	return $this->desc['NAME'][0];
	}

public function getVersion()
	{
	return $this->desc['VERSION'][0];
	}

public function getDescription()
	{
	return isset($this->desc['DESC'][0]) ? $this->desc['DESC'][0] : '';
	}

public function getGroups()
	{
	return isset($this->desc['GROUPS']) ? $this->desc['GROUPS'] : array();
	}

public function getCompressedSize()
	{
	return $this->desc['CSIZE'][0];
	}

public function getInstalledSize()
	{
	return isset($this->desc['ISIZE'][0]) ? $this->desc['ISIZE'][0] : $this->getCompressedSize();
	}

public function getMD5SUM()
	{
	return $this->desc['MD5SUM'][0];
	}

public function getURL()
	{
	return isset($this->desc['URL'][0]) ? $this->desc['URL'][0] : '';
	}

public function getLicenses()
	{
	return isset($this->desc['LICENSE']) ? $this->desc['LICENSE'] : array();
	}

public function getArchitecture()
	{
	return $this->desc['ARCH'][0];
	}

public function getBuildDate()
	{
	return isset($this->desc['BUILDDATE'][0]) && $this->desc['BUILDDATE'][0] <= $this->filemtime ? $this->desc['BUILDDATE'][0] : $this->filemtime;
	}

public function getPackager()
	{
	return isset($this->desc['PACKAGER'][0]) ? $this->desc['PACKAGER'][0] : '';
	}

public function getReplaces()
	{
	return isset($this->desc['REPLACES']) ? $this->desc['REPLACES'] : array();
	}

public function isForced()
	{
	return isset($this->desc['FORCE']);
	}

public function getDepends()
	{
	return isset($this->depends['DEPENDS']) ? $this->depends['DEPENDS'] : array();
	}

public function getConflicts()
	{
	return isset($this->depends['CONFLICTS']) ? $this->depends['CONFLICTS'] : array();
	}

public function getProvides()
	{
	return isset($this->depends['PROVIDES']) ? $this->depends['PROVIDES'] : array();
	}

}

?>