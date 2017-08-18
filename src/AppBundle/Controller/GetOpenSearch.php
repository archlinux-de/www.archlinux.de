<?php

namespace AppBundle\Controller;

use archportal\lib\Config;
use DOMDocument;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class GetOpenSearch extends Controller
{
    /** @var RouterInterface */
    private $router;
    /** @var Packages */
    private $assetPackages;

    /**
     * @param RouterInterface $router
     * @param Packages $assetPackages
     */
    public function __construct(RouterInterface $router, Packages $assetPackages)
    {
        $this->router = $router;
        $this->assetPackages = $assetPackages;
    }

    /**
     * @Route("/feed/search")
     * @return Response
     */
    public function indexAction(): Response
    {
        $this->get('AppBundle\Service\LegacyEnvironment')->initialize();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $body = $dom->createElementNS('http://a9.com/-/spec/opensearch/1.1/', 'OpenSearchDescription');

        $shortName = $dom->createElement('ShortName', 'Paket-Suche');
        $description = $dom->createElement('Description', 'Suche nach Arch Linux Paketen');

        $results = $dom->createElement('Url');
        $results->setAttribute('template',
            $this->router->generate('app_packages_index', ['submit' => '', 'search' => '{searchTerms}'], UrlGeneratorInterface::ABSOLUTE_URL));
        $results->setAttribute('type', 'text/html');
        $results->setAttribute('rel', 'results');

        $suggestions = $dom->createElement('Url');
        $suggestions->setAttribute('template',
            $this->router->generate('app_packagessuggest_suggest', ['field' => '0', 'term' => '{searchTerms}'], UrlGeneratorInterface::ABSOLUTE_URL));
        $suggestions->setAttribute('type', 'application/json');
        $suggestions->setAttribute('rel', 'suggestions');

        $self = $dom->createElement('Url');
        $self->setAttribute('template', $this->router->generate('app_getopensearch_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $self->setAttribute('type', 'application/opensearchdescription+xml');
        $self->setAttribute('rel', 'self');

        $contact = $dom->createElement('Contact', Config::get('common', 'email'));

        $icon = $dom->createElement('Image', $this->assetPackages->getUrl('style/favicon.ico'));
        $icon->setAttribute('height', '16');
        $icon->setAttribute('width', '16');
        $icon->setAttribute('type', 'image/x-icon');

        $image = $dom->createElement('Image', $this->assetPackages->getUrl('style/archlogo-64.png'));
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

        return new Response(
            $dom->saveXML(),
            Response::HTTP_OK,
            ['Content-Type' => 'application/opensearchdescription+xml; charset=UTF-8']
        );
    }
}
