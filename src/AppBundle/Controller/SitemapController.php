<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Packages\Package;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{

    /**
     * @Route("/sitemap.xml", methods={"GET"})
     * @Cache(smaxage="600")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager): Response
    {
        $packages = $entityManager
            ->createQueryBuilder()
            ->select('package', 'repository')
            ->from(Package::class, 'package')
            ->join('package.repository', 'repository', 'WITH', 'repository.architecture = :architecture')
            ->setParameter('architecture', $this->getParameter('app.packages.default_architecture'))
            ->getQuery()
            ->getResult();

        $response = $this->render(
            'sitemap/index.xml.twig',
            ['packages' => $packages]
        );
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        return $response;
    }
}
