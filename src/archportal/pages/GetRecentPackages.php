<?php

namespace archportal\pages;

use archportal\lib\Config;
use archportal\lib\Page;
use Doctrine\DBAL\Driver\Connection;
use DOMDocument;
use Symfony\Component\HttpFoundation\Request;

class GetRecentPackages extends Page
{
    /** @var string */
    private $feed = '';
    /** @var Connection */
    private $database;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->database = $connection;
    }

    public function prepare(Request $request)
    {
        $path = $request->getSchemeAndHttpHost().'/';
        $dom = new DOMDocument('1.0', 'UTF-8');
        $body = $dom->createElementNS('http://www.w3.org/2005/Atom', 'feed');

        $id = $dom->createElement('id', $path);
        $title = $dom->createElement('title', $this->l10n->getText('Recent Arch Linux packages'));
        $updated = $dom->createElement('updated',
            date('c', $this->database->query('SELECT MAX(builddate) FROM packages')->fetchColumn()));

        $author = $dom->createElement('author');
        $authorName = $dom->createElement('name', Config::get('common', 'sitename'));
        $authorEmail = $dom->createElement('email', Config::get('common', 'email'));
        $authorUri = $dom->createElement('uri', $path);
        $author->appendChild($authorName);
        $author->appendChild($authorEmail);
        $author->appendChild($authorUri);

        $alternate = $dom->createElement('link');
        $alternate->setAttribute('href', $this->createUrl('Packages', array(), true, false));
        $alternate->setAttribute('rel', 'alternate');
        $alternate->setAttribute('type', 'text/html');
        $self = $dom->createElement('link');
        $self->setAttribute('href', $this->createUrl($this->getName(), array(), true, false));
        $self->setAttribute('rel', 'self');
        $self->setAttribute('type', 'application/atom+xml');

        $icon = $dom->createElement('icon', $path.'style/favicon.ico');
        $logo = $dom->createElement('logo', $path.'style/archlogo-64.png');

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
            $entryId = $dom->createElement('id', $this->createUrl('PackageDetails', array(
                'repo' => $package['repository'],
                'arch' => $package['architecture'],
                'pkgname' => $package['name'],
            ), true));
            $entryTitle = $dom->createElement('title',
                $package['name'].' '.$package['version'].' ('.$package['architecture'].')');
            $entryUpdated = $dom->createElement('updated', date('c', $package['builddate']));

            $entryAuthor = $dom->createElement('author');
            $entryAuthorName = $dom->createElement('name', $package['packager']);
            $entryAuthorEmail = $dom->createElement('email', $package['email']);
            $entryAuthor->appendChild($entryAuthorName);
            $entryAuthor->appendChild($entryAuthorEmail);

            $entryLink = $dom->createElement('link');
            $entryLink->setAttribute('href', $this->createUrl('PackageDetails', array(
                'repo' => $package['repository'],
                'arch' => $package['architecture'],
                'pkgname' => $package['name'],
            ), true));
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

        $this->feed = $dom->saveXML();
    }

    public function printPage()
    {
        $this->setContentType('application/atom+xml; charset=UTF-8');
        echo $this->feed;
    }
}
