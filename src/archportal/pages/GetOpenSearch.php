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

namespace archportal\pages;

use archportal\lib\Config;
use archportal\lib\Input;
use archportal\lib\Page;
use DOMDocument;

class GetOpenSearch extends Page
{

    private $opensearch = '';

    public function prepare()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $body = $dom->createElementNS('http://a9.com/-/spec/opensearch/1.1/', 'OpenSearchDescription');

        $shortName = $dom->createElement('ShortName', $this->l10n->getText('Package search'));
        $description = $dom->createElement('Description', $this->l10n->getText('Search for Arch Linux packages'));

        $results = $dom->createElement('Url');
        $results->setAttribute('template', $this->createUrl('Packages', array('submit' => '', 'search' => '{searchTerms}'), true, false, false));
        $results->setAttribute('type', 'text/html');
        $results->setAttribute('rel', 'results');

        $suggestions = $dom->createElement('Url');
        $suggestions->setAttribute('template', $this->createUrl('PackagesSuggest', array('field' => '0', 'term' => '{searchTerms}'), true, false, false));
        $suggestions->setAttribute('type', 'application/json');
        $suggestions->setAttribute('rel', 'suggestions');

        $self = $dom->createElement('Url');
        $self->setAttribute('template', $this->createUrl($this->getName(), array(), true, false));
        $self->setAttribute('type', 'application/opensearchdescription+xml');
        $self->setAttribute('rel', 'self');

        $contact = $dom->createElement('Contact', Config::get('common', 'email'));

        $icon = $dom->createElement('Image', Input::getPath() . 'style/favicon.ico');
        $icon->setAttribute('height', '16');
        $icon->setAttribute('width', '16');
        $icon->setAttribute('type', 'image/x-icon');

        $image = $dom->createElement('Image', Input::getPath() . 'style/archlogo-64.png');
        $image->setAttribute('height', '64');
        $image->setAttribute('width', '64');
        $image->setAttribute('type', 'image/png');

        $body->appendChild($shortName);
        $body->appendChild($description);
        $body->appendChild($results);
        $body->appendChild($suggestions);
        $body->appendChild($self);
        $body->appendChild($contact);
        $body->appendChild($icon);
        $body->appendChild($image);
        $dom->appendChild($body);

        $this->opensearch = $dom->saveXML();
    }

    public function printPage()
    {
        $this->setContentType('application/opensearchdescription+xml; charset=UTF-8');
        echo $this->opensearch;
    }

}
