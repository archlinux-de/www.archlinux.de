<?php

namespace archportal\pages;

use archportal\lib\Config;
use archportal\lib\Page;
use DOMDocument;
use Symfony\Component\HttpFoundation\Request;

class GetOpenSearch extends Page
{
    /** @var string */
    private $opensearch = '';

    public function prepare(Request $request)
    {
        $path = $request->getSchemeAndHttpHost().'/';
        $dom = new DOMDocument('1.0', 'UTF-8');
        $body = $dom->createElementNS('http://a9.com/-/spec/opensearch/1.1/', 'OpenSearchDescription');

        $shortName = $dom->createElement('ShortName', $this->l10n->getText('Package search'));
        $description = $dom->createElement('Description', $this->l10n->getText('Search for Arch Linux packages'));

        $results = $dom->createElement('Url');
        $results->setAttribute('template',
            $this->createUrl('Packages', array('submit' => '', 'search' => '{searchTerms}'), true, false, false));
        $results->setAttribute('type', 'text/html');
        $results->setAttribute('rel', 'results');

        $suggestions = $dom->createElement('Url');
        $suggestions->setAttribute('template',
            $this->createUrl('PackagesSuggest', array('field' => '0', 'term' => '{searchTerms}'), true, false, false));
        $suggestions->setAttribute('type', 'application/json');
        $suggestions->setAttribute('rel', 'suggestions');

        $self = $dom->createElement('Url');
        $self->setAttribute('template', $this->createUrl($this->getName(), array(), true, false));
        $self->setAttribute('type', 'application/opensearchdescription+xml');
        $self->setAttribute('rel', 'self');

        $contact = $dom->createElement('Contact', Config::get('common', 'email'));

        $icon = $dom->createElement('Image', $path.'style/favicon.ico');
        $icon->setAttribute('height', '16');
        $icon->setAttribute('width', '16');
        $icon->setAttribute('type', 'image/x-icon');

        $image = $dom->createElement('Image', $path.'style/archlogo-64.png');
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
