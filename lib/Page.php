<?php

/*
  Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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

    private $title = '';
    private $body = '';
    private $metaRobots = 'index,follow';
    private $cssFiles = array('arch', 'archnavbar');
    private $jsFiles = array();
    protected $l10n = null;

    public function __construct()
    {
        $this->l10n = new L10n();
        parent::__construct();
    }

    protected function setTitle($title)
    {
        $this->title = $title;
    }

    protected function getTitle()
    {
        return $this->title;
    }

    protected function setBody($body)
    {
        $this->body = $body;
    }

    protected function getBody()
    {
        return $this->body;
    }

    protected function setMetaRobots($metaRobots)
    {
        $this->metaRobots = $metaRobots;
    }

    protected function getMetaRobots()
    {
        return $this->metaRobots;
    }

    protected function addCSS($name)
    {
        $this->cssFiles[] = $name;
    }

    protected function getCSS()
    {
        return $this->cssFiles;
    }

    protected function addJS($name)
    {
        $this->jsFiles[] = $name;
    }

    protected function getJS()
    {
        return $this->jsFiles;
    }

    protected function getName()
    {
        return get_class($this);
    }

    protected function showFailure($text)
    {
        $this->setMetaRobots('noindex,nofollow');
        $this->setTitle($this->l10n->getText('Error'));
        $this->setBody('<div id="warning">' . $text . '</div>');
        require (__DIR__ . '/../templates/PageTemplate.php');
        exit();
    }

    public function prepare()
    {
        $this->setTitle($this->l10n->getText('Warning'));
        $this->setBody($this->l10n->getText('no text'));
    }

    protected function cutString($string, $length)
    {
        // Verhindere das Abschneiden im Entity
        $string = htmlspecialchars_decode(trim($string));
        $string = (mb_strlen($string, 'UTF-8') > $length ? mb_substr($string, 0, ($length - 3), 'UTF-8') . '...' : $string);

        return htmlspecialchars($string);
    }

    public function printPage()
    {
        require (__DIR__ . '/../templates/PageTemplate.php');
    }

}
