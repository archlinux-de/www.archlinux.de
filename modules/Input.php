<?php

/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

require ('modules/Request.php');
require ('modules/File.php');
require ('modules/RemoteFile.php');
require ('modules/UploadedFile.php');

class Input extends Modul {

public $Get 	= null;
public $Post 	= null;
public $Cookie 	= null;
public $Env 	= null;
public $Server 	= null;

private $time	= 0;

public function __construct()
	{
	$this->time = time();

	$this->Get 	= new Request($_GET);
	$this->Post 	= new Request($_POST);
	$this->Cookie 	= new Request($_COOKIE);
	$this->Env 	= new Request($_ENV);
	$this->Server 	= new Request($_SERVER);
	}

public function getTime()
	{
	return $this->time;
	}

public function getHost()
	{
	return $this->Server->getString('HTTP_HOST');
	}

public function getPath()
	{
	$directory = dirname($this->Server->getString('SCRIPT_NAME'));

	return 'http'.(!$this->Server->isString('HTTPS') ? '' : 's').'://'
			.$this->getHost()
			.($directory == '/' ? '' : $directory).'/';
	}

public function getRelativePath()
	{
	$directory = dirname($this->Server->getString('SCRIPT_NAME'));

	return ($directory == '/' ? '' : $directory).'/';
	}

public function getRemoteFile($url)
	{
	return new RemoteFile($url);
	}

public function getUploadedFile($url)
	{
	return new UploadedFile($url);
	}

}

?>