<?php

namespace AppBundle\Controller;

use archportal\lib\Config;
use Doctrine\DBAL\Driver\Connection;
use DOMDocument;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Asset\Packages;

class RecentPackagesController extends Controller
{
    /** @var Connection */
    private $database;
    /** @var RouterInterface */
    private $router;
    /** @var Packages */
    private $assetPackages;

    /**
     * @param Connection $connection
     * @param RouterInterface $router
     */
    public function __construct(Connection $connection, RouterInterface $router, Packages $assetPackages)
    {
        $this->database = $connection;
        $this->router = $router;
        $this->assetPackages = $assetPackages;
    }

    /**
     * @Route("/feed/packages")
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $this->get('AppBundle\Service\LegacyEnvironment')->initialize();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $body = $dom->createElementNS('http://www.w3.org/2005/Atom', 'feed');

        $id = $dom->createElement('id', $this->router->generate('app_recentpackages_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $title = $dom->createElement('title', 'Aktuelle Arch Linux Pakete');
        $updated = $dom->createElement('updated',
            date('c', $this->database->query('SELECT MAX(builddate) FROM packages')->fetchColumn()));

        $author = $dom->createElement('author');
        $authorName = $dom->createElement('name', Config::get('common', 'sitename'));
        $authorEmail = $dom->createElement('email', Config::get('common', 'email'));
        $authorUri = $dom->createElement('uri', $this->router->generate('app_recentpackages_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $author->appendChild($authorName);
        $author->appendChild($authorEmail);
        $author->appendChild($authorUri);

        $alternate = $dom->createElement('link');
        $alternate->setAttribute('href', $this->router->generate('app_packages_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $alternate->setAttribute('rel', 'alternate');
        $alternate->setAttribute('type', 'text/html');
        $self = $dom->createElement('link');
        $self->setAttribute('href', $this->router->generate('app_recentpackages_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $self->setAttribute('rel', 'self');
        $self->setAttribute('type', 'application/atom+xml');

        $icon = $dom->createElement('icon', $this->assetPackages->getUrl('style/favicon.ico'));
        $logo = $dom->createElement('logo', $this->assetPackages->getUrl('style/archlogo-64.png'));

        $body->appendChild($id);
        $body->appendChild($title);
        $body->appendChild($updated);
        $body->appendChild($author);
        $body->appendChild($alternate);
        $body->appendChild($self);
        $body->appendChild($icon);
        $body->appendChild($logo);

        $packages = $this->database->query('
        SELECT
            packages.name,
            packages.builddate,
            packages.version,
            packages.desc,
            packagers.name AS packager,
            packagers.email AS email,
            architectures.name AS architecture,
            repositories.name AS repository
        FROM
            packages
                JOIN
                    packagers
                ON
                    packages.packager = packagers.id
                JOIN
                    architectures
                ON
                    packages.arch = architectures.id
                JOIN
                    repositories
                ON
                    packages.repository = repositories.id
        ORDER BY
            packages.builddate DESC
        LIMIT
            25
        ');
        foreach ($packages as $package) {
            $entry = $dom->createElement('entry');
            $entryId = $dom->createElement('id', htmlspecialchars($this->router->generate('app_legacy_page', [
                'page' => 'PackageDetails',
                'repo' => $package['repository'],
                'arch' => $package['architecture'],
                'pkgname' => $package['name'],
            ], UrlGeneratorInterface::ABSOLUTE_URL)));
            $entryTitle = $dom->createElement('title',
                $package['name'] . ' ' . $package['version'] . ' (' . $package['architecture'] . ')');
            $entryUpdated = $dom->createElement('updated', date('c', $package['builddate']));

            $entryAuthor = $dom->createElement('author');
            $entryAuthorName = $dom->createElement('name', $package['packager']);
            $entryAuthorEmail = $dom->createElement('email', $package['email']);
            $entryAuthor->appendChild($entryAuthorName);
            $entryAuthor->appendChild($entryAuthorEmail);

            $entryLink = $dom->createElement('link');
            $entryLink->setAttribute('href', $this->router->generate('app_legacy_page', [
                'page' => 'PackageDetails',
                'repo' => $package['repository'],
                'arch' => $package['architecture'],
                'pkgname' => $package['name'],
            ], UrlGeneratorInterface::ABSOLUTE_URL));
            $entryLink->setAttribute('rel', 'alternate');
            $entryLink->setAttribute('type', 'text/html');

            $entrySummary = $dom->createElement('summary', $package['desc']);

            $entry->appendChild($entryId);
            $entry->appendChild($entryTitle);
            $entry->appendChild($entryUpdated);
            $entry->appendChild($entryAuthor);
            $entry->appendChild($entryLink);
            $entry->appendChild($entrySummary);

            $body->appendChild($entry);
        }

        $dom->appendChild($body);

        return new Response(
            $dom->saveXML(),
            Response::HTTP_OK,
            ['Content-Type' => 'application/atom+xml; charset=UTF-8']
        );
    }
}
