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

class Request
{
    /** @var array */
    private static $instances = array();
    /** @var array */
    private $request = array();

    /**
     * @param string $type
     */
    private function __construct(string $type)
    {
        switch ($type) {
            case 'get':
                $this->request = &$_GET;
                break;
            case 'post':
                $this->request = &$_POST;
                break;
            case 'cookie':
                $this->request = &$_COOKIE;
                break;
            case 'request':
                $this->request = &$_REQUEST;
                break;
            case 'server':
                $this->request = &$_SERVER;
                break;
            case 'env':
                $this->request = &$_ENV;
                break;
        }
    }

    /**
     * @param string $type
     *
     * @return Request
     */
    public static function getInstance(string $type): Request
    {
        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = new self($type);
        }

        return self::$instances[$type];
    }

    /**
     * @param string $input
     *
     * @return bool
     */
    private function is_unicode(string $input): bool
    {
        return mb_check_encoding($input, 'UTF-8');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isString(string $name): bool
    {
        return isset($this->request[$name]) && is_string($this->request[$name]) && $this->is_unicode($this->request[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isEmptyString(string $name): bool
    {
        return !$this->isString($name) || !$this->isRegex($name, '/\S+/');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isRequest(string $name): bool
    {
        return isset($this->request[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isInt(string $name): bool
    {
        return $this->isRegex($name, '/^-?[0-9]+$/');
    }

    /**
     * @param string $name
     * @param string $regex
     *
     * @return bool
     */
    public function isRegex(string $name, string $regex): bool
    {
        return $this->isString($name) && preg_match($regex, $this->request[$name]);
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function getHtmlLength(string $name): int
    {
        return $this->isEmptyString($name) ? 0 : strlen(htmlspecialchars($this->request[$name], ENT_COMPAT));
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getString(string $name, string $default = null): string
    {
        if (!$this->isEmptyString($name)) {
            return $this->request[$name];
        } elseif ($default !== null) {
            return $default;
        } else {
            throw new RequestException($name);
        }
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return int
     */
    public function getInt(string $name, string $default = null): int
    {
        if ($this->isInt($name)) {
            return (int) $this->request[$name];
        } elseif ($default !== null) {
            return $default;
        } else {
            throw new RequestException($name);
        }
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getHtml(string $name, string $default = null): string
    {
        return htmlspecialchars($this->getString($name, $default), ENT_COMPAT);
    }
}
