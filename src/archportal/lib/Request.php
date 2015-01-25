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

class Request
{

    private static $instances = array();
    private $request = array();

    private function __construct($type)
    {
        switch ($type) {
            case 'get': $this->request = & $_GET;
                break;
            case 'post': $this->request = & $_POST;
                break;
            case 'cookie': $this->request = & $_COOKIE;
                break;
            case 'request': $this->request = & $_REQUEST;
                break;
            case 'server': $this->request = & $_SERVER;
                break;
            case 'env': $this->request = & $_ENV;
                break;
        }
    }

    /**
     * @param string $type
     * @return Request
     */
    public static function getInstance($type)
    {
        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = new self($type);
        }

        return self::$instances[$type];
    }

    /**
     * @param string $input
     * @return bool
     */
    private function is_unicode($input)
    {
        return mb_check_encoding($input, 'UTF-8');
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isString($name)
    {
        return isset($this->request[$name]) && is_string($this->request[$name]) && $this->is_unicode($this->request[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isEmptyString($name)
    {
        return !$this->isString($name) || !$this->isRegex($name, '/\S+/');
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRequest($name)
    {
        return isset($this->request[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isInt($name)
    {
        return $this->isRegex($name, '/^-?[0-9]+$/');
    }

    /**
     * @param string $name
     * @param string $regex
     * @return bool
     */
    public function isRegex($name, $regex)
    {
        return $this->isString($name) && preg_match($regex, $this->request[$name]);
    }

    /**
     * @param string $name
     * @return int
     */
    public function getHtmlLength($name)
    {
        return $this->isEmptyString($name) ? 0 : strlen(htmlspecialchars($this->request[$name], ENT_COMPAT));
    }

    /**
     * @param string $name
     * @param bool $default
     * @return string
     */
    public function getString($name, $default = false)
    {
        if (!$this->isEmptyString($name)) {
            return $this->request[$name];
        } elseif ($default !== false) {
            return $default;
        } else {
            throw new RequestException($name);
        }
    }

    /**
     * @param string $name
     * @param bool $default
     * @return int
     */
    public function getInt($name, $default = false)
    {
        if ($this->isInt($name)) {
            return $this->request[$name];
        } elseif ($default !== false) {
            return $default;
        } else {
            throw new RequestException($name);
        }
    }

    /**
     * @param string $name
     * @param bool $default
     * @return string
     */
    public function getHtml($name, $default = false)
    {
        return htmlspecialchars($this->getString($name, $default), ENT_COMPAT);
    }

}
