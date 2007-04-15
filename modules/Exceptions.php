<?php

ini_set('docref_root', 'http://www.php.net/');
set_exception_handler('ExceptionHandler');
set_error_handler('ErrorHandler');

function ExceptionHandler(Exception $e)
	{
	require_once ('modules/Modul.php');
	require_once ('modules/Settings.php');
	$Settings = new Settings();

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

		if ($Settings->getValue('debug'))
			{
			header('HTTP/1.1 500 Exception');
			header('Content-Length: '.strlen($screen));
			header('Content-Type: text/html; charset=UTF-8');
			echo $screen;
			exit();
			}
		else
			{
			if ($Settings->getValue('log_dir') != '')
				{
				file_put_contents($Settings->getValue('log_dir').time().'.html', $screen);
				}

// 			$mail = Modul::__get('Mail');
//
// 			$mail->setTo('support@laber-land.de');
// 			$mail->setFrom('support@laber-land.de');
// 			$mail->setSubject('LL-Error');
// 			$mail->setText($screen);
// 			$mail->send();

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