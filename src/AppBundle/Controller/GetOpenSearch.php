<?php

namespace AppBundle\Controller;

use DOMDocument;
use Symfony\Component\Asset\Packages;
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
     * @Route("/packages/opensearch", methods={"GET"})
     * @return Response
     */
    public function indexAction(): Response
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $body = $dom->createElementNS('http://a9.com/-/spec/opensearch/1.1/', 'OpenSearchDescription');

        $shortName = $dom->createElement('ShortName', 'Paket-Suche');
        $description = $dom->createElement('Description', 'Suche nach Arch Linux Paketen');

        $results = $dom->createElement('Url');
        $results->setAttribute(
            'template',
            $this->router->generate('app_packages_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
            . '?search={searchTerms}'
        );
        $results->setAttribute('type', 'text/html');
        $results->setAttribute('rel', 'results');

        $suggestions = $dom->createElement('Url');
        $suggestions->setAttribute(
            'template',
            $this->router->generate(
                'app_packagessuggest_suggest',
                ['field' => '0', 'term' => '{searchTerms}'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
        $suggestions->setAttribute('type', 'application/json');
        $suggestions->setAttribute('rel', 'suggestions');

        $self = $dom->createElement('Url');
        $self->setAttribute('template', $this->router->generate(
            'app_getopensearch_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ));
        $self->setAttribute('type', 'application/opensearchdescription+xml');
        $self->setAttribute('rel', 'self');

        $contact = $dom->createElement('Contact', $this->getParameter('app.common.email'));

        $icon = $dom->createElement('Image', $this->assetPackages->getUrl('build/images/archicon.svg'));
        $icon->setAttribute('type', 'image/svg+xml');

        $body->appendChild($shortName);
        $body->appendChild($description);
        $body->appendChild($results);
        $body->appendChild($suggestions);
        $body->appendChild($self);
        $body->appendChild($contact);
        $body->appendChild($icon);
        $dom->appendChild($body);

        return (new Response(
            $dom->saveXML(),
            Response::HTTP_OK,
            ['Content-Type' => 'application/opensearchdescription+xml; charset=UTF-8']
        ))->setSharedMaxAge(600);
    }
}
