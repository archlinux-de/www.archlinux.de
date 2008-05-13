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
set_exception_handler('ExceptionHandler');
set_error_handler('ErrorHandler');

function ExceptionHandler(Exception $e)
	{
	try
		{
		$screen = '<?xml version="1.0" encoding="UTF-8" ?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
			<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
			<title>'.get_class($e).'</title>
			</head>
			<body>
				<h1 style="font-size:16px;">'.get_class($e).'</h1>
				<pre style="overflow:auto;">'.$e->getMessage().'</pre>
				<pre>
<strong>Code</strong>: '.$e->getCode().'
<strong>File</strong>: '.$e->getFile().'
<strong>Line</strong>: '.$e->getLine().'</pre>
				<h2 style="font-size:14px;">Trace:</h2>
				<pre>'.$e->getTraceAsString().'</pre>
			</body>
			</html>';

		if (Modul::__get('Settings')->getValue('debug'))
			{
			header('HTTP/1.1 500 Exception');
			header('Content-Length: '.strlen($screen));
			header('Content-Type: text/html; charset=UTF-8');
			echo $screen;
			exit();
			}
		else
			{
			if (Modul::__get('Settings')->getValue('log_dir') != '')
				{
				file_put_contents(Modul::__get('Settings')->getValue('log_dir').time().'.html', $screen);
				}

			$screen = '<?xml version="1.0" encoding="UTF-8" ?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
			<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
			<title>Schwerer Fehler</title>
			</head>
			<body>
				<h1 style="font-size:16px;">Fehler in Modul '.get_class($e).'</h1>
				<p>Es ist ein schwerer Fehler aufgetreten. Die LL-Administration wurde bereits benachrichtigt. Das Problem wird sobald wie möglich behoben.</p>
				<h2 style="font-size:14px;">Kontakt</h2>
				<p><a href="mailto:support@laber-land.de">support@laber-land.de</a></p>
			</body>
			</html>';

			header('HTTP/1.1 500 Exception');
			header('Content-Type: text/html; charset=UTF-8');
			header('Content-Length: '.strlen($screen));
			echo $screen;
			exit();
			}
		}
	catch (Exception $e)
		{
		die($e->getMessage());
		}
	}

/**
* Hiermit sorgen wir dafür, daß auch PHP-Fehler eine Exception werfen.
*/
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