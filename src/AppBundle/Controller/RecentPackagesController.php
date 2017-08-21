<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use FeedIo\Factory;
use FeedIo\Feed;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Asset\Packages;

class RecentPackagesController extends Controller
{
    /** @var Connection */
    private $database;
    /** @var Packages */
    private $assetPackages;

    /**
     * @param Connection $connection
     * @param Packages $assetPackages
     */
    public function __construct(Connection $connection, Packages $assetPackages)
    {
        $this->database = $connection;
        $this->assetPackages = $assetPackages;
    }

    /**
     * @Route(
     *     "/packages/feed.{_format}",
     *     methods={"GET"},
     *     defaults={"_format": "atom"},
     *     requirements={"_format": "atom|rss|json"}
     * )
     * @param string $_format
     * @return Response
     */
    public function indexAction(string $_format): Response
    {
        $packages = $this->database->prepare('
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
                JOIN
                    architectures repoarch
                ON
                    repoarch.id = repositories.arch
        WHERE
            repoarch.name = :architecture
        ORDER BY
            packages.builddate DESC
        LIMIT
            25
        ');
        $packages->bindValue('architecture', $this->getParameter('app.packages.default_architecture'), \PDO::PARAM_STR);
        $packages->execute();

        $feed = new Feed();
        $feedUrl = $this->generateUrl('app_recentpackages_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feed->setUrl($feedUrl);
        $feed->setTitle('Aktuelle Arch Linux Pakete');
        $feed->setPublicId($feedUrl);
        $feed->setLink($this->generateUrl('app_packages_index', [], UrlGeneratorInterface::ABSOLUTE_URL));

        $icon = $feed->newElement();
        $icon->setName('icon')->setValue($this->assetPackages->getUrl('style/favicon.ico'));
        $feed->addElement($icon);

        $logo = $feed->newElement();
        $logo->setName('logo')->setValue($this->assetPackages->getUrl('style/archlogo-64.png'));
        $feed->addElement($logo);
        foreach ($packages as $package) {
            $packageUrl = $this->generateUrl('app_packagedetails_index', [
                'repo' => $package['repository'],
                'arch' => $package['architecture'],
                'pkgname' => $package['name'],
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $item = $feed->newItem();
            $item->setPublicId($packageUrl);
            $item->setTitle($package['name'] . ' ' . $package['version']);
            $item->setLastModified((new \DateTime())->setTimestamp($package['builddate']));
            $author = $item->newAuthor();
            $author->setName($package['packager']);
            $author->setEmail($package['email']);
            $item->setAuthor($author);
            $item->setLink($packageUrl);
            $item->setDescription($package['desc']);

            $feed->add($item);
        }

        $feedIo = Factory::create()->getFeedIo();
        return (new Response($feedIo->format($feed, $_format)))->setSharedMaxAge(600);
    }
}
