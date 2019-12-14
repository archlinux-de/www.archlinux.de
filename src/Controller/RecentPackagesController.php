<?php

namespace App\Controller;

use App\Entity\Packages\Package;
use App\Repository\PackageRepository;
use FeedIo\Factory;
use FeedIo\Feed;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecentPackagesController extends AbstractController
{

    /**
     * @Route("/packages/feed", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Packages $assetPackages
     * @param PackageRepository $packageRepository
     * @return Response
     */
    public function indexAction(Packages $assetPackages, PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findLatestByArchitecture(
            $this->getParameter('app.packages.default_architecture'),
            25
        );

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
            $packageUrl = $this->generateUrl(
                'app_packagedetails_index',
                [
                    'repo' => $package->getRepository()->getName(),
                    'arch' => $package->getRepository()->getArchitecture(),
                    'pkgname' => $package->getName(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $item = $feed->newItem();
            $item->setPublicId($packageUrl);
            $item->setTitle($package->getName() . ' ' . $package->getVersion());
            $item->setLastModified($package->getBuildDate() ?: new \DateTime());
            if (!is_null($package->getPackager())) {
                $author = $item->newAuthor();
                $author->setName($package->getPackager()->getName() ?: '');
                $author->setEmail($package->getPackager()->getEmail() ?: '');
                $author->setUri('');
                $item->setAuthor($author);
            }
            $item->setLink($packageUrl);
            $item->setDescription($package->getDescription());

            $feed->add($item);
        }

        $feedIo = Factory::create()->getFeedIo();
        return (new Response($feedIo->toAtom($feed)));
    }
}
