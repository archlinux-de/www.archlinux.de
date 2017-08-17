<?php

namespace archportal\lib;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Request
{
    /** @var array */
    private static $instances = array();
    /** @var ParameterBag */
    private $request = array();

    /**
     * @param string $type
     */
    private function __construct(string $type, HttpRequest $httpRequest)
    {
        switch ($type) {
            case 'get':
                $this->request = $httpRequest->query;
                break;
            case 'post':
                $this->request = $httpRequest->request;
                break;
            case 'server':
                $this->request = $httpRequest->server;
                break;
        }
    }

    /**
     * @param string $type
     *
     * @return Request
     */
    public static function getInstance(string $type, HttpRequest $httpRequest): Request
    {
        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = new self($type, $httpRequest);
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
        return $this->request->has($name) && is_string($this->request->get($name)) && $this->is_unicode($this->request->get($name));
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
        return $this->request->has($name);
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
        return $this->isString($name) && preg_match($regex, $this->request->get($name));
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public function getHtmlLength(string $name): int
    {
        return $this->isEmptyString($name) ? 0 : strlen(htmlspecialchars($this->request->get($name), ENT_COMPAT));
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
            return $this->request->get($name);
        } elseif ($default !== null) {
            return $default;
        } else {
            throw new RequestException($name);
        }
    }

    /**
     * @param string $name
     * @param int    $default
     *
     * @return int
     */
    public function getInt(string $name, int $default = null): int
    {
        if ($this->isInt($name)) {
            return (int) $this->request->get($name);
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
