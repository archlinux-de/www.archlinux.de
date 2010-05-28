<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

ini_set('docref_root', 'http://www.php.net/');
set_exception_handler('ExceptionHandler');
set_error_handler('ErrorHandler');

function ExceptionHandler(Exception $e)
	{
	try
		{
		$errorType = array (
			E_ERROR			=> 'ERROR',
			E_WARNING		=> 'WARNING',
			E_PARSE			=> 'PARSING ERROR',
			E_NOTICE		=> 'NOTICE',
			E_CORE_ERROR		=> 'CORE ERROR',
			E_CORE_WARNING		=> 'CORE WARNING',
			E_COMPILE_ERROR		=> 'COMPILE ERROR',
			E_COMPILE_WARNING	=> 'COMPILE WARNING',
			E_USER_ERROR		=> 'USER ERROR',
			E_USER_WARNING		=> 'USER WARNING',
			E_USER_NOTICE		=> 'USER NOTICE',
			E_STRICT		=> 'STRICT NOTICE',
			E_RECOVERABLE_ERROR	=> 'RECOVERABLE ERROR'
			);

		$screen = '<?xml version="1.0" encoding="UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>'.get_class($e).'</title>
</head>
<body>
<h1 style="font-size:16px;">'.get_class($e).'</h1>
<pre style="overflow:auto;">'.htmlspecialchars($e->getMessage()).'</pre>
<pre>
<strong>Type</strong>: '.(isset($errorType[$e->getCode()]) ? $errorType[$e->getCode()] : $e->getCode()).'
<strong>File</strong>: '.htmlspecialchars($e->getFile()).'
<strong>Line</strong>: '.$e->getLine().'</pre>
<h2 style="font-size:14px;">Trace:</h2>
<pre>'.htmlspecialchars($e->getTraceAsString()).'</pre>
</body>
</html>';

		if (Modul::get('Settings')->getValue('debug'))
			{
			if (!headers_sent())
				{
				header('HTTP/1.1 500 Exception');
				header('Content-Length: '.strlen($screen));
				header('Content-Type: text/html; charset=UTF-8');
				}

			if (php_sapi_name() == 'cli')
				{
				echo strip_tags(unhtmlspecialchars($screen));
				}
			else
				{
				echo $screen;
				}
			die();
			}
		else
			{
			if (Modul::get('Settings')->getValue('log_dir') != '')
				{
				file_put_contents(Modul::get('Settings')->getValue('log_dir').time().'.html', $screen);
				}

			$mail = Modul::get('Mail');

			$mail->setTo(Modul::get('Settings')->getValue('email'));
			$mail->setFrom(Modul::get('Settings')->getValue('email'));
			$mail->setSubject('LL-Error');
			$mail->setText(strip_tags(unhtmlspecialchars($screen)));
			$mail->send();

			$screen = '<?xml version="1.0" encoding="UTF-8" ?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
			<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
			<title>Schwerer Fehler</title>
			</head>
			<body>
				<h1 style="font-size:16px;">Fehler in Modul '.get_class($e).'</h1>
				<p>Es ist ein schwerer Fehler aufgetreten. Die Administration wurde bereits benachrichtigt. Das Problem wird sobald wie möglich behoben.</p>
				<h2 style="font-size:14px;">Kontakt</h2>
				<p><a href="mailto:'.Modul::get('Settings')->getValue('email').'">'.Modul::get('Settings')->getValue('email').'</a></p>
			</body>
			</html>';

			if (!headers_sent())
				{
				header('HTTP/1.1 500 Exception');
				header('Content-Type: text/html; charset=UTF-8');
				header('Content-Length: '.strlen($screen));
				}

			if (php_sapi_name() == 'cli')
				{
				echo strip_tags(unhtmlspecialchars($screen));
				}
			else
				{
				echo $screen;
				}
			die();
			}
		}
	catch (Exception $e)
		{
		die($e->getMessage());
		}
	}

function ErrorHandler($code, $string, $file, $line)
	{
	ExceptionHandler(new InternalRuntimeException ($string, $code, $file, $line));
	}

class InternalRuntimeException extends RuntimeException {

public function __construct($string, $code, $file, $line)
	{
	parent::__construct($string, $code);
	$this->file = $file;
	$this->line = $line;
	}
}

?>
