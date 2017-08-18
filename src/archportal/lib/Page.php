<?php

namespace archportal\lib;

use Symfony\Component\HttpFoundation\Request;

abstract class Page extends Output
{
    /** @var string */
    private $title = '';
    /** @var string */
    private $body = '';
    /** @var array */
    private $cssFiles = array('arch', 'archnavbar');
    /** @var array */
    private $jsFiles = array();
    /** @var L10n|null */
    protected $l10n = null;

    public function __construct()
    {
        $this->l10n = new L10n();
        parent::__construct();
    }

    /**
     * @param string $title
     */
    protected function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    protected function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $body
     */
    protected function setBody(string $body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    protected function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $name
     */
    protected function addCSS(string $name)
    {
        $this->cssFiles[] = $name;
    }

    /**
     * @return array
     */
    protected function getCSS():array
    {
        return $this->cssFiles;
    }

    /**
     * @param string $name
     */
    protected function addJS(string $name)
    {
        $this->jsFiles[] = $name;
    }

    /**
     * @return array
     */
    protected function getJS(): array
    {
        return $this->jsFiles;
    }

    /**
     * @return string
     */
    protected function getName(): string
    {
        return get_class($this);
    }

    /**
     * @param Request $request
     */
    abstract public function prepare(Request $request);

    public function printPage()
    {
        require __DIR__.'/../templates/PageTemplate.php';
    }
}
