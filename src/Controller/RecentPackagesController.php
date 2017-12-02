<?php

namespace App\Controller;

use App\Entity\Packages\Package;
use FeedIo\Factory;
use FeedIo\Feed;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecentPackagesController extends Controller
{

    /**
     * @Route(
     *     "/packages/feed.{_format}",
     *     methods={"GET"},
     *     defaults={"_format": "atom"},
     *     requirements={"_format": "atom|rss|json"}
     * )
     * @Cache(smaxage="600")
     * @param string $_format
     * @param Packages $assetPackages
     * @return Response
     */
    public function indexAction(string $_format, Packages $assetPackages): Response
    {
        $packages = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('package', 'repository')
            ->from(Package::class, 'package')
            ->join('package.repository', 'repository', 'WITH', 'repository.architecture = :architecture')
            ->orderBy('package.buildDate', 'DESC')
            ->setMaxResults(25)
            ->setParameter('architecture', $this->getParameter('app.packages.default_architecture'))
            ->getQuery()
            ->getResult();

        $feed = new Feed();
        $feedUrl = $this->generateUrl('app_recentpackages_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feed->setUrl($feedUrl);
        $feed->setTitle('Aktuelle Arch Linux Pakete');
        $feed->setPublicId($feedUrl);
        $feed->setLink($this->generateUrl('app_packages_index', [], UrlGeneratorInterface::ABSOLUTE_URL));

        $icon = $feed->newElement();
        $icon->setName('icon')->setValue($assetPackages->getUrl('build/images/archicon.svg'));
        $feed->addElement($icon);

        $logo = $feed->newElement();
        $logo->setName('logo')->setValue($assetPackages->getUrl('build/images/archicon.svg'));
        $feed->addElement($logo);
        /** @var Package $package */
        foreach ($packages as $package) {
            $packageUrl = $this->generateUrl('app_packagedetails_index', [
                'repo' => $package->getRepository()->getName(),
                'arch' => $package->getRepository()->getArchitecture(),
                'pkgname' => $package->getName(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $item = $feed->newItem();
            $item->setPublicId($packageUrl);
            $item->setTitle($package->getName() . ' ' . $package->getVersion());
            $item->setLastModified($package->getBuilddate() ?: new \DateTime());
            $author = $item->newAuthor();
            $author->setName($package->getPackager()->getName());
            $author->setEmail($package->getPackager()->getEmail());
            $author->setUri('');
            $item->setAuthor($author);
            $item->setLink($packageUrl);
            $item->setDescription($package->getDescription());

            $feed->add($item);
        }

        $feedIo = Factory::create()->getFeedIo();
        return (new Response($feedIo->format($feed, $_format)));
    }
}
