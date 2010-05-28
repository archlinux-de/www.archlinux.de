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

class Request {

private $request = array();

public function __construct(&$request)
	{
	$this->request = &$request;
	}

public function isString($name)
	{
	return isset($this->request[$name]) && is_unicode($this->request[$name]);
	}

public function isEmptyString($name)
	{
	return !$this->isString($name) || !$this->isRegex($name, '/\S+/');
	}

public function isHex($name)
	{
	return $this->isRegex($name, '/^[a-f0-9]+$/i');
	}

public function isInt($name)
	{
	return $this->isRegex($name, '/^-?[0-9]+$/');
	}

public function isRegex($name, $regex)
	{
	return isset($this->request[$name]) && preg_match($regex, $this->request[$name]);
	}

public function getLength($name)
	{
	return $this->isEmptyString($name) ? 0 : strlen($this->request[$name]);
	}

public function getHtmlLength($name)
	{
	return $this->isEmptyString($name) ? 0 : strlen(htmlspecialchars($this->request[$name], ENT_COMPAT));
	}

public function getString($name, $default = false)
	{
	if (!$this->isEmptyString($name))
		{
		return $this->request[$name];
		}
	elseif ($default !== false)
		{
		return $default;
		}
	else
		{
		throw new RequestException($name);
		}
	}

public function getInt($name, $default = false)
	{
	if ($this->isInt($name))
		{
		return $this->request[$name];
		}
	elseif ($default !== false)
		{
		return $default;
		}
	else
		{
		throw new RequestException($name);
		}
	}

public function getHex($name, $default = false)
	{
	if ($this->isHex($name))
		{
		return $this->request[$name];
		}
	elseif ($default !== false)
		{
		return $default;
		}
	else
		{
		throw new RequestException($name);
		}
	}

public function getHtml($name, $default = false)
	{
	return htmlspecialchars($this->getString($name, $default), ENT_COMPAT);
	}

private function checkArray(&$value, $key)
	{
	if (!is_unicode($value) || !preg_match('/\S+/', $value))
		{
		throw new RequestException($key);
		}
	}

public function getArray($name, $default = false)
	{
	if(isset($this->request[$name]) && is_array($this->request[$name]))
		{
		array_walk_recursive($this->request[$name], array($this, 'checkArray'));

		return $this->request[$name];
		}
	elseif ($default !== false)
		{
		return $default;
		}
	else
		{
		throw new RequestException($name);
		}
	}
}

class RequestException extends RuntimeException {

function __construct($message)
	{
	parent::__construct(sprintf('Parameter %s could not be read', $message), 0);
	}

}

?>