<?php

declare (strict_types = 1);

/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

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

namespace archportal\lib;

class Exceptions
{
    /**
     * @param \Throwable $e
     */
    public static function ExceptionHandler(\Throwable $e)
    {
        try {
            $errorType = array(
                \E_WARNING => 'WARNING',
                \E_NOTICE => 'NOTICE',
                \E_USER_ERROR => 'USER ERROR',
                \E_USER_WARNING => 'USER WARNING',
                \E_USER_NOTICE => 'USER NOTICE',
                \E_STRICT => 'STRICT NOTICE',
                \E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
                \E_DEPRECATED => 'DEPRECATED',
                \E_USER_DEPRECATED => 'USER_DEPRECATED',
            );
            $type = (isset($errorType[$e->getCode()]) ? $errorType[$e->getCode()] : $e->getCode());
            $files = get_included_files();
            $context = array_slice(file($e->getFile(), \FILE_IGNORE_NEW_LINES), max(0, $e->getLine() - 2), 3, true);

            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
                header('text/html; charset=UTF-8');
            }
            if (php_sapi_name() == 'cli') {
                require __DIR__.'/../templates/ExceptionCliTemplate.php';
            } elseif (Config::get('common', 'debug')) {
                require __DIR__.'/../templates/ExceptionDebugTemplate.php';
            } else {
                ob_start();
                require __DIR__.'/../templates/ExceptionLogTemplate.php';
                self::sendLog(ob_get_contents());
                ob_end_clean();
                $l10n = new L10n();
                require __DIR__.'/../templates/ExceptionTemplate.php';
            }
        } catch (\Exception $d) {
            echo $d->getMessage(), "<br />\n", $e->getMessage();
        }
        die();
    }

    /**
     * @param string $log
     */
    private static function sendLog(string $log)
    {
        mail(
            Config::get('common', 'email'), Config::get('common', 'sitename').': Exception', utf8_decode($log),
            'From: '.Config::get('common', 'email')
        );
    }

    public static function ErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new \ErrorException($errstr, $errno, \E_WARNING, $errfile, $errline);
    }
}
