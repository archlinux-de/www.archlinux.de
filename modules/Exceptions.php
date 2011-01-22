<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

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

ini_set('docref_root', 'http://www.php.net/');
set_exception_handler('ExceptionHandler');
set_error_handler('ErrorHandler');

function ExceptionHandler(Exception $e) {
	try {
		$errorType = array(
			E_ERROR => 'ERROR',
			E_WARNING => 'WARNING',
			E_PARSE => 'PARSING ERROR',
			E_NOTICE => 'NOTICE',
			E_CORE_ERROR => 'CORE ERROR',
			E_CORE_WARNING => 'CORE WARNING',
			E_COMPILE_ERROR => 'COMPILE ERROR',
			E_COMPILE_WARNING => 'COMPILE WARNING',
			E_USER_ERROR => 'USER ERROR',
			E_USER_WARNING => 'USER WARNING',
			E_USER_NOTICE => 'USER NOTICE',
			E_STRICT => 'STRICT NOTICE',
			E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
			E_DEPRECATED => 'DEPRECATED',
			E_USER_DEPRECATED =>'USER_DEPRECATED'
		);
		$type = (isset($errorType[$e->getCode() ]) ? $errorType[$e->getCode() ] : $e->getCode());

		Modul::get('Output')->setStatus(Output::INTERNAL_SERVER_ERROR);

		if (php_sapi_name() == 'cli') {
			require (__DIR__.'/../templates/ExceptionCliTemplate.php');
		} elseif (Modul::get('Settings')->getValue('debug')) {
			require (__DIR__.'/../templates/ExceptionDebugTemplate.php');
		} else {
			require (__DIR__.'/../templates/ExceptionTemplate.php');
		}
	} catch (Exception $e) {
		die($e->getMessage());
	}
}

function ErrorHandler($code, $string, $file, $line) {
	throw new ErrorException($string, $code, E_ERROR, $file, $line);
}


?>
