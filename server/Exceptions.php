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
ini_set('docref_root', 'http://www.php.net/');
// set_exception_handler('ExceptionHandler');
// set_error_handler('ErrorHandler');

function ExceptionHandler(Exception $e)
	{
	die($e->getMessage());
	}

function ErrorHandler($code, $string, $file, $line)
	{
	throw new InternalRuntimeException ($string, $code, $file, $line);
	}

class InternalRuntimeException extends RuntimeException{

public function __construct($string, $code, $file, $line)
	{
	parent::__construct($string, $code);
	$this->file = $file;
	$this->line = $line;
	}

}

?>