<?php

/*
  Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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

abstract class Output
{

    const OK = 'HTTP/1.1 200 OK';
    const FOUND = 'HTTP/1.1 302 Found';
    const MOVED_PERMANENTLY = 'HTTP/1.1 301 Moved Permanently';
    const BAD_REQUEST = 'HTTP/1.1 400 Bad Request';
    const NOT_FOUND = 'HTTP/1.1 404 Not Found';
    const INTERNAL_SERVER_ERROR = 'HTTP/1.1 500 Internal Server Error';

    private $contentType = 'text/html; charset=UTF-8';
    private $status = Output::OK;
    private $outputSeparator = '&';
    private $outputSeparatorHtml = '&amp;';

    public function __construct()
    {
        $this->outputSeparator = ini_get('arg_separator.output');
        $this->outputSeparatorHtml = htmlspecialchars($this->outputSeparator);
        ob_start();
    }

    public function __destruct()
    {
        header($this->status);
        header('Content-Type: ' . $this->contentType);
    }

    /**
     * @param string $code
     */
    protected function setStatus($code)
    {
        $this->status = $code;
    }

    /**
     * @param string $type
     */
    protected function setContentType($type)
    {
        $this->contentType = $type;
    }

    /**
     * @param string $url
     */
    protected function redirectToUrl($url)
    {
        $this->setStatus(Output::FOUND);
        header('Location: ' . $url);
        exit();
    }

    /**
     * @param string $url
     */
    protected function redirectPermanentlyToUrl($url)
    {
        $this->setStatus(Output::MOVED_PERMANENTLY);
        header('Location: ' . $url);
        exit();
    }

    /**
     * @param string $page
     * @param array $options
     * @param bool $absolute
     * @param bool $html
     * @param bool $urlencode
     * @return string
     */
    protected function createUrl($page, $options = array(), $absolute = false, $html = true, $urlencode = true)
    {
        $separator = ($html ? $this->outputSeparatorHtml : $this->outputSeparator);
        $params = array();
        foreach (array_merge(array(
            'page' => $page
                ), $options) as $key => $value) {
            $params[] = $key . '=' . ($urlencode ? urlencode($value) : $value);
        }

        return ($absolute ? Input::getPath() : '') . '?' . implode($separator, $params);
    }

}
