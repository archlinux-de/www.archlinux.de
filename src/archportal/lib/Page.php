<?php

/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\lib;

abstract class Page extends Output
{

    /** @var string */
    private $title = '';
    /** @var string */
    private $body = '';
    /** @var string */
    private $metaRobots = 'index,follow';
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
     * @param string $metaRobots
     */
    protected function setMetaRobots(string $metaRobots)
    {
        $this->metaRobots = $metaRobots;
    }

    /**
     * @return string
     */
    protected function getMetaRobots(): string
    {
        return $this->metaRobots;
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
     * @param string $text
     */
    protected function showFailure(string $text)
    {
        $this->setMetaRobots('noindex,nofollow');
        $this->setTitle($this->l10n->getText('Error'));
        $this->setBody('<div id="warning">' . $text . '</div>');
        require(__DIR__ . '/../templates/PageTemplate.php');
        exit();
    }

    public function prepare()
    {
        $this->setTitle($this->l10n->getText('Warning'));
        $this->setBody($this->l10n->getText('no text'));
    }

    /**
     * @param string $string
     * @param int $length
     * @return string
     */
    protected function cutString(string $string, int $length): string
    {
        // Verhindere das Abschneiden im Entity
        $string = htmlspecialchars_decode(trim($string));
        $string = (mb_strlen($string, 'UTF-8') > $length ? mb_substr($string, 0, ($length - 3),
                'UTF-8') . '...' : $string);

        return htmlspecialchars($string);
    }

    public function printPage()
    {
        require(__DIR__ . '/../templates/PageTemplate.php');
    }
}
