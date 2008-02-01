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
define('IN_LL', null);

// define('LL_PATH', '/home/pierre/public_html/ll/');
require ('LLPath.php');
require ('modules/Modul.php');
require ('modules/Settings.php');
require ('modules/Exceptions.php');
require (LL_PATH.'modules/Functions.php');
require (LL_PATH.'modules/Io.php');

Modul::__set('Settings', new Settings());
$Io = Modul::__set('Io', new Io());

function __autoload($class)
	{
	Modul::loadModul($class);
	}

try
	{
	$page = $Io->getString('page');
	}
catch(IoRequestException $e)
	{
	$page = 'Start';
	}

	try
		{
		Page::loadPage($page);
		}
	catch (RuntimeException $e)
		{
		$page = 'NotFound';
		Page::loadPage($page);
		}

	$class = new $page();
	$class->prepare();
	$class->show();

?>