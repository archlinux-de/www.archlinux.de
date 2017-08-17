<?php
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

use Symfony\Component\HttpFoundation\Response;

abstract class Output
{
    /** @var string */
    private $contentType = 'text/html; charset=UTF-8';
    /** @var int */
    private $status = Response::HTTP_OK;
    /** @var string */
    private $outputSeparator = '&';
    /** @var string */
    private $outputSeparatorHtml = '&amp;';

    /** @var array */
    private $headers = array();

    public function __construct()
    {
        $this->outputSeparator = ini_get('arg_separator.output');
        $this->outputSeparatorHtml = htmlspecialchars($this->outputSeparator);
    }

    /**
     * @param int $code
     */
    protected function setStatus(int $code)
    {
        $this->status = $code;
    }

    /**
     * @param string $type
     */
    protected function setContentType(string $type)
    {
        $this->contentType = $type;
    }

    /**
     * @param string $url
     */
    protected function redirectToUrl(string $url)
    {
        $this->setStatus(Response::HTTP_FOUND);
        $this->headers = ['Location' => $url];
    }

    /**
     * @param string $url
     */
    protected function redirectPermanentlyToUrl(string $url)
    {
        $this->setStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->headers = ['Location' => $url];
    }

    /**
     * @param string $page
     * @param array  $options
     * @param bool   $absolute
     * @param bool   $html
     * @param bool   $urlencode
     *
     * @return string
     */
    protected function createUrl(
        string $page,
        array $options = array(),
        bool $absolute = false,
        bool $html = true,
        bool $urlencode = true
    ): string {
        $separator = ($html ? $this->outputSeparatorHtml : $this->outputSeparator);
        $params = array();
        foreach (array_merge(array(
            'page' => $page,
        ), $options) as $key => $value) {
            $params[] = $key.'='.($urlencode ? urlencode((string) $value) : $value);
        }

        return ($absolute ? Input::getPath() : '').'?'.implode($separator, $params);
    }

    protected function disallowCaching()
    {
        $this->headers['Cache-Control'] = 'no-cache, no-store, must-revalidate'; // HTTP 1.1
        $this->headers['Pragma'] = 'no-cache'; // HTTP 1.0
        $this->headers['Expires'] = '0'; // Proxies
        $this->headers['X-Accel-Expires'] = '0'; // Nginx
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
